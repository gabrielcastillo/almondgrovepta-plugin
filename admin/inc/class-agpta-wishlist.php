<?php
	/**
	 * File: class-agpta-wishlist.php
	 *
	 * @author Gabriel Castillo <gabriel@gabrielcastillo.net>
	 * Copyright (c) 2025.
	 */
class AGPTA_Wishlist {

	/**
	 * Plugin Name
	 *
	 * @var string
	 */
	private string $plugin_name;

	/**
	 * Plugin Version
	 *
	 * @var string
	 */
	private string $plugin_version;

	/**
	 * Database Object - WPDB object
	 *
	 * @var object
	 */
	private object $db;

	/**
	 * Parent Page Slug
	 *
	 * @var string
	 */
	private string $page_slug;

	/**
	 * Database tablename for wishlists
	 *
	 * @var string
	 */
	private string $tablename;
    
    private string $nonce;

	/**
	 * Constructor
	 *
	 * @param  string $plugin_name     Plugin name passed from parent class.
	 * @param  string $plugin_version  Plugin version passed from parent class.
	 * @param  object $wpdb            Passed from parent class.
	 */
	public function __construct( string $plugin_name, string $plugin_version, object $wpdb ) {
		$this->plugin_name    = $plugin_name;
		$this->plugin_version = $plugin_version;
		$this->db             = $wpdb;
		$this->page_slug      = 'agpta-wishlists';
		$this->tablename      = $this->db->prefix . 'agpta_wishlists';
	}

	/**
	 * Add admin menu display pages
	 *
	 * @return void
	 */
	public function agpta_wishlist_admin_page_init(): void {
		add_menu_page(
			__( 'Wishlists', 'agpta_wishlists' ),
			__( 'Wishlists', 'agpta_wishlists' ),
			'manage_options',
			$this->page_slug,
			array( $this, 'agpta_wishlist_admin_page_callback' ),
			'dashicons-admin-generic',
			8
		);

		add_submenu_page(
			$this->page_slug,
			__( 'Add Wishlist', 'agpta_wishlists' ),
			__( 'Add Wishlist', 'agpta_wishlists' ),
			'manage_options',
			$this->page_slug . '-new',
			array( $this, 'agpta_wishlist_add_new_admin_page_callback' ),
			8
		);

		add_submenu_page(
			'_null_dev',
			__( 'Edit Wishlist', 'agpta_wishlists' ),
			__( 'Edit Wishlist', 'agpta_wishlists' ),
			'manage_options',
			$this->page_slug . '-edit',
			array( $this, 'agpta_wishlist_edit_admin_page_callback' ),
			8
		);
		
		$this->nonce = wp_create_nonce( 'agpta_admin_nonce' );
	}

