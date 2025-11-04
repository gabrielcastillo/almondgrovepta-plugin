<?php
/*
 * File: agpta-cpt.php
 *
 * @author Gabriel Castillo <gabriel@gabrielcastillo.net>
 * Copyright (c) 2025.
 */


class AGPTA_Board_Members {

	/**
	 * Plugin Name
	 *
	 * @var string
	 */
	protected string $plugin_name;

	public function __construct( $plugin_name ) {
		$this->plugin_name = $plugin_name;
	}

	/**
	 * Initialize
	 *
	 * @return void
	 */
	public function init(): void {
		$this->agpta_team_cpt();
		add_action( 'pre_get_posts', array( $this, 'team_custom_orderby' ) );
	}

	/**
	 * Board Member Custom Post Type
	 *
	 * @post_type team
	 * @return void
	 */
	private function agpta_team_cpt(): void {
		$labels = array(
			'name'               => _x( 'Board Members', 'agpta' ),
			'singular_name'      => _x( 'Board Member', 'agpta' ),
			'menu_name'          => _x( 'Board  Members', 'agpta' ),
			'name_admin_bar'     => _x( 'Board Member', 'agpta' ),
			'add_new'            => _x( 'Add Board Member', 'agpta' ),
			'add_new_item'       => __( 'Add New Member', 'agpta' ),
			'new_item'           => __( 'New Board Member', 'agpta' ),
			'edit_item'          => __( 'Edit Board Member', 'agpta' ),
			'view_item'          => __( 'View Member', 'agpta' ),
			'all_items'          => __( 'All Members', 'agpta' ),
			'search_items'       => __( 'Search Board Members', 'agpta' ),
			'parent_item_colon'  => __( 'Parent Members', 'agpta' ),
			'not_found'          => __( 'No Members Found', 'agpta' ),
			'not_found_in_trash' => __( 'No Board Members Found', 'agpta' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'board' ),
			'capability_type'    => 'page',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 5,
			'supports'           => array( 'author', 'thumbnail' ),
			'show_in_rest'       => true,
		);

		register_post_type( 'team', $args );
	}


	// Team Custom Post Type
	public function set_custom_team_columns( $columns ) {

		unset( $columns['date'] );
		unset( $columns['author'] );
		unset( $columns['title'] );

		$columns['member_name']   = __( 'Board Member Name', '' );
		$columns['member_email']  = __( 'Board Member Email' );
		$columns['member_role']   = __( 'Board Role', '' );
		$columns['member_status'] = __( 'Status', '' );

		return $columns;
	}

	/**
	 * Custom Team Column
	 *
	 * @param $column
	 * @param $post_id
	 *
	 * @return void
	 */
	public function custom_team_column( $column, $post_id ): void {

		switch ( $column ) {
			case 'member_name':
				$featured_image = get_the_post_thumbnail_url( $post_id );
				$member_name    = get_post_meta( $post_id, '_member_name', true );

				echo '<div class="board-member-cpt-list-table-name-container">';

				if ( $featured_image ) {
					echo '<img src="' . esc_url( $featured_image ) . '" alt="board member image" width="50" height="50" />';
				}

				echo '<p>' . esc_html( $member_name ? $member_name : '-' ) . '</p>';
				echo '</div>';
				break;

			case 'member_email':
				$member_email = get_post_meta( $post_id, '_member_email', true );
				echo esc_html( $member_email ? $member_email : '-' );
				break;

			case 'member_role':
				$member_role = get_post_meta( $post_id, '_member_role', true );
				echo esc_html( $member_role ? $member_role : '-' );
				break;

			case 'member_status':
				$member_status = get_post_meta( $post_id, '_member_status', true );
				echo esc_html( $member_status );
				break;

			default:
				return;
				break;
		}
	}

