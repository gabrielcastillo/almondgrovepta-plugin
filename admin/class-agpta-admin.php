<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://gabrielcastillo.net
 * @since      1.0.0
 *
 * @package    Agpta
 * @subpackage Agpta/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Agpta
 * @subpackage Agpta/admin
 * @author     Gabriel Castillo <gabriel@gabrielcastillo.net>
 */
class Agpta_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private string $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private string $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( string $plugin_name, string $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles(): void {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/agpta-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts(): void {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/agpta-admin.js', array( 'jquery' ), $this->version, false );

		wp_localize_script(
			$this->plugin_name,
			'agpta_ajax_params',
			array(
				'ajaxurl'   => admin_url( 'admin-ajax.php' ),
				'ajaxnonce' => wp_create_nonce( $this->plugin_name . '_nonce' ),
			)
		);
	}
	
	/**
	 * Admin Notices.
	 *
	 * @return void
	 */
	public function agpta_admin_notices(): void {

		if ( ! isset( $_GET['status'], $_GET['message'] ) ) {
			return;
		}

		$status  = sanitize_key( $_GET['status'] );
		$message = esc_html( urldecode( $_GET['message'] ) );

		$class = match ( $status ) {
			'success'   => 'notice-success',
			'error'     => 'notice-error',
			'warning'   => 'notice-warning',
			default     => 'notice-info',
		};

		printf(
			'<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $class ),
			esc_html( $message )
		);
	}

	/**
	 * Check Admin Nonce
	 *
	 * Redirect back to slug if nonce fails.
	 *
	 * @param  string $slug url to be redirected.
	 *
	 * @return void
	 */
	public function agpta_wishlist_edit_page_verification_check() {
		$page = 'agpta-wishlists';
		if ( isset( $_GET['page'] ) && $_GET['page'] === $page . '-edit' ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				$args = array(
					'status'  => 'error',
					'message' => rawurlencode( 'You do not have permission to view this page.' ),
				);
				wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php?page=' . $page ) ) );
				exit;
			}

			if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'agpta_admin_nonce' ) ) {
				$referer = admin_url( 'admin.php?page=' . $page );
				$args    = array(
					'status'  => 'error',
					'message' => rawurlencode( 'You do not have permission to view this page.' ),
				);
				wp_safe_redirect( add_query_arg( $args, $referer ) );
				exit;
			}

			if ( empty( $_GET['id'] ) ) {
				$referer = admin_url( 'admin.php?page=' . $page );
				$args    = array(
					'status'  => 'error',
					'message' => rawurlencode( 'Missing Wishlist ID.' ),
				);
				wp_safe_redirect( add_query_arg( $args, $referer ) );
				exit;
			}
		}
	}
}