	/**
	 * Main wishlist display page.
	 *
	 * @return void
	 */
	public function agpta_wishlist_admin_page_callback(): void {
		echo '<h1>Staff Wishlists</h1>';
		echo '<p>Manage staff wishlists</p>';

		unset( $_SESSION['agpta_form_data'] ); // Session data used to store errors from crud pages.

		$results = $this->agpta_wishlist_get_data(); // Get wishlist data.

		?>
		<div class="wrap">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=agpta-wishlists-new' ) ); ?>" class="page-title-action">Add New Wishlist</a>
			<table id="wishlist-table" class="table widefat">
				<thead>
				<tr>
					<th>Name</th>
					<th>Location</th>
					<th>URL</th>
					<th>Created At</th>
					<th></th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ( $results as $result ) : ?>
					<tr>
						<td><?php echo esc_html( $result->name ); ?></td>
						<td><?php echo esc_html( $result->location ); ?></td>
						<td><?php echo esc_url( $result->url ); ?></td>
						<td><?php echo esc_html( $result->created_at ); ?></td>
						<td><a href="
						<?php
								echo esc_url(
									add_query_arg(
										array(
											'page'  => $this->page_slug . '-edit',
											'id'    => $result->id,
											'nonce' => $this->nonce,
										),
										admin_url( 'admin.php' )
									)
								);
						?>
						" title="Edit wishlist item">Edit</a></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Display Add Wishlist page
	 *
	 * @return void
	 */
	public function agpta_wishlist_add_new_admin_page_callback(): void {
		echo '<h1>New Staff Wishlists</h1>';
		echo '<p>Add new staff wishlist</p>';

		?>
		<div class="wrap">
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
				<input type="hidden" name="agpta_nonce" value="<?php echo esc_attr( $this->nonce ); ?>" />
				<input type="hidden" name="action" value="agpta_wishlist_add_new" />

				<table class="form-table">
					<tr>
						<th><label for="staff_name">Staff Name</label></th>
						<td>
							<input id="staff_name" class="regular-text" name="staff_name" type="text"  value="<?php echo esc_attr( ( isset( $_SESSION['agpta_form_data']['post']['staff_name'] ) ) ? sanitize_text_field( wp_unslash( $_SESSION['agpta_form_data']['post']['staff_name'] ) ) : '' ); ?>"/>
							<?php echo ( isset( $_SESSION['agpta_form_data']['errors']['staff_name'] ) ) ? esc_html( sanitize_text_field( wp_unslash( $_SESSION['agpta_form_data']['errors']['staff_name'] ) ) ) : ''; ?>
						</td>
					</tr>
					<tr>
						<th><label for="staff_location">Staff Location</label></th>
						<td>
							<input id="staff_location" class="regular-text" name="staff_location" type="text" placeholder="Room 5 or Office or Library" value="<?php echo esc_attr( ( isset( $_SESSION['agpta_form_data']['post']['staff_location'] ) ) ? sanitize_text_field( wp_unslash( $_SESSION['agpta_form_data']['post']['staff_location'] ) ) : '' ); ?>" />
							<?php echo ( isset( $_SESSION['agpta_form_data']['errors']['staff_location'] ) ) ? esc_html( sanitize_text_field( wp_unslash( $_SESSION['agpta_form_data']['errors']['staff_location'] ) ) ) : ''; ?>
						</td>
					</tr>
					<tr>
						<th><label for="staff_list_url">Staff Wishlist URL</label></th>
						<td>
							<input id="staff_list_url" class="regular-text" name="staff_list_url" type="text" placeholder="URL to amazon, or any URL that has a list of wants." value="<?php echo esc_attr( ( isset( $_SESSION['agpta_form_data']['post']['staff_list_url'] ) ) ? sanitize_text_field( wp_unslash( $_SESSION['agpta_form_data']['post']['staff_list_url'] ) ) : '' ); ?>"/>
							<?php echo ( isset( $_SESSION['agpta_form_data']['errors']['staff_list_url'] ) ) ? esc_html( sanitize_text_field( wp_unslash( $_SESSION['agpta_form_data']['errors']['staff_list_url'] ) ) ) : ''; ?>
						</td>
					</tr>
					<tr>
						<th><label for="staff_list_description">Staff Wishlist Description</label></th>
						<td>
							<textarea id="staff_list_description" class="regular-text" name="staff_list_description" rows="8"><?php echo esc_html( ( isset( $_SESSION['agpta_form_data']['post']['staff_list_description'] ) ) ? esc_html( sanitize_textarea_field( wp_unslash( $_SESSION['agpta_form_data']['post']['staff_list_description'] ) ) ) : '' ); ?></textarea>
						</td>
					</tr>
				</table>
				<?php submit_button( 'Submit Form' ); ?>
			</form>
		</div>
		<?php
		unset( $_SESSION['agpta_form_data'] );
	}

	/**
	 * Wishlist Edit Admin Page
	 *
	 * @return void
	 */
	public function agpta_wishlist_edit_admin_page_callback(): void {

		$record = $this->agpta_wishlist_get_by_id( sanitize_text_field( wp_unslash( $_GET['id'] ) ) );

		if ( ! $record ) {
			echo 'No record found.';
			exit;
		}
		echo '<h1>Edit Wishlist</h1>';
		echo '<p>Edit wishlist</p>';
		?>
		<div class="wrap">
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
				<input type="hidden" name="agpta_nonce" value="<?php echo esc_attr( $this->nonce ); ?>" />
				<input type="hidden" name="action" value="agpta_wishlist_edit" />
				<input type="hidden" name="id" value="<?php echo esc_attr( $record->id ); ?>" />
				<table class="form-table">
					<tr>
						<th><label for="staff_name">Staff Name</label></th>
						<td>
							<input id="staff_name" class="regular-text" name="staff_name" type="text"  value="<?php echo esc_attr( $record->name ); ?>"/>
							<?php echo ( ! empty( $_SESSION['agpta_form_data']['errors']['staff_name'] ) ) ? esc_html( sanitize_text_field( wp_unslash( $_SESSION['agpta_form_data']['errors']['staff_name'] ) ) ) : ''; ?>
						</td>
					</tr>
					<tr>
						<th><label for="staff_location">Staff Location</label></th>
						<td>
							<input id="staff_location" class="regular-text" name="staff_location" type="text" placeholder="Room 5 or Office or Library" value="<?php echo esc_attr( $record->location ); ?>" />
							<?php echo ( ! empty( $_SESSION['agpta_form_data']['errors']['staff_location'] ) ) ? esc_html( sanitize_text_field( wp_unslash( $_SESSION['agpta_form_data']['errors']['staff_location'] ) ) ) : ''; ?>
						</td>
					</tr>
					<tr>
						<th><label for="staff_list_url">Staff Wishlist URL</label></th>
						<td>
							<input id="staff_list_url" class="regular-text" name="staff_list_url" type="text" placeholder="URL to amazon, or any URL that has a list of wants." value="<?php echo esc_url( $record->url ); ?>"/>
							<?php echo ( ! empty( $_SESSION['agpta_form_data']['errors']['staff_list_url'] ) ) ? esc_html( sanitize_text_field( wp_unslash( $_SESSION['agpta_form_data']['errors']['staff_list_url'] ) ) ) : ''; ?>
						</td>
					</tr>
					<tr>
						<th><label for="staff_list_description">Staff Wishlist Description</label></th>
						<td>
							<textarea id="staff_list_description" class="regular-text" name="staff_list_description" rows="8"><?php echo esc_textarea( $record->description ); ?></textarea>
						</td>
					</tr>
				</table>
				<div>
					<p class="submit">
						<input class="button button-primary" type="submit" name="submit" value="Submit Form" />
						<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $this->page_slug ) ); ?>">View Wishlists</a>
						<button id="delete-wishlist" class="button button-link-delete" data-id="<?php echo esc_attr( $record->id ); ?>" type="button">Delete</button>
					</p>

