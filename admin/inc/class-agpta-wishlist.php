<?php
/*
 * File: class-agpta-wishlist.php
 *
 * @author Gabriel Castillo <gabriel@gabrielcastillo.net>
 * Copyright (c) 2025.
 */


class AGPTA_Wishlist {

	private string $plugin_name;

	private string $plugin_version;

	private object $db;

	private string $page_slug;

    private string $tablename;

	public function __construct( $plugin_name, $plugin_version, $wpdb ) {
		$this->plugin_name    = $plugin_name;
		$this->plugin_version = $plugin_version;
		$this->db             = $wpdb;
		$this->page_slug      = 'agpta-wishlists';
        $this->tablename = $this->db->prefix . 'agpta_wishlists';
	}

	public function agpta_wishlist_admin_page_init() {
		add_menu_page(
			__( 'Wishlists', $this->plugin_name ),
			__( 'Wishlists', $this->plugin_name ),
			'manage_options',
			$this->page_slug,
			array( $this, 'agpta_wishlist_admin_page_callback' ),
			'dashicons-admin-generic',
			20
		);

		add_submenu_page(
			$this->page_slug,
			__( 'Add Wishlist', $this->plugin_name ),
			__( 'Add Wishlist', $this->plugin_name ),
			'manage_options',
			'agpta-wishlists-new',
			array( $this, 'agpta_wishlist_add_new_admin_page_callback' ),
			20
		);
	}

	public function agpta_wishlist_admin_page_callback() {
		echo '<h1>Staff Wishlists</h1>';
		echo '<p>Manage staff wishlists</p>';
		echo ( isset($_GET['message'] ) ) ? $_GET['message'] : '';
		unset($_SESSION['agpta_form_data']);
		?>
		<div class="wrap">
			<div id="loader">

			</div>
		</div>
		<?php
	}

	/**
	 * Display Add Wishlist page
	 *
	 * @return void
	 */
	public function agpta_wishlist_add_new_admin_page_callback() {
		echo '<h1>New Staff Wishlists</h1>';
		echo '<p>Add new staff wishlist</p>';
		echo ( isset($_GET['message'] ) ) ? $_GET['message'] : '';

		?>
		<div class="wrap">
			<form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="POST">
				<?php wp_nonce_field( 'agpta_form_nonce', 'agpta_form_nonce' ); ?>
				<input type="hidden" name="action" value="agpta_wishlist_add_new" />

				<table class="form-table">
					<tr>
						<th><label for="staff_name">Staff Name</label></th>
						<td>
							<input id="staff_name" class="regular-text" name="staff_name" type="text"  value="<?php echo esc_attr( (isset($_SESSION['agpta_form_data']['post']['staff_name'])) ? $_SESSION['agpta_form_data']['post']['staff_name'] : '' ); ?>"/>
							<?php echo ( isset($_SESSION['agpta_form_data']['errors']['staff_name'] ) ) ? $_SESSION['agpta_form_data']['errors']['staff_name'] : ''; ?>
						</td>
					</tr>
					<tr>
						<th><label for="staff_location">Staff Location</label></th>
						<td>
							<input id="staff_location" class="regular-text" name="staff_location" type="text" placeholder="Room 5 or Office or Library" value="<?php echo esc_attr( (isset($_SESSION['agpta_form_data']['post']['staff_location'])) ? $_SESSION['agpta_form_data']['post']['staff_location'] : '' ); ?>" />
							<?php echo ( isset($_SESSION['agpta_form_data']['errors']['staff_location'] ) ) ? $_SESSION['agpta_form_data']['errors']['staff_location'] : ''; ?>
						</td>
					</tr>
					<tr>
						<th><label for="staff_list_url">Staff Wishlist URL</label></th>
						<td>
							<input id="staff_list_url" class="regular-text" name="staff_list_url" type="text" placeholder="URL to amazon, or any URL that has a list of wants." value="<?php echo esc_attr( (isset($_SESSION['agpta_form_data']['post']['staff_list_url'])) ? $_SESSION['agpta_form_data']['post']['staff_list_url'] : '' ); ?>"/>
							<?php echo ( isset($_SESSION['agpta_form_data']['errors']['staff_list_url'] ) ) ? $_SESSION['agpta_form_data']['errors']['staff_list_url'] : ''; ?>
						</td>
					</tr>
					<tr>
						<th><label for="staff_list_description">Staff Wishlist Description</label></th>
						<td>
							<textarea id="staff_list_description" class="regular-text" name="staff_list_description" rows="8"><?php echo esc_html( (isset($_SESSION['agpta_form_data']['post']['staff_list_description'])) ? $_SESSION['agpta_form_data']['post']['staff_list_description'] : '' ); ?></textarea>
						</td>
					</tr>
				</table>
				<?php submit_button( 'Submit Form' ); ?>
			</form>
		</div>
		<?php
		unset($_SESSION['agpta_form_data']);
	}

	/**
	 * Process the form submission for add new wishlists
	 *
	 * @return void
	 */
	public function agpta_wishlist_add_new_handler() {
		// Check if user is allowed to be here
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized user' );
		}

		$has_errors = 0;

		if ( ! isset( $_POST['agpta_form_nonce'] ) || ! wp_verify_nonce( $_POST['agpta_form_nonce'], 'agpta_form_nonce' ) ) {
			wp_die( 'Nonce verification failed.' );
		}

		if ( ! isset( $_POST['staff_name'] ) || empty($_POST['staff_name']) ) {
			$_SESSION['agpta_form_data']['errors']['staff_name'] = 'Staff name is required';
			$has_errors                            = 1;
		} else {
			$_SESSION['agpta_form_data']['post']['staff_name'] = sanitize_text_field($_POST['staff_name']);
		}

		if ( ! isset( $_POST['staff_location'] ) || empty($_POST['staff_location']) ) {
			$_SESSION['agpta_form_data']['errors']['staff_location'] = 'Staff location is required';
			$has_errors                             = 1;
		} else {
			$_SESSION['agpta_form_data']['post']['staff_location'] = sanitize_text_field($_POST['staff_location']);
		}

		if ( ! isset( $_POST['staff_list_url'] ) || empty($_POST['staff_list_url']) ) {
			$_SESSION['agpta_form_data']['errors']['staff_list_url'] = 'Wishlist URL is required';
			$has_errors                            = 1;
		} else {
			$_SESSION['agpta_form_data']['post']['staff_list_url'] = sanitize_text_field($_POST['staff_list_url']);
		}

		if ( isset( $_POST['staff_list_description'] ) ) {
			$_SESSION['agpta_form_data']['post']['staff_list_description'] = sanitize_textarea_field($_POST['staff_list_description']);
		}

		if ( $has_errors === 1 ) {
			$args = array(
				'page'    => $this->page_slug . '-new',
				'status'  => 'error',
				'message' => rawurlencode( 'Please fix errors.' ),
			);

			wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
			exit;
		}


		$data = array(
			'name'      => sanitize_text_field( $_POST['staff_name'] ),
			'url'       => esc_url_raw( $_POST['staff_list_url'] ),
            'location'  => sanitize_text_field( $_POST['staff_location'] ),
            'description' => sanitize_textarea_field( $_POST['staff_list_description'] ),
		);

        $this->db->insert( $this->tablename, $data );

        $args = array(
                'page'      => $this->page_slug,
                'status' => 'success',
                'message' => rawurlencode('Wishlist successfully added.'),
        );
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
	}
}
