<?php
/*
 * File: class-agpta-list-table-teams.php
 *
 * @author Gabriel Castillo <gabriel@gabrielcastillo.net>
 * Copyright (c) 2025.
 */


class AGPTA_List_Table_Teams extends WP_List_Table {

	public string $plugin_name;
	public string $plugin_version;

	public function __construct( $plugin_version, $plugin_name ) {
		parent::__construct();

		$this->plugin_name = $plugin_name;
		$this->plugin_version = $plugin_version;
	}

	public function prepare_items() :void {
		global $wpdb;

		$this->process_bulk_actions();

		$tablename  = $wpdb->prefix . 'agpta_team';
		$per_page   = $this->get_items_per_page('posts_per_page');
		$paged      = isset( $_GET['paged'] ) ? $_GET['paged'] : 1;
		$offset     = ( $paged - 1 ) * $per_page;

		$query = /** @lang sql */
			"SELECT * FROM {$tablename} LIMIT %d, %d";
		$data = $wpdb->get_results( $wpdb->prepare( $query, $offset, $per_page ) );

		$total_items = $wpdb->get_var( /** @lang sql */"SELECT COUNT(*) FROM {$tablename}" );

		$columns = $this->get_columns();
		$hidden = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = $data;
		$this->set_pagination_args([
			'total_items' => $total_items,
			'per_page' => $per_page,
		]);
	}

	public function get_hidden_columns() {
		return [];
	}

	public function get_columns()
	{
		$columns = [
			'cb' => '<input type="checkbox" />',
			'member_name' => __( 'Name', $this->plugin_name ),
			'member_role' => __( 'Role', $this->plugin_name ),
			'member_email' => __( 'Email', $this->plugin_name ),
		];

		return $columns;
	}

	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {
			case 'member_name':
				return $item->member_name;
				break;

			case 'member_role':
				return $item->member_role;
				break;

			case 'member_email':
				return $item->member_email;
				break;
			default:
				return print_r($item, true); // Debugging
		}
	}

	public function get_sortable_columns(): array {
		$sortable_columns = [
			'member_name' => ['member_name', true],
			'member_role' => ['member_role', false],
		];

		return $sortable_columns;
	}

	public function search_items( $search_term ): array|object|null {
		global $wpdb;

		$tablename = $wpdb->prefix . 'agpta_team';

		$query = /** @lang sql */
		"SELECT * FROM {$tablename} WHERE member_name LIKE %s OR member_email LIKE %s";
		return $wpdb->get_results( $wpdb->prepare( $query, '%'. $wpdb->esc_like($search_term) . '%', '%'. $wpdb->esc_like($search_term) . '%' ) );
	}

	public function get_bulk_actions(): array {
		$actions = [
			'bulk-delete' => __( 'Delete', $this->plugin_name ),
		];

		return $actions;
	}

	public function process_bulk_actions(): void {

		if ( 'bulk-delete' === $this->current_action() ) {

			$ids = isset( $_POST['bulk-delete'] ) ? $_POST['bulk-delete'] : [];

			foreach( $ids as $id ) {
				global $wpdb;

				$wpdb->delete( "{$wpdb->prefix}agpta_team",  ['ID' => $id] );
			}
		}
	}

	public function column_cb( $item ) {

		return sprintf( '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item->id );
	}

	public function no_items() {
		_e( 'No board members found', $this->plugin_name );
	}

	private function dd( $value ) {
		echo '<pre>';
		echo print_r( $value, true);
		echo '</pre>';
		exit;
	}
}