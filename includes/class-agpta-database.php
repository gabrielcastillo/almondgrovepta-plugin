<?php
/*
 * File: class-agpta-database.php
 *
 * @author Gabriel Castillo <gabriel@gabrielcastillo.net>
 * Copyright (c) 2025.
 */

class AGPTA_Database {

	private object $db;

	private string $charset_collate;

	public function __construct() {
		global $wpdb;

		$this->db              = $wpdb;
		$this->charset_collate = $this->db->get_charset_collate();
	}

	public function agpta_create_teams_table(): void {
		$tablename = $this->db->prefix . 'agpta_team';

		$sql = /** @lang sql */
			"CREATE TABLE IF NOT EXISTS $tablename (
    	 id mediumint(9) NOT NULL AUTO_INCREMENT,
    	 member_name varchar(200) DEFAULT '' NOT NULL,
    	 member_role varchar(50) DEFAULT '' NOT NULL,
    	 member_email varchar(100) DEFAULT '' NOT NULL,
    	 member_avatar text DEFAULT '',
    	 PRIMARY KEY (id)
		) $this->charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $sql );
	}

	public function agpta_create_stripe_customer_table(): void {
		$tablename = $this->db->prefix . 'agpta_stripe_customer';

		$sql = /** @lang sql */
			"CREATE TABLE IF NOT EXISTS $tablename (
			customer_id INT(11) NOT NULL AUTO_INCREMENT,
			customer_name VARCHAR(200) DEFAULT '' NOT NULL,
			customer_email VARCHAR(255) DEFAULT '' NOT NULL,
			customer_phone VARCHAR(50) DEFAULT '',
			PRIMARY KEY (customer_id)
			) $this->charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $sql );
	}

	public function agpta_create_stripe_customer_transaction_table(): void {
		$tablename = $this->db->prefix . 'agpta_stripe_transaction';

		$sql = /** @lang SQL */
		"CREATE TABLE IF NOT EXISTS $tablename (
		transaction_id INT(11) NOT NULL AUTO_INCREMENT,
		customer_id INT(11) NOT NULL,
		item_name VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
		item_number VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
		item_price VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL,
		item_price_currency VARCHAR(10) COLLATE utf8_unicode_ci NOT NULL,
		paid_amount VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL,
		paid_amount_currency VARCHAR(10) COLLATE utf8_unicode_ci NOT NULL,
		txn_id VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
		payment_status VARCHAR(25) COLLATE utf8_unicode_ci NOT NULL,
		stripe_checkout_session_id VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL,
		created_at DATETIME DEFAULT current_timestamp NOT NULL,
		updated_at DATETIME DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY (transaction_id)
		) $this->charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $sql );
	}

	public function agpta_create_contact_form_table() {

		$table_name      = $this->db->prefix . 'agpta_contact_form_entries';
		$charset_collate = $this->db->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = /** @lang SQL */
			"CREATE TABLE {$table_name} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL,
			email VARCHAR(255) NOT NULL,
			message TEXT NOT NULL,
			status VARCHAR(50) NOT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";

		dbDelta( $sql );
	}

	public function agpta_create_wishlist_table() {

		$table_name      = $this->db->prefix . 'agpta_wishlists';
		$charset_collate = $this->db->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$table_name} (
    		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    		name VARCHAR(100) NOT NULL,
    		url VARCHAR(255) NOT NULL,
    		location VARCHAR(100) DEFAULT '',
    		description TEXT DEFAULT '',
    		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    		PRIMARY KEY ( id )
		) $charset_collate;";

		dbDelta( $sql );
	}



	function agpta_create_transactions_table() {
		global $wpdb;
		$table = $wpdb->prefix . 'ticket_transactions';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_email VARCHAR(255) NOT NULL,
        event_ids LONGTEXT NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        transaction_id VARCHAR(255) NOT NULL,
        status VARCHAR(50) NOT NULL,
        customer_id VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
}
