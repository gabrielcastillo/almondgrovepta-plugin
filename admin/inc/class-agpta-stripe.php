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

	public function __construct( $plugin_name, $plugin_version ) {
		$this->plugin_name = $plugin_name;
		$this->plugin_version = $plugin_version;

		$options = get_option( 'agpta_settings', array() );

		$this->stripe_key = ( $options['enable_stripe_test'] ) ? $options['test_secret_key'] : $options['live_secret_key'];
	}

	public function init() {
		add_action( 'init', array( $this, 'agpta_handle_stripe_payment_request' ) );
		add_action( 'agpta_request_stripe_refund', array( $this, 'agpta_handle_stripe_refund_callback' ) );
	}

	public function agpta_handle_stripe_payment_request() {
		if ( isset( $_POST['stripeToken'] ) ) {
			$stripeToken = $_POST['stripeToken'];

			Stripe::setApiKey( $this->stripe_key );

			try {

				$charge = Charge::create([
					'amount' => $_POST['price'],
					'currency' => 'usd',
					'description' => 'Event Description',
					'source' => $stripeToken,
				]);

				wp_redirect('thank-you-page');
				exit;

			} catch ( CardException $e ) {
				$error_message = $e->getMessage();
				wp_redirect('payment-failed-page?error=' . urlencode($error_message) );
				exit;
			}
		}
	}


	public function agpta_handle_stripe_refund_request( $charge_id ) {
		if ( empty( $charge_id ) ) {
			return new WP_Error( 'invalid_charge_id', 'Charge ID is required for a refund.' );
		}

		Stripe::setApiKey( $this->stripe_key );

		try {
			return Refund::create([
				'charge' => $charge_id,
			]);
		} catch ( ApiErrorException $e ) {
			return new WP_Error('refund_error', 'Error processing refund: ' . $e->getMessage() );
		}
	}

	public function agpta_handle_stripe_refund_callback($order_id) {
		$order = array();

		if ( $order ) {
			$charge_id = get_post_meta($order_id, '_stripe_charge_id', true);

			$refund = $this->agpta_handle_stripe_refund_request($charge_id);

			if ( is_wp_error( $refund ) ) {
				error_log('Stripe refund error: ' . $refund->get_error_message() );
			}
		}
	}

}