<?php
/*
 * File: class-agpta-webhooks.php
 *
 * @author Gabriel Castillo <gabriel@gabrielcastillo.net>
 * Copyright (c) 2025.
 */


class AGPTA_Webhooks {

	public string $plugin_name;

	public string $plugin_version;

	public function __construct( string $plugin_name, string $plugin_version ) {
		$this->plugin_name = $plugin_name;
		$this->plugin_version = $plugin_version;
	}

	public function init() {
		//remove_action( 'rest_api_init', array( $this, 'agpta_register_stripe_create_checkout_session_endpoint' ) );
		add_action( 'rest_api_init', array( $this, 'agpta_register_stripe_endpoint') );

	}


	public function agpta_register_stripe_endpoint() {

		register_rest_route( 'agpta/v1/', '/stripe/checkout/session', array(
			'methods' => 'POST',
			'callback' => array( $this, 'agpta_create_checkout_session' ),
			'permission_callback' => '__return_true',
		));
	}

	/**
	 * @throws JsonException
	 */
	public function agpta_create_checkout_session( WP_REST_Request $request ) {

		$headers = $request->get_headers();
		$eventId = $request->get_param('eventId');
		$qty = $request->get_param('qty');

		echo print_r(array($eventId, $qty), true);
		exit;

		if ( isset( $data['eventId'] ) && isset( $data['qty'] ) ) {
			return new WP_REST_Response(print_r($data), 200);
		}

		return new WP_REST_Response('checkout session', 200);
	}
}