				</div>
			</form>
		</div>
		<?php
		unset( $_SESSION['agpta_form_data'] );
	}

	/**
	 * Process the form submission for add new wishlists
	 *
	 * @return void
	 */
	public function agpta_wishlist_add_new_handler(): void {

		if ( ! current_user_can( 'manage_options' ) ) {
			$referer = wp_get_referer() ?? admin_url();

			$args = array(
				'status'  => 'error',
				'message' => rawurlencode( 'You do not have permission to view this page.' ),
			);
			wp_safe_redirect( add_query_arg( $args, $referer ) );
			exit;
		}

		$has_errors = 0;

		if ( ! isset( $_POST['agpta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['agpta_nonce'] ) ), 'agpta_admin_nonce' ) ) {
			$referer = wp_get_referer() ?? admin_url();

			$args = array(
				'status'  => 'error',
				'message' => rawurlencode( 'You do not have permission to view this page.' ),
			);
			wp_safe_redirect( add_query_arg( $args, $referer ) );
			exit;
		}

		if ( empty( $_POST['staff_name'] ) ) {
			$_SESSION['agpta_form_data']['errors']['staff_name'] = 'Staff name is required';
			$has_errors = 1;
		} else {
			$_SESSION['agpta_form_data']['post']['staff_name'] = sanitize_text_field( wp_unslash( $_POST['staff_name'] ) );
		}

		if ( empty( $_POST['staff_location'] ) ) {
			$_SESSION['agpta_form_data']['errors']['staff_location'] = 'Staff location is required';
			$has_errors = 1;
		} else {
			$_SESSION['agpta_form_data']['post']['staff_location'] = sanitize_text_field( wp_unslash( $_POST['staff_location'] ) );
		}

		if ( ! isset( $_POST['staff_list_url'] ) ) {
			$_SESSION['agpta_form_data']['errors']['staff_list_url'] = 'Wishlist URL is required';
			$has_errors = 1;
		} else {
			$_SESSION['agpta_form_data']['post']['staff_list_url'] = sanitize_text_field( wp_unslash( $_POST['staff_list_url'] ) );
		}

		if ( isset( $_POST['staff_list_description'] ) ) {
			$_SESSION['agpta_form_data']['post']['staff_list_description'] = sanitize_textarea_field( wp_unslash( $_POST['staff_list_description'] ) );
		}
        
        session_write_close();
        
		if ( 1 === $has_errors ) {
			$args = array(
				'page'    => $this->page_slug . '-new',
				'status'  => 'error',
				'message' => rawurlencode( 'Please fix errors.' ),
			);

			wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
			exit;
		}

		$data = array(
			'name'        => sanitize_text_field( wp_unslash( $_POST['staff_name'] ) ),
			'url'         => esc_url_raw( wp_unslash( $_POST['staff_list_url'] ) ),
			'location'    => sanitize_text_field( wp_unslash( $_POST['staff_location'] ) ),
			'description' => sanitize_textarea_field( wp_unslash( $_POST['staff_list_description'] ) ),
			'created_at'  => current_time( 'mysql' ),
		);

		$this->db->insert( $this->tablename, $data );

		$args = array(
			'page'    => $this->page_slug,
			'status'  => 'success',
			'message' => rawurlencode( 'Wishlist successfully added.' ),
		);
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
	}

	/**
	 * Get All Wishlist Data
	 *
	 * @return mixed
	 */
	public function agpta_wishlist_get_data(): array {
		return (array) $this->db->get_results( "SELECT * FROM {$this->tablename} ORDER BY created_at DESC" );
	}

	/**
	 * Get Wishlist Record by ID
	 *
	 * @param int $id record ID wishlist items.
	 *
	 * @return mixed
	 */
	public function agpta_wishlist_get_by_id( int $id ): mixed {
		$sql = "SELECT * FROM $this->tablename WHERE `id` = %d";

		return $this->db->get_row( $this->db->prepare( $sql, $id ) );
	}

	/**
	 * Process wishlist edit request.
	 *
	 * @return void
	 */
	public function agpta_wishlist_edit_handler(): void {

		if ( ! current_user_can( 'manage_options' ) ) {

			$args = array(
				'page'    => $this->page_slug . '-edit',
				'status'  => false,
				'message' => rawurlencode( 'Not Authorized.' ),
			);
			wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( empty( $_POST['id'] ) ) {
			$args = array(
				'page'    => $this->page_slug,
				'status'  => 'error',
				'message' => rawurlencode( 'Invalid Wishlist ID.' ),
			);
			wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
			exit;
		}

		$wishlist_id = sanitize_text_field( wp_unslash( $_POST['id'] ) );

		if ( ! isset( $_POST['agpta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['agpta_nonce'] ) ), 'agpta_admin_nonce' ) ) {

			$args = array(
				'page'    => $this->page_slug . '-edit',
				'id'      => $wishlist_id,
				'status'  => 'error',
				'message' => rawurlencode( 'Security check failed.' ),
			);
			wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( empty( $_POST['staff_name'] ) ) {
			$args = array(
				'page'    => $this->page_slug . '-edit',
				'id'      => $wishlist_id,
				'status'  => 'error',
				'field'   => 'staff_name',
				'message' => rawurlencode( 'Name field is required' ),
			);
			wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( empty( $_POST['staff_location'] ) ) {
			$args = array(
				'page'    => $this->page_slug . '-edit',
				'id'      => $wishlist_id,
				'status'  => 'error',
				'field'   => 'staff_location',
				'message' => rawurlencode( 'Location field is required' ),
			);
			wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( ! isset( $_POST['staff_list_url'] ) ) {
			$args = array(
				'page'    => $this->page_slug . '-edit',
				'id'      => $wishlist_id,
				'status'  => 'error',
				'message' => rawurlencode( 'Wishlist URL field is required' ),
			);
			wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
			exit;
		}

		$data = array(
			'name'        => sanitize_text_field( wp_unslash( $_POST['staff_name'] ) ),
			'location'    => sanitize_text_field( wp_unslash( $_POST['staff_location'] ) ),
			'url'         => esc_url_raw( wp_unslash( $_POST['staff_list_url'] ) ),
			'description' => ( isset( $_POST['staff_list_description'] ) ) ? sanitize_textarea_field( wp_unslash( $_POST['staff_list_description'] ) ) : '',
		);

		$updated = $this->db->update( $this->tablename, $data, array( 'id' => $wishlist_id ), array( '%s', '%s', '%s', '%s' ), array( '%d' ) );

		$agpta_admin_nonce = wp_create_nonce( 'agpta_admin_nonce' );
		if ( $updated ) {
			$args = array(
				'page'    => $this->page_slug . '-edit',
				'id'      => $wishlist_id,
				'status'  => 'success',
				'message' => rawurlencode( 'Wishlist has been updated.' ),
				'nonce'   => $agpta_admin_nonce,
			);
		} else {
			$args = array(
				'page'    => $this->page_slug . '-edit',
				'id'      => $wishlist_id,
				'status'  => 'error',
				'message' => rawurlencode( 'Failed to update wishlist. Please try again.' ),
				'nonce'   => $agpta_admin_nonce,
			);
		}

		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Delete Wishlist handler.
	 *
	 * @return void
	 */
	public function agpta_wishlist_delete_handler(): void {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'You do not have permission to view this page.' ), 401 );
		}

		if ( empty( $_POST['id'] ) ) {
			wp_send_json_error( array( 'message' => 'Wishlist ID is required.' ), 400 );
		}

		$wishlist_id = absint( $_POST['id'] );

		if ( ! isset( $_POST['agpta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['agpta_nonce'] ) ), 'agpta_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed. Please try again.' ), 400 );
		}

		$exists = $this->db->get_var( $this->db->prepare( "SELECT COUNT(*) FROM $this->tablename WHERE id = %d", $wishlist_id ) );

		if ( ! $exists ) {
			wp_send_json_error( array( 'message' => ' Wishlist not found.' ), 500 );
		}

		$deleted = $this->db->delete( $this->tablename, array( 'id' => $wishlist_id ), array( '%d' ) );

		if ( false === $deleted ) {
			wp_send_json_error( array( 'message' => 'Failed to delete the wishlist. Please try again' ), 400 );
		}

		wp_send_json_success( array( 'message' => 'Wishlist has been deleted.' ), 200 );
	}

	/**
	 * Display wishlist content via shortcode.
	 *
	 * @return string
	 */
	public function agpta_wishlist_display_shortcode(): string {

		$results = $this->db->get_results( "SELECT * FROM $this->tablename ORDER BY created_at DESC", ARRAY_A );

		if ( empty( $results ) ) {
			return '<p class="text-gray-600">No wishlists found.</p>';
		}

		ob_start();
		?>
		<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6">
			<?php foreach ( $results as $result ) : ?>
			<div class="bg-white shadow rounded-lg p-6 border border-gray-200">
				<h3 class="text-xl font-semibold text-gray-800 mb-2">
					<?php echo esc_html( $result['name'] ); ?>
				</h3>
				<p class="text-sm text-gray-500 mb-1">
					<strong>Location:</strong> <?php echo esc_html( $result['location'] ); ?>
				</p>
				<p class="text-sm text-gray-700 mb-3">
					<?php echo nl2br( esc_html( $result['description'] ) ); ?>
				</p>
				<?php if ( ! empty( $result['url'] ) ) : ?>
				<a href="<?php echo esc_url( $result['url'] ); ?>" target="_blank" rel="noopener noreferrer" class="inline-block mt-2 text-red-700 hover:underline">
					Visit Wishlist
				</a>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>
		<?php
		// return clean output buffer with ob_get_clean().
		return ob_get_clean();
	}
}
