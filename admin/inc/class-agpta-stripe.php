<?php
/*
 * File: class-agpta-stripe.php
 *
 * @author Gabriel Castillo <gabriel@gabrielcastillo.net>
 * Copyright (c) 2025.
 */


use Stripe\Charge;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;
use Stripe\Refund;
use Stripe\Stripe;

class AGPTA_Stripe {

	public string $plugin_name;

	public string $plugin_version;

	public string $stripe_key;

	private array $options;

	public function __construct( $plugin_name, $plugin_version ) {
		$this->plugin_name    = $plugin_name;
		$this->plugin_version = $plugin_version;

		$this->options = get_option( 'agpta_settings', array() );

		$this->stripe_key = ( $this->options['enable_stripe_test'] ) ? $this->options['test_secret_key'] : $this->options['live_secret_key'];
	}

	public function agpta_stripe_init() {
	}

	public function agpta_create_stripe_checkout_session() {
		if ( empty( $_SESSION['cart'] ) ) {
			wp_safe_redirect( home_url( '/cart' ) );
			exit;
		}

		\Stripe\Stripe::setApiKey( $this->stripe_key );

		$line_items = array();

		foreach ( $_SESSION['cart'] as $item ) {
			$event       = get_post( $item['event_id'] );
			$event_title = $event->post_title;
			$price       = $item['price'] * 100;
			$qty         = $item['qty'];

			$line_items[] = array(
				'price_data' => array(
					'currency'     => 'usd',
					'product_data' => array( 'name' => $event_title ),
					'unit_amount'  => $price,
				),
				'quantity'   => $qty,
			);
		}

		try {
			$checkout_session = \Stripe\Checkout\Session::create(
				array(
					'payment_method_types' => array( 'card' ),
					'line_items'           => $line_items,
					'mode'                 => 'payment',
					'success_url'          => home_url( '/checkout/thank-you?session_id={CHECKOUT_SESSION_ID}' ),
					'cancel_url'           => home_url( '/cart' ),
				)
			);

			wp_redirect( $checkout_session->url );
			exit;
		} catch ( \Exception $e ) {
			error_log( 'Stripe checkout session creation failed: ' . $e->getMessage() );
			$args = array(
				'payment_error' => 1,
				'status'        => 'error',
				'message'       => rawurlencode( 'Payment failed. Please try again, or use contact form to send us a message.' ),
			);
			wp_safe_redirect( add_query_arg( $args, home_url( '/cart' ) ) );
			exit;
		}
	}

	public function agpta_register_webhook_route() {
		register_rest_route(
			'agpta-stripe/v1',
			'/stripe-payments',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'agpta_handle_stripe_webhook' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function agpta_handle_stripe_webhook( $request ): WP_Error|WP_REST_Response|WP_HTTP_Response {
		$payload         = $request->get_body();
		$sig_header      = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
		$endpoint_secret = $this->options['webhook_secret'];

		try {
			$event = \Stripe\Webhook::constructEvent(
				$payload,
				$sig_header,
				$endpoint_secret
			);
		} catch ( \Exception $e ) {
			return new WP_Error( 'stripe_error', $e->getMessage(), array( 'status' => 400 ) );
		}

		if ( $event->type === 'checkout.session.completed' ) {
			$this->handle_successful_payment( $event );
		} elseif ( $event->type === 'payment_intent.payment_failed' ) {
			$this->handle_failed_payment( $event );
		}

		return rest_ensure_response( array( 'status' => 'success' ) );
	}

	private function handle_successful_payment( $event ) {
		$session = $event->data->object;

		// Fetch line items
		$line_items = \Stripe\Checkout\Session::allLineItems( $session->id );

		// Save to DB
		$this->save_transaction_data( $session, $line_items );

		// Send invoice email
		$this->send_invoice_email( $session->customer_email, $session, $line_items );
	}


	private function handle_failed_payment( $event ) {
		$session        = $event->data->object;
		$session_id     = $session->id;
		$user_email     = $session->customer_email;
		$payment_intent = $session->payment_intent;

		// You can now save the transaction info in the database
		$this->save_transaction_data( $session );
	}

	public function save_transaction_data( $session, $line_items ) {
		global $wpdb;

		$events = array();
		if ( $line_items && isset( $line_items->data ) ) {
			foreach ( $line_items->data as $item ) {
				$events[] = array(
					'name'     => $item->description,
					'quantity' => $item->quantity,
					'amount'   => $item->amount_total / 100,
				);
			}
		}

		$wpdb->insert(
			"{$wpdb->prefix}ticket_transactions",
			array(
				'user_email'     => sanitize_email( $session->customer_email ),
				'event_ids'      => wp_json_encode( $events ),
				'total_amount'   => (float) ( $session->amount_total / 100 ),
				'transaction_id' => sanitize_text_field( $session->payment_intent ),
				'status'         => sanitize_text_field( $session->payment_status ),
				'customer_id'    => sanitize_text_field( $session->customer ),
				'created_at'     => current_time( 'mysql' ),
			)
		);
	}

	public function send_invoice_email( $user_email, $session, $line_items ) {

		$subject  = 'Your Ticket Invoice';
		$message  = "Thank you for your purchase!\n\n";
		$message .= "Transaction ID: {$session->payment_intent}\n";
		$message .= 'Total: $' . number_format( $session->amount_total / 100, 2 ) . "\n\n";

		foreach ( $line_items->data as $item ) {
			$message .= "Event: {$item->description}\n";
			$message .= "Quantity: {$item->quantity}\n";
			$message .= 'Subtotal: $' . number_format( $item->amount_total / 100, 2 ) . "\n\n";
		}

		wp_mail( $user_email, $subject, $message );
	}

	public function agpta_thank_you_page_display() {
		$session_id = isset( $_GET['session_id'] ) ? sanitize_text_field( $_GET['session_id'] ) : '';

		if ( ! $session_id ) {
			return '<p>Thank you for your purchase!</p>';
		}

		// Retrieve the session details from Stripe (if necessary)
		Stripe::setApiKey( $this->stripe_key );
		try {
			$session    = \Stripe\Checkout\Session::retrieve( $session_id );
			$user_email = $session->customer_email;
		} catch ( Exception $e ) {
			return '<p>Error retrieving your session details. Please contact support.</p>';
		}

		ob_start();
		?>
		<div class="thank-you-page max-w-lg mx-auto p-4 bg-white border border-gray-300 rounded-lg shadow-lg">
			<h2 class="text-2xl font-semibold mb-4">Thank You for Your Purchase!</h2>
			<p>We've received your payment and your tickets are confirmed.</p>
			<p>Your receipt has been sent to <strong><?php echo $user_email; ?></strong>.</p>
		</div>
		<?php
		return ob_get_clean();
	}
}
