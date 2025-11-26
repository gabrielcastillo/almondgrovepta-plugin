<?php
/*
 * File: class-agpta-events.php
 *
 * @author Gabriel Castillo <gabriel@gabrielcastillo.net>
 * Copyright (c) 2025.
 */


class AGPTA_Calendar {

	/**
	 * Plugin Name
	 *
	 * @var string
	 */
	public string $plugin_name;

	/**
	 * Database Object
	 *
	 * @var object
	 */
	public object $db;

	/**
	 * Page slug
	 *
	 * @var string
	 */
	public string $page_slug;

	/**
	 * Constructor
	 *
	 * @param string $plugin_name plugin name.
	 * @param object $wpdb WordPress database object.
	 */
	public function __construct( string $plugin_name, object $wpdb ) {
		$this->plugin_name = $plugin_name;
		$this->db          = $wpdb;
		$this->page_slug   = 'edit.php?post_type=agpta_calendar';
	}

	/**
	 * Initialize CPT, Actions
	 *
	 * @return void
	 */
	public function init(): void {
		$this->agpta_events_cpt();
		add_action( 'wp_ajax_get_agpta_calendar', array( $this, 'get_agpta_calendar' ) );
		add_action( 'wp_ajax_nopriv_get_agpta_calendar', array( $this, 'get_agpta_calendar' ) );
	}

	/**
	 * Add Calendar Meta Box
	 *
	 * @return void
	 */
	public function add_calendar_meta_box(): void {

		add_meta_box(
			'agpta_calendar_date_meta_box',
			'Calendar Date',
			array( $this, 'agpta_calendar_date_meta_box_cb' ),
			'agpta_calendar',
			'side',
		);

		add_meta_box(
			'agpta_calendar_status_meta_box',
			'Show in Calendar',
			array( $this, 'agpta_calendar_status_meta_box_cb' ),
			'agpta_calendar',
			'side'
		);
	}

	/**
	 * Calendar Date Meta Box Callback
	 *
	 * @param object $post post object.
	 *
	 * @return void
	 */
	public function agpta_calendar_date_meta_box_cb( object $post ): void {

		// Add nonce for verification.
		wp_nonce_field( 'agpta_save_calendar_date_nonce', 'agpta_save_calendar_date_nonce' );

		// Retrieve stored value.
		$value          = get_post_meta( $post->ID, '_agpta_calendar_date', true );
		$end_date_value = get_post_meta( $post->ID, '_agpta_calendar_end_date', true );
		?>
		
		<p>
			<label for="agpta_calendar_date_field">Start Date</label>
				<input
						type="date"
						id="agpta_calendar_date_field"
						name="agpta_calendar_date_field"
						class="all-options"
						value="<?php echo esc_attr( $value ); ?>"
				/>
		</p>
		<p>
			<label for="agpta_calendar_end_date_field">End Date</label>
			<input type="date" id="agpta_calendar_end_date_field" name="agpta_calendar_end_date_field" class="all-options" value="<?php echo esc_attr( $end_date_value ); ?>"
		</p>
		<?php
	}

	/**
	 * Status Meta Box Callback
	 *
	 * @param object $post post object.
	 *
	 * @return void
	 */
	public function agpta_calendar_status_meta_box_cb( object $post ): void {
		wp_nonce_field( 'agpta_save_calendar_status_nonce', 'agpta_save_calendar_status_nonce' );
		$value = get_post_meta( $post->ID, '_agpta_calendar_status', true );
		echo '<label for="agpta_calendar_status_field">';
		echo '<select id="agpta_calendar_status_field" name="agpta_calendar_status_field" class="regular-text">';
		echo '<option value="0"' . ( absint( $value ) === 0 ? ' selected="selected"' : '' ) . '>Not Public</option>';
		echo '<option value="1"' . ( absint( $value ) === 1 ? ' selected="selected"' : '' ) . '>Public</option>';
		echo '</select>';
		echo '</label>';
	}

