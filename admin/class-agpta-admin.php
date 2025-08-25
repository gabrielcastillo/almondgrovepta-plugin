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
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Agpta_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Agpta_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/agpta-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Agpta_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Agpta_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/agpta-admin.js', array( 'jquery' ), $this->version, false );

		wp_localize_script(
			$this->plugin_name,
			'agpta_ajax_params',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'ajaxnonce'   => wp_create_nonce( $this->plugin_name . '_nonce' ),
			)
		);
	}


	public function agpta_admin_notices() {

		if ( ! isset( $_GET['status'], $_GET['message'] ) ) {
			return;
		}

		$status  = sanitize_key( $_GET['status'] );
		$message = esc_html( urldecode( $_GET['message'] ) );

		$class = match ( $status ) {
			'success' => 'notice-success',
			'error' => 'notice-error',
			'warning' => 'notice-warning',
			default => 'notice-info',
		};

		printf(
			'<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $class ),
			$message
		);
	}
}
