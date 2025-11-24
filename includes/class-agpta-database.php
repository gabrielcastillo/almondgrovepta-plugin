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
    		description TEXT NULL,
    		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    		PRIMARY KEY ( id )
		) $charset_collate;";

		dbDelta( $sql );
	}

	public function agpta_create_transactions_table(): void {
		global $wpdb;
		$table           = $wpdb->prefix . 'agpta_stripe_transactions';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_email VARCHAR(255) NOT NULL,
        user_name VARCHAR(255) DEFAULT NULL,
        user_phone VARCHAR(50) DEFAULT NULL,
        address_line1 VARCHAR(255) DEFAULT NULL,
        address_line2 VARCHAR(255) DEFAULT NULL,
        city VARCHAR(100) DEFAULT NULL,
        state VARCHAR(50) DEFAULT NULL,
        postal_code VARCHAR(20) DEFAULT NULL,
        country VARCHAR(50) DEFAULT NULL,
        transaction_id VARCHAR(255) NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
        currency VARCHAR(10) DEFAULT 'usd',
        payment_status VARCHAR(50) DEFAULT NULL,
        line_items LONGTEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY transaction_id_unique (transaction_id)
    ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
