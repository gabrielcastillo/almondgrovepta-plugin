<?php
/*
 * File: class-agpta-principal-report.php
 *
 * @author Gabriel Castillo <gabriel@gabrielcastillo.net>
 * Copyright (c) 2025.
 */


class AGPTA_Principal_Report {

	protected string $plugin_name;

	public function __construct( $plugin_name ) {
		$this->plugin_name = $plugin_name;
	}


	public function init() :void {
		$this->agpta_principal_report_cpt();
	}

	public function agpta_principal_report_cpt() :void {
		$labels = array(
			'name' => _x( 'Principal Report', $this->plugin_name ),
			'singular_name' => _x( 'Principal Report', $this->plugin_name ),
			'menu_name' => _x( 'Principal Reports', $this->plugin_name ),
			'name_admin_bar' => _x( 'Principal Report', $this->plugin_name ),
			'add_new' => _x( 'Add Principal Report', $this->plugin_name ),
			'add_new_item' => __( 'Add New Report', $this->plugin_name ),
			'new_item' => __( 'New Principal Report', $this->plugin_name ),
			'edit_item' => __( 'Edit Principal Report', $this->plugin_name ),
			'view_item' => __( 'View Report', $this->plugin_name ),
			'all_items' => __( 'All Reports', $this->plugin_name ),
			'search_items' => __( 'Search Principal Reports', $this->plugin_name ),
			'parent_item_colon' => __( 'Parent Reports', $this->plugin_name ),
			'not_found' => __('No Reports Found', $this->plugin_name ),
			'not_found_in_trash' => __('No Principal Reports Found', $this->plugin_name ),
		);

		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'query_var' => true,
			'rewrite' => array( 'slug' => 'principal-reports'),
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => true,
			'menu_position' => 5,
			'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'revisions', 'excerpt', 'page-attributes', 'post-formats'),
			'show_in_rest' => true,
		);

		register_post_type( 'principal_report', $args );
	}

}