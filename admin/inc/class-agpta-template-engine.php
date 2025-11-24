<?php
	/*
 * File: class-agpta-template-engine.php
 *
 * @author Gabriel Castillo <gabriel@gabrielcastillo.net>
 * Copyright (c) 2024-2025.
 */


if ( ! defined( 'ABSPATH' ) ) {
	return;
}

class AGPTA_Template_Engine {

	protected string $template_dir;

	protected array $vars;

	public function __construct( $template_dir = '' ) {
		$this->template_dir = $template_dir;
	}

	/**
	 * Render Email Template
	 *
	 * @param string $template_file file path for template file.
	 *
	 */
	public function render( $template_file ) {
		if ( ! file_exists( $this->template_dir . $template_file ) ) {
			return new WP_Error( 'Template File not found. "' . $template_file . '"', array( 'status' => 400 ) );
		}
		include $this->template_dir . $template_file;
	}

	public function __set( $name, $value ) {
		$this->vars[ $name ] = $value;
	}
	
	public function __isset(string $name): bool
	{
		if ( isset( $name ) ) {
			return true;
		}
		
		return false;
	}
	
	public function __get( $name ) {
		return $this->vars[ $name ];
	}
}