	/**
	 * Save Calendar Date Meta
	 *
	 * @param  int $post_id  post id.
	 *
	 * @return void
	 */
	public function agpta_save_calendar_date_meta( int $post_id ): void {

		if ( ! isset( $_POST['agpta_save_calendar_date_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['agpta_save_calendar_date_nonce'] ) ), 'agpta_save_calendar_date_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['agpta_calendar_date_field'] ) ) {
			update_post_meta(
				$post_id,
				'_agpta_calendar_date',
				sanitize_text_field( wp_unslash( $_POST['agpta_calendar_date_field'] ) )
			);
		}

		if ( isset( $_POST['agpta_calendar_end_date_field'] ) ) {
			update_post_meta(
				$post_id,
				'_agpta_calendar_end_date',
				sanitize_text_field( wp_unslash( $_POST['agpta_calendar_end_date_field'] ) )
			);
		}
	}


	/**
	 * Save Calendar Status
	 *
	 * @param int $post_id post id.
	 *
	 * @return void
	 */
	public function agpta_save_calendar_status_meta( int $post_id ): void {

		if ( ! isset( $_POST['agpta_save_calendar_status_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['agpta_save_calendar_status_nonce'] ) ), 'agpta_save_calendar_status_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( isset( $_POST['agpta_calendar_status_field'] ) ) {
			$status = sanitize_text_field( wp_unslash( $_POST['agpta_calendar_status_field'] ) );
			if ( in_array( $status, array( '0', '1' ), true ) ) {
				update_post_meta( $post_id, '_agpta_calendar_status', $status );
			}
		}
	}

	/**
	 * Register custom post type for Calendar
	 *
	 * @return void
	 */
	public function agpta_events_cpt(): void {
		$labels = array(
			'name'               => __( 'PTA Calendar', $this->plugin_name ),
			'singular_name'      => __( 'PTA Calendar', $this->plugin_name ),
			'menu_name'          => __( 'PTA Calendar', $this->plugin_name ),
			'name_admin_bar'     => __( 'PTA Calendar', $this->plugin_name ),
			'add_new'            => __( 'Add New', $this->plugin_name ),
			'add_new_item'       => __( 'Add New Item', $this->plugin_name ),
			'new_item'           => __( 'New Calendar', $this->plugin_name ),
			'edit_item'          => __( 'Edit Calendar Item', $this->plugin_name ),
			'view_item'          => __( 'View Calendar Item', $this->plugin_name ),
			'all_items'          => __( 'PTA Calendar', $this->plugin_name ),
			'search_items'       => __( 'Search PTA Calendar', $this->plugin_name ),
			'parent_item_colon'  => __( 'Parent Calendar', $this->plugin_name ),
			'not_found'          => __( 'No Calendar Found', $this->plugin_name ),
			'not_found_in_trash' => __( 'No PTA Calendar Found', $this->plugin_name ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'agpta-calendar' ),
			'capability_type'    => 'post',
			'has_archive'        => 'agpta-calendar',
			'hierarchical'       => false,
			'menu_position'      => 5,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'revisions', 'excerpt' ),
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-calendar-alt',
		);

		register_post_type( 'agpta_calendar', $args );
	}

	/**
	 * Get Calendar Ajax
	 *
	 * @return void
	 */
	public function get_agpta_calendar(): void {
		$calendars = get_posts(
			array(
				'post_type'      => 'agpta_calendar',
				'posts_per_page' => -1,
			)
		);

		$data = array();

		foreach ( $calendars as $calendar ) {
			$data[] = array(
				'id'           => $calendar->ID,
				'eventTitle'   => get_the_title( $calendar ),
				'eventDate'    => get_post_meta( $calendar->ID, '_agpta_calendar_date', true ),
				'eventEndDate' => get_post_meta( $calendar->ID, '_agpta_calendar_end_date', true ),
			);
		}

		wp_send_json( $data );
	}
}
