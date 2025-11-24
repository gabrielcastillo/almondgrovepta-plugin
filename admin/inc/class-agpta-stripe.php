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

	private object $template_engine;

	/**
	 * Constructor
	 *
	 * @param string $plugin_name plugin name.
	 * @param string $plugin_version plugin version.
	 */
	public function __construct( string $plugin_name, string $plugin_version, $wpdb, $template_engine ) {
		$this->plugin_name     = $plugin_name;
		$this->plugin_version  = $plugin_version;
		$this->options         = get_option( 'agpta_settings', array() );
		$this->stripe_key      = ( $this->options['enable_stripe_test'] ) ? $this->options['test_secret_key'] : $this->options['live_secret_key'];
		$this->db              = $wpdb;
		$this->template_engine = $template_engine;
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

			wp_safe_redirect( $checkout_session->url );
			exit;
		} catch ( \Exception $e ) {
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
			),
			true
		);
	}

	/**
	 * Handle Stripe Webhook Request
	 *
	 * @param  WP_REST_Request $request  stripe request data.
	 *
	 * @return WP_Error|WP_REST_Response|WP_HTTP_Response
	 */
	public function agpta_handle_stripe_webhook( WP_REST_Request $request ): WP_Error|WP_REST_Response|WP_HTTP_Response {

		\Stripe\Stripe::setApiKey( $this->stripe_key );
		$payload = $request->get_body();
		$event   = null;

		if ( ! $payload ) {
			return new WP_Error( 'stripe_error', 'Empty payload', array( 'status' => 400 ) );
		}

		$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

		if ( empty( $sig_header ) ) {
			return new WP_Error( 'stripe_error', 'Missing signature', array( 'status' => 400 ) );
		}

		$endpoint_secret = $this->options['webhook_secret'];

		if ( empty( $endpoint_secret ) ) {
			return new WP_Error( 'stripe_error', 'Webhook secret not configured', array( 'status' => 500 ) );
		}

		if ( $endpoint_secret ) {
			$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
			try {
				$event = \Stripe\Webhook::constructEvent(
					$payload,
					$sig_header,
					$endpoint_secret
				);
			} catch ( \Stripe\Exception\SignatureVerificationException $e ) {
				return new WP_Error( "Stripe Signature Error: {$e->getMessage()}", array( 'status' => 400 ) );
			}
		}

		switch ( $event->type ) {
			case 'checkout.session.completed':
				$this->handle_successful_payment( $event, $request->get_body() );
				break;

			case 'payment_intent.payment_failed':
			case 'checkout.session.async_payment_failed':
				$this->handle_failed_payment( $event );
				break;

			default:
				// Log unhandled events but return 200 OK
				// error_log( "AGPTA Stripe: unhandled event type {$event->type}" );
				break;
		}

		return rest_ensure_response( array( 'status' => 'ok' ) );
	}

	/**
	 * Handle Successful Payment
	 *
	 * @param  object $event         stripe event.
	 * @param  string $request_body  request body object.
	 *
	 * @return void
	 */
	private function handle_successful_payment( object $event, string $request_body ): void {

		\Stripe\Stripe::setApiKey( $this->stripe_key );

		$session_data = $event->data->object;

		// Try to retrieve the real session with line items.
		try {
			$session    = \Stripe\Checkout\Session::retrieve(
				array(
					'id'     => $session_data->id,
					'expand' => array( 'line_items' ),
				)
			);
			$line_items = $session->line_items;
		} catch ( \Stripe\Exception\ApiErrorException $e ) {
			// Stripe CLI synthetic session → fallback.
			error_log( 'Stripe CLI synthetic session: ' . $e->getMessage() );

			$session    = $session_data;
			$line_items = (object) array( 'data' => array() );

			$req = json_decode( $request_body, true );
			if ( ! empty( $req['data']['object']['customer_details'] ) ) {
				// Ensure we have an object.
				$session->customer_details = (object) $req['data']['object']['customer_details'];

				// Convert address to object if it's an array.
				if ( isset( $session->customer_details->address ) && is_array( $session->customer_details->address ) ) {
					$session->customer_details->address = (object) $session->customer_details->address;
				}
			}
		}

		// -------------------------------
		// Save customer & transaction data
		// -------------------------------
		$this->save_full_transaction_data( $session, $line_items );

		// -------------------------------
		// Send invoice email
		// -------------------------------
		$customer_email = $session->customer_email ?? $session->customer_details->email ?? '';
		if ( $customer_email ) {
			$this->send_invoice_email( $customer_email, $session, $line_items );
		}
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
	}



	private function save_full_transaction_data( object $session, object $line_items ): void {

		// Ensure customer object exists.
		$customer = $session->customer_details ?? (object) array();
		$address  = $customer->address ?? (object) array();

		// Line items.
		$items = array();
		if ( ! empty( $line_items->data ) && is_array( $line_items->data ) ) {
			foreach ( $line_items->data as $item ) {
				$items[] = array(
					'name'     => $item->description ?? '',
					'quantity' => $item->quantity ?? 1,
					'amount'   => isset( $item->amount_total ) ? $item->amount_total / 100 : 0,
				);
			}
		}

		$line_items_json = wp_json_encode( $items );

		// Insert into database safely.
		$this->db->insert(
			"{$this->db->prefix}agpta_stripe_transactions",
			array(
				'user_email'     => sanitize_email( $customer->email ?? '' ),
				'user_name'      => sanitize_text_field( $customer->name ?? '' ),
				'user_phone'     => sanitize_text_field( $customer->phone ?? '' ),
				'address_line1'  => sanitize_text_field( $address->line1 ?? '' ),
				'address_line2'  => sanitize_text_field( $address->line2 ?? '' ),
				'city'           => sanitize_text_field( $address->city ?? '' ),
				'state'          => sanitize_text_field( $address->state ?? '' ),
				'postal_code'    => sanitize_text_field( $address->postal_code ?? '' ),
				'country'        => sanitize_text_field( $address->country ?? '' ),
				'transaction_id' => sanitize_text_field( $session->payment_intent ?? '' ),
				'total_amount'   => (float) ( ( $session->amount_total ?? 0 ) / 100 ),
				'subtotal'       => (float) ( ( $session->amount_subtotal ?? 0 ) / 100 ),
				'currency'       => sanitize_text_field( $session->currency ?? 'usd' ),
				'payment_status' => sanitize_text_field( $session->payment_status ?? '' ),
				'line_items'     => $line_items_json,
				'created_at'     => current_time( 'mysql' ),
			)
		);
	}



	/**
	 * Send Invoice Email
	 *
	 * @param string $user_email user email.
	 * @param object $session order session data.
	 * @param object $line_items order line items.
	 */
	public function send_invoice_email( string $user_email, object $session, object $line_items ) {

		try {

			$subject  = 'Your Invoice';
			$message  = "Thank you for your purchase!\n\n";
			$message .= "Transaction ID: {$session->payment_intent}\n";
			$message .= 'Total: $' . number_format( ( $session->amount_total ?? 0 ) / 100, 2 ) . "\n\n";

			if ( ! empty( $line_items->data ) ) {
				foreach ( $line_items->data as $item ) {
					$message .= "Event: {$item->description}\n";
					$message .= "Quantity: {$item->quantity}\n";
					$message .= 'Subtotal: $' . number_format( ( $item->amount_total ?? 0 ) / 100, 2 ) . "\n\n";
				}
			}

			// Billing address.
			if ( ! empty( $session->customer_details ) ) {
				$customer = $session->customer_details;
				$address  = $customer->address ?? (object) array();
				$message .= 'Name: ' . $customer->name . "\n";
				if ( $address ) {

					$message .= "Billing Address:\n";
					$message .= ( $address->line1 ?? '' ) . ' ' . ( $address->line2 ?? '' ) . "\n";
					$message .= ( $address->city ?? '' ) . ', ' . ( $address->state ?? '' ) . ' ' . ( $address->postal_code ?? '' ) . "\n";
					$message .= ( $address->country ?? '' ) . "\n\n";
				}
				$this->template_engine->customer_name = $customer->name;
				$to                                   = $customer->name . "<$user_email>";
			}

			$this->template_engine->plugin_name  = $this->plugin_name;
			$this->template_engine->site_name    = get_bloginfo( 'name' );
			$this->template_engine->site_url     = get_bloginfo( 'url' );
			$this->template_engine->message_body = $message;
			$this->template_engine->page_title   = get_bloginfo( 'name' ) . ' | Transaction # ' . $session->payment_intent;

			ob_start();
			$this->template_engine->render( 'stripe-success.php' );
			$contents = ob_get_clean();

			$headers = array(
				'Content-Type: text/html; charset=UTF-8',
				'From: Almond Grove PTA <no-reply@ptasite.test>',
				'Reply-To: Almond Grove PTA <no-reply@ptasite.test>',
			);

			$sent = wp_mail( $to, $subject, $contents, $headers );

			if ( ! $sent ) {
				throw new RuntimeException( 'wp_mail() failed.' );
			}
		} catch ( Exception $e ) {
			error_log( 'Message Failed: ' . $e->getMessage() );
			return new WP_Error( 'stripe_error', $e->getMessage(), array( 'status' => 400 ) );
		}
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