	/**
	 * Set Custom Team Sortable Columns
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function set_custom_team_sortable_columns( $columns ) {
		$columns['member_name'] = 'member_name';
		$columns['member_role'] = 'member_role';

		return $columns;
	}

	/**
	 * Team Custom OrderBy Query
	 *
	 * @param $query
	 *
	 * @return void
	 */
	public function team_custom_orderby( $query ): void {
		// Apply default sorting by member name in the admin list view.
		if ( is_admin() && $query->is_main_query() && $query->get( 'post_type' ) === 'team' && ! $query->get( 'orderby' ) ) {
			$query->set( 'meta_key', '_member_name' );
			$query->set( 'orderby', 'meta_value' );
			$query->set( 'order', 'ASC' );
		}

		// Handle manual sorting via column headers.
		$orderby = $query->get( 'orderby' );
		if ( 'member_name' === $orderby ) {
			$query->set( 'meta_key', '_member_name' );
			$query->set( 'orderby', 'meta_value' );
		} elseif ( 'member_role' === $orderby ) {
			$query->set( 'meta_key', '_member_role' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}


	/**
	 * Remove Quick Edit List
	 *
	 * @param $actions passed actions.
	 * @param $post post.
	 *
	 * @return mixed
	 */
	public function remove_quick_edit_list( $actions, $post ) {
		if ( $post->post_type === 'team' ) {
			if ( isset( $actions['inline hide-if-no-js'] ) ) {
				unset( $actions['inline hide-if-no-js'] );
			}
			if ( isset( $actions['view'] ) ) {
				unset( $actions['view'] );
			}
		}

		return $actions;
	}

	/**
	 * Admin Notice Save Post
	 *
	 * @param $messages
	 *
	 * @return mixed
	 */
	public function admin_notice_save_post( $messages ) {
		global $post;

		$post_type = 'team';

		$messages[ $post_type ] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Board member updated successfully.', 'agpta' ),
			2  => __( 'Custom field updated.', 'agpta' ),
			3  => __( 'Custom field deleted.', 'agpta' ),
			4  => __( 'Board member updated.', 'agpta' ),
			5  => isset( $_GET['revision'] ) ? sprintf(
				__(
					'Board member restored to revision from %s.',
					$this->plugin_name
				),
				wp_post_revision_title( (int) $_GET['revision'], false )
			) : false,
			6  => __( 'Board member published.', 'agpta' ),
			7  => __( 'Board member saved.', 'agpta' ),
			8  => __( 'Board member submitted for review.', 'agpta' ),
			9  => sprintf(
				__( 'Board member scheduled for: <strong>%1$s</strong>.', 'agpta' ),
				date_i18n( __( 'M j, Y @ G:i', 'agpta' ), strtotime( $post->post_date ) )
			),
			10 => __( 'Board member draft updated.', 'agpta' ),
		);

		return $messages;
	}

	/**
	 * Admin search team members
	 *
	 * @param $query
	 *
	 * @return void
	 */
	public function agpta_team_cpt_search( $query ): void {

		if ( is_admin() && $query->is_main_query() && $query->get( 'post_type' ) === 'team' ) {

			if ( ! empty( $_GET['s'] ) ) {

				$search_term = sanitize_text_field( wp_unslash( $_GET['s'] ) );
				$meta_query  = array(
					'relation' => 'OR',
					array(
						'key'     => '_member_name',
						'value'   => $search_term,
						'compare' => 'LIKE',
					),
					array(
						'key'     => '_member_email',
						'value'   => $search_term,
						'compare' => 'LIKE',
					),
				);

				$query->set( 'meta_query', $meta_query );

				add_filter( 'post_search', '__return_empty_string' );
			}
		}
	}

	/**
	 * Register meta boxes
	 *
	 * @return void
	 */
	public function agpta_team_meta_box_init(): void {
		add_meta_box(
			'team_details_meta_box',
			'Board Member Details',
			array( $this, 'team_meta_box_html' ),
			'team',
			'normal',
			'high',
		);
	}

	/**
	 * Display team meta box
	 *
	 * @param $post
	 *
	 * @return void
	 */
	public function team_meta_box_html( $post ): void {
		wp_nonce_field( 'team_meta_box_nonce_action', 'team_meta_box_nonce' );
		$member_name   = get_post_meta( $post->ID, '_member_name', true );
		$member_email  = get_post_meta( $post->ID, '_member_email', true );
		$member_role   = get_post_meta( $post->ID, '_member_role', true );
		$member_status = get_post_meta( $post->ID, '_member_status', true );

		?>
		<div id="board-member-cpt-meta-box">
			<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'agpta_admin_nonce' ) ); ?>" />
			<ul>
				<li>
					<input type="text" id="member_name_field" name="member_name_field"
							value="<?php echo esc_attr( $member_name ); ?>" size="25"
							class="field-style field-split align-left" placeholder="Member Name" required/>
					<input type="text" id="member_email_field" name="member_email_field"
							value="<?php echo esc_attr( $member_email ); ?>" size="25"
							class="field-style field-split align-left" placeholder="Member Email"/>
				</li>
				<li>
					<input type="text" id="member_role_field" name="member_role_field"
							value="<?php echo esc_attr( $member_role ); ?>" size="25"
							class="field-style field-split align-left" placeholder="Member Role" required/>

					<select id="member_status_field" name="member_status_field"
							class="field-style field-split align-left" required>
						<option value="">-- Select Status --</option>
						<option value="active" <?php echo ( 'active' === $member_status ) ? 'selected="selected"' : ''; ?>>
							Active
						</option>
						<option value="inactive" <?php echo ( 'inactive' === $member_status ) ? 'selected="selected"' : ''; ?>>
							In Active
						</option>
					</select>
				</li>
			</ul>
		</div>
		<?php
	}

	/**
	 * Save team meta box data
	 *
	 * @param $post_id
	 *
	 * @return void
	 */
	public function save_team_meta_box_data( $post_id ): void {
		if ( ! isset( $_POST['team_meta_box_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['team_meta_box_nonce'] ) ), 'team_meta_box_nonce_action' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'member_name_field'   => '_member_name',
			'member_email_field'  => '_member_email',
			'member_role_field'   => '_member_role',
			'member_status_field' => '_member_status',
		);

		foreach ( $fields as $field => $meta_key ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
			}
		}
	}
}