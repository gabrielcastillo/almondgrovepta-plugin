<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://gabrielcastillo.net
 * @since      1.0.0
 *
 * @package    Agpta
 * @subpackage Agpta/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Agpta
 * @subpackage Agpta/public
 * @author     Gabriel Castillo <gabriel@gabrielcastillo.net>
 */
class Agpta_Public {

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
	 * @param      string $plugin_name  The name of the plugin.
	 * @param  string $version    The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( string $plugin_name, string $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles(): void {

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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/agpta-public.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'tooltip-css', plugin_dir_url( __FILE__ ) . 'css/tooltip.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts(): void {

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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/agpta-public.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'tooltip-js', plugin_dir_url( __FILE__ ) . 'js/tooltip.js', array( 'jquery' ), $this->version, false );

		wp_localize_script(
			$this->plugin_name,
			'agpta_plugin',
			array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'agpta_nonce' => wp_create_nonce( 'agpta_nonce' ),
			)
		);
	}
}
