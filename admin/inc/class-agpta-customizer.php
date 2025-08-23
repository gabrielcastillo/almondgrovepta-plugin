<?php
/*
 * File: class-agpta-customizer.php
 *
 * @author Gabriel Castillo <gabriel@gabrielcastillo.net>
 * Copyright (c) 2025.
 */


class AGPTA_Customizer {

	public string $plugin_name;


	public function __construct( $plugin_name) {
		$this->plugin_name = $plugin_name;
	}


	public function agpta_customizer_register_init( $wp_customize ) :void {
		$wp_customize->add_section( 'principal_reports_archive_page_section', array(
			'title' => __( 'Archive Page Settings', $this->plugin_name ),
			'priority' => 30,
		) );

		$wp_customize->add_setting( 'principal_reports_archive_page', array(
			'default' => '',
			'sanitize_callback' => 'absint',
		) );

		$wp_customize->add_control( new WP_Customize_Control(
			$wp_customize,
			'principal_reports_archive_page',
			array(
				'label' => __( 'Principal Reports Archive Page', $this->plugin_name ),
				'section' => 'principal_reports_archive_page_section',
				'settings' => 'principal_reports_archive_page',
				'type' => 'dropdown-page',
			),
		) );
	}




}