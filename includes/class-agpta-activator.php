<?php

/**
 * Fired during plugin activation
 *
 * @link       https://gabrielcastillo.net
 * @since      1.0.0
 *
 * @package    Agpta
 * @subpackage Agpta/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Agpta
 * @subpackage Agpta/includes
 * @author     Gabriel Castillo <gabriel@gabrielcastillo.net>
 */
class Agpta_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		$database = new AGPTA_Database();

		$database->agpta_create_teams_table();
		$database->agpta_create_contact_form_table();
		$database->agpta_create_wishlist_table();
		$database->agpta_create_transactions_table();
	}

}
