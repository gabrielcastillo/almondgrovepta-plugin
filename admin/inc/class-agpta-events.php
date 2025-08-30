<?php
/*
 * File: class-agpta-events.php
 *
 * @author Gabriel Castillo <gabriel@gabrielcastillo.net>
 * Copyright (c) 2025.
 */


class AGPTA_Events {

	public string $plugin_name;

	public object $db;

	public string $page_slug;

	public function __construct( $plugin_name ) {
		global $wpdb;

		$this->plugin_name = $plugin_name;
		$this->db          = $wpdb;
		$this->page_slug   = 'edit.php?post_type=pta_events';
	}

	public function init() {
		$this->agpta_events_cpt();
		add_action( 'wp_ajax_submit_cart', array( $this, 'agpta_submit_cart' ) );
		add_action( 'wp_ajax_nopriv_submit_cart', array( $this, 'agpta_submit_cart' ) );
		add_action( 'wp_ajax_get_pta_events', array( $this, 'agpta_get_pta_events' ) );
		add_action( 'wp_ajax_nopriv_get_pta_events', array( $this, 'agpta_get_pta_events' ) );
	}

	public function add_event_price_meta_box() {
		add_meta_box(
			'agpta_event_price_meta_box',
			'Event Price',
			array( $this, 'agpta_event_price_meta_box_cb' ),
			'pta_events',
			'side',
		);

		add_meta_box(
			'agpta_event_date_meta_box',
			'Event Date',
			array( $this, 'agpta_event_date_meta_box_cb' ),
			'pta_events',
			'side',
		);

		add_meta_box(
			'agpta_event_status_meta_box',
			'Event Status',
			array( $this, 'agpta_event_status_meta_box_cb' ),
			'pta_events',
			'side'
		);
	}

	public function agpta_event_price_meta_box_cb( $post ): void {

		wp_nonce_field( 'agpta_save_event_price', 'agpta_event_price_nonce' );
		$value = get_post_meta( $post->ID, '_agpta_event_price', true );
		$value = ( $value ) ? number_format( $value, 2 ) : '';
		echo '<label for="agpta_event_price_field">';
		echo '<input type="text" class="all-options" id="agpta_event_price_field" name="agpta_event_price_field" value="' . esc_attr( $value ) . '" placeholder="$0.00" />';
		echo '</label>';
	}

	public function agpta_event_date_meta_box_cb( $post ): void {
		wp_nonce_field( 'agpta_save_event_date', 'agpta_event_date_nonce' );
		$value = get_post_meta( $post->ID, '_agpta_event_date', true );
		echo '<label for="agpta_event_date_field">';
		echo '<input type="date" class="all-options" id="agpta_event_date_field" name="agpta_event_date_field" value="' . esc_attr( $value ) . '" />';
		echo '</label>';
	}

	public function agpta_event_status_meta_box_cb( $post ) {
		wp_nonce_field( 'agpta_save_event_status', 'agpta_event_status_nonce' );
		$value = get_post_meta( $post->ID, '_agpta_event_status', true );
		echo '<label for="agpta_event_status_field">';
		echo '<select id="agpta_event_status_field" name="agpta_event_status_field" class="regular-text">';
		echo '<option value="0"' . ( absint( $value ) == 0 ? ' selected="selected"' : '' ) . '>Not Public</option>';
		echo '<option value="1"' . ( absint( $value ) == 1 ? ' selected="selected"' : '' ) . '>Public</option>';
		echo '</select>';
		echo '</label>';
	}

	public function agpta_save_event_price_meta( $post_id ): void {

		if ( ! isset( $_POST['agpta_event_price_nonce'] ) || ! wp_verify_nonce( $_POST['agpta_event_price_nonce'], 'agpta_save_event_price' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( isset( $_POST['agpta_event_price_field'] ) ) {
			update_post_meta( $post_id, '_agpta_event_price', (float) $_POST['agpta_event_price_field'] );
		}
	}

	public function agpta_save_event_date_meta( $post_id ) {

		if ( ! isset( $_POST['agpta_event_date_nonce'] ) || ! wp_verify_nonce( $_POST['agpta_event_date_nonce'], 'agpta_save_event_date' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( isset( $_POST['agpta_event_date_field'] ) ) {
			update_post_meta( $post_id, '_agpta_event_date', sanitize_text_field( $_POST['agpta_event_date_field'] ) );
		}
	}

	public function agpta_save_event_status_meta( $post_id ) {

		if ( ! isset( $_POST['agpta_event_status_nonce'] ) || ! wp_verify_nonce( $_POST['agpta_event_status_nonce'], 'agpta_save_event_status' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( isset( $_POST['agpta_event_status_field'] ) ) {
			$status = $_POST['agpta_event_status_field'];
			if ( in_array( $status, array( '0', '1' ), true ) ) {
				update_post_meta( $post_id, '_agpta_event_status', $status );
			}
		}
	}

	public function agpta_events_cpt(): void {
		$labels = array(
			'name'               => _x( 'PTA Event', $this->plugin_name ),
			'singular_name'      => _x( 'PTA Event', $this->plugin_name ),
			'menu_name'          => _x( 'PTA Events', $this->plugin_name ),
			'name_admin_bar'     => _x( 'PTA Event', $this->plugin_name ),
			'add_new'            => _x( 'Add PTA Event', $this->plugin_name ),
			'add_new_item'       => __( 'Add Event', $this->plugin_name ),
			'new_item'           => __( 'New PTA Event', $this->plugin_name ),
			'edit_item'          => __( 'Edit PTA Event', $this->plugin_name ),
			'view_item'          => __( 'View Event', $this->plugin_name ),
			'all_items'          => __( 'PTA Events', $this->plugin_name ),
			'search_items'       => __( 'Search PTA Events', $this->plugin_name ),
			'parent_item_colon'  => __( 'Parent Events', $this->plugin_name ),
			'not_found'          => __( 'No Events Found', $this->plugin_name ),
			'not_found_in_trash' => __( 'No PTA Events Found', $this->plugin_name ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'pta-events' ),
			'capability_type'    => 'page',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 5,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'revisions', 'excerpt' ),
			'show_in_rest'       => true,
		);

		register_post_type( 'pta_events', $args );

		register_post_type(
			'agpta_orders',
			array(
				'label'           => 'Orders',
				'public'          => false,
				'show_ui'         => true,
				'supports'        => array( 'title', 'editor' ),
				'capability_type' => 'post',
			)
		);
	}

	public function agpta_get_pta_events() {
		$events = get_posts(
			array(
				'post_type'      => 'pta_events',
				'posts_per_page' => -1,
			)
		);

		$data = array();

		foreach ( $events as $event ) {
			$data[] = array(
				'id'         => $event->ID,
				'eventTitle' => get_the_title( $event ),
				'eventPrice' => get_post_meta( $event->ID, '_agpta_event_price', true ),
				'eventDate'  => get_post_meta( $event->ID, '_agpta_event_date', true ),
			);
		}

		wp_send_json( $data );
	}

}
