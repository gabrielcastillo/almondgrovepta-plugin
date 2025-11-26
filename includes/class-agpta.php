<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://gabrielcastillo.net
 * @since      1.0.0
 *
 * @package    Agpta
 * @subpackage Agpta/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Agpta
 * @subpackage Agpta/includes
 * @author     Gabriel Castillo <gabriel@gabrielcastillo.net>
 */
class Agpta {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Agpta_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'AGPTA_VERSION' ) ) {
			$this->version = AGPTA_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'agpta';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Agpta_Loader. Orchestrates the hooks of the plugin.
	 * - Agpta_i18n. Defines internationalization functionality.
	 * - Agpta_Admin. Defines all hooks for the admin area.
	 * - Agpta_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}

		require_once plugin_dir_path( __DIR__ ) . 'includes/class-agpta-list-table-teams.php';

		require_once plugin_dir_path( __DIR__ ) . 'admin/inc/class-agpta-board-members.php';

		require_once plugin_dir_path( __DIR__ ) . 'admin/inc/class-agpta-principal-report.php';

		require_once plugin_dir_path( __DIR__ ) . 'admin/inc/class-agpta-events.php';

		require_once plugin_dir_path( __DIR__ ) . 'admin/inc/class-agpta-settings.php';

		require_once plugin_dir_path( __DIR__ ) . 'admin/inc/class-agpta-customizer.php';

		require_once plugin_dir_path( __DIR__ ) . 'admin/inc/class-agpta-webhooks.php';

		require_once plugin_dir_path( __DIR__ ) . 'admin/inc/class-agpta-contact-form.php';

		require_once plugin_dir_path( __DIR__ ) . 'admin/inc/class-agpta-wishlist.php';

		require_once plugin_dir_path( __DIR__ ) . 'admin/inc/class-agpta-stripe.php';

		require_once plugin_dir_path( __DIR__ ) . 'admin/inc/class-agpta-shopping-cart.php';

		require_once plugin_dir_path( __DIR__ ) . 'public/includes/shortcodes.php';
		
		require_once plugin_dir_path( __DIR__ ) . 'admin/inc/class-agpta-template-engine.php';
		
		require_once plugin_dir_path( __DIR__ ) . 'admin/inc/class-agpta-calendar.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-agpta-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-agpta-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-agpta-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'public/class-agpta-public.php';

		$this->loader = new Agpta_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Agpta_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Agpta_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		global $wpdb;

		$plugin_admin        = new Agpta_Admin( $this->get_plugin_name(), $this->get_version() );
		$board_members       = new AGPTA_Board_Members( $this->get_plugin_name() );
		$principal_reports   = new AGPTA_Principal_Report( $this->get_plugin_name() );
		$pta_events          = new AGPTA_Events( $this->get_plugin_name() );
		$plugin_settings     = new AGPTA_Settings( $this->get_plugin_name() );
		$agpta_webhooks      = new AGPTA_Webhooks( $this->get_plugin_name(), $this->get_version() );
		$agpta_contact_form  = new AGPTA_ContactForm( $this->get_plugin_name(), $this->get_version() );
		$agpta_wishlist      = new AGPTA_Wishlist( $this->get_plugin_name(), $this->get_version(), $wpdb );
		$template_engine     = new AGPTA_Template_Engine(plugin_dir_path( __DIR__ ) . 'admin/partials/templates/emails/');
		$agpta_stripe        = new Agpta_Stripe( $this->get_plugin_name(), $this->get_version(), $wpdb, $template_engine );
		$agpta_shopping_cart = new AGPTA_ShoppingCart( $this->get_plugin_name(), $this->get_version(), $wpdb );
		$agpta_calendar      = new AGPTA_Calendar( $this->get_plugin_name(), $wpdb );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'agpta_admin_notices' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'agpta_wishlist_edit_page_verification_check' );

		$this->loader->add_action( 'init', $board_members, 'init' );
		$this->loader->add_action( 'add_meta_boxes', $board_members, 'agpta_team_meta_box_init' );
		$this->loader->add_action( 'save_post', $board_members, 'save_team_meta_box_data' );
		$this->loader->add_filter( 'manage_team_posts_columns', $board_members, 'set_custom_team_columns' );
		$this->loader->add_action( 'manage_team_posts_custom_column', $board_members, 'custom_team_column', 10, 2 );
		$this->loader->add_action( 'manage_edit-team_sortable_columns', $board_members, 'set_custom_team_sortable_columns' );
		$this->loader->add_filter( 'post_row_actions', $board_members, 'remove_quick_edit_list', 10, 2 );
		$this->loader->add_filter( 'post_updated_messages', $board_members, 'admin_notice_save_post' );
		$this->loader->add_action( 'pre_get_posts', $board_members, 'agpta_team_cpt_search' );

		$this->loader->add_action( 'init', $principal_reports, 'init' );
		$this->loader->add_action( 'init', $pta_events, 'init' );

		$this->loader->add_action( 'admin_menu', $plugin_settings, 'agpta_admin_menu_settings_page_init' );
		$this->loader->add_action( 'admin_init', $plugin_settings, 'agpta_settings_init' );

		$this->loader->add_action( 'add_meta_boxes', $pta_events, 'add_event_price_meta_box' );
		$this->loader->add_action( 'save_post', $pta_events, 'agpta_save_event_price_meta' );
		$this->loader->add_action( 'save_post', $pta_events, 'agpta_save_event_date_meta' );
		$this->loader->add_action( 'save_post', $pta_events, 'agpta_save_event_status_meta' );

		$this->loader->add_action( 'admin_post_agpta_add_ticket_to_cart', $agpta_shopping_cart, 'agpta_handle_add_ticket_to_cart' );
		$this->loader->add_action( 'admin_post_nopriv_agpta_add_ticket_to_cart', $agpta_shopping_cart, 'agpta_handle_add_ticket_to_cart' );
		$this->loader->add_action( 'admin_post_agpta_update_cart_items', $agpta_shopping_cart, 'agpta_update_cart_items' );
		$this->loader->add_action( 'admin_post_nopriv_agpta_update_cart_items', $agpta_shopping_cart, 'agpta_update_cart_items' );
		$this->loader->add_shortcode( 'agpta_cart_page', $agpta_shopping_cart, 'agpta_display_cart_page' );
		$this->loader->add_shortcode( 'agpta_checkout_page', $agpta_shopping_cart, 'agpta_display_checkout_page' );
		$this->loader->add_shortcode( 'agpta_add_to_cart', $agpta_shopping_cart, 'agpta_add_to_cart_shortcode' );

		$this->loader->add_action( 'init', $agpta_webhooks, 'init' );

		$this->loader->add_action( 'admin_menu', $agpta_contact_form, 'contact_form_admin_page_init' );
		$this->loader->add_action( 'admin_enqueue_scripts', $agpta_contact_form, 'load_admin_scripts' );
		$this->loader->add_action( 'wp_ajax_get_form_message_ajax_call', $agpta_contact_form, 'get_form_message_ajax_callback' );
		$this->loader->add_action( 'admin_post_agpta_contact_form', $agpta_contact_form, 'agpta_contact_form_submission' );
		$this->loader->add_action( 'admin_post_nopriv_agpta_contact_form', $agpta_contact_form, 'agpta_contact_form_submission' );
		$this->loader->add_shortcode( 'agpta_contact_form', $agpta_contact_form, 'contact_form_display_shortcode' );

		$this->loader->add_action( 'admin_menu', $agpta_wishlist, 'agpta_wishlist_admin_page_init', 99 );
		$this->loader->add_action( 'admin_post_agpta_wishlist_add_new', $agpta_wishlist, 'agpta_wishlist_add_new_handler' );
		$this->loader->add_action( 'admin_post_agpta_wishlist_edit', $agpta_wishlist, 'agpta_wishlist_edit_handler' );
		$this->loader->add_action( 'wp_ajax_agpta_wishlist_delete', $agpta_wishlist, 'agpta_wishlist_delete_handler' );
		$this->loader->add_shortcode( 'agpta_wishlist_list', $agpta_wishlist, 'agpta_wishlist_display_shortcode' );
		
		$this->loader->add_action( 'admin_post_agpta_create_stripe_checkout_session', $agpta_stripe, 'agpta_create_stripe_checkout_session' );
		$this->loader->add_action( 'admin_post_nopriv_agpta_create_stripe_checkout_session', $agpta_stripe, 'agpta_create_stripe_checkout_session' );
		$this->loader->add_shortcode( 'agpta_stripe_thank_you', $agpta_stripe, 'agpta_thank_you_page_display' );
		$this->loader->add_action( 'rest_api_init', $agpta_stripe, 'agpta_register_webhook_route' );
		
		$this->loader->add_action( 'init', $agpta_calendar, 'init' );
		$this->loader->add_action( 'add_meta_boxes', $agpta_calendar, 'add_calendar_meta_box' );
		$this->loader->add_action( 'save_post', $agpta_calendar, 'agpta_save_calendar_date_meta' );
		$this->loader->add_action( 'save_post', $agpta_calendar, 'agpta_save_calendar_status_meta' );
		
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Agpta_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		
		$this->loader->add_action( 'init', $plugin_public, 'agpta_track_email' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Agpta_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	
}
