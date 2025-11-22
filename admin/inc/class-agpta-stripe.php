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

	/**
	 * Plugin Name
	 *
	 * @var string
	 */
	public string $plugin_name;

	/**
	 * Plugin Version
	 *
	 * @var string
	 */
	public string $plugin_version;

	/**
	 * Strip Private Key
	 *
	 * @var string|mixed
	 */
	public string $stripe_key;

	/**
	 * WP Settings Options
	 *
	 * @var array|false|mixed|void
	 */
	private array $options;

	/**
	 * Database Object
	 *
	 * @var wpdb
	 */
	private wpdb $db;

	/**
	 * Constructor
	 *
	 * @param string $plugin_name plugin name.
	 * @param string $plugin_version plugin version.
	 */
	public function __construct( string $plugin_name, string $plugin_version ) {
		global $wpdb;

		$this->plugin_name    = $plugin_name;
		$this->plugin_version = $plugin_version;
		$this->options        = get_option( 'agpta_settings', array() );
		$this->stripe_key     = ( $this->options['enable_stripe_test'] ) ? $this->options['test_secret_key'] : $this->options['live_secret_key'];
		$this->db             = $wpdb;
	}

	/**
	 * Initialize Stripe Class
	 *
	 * @return void
	 */
	public function agpta_stripe_init(): void {
	}

	/**
	 * Create Strip Checkout Session
	 *
	 * @return void
	 */
	public function agpta_create_stripe_checkout_session(): void {
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

	/**
	 * Register Stripe Webhook
	 *
	 * @return void
	 */
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

	/**
	 * Handle Stripe Webhook Request
	 *
	 * @param object $request stripe request data.
	 *
	 * @return WP_Error|WP_REST_Response|WP_HTTP_Response
	 * @throws ApiErrorException Stripe api error exception.
	 */
	public function agpta_handle_stripe_webhook( object $request ): WP_Error|WP_REST_Response|WP_HTTP_Response {
		$payload         = $request->get_body();
		$sig_header      = isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ) : '';
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

		if ( 'checkout.session.completed' === $event->type ) {
			$this->handle_successful_payment( $event );
		} elseif ( 'payment_intent.payment_failed' === $event->type ) {
			$this->handle_failed_payment( $event );
		}

		return rest_ensure_response( array( 'status' => 'success' ) );
	}

	/**
	 * Handle Success Payment Event
	 *
	 * @param array $event event data.
	 *
	 * @return void
	 * @throws ApiErrorException Stripe api error exception.
	 */
	private function handle_successful_payment( $event ) {
		$session = $event->data->object;

		// Fetch line items.
		$line_items = \Stripe\Checkout\Session::allLineItems( $session->id );

		// Save to DB.
		$this->save_transaction_data( $session, $line_items );

		// Send invoice email.
		$this->send_invoice_email( $session->customer_email, $session, $line_items );
	}

	/**
	 * Handle failed payment request
	 *
	 * @param object $event event data.
	 *
	 * @return void
	 */
	private function handle_failed_payment( object $event ): void {
		$session_data   = $event->data->object;
		$session_id     = $session_data->id;
		$user_email     = $session_data->customer_email;
		$payment_intent = $session_data->payment_intent;

		// You can now save the transaction info in the database.
		$this->save_transaction_data( $session_data );
	}

	/**
	 * Save Transaction To Database
	 *
	 * @param object $session session data.
	 * @param array  $line_items order line items.
	 *
	 * @return void
	 */
	public function save_transaction_data( object $session, array $line_items ): void {

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

		$this->db->insert(
			"{$this->db->prefix}ticket_transactions",
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

	/**
	 * Send Invoice Email
	 *
	 * @param string $user_email user email.
	 * @param object $session order session data.
	 * @param array  $line_items order line items.
	 *
	 * @return void
	 */
	public function send_invoice_email( string $user_email, object $session, array $line_items ): void {

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

	/**
	 * Display Thank You, Page.
	 *
	 * @return string
	 */
	public function agpta_thank_you_page_display(): string {
		$session_id = isset( $_GET['session_id'] ) ? sanitize_text_field( wp_unslash( $_GET['session_id'] ) ) : '';

		if ( ! $session_id ) {
			return '<p>Thank you for your purchase!</p>';
		}

		// Retrieve the session details from Stripe (if necessary).
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
			<p>Your receipt has been sent to <strong><?php echo esc_html( $user_email ); ?></strong>.</p>
		</div>
		<?php
		return ob_get_clean();
	}
}
