<?php
/*
 * File: class-agpta-contact-form.php
 *
 * @author Gabriel Castillo <gabriel@gabrielcastillo.net>
 * Copyright (c) 2025.
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AGPTA_ContactForm {

	private string $plugin_name;

	private string $plugin_version;

	public string $textdomain = 'agpta';

	public string $tablename_form_entries;

	private object $db;

	public function __construct( $plugin_name, $plugin_version ) {
		global $wpdb;
		$this->db                     = $wpdb;
		$this->plugin_name            = $plugin_name;
		$this->plugin_version         = $plugin_version;
		$this->tablename_form_entries = $this->db->prefix . 'agpta_contact_form_entries';
	}

	public function contact_form_admin_page_init() {
		add_menu_page(
			__( 'Form Entries', $this->plugin_name ),
			__( 'Form Entries', $this->textdomain ),
			'manage_options',
			'contact-form-admin-page',
			array( $this, 'contact_form_admin_page_callback' ),
			'dashicons-admin-generic',
			100,
		);
	}

	public function load_admin_scripts() {

		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_style( 'jquery-ui-style', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', false, '1.12.1' );

		wp_localize_script(
			'jquery',
			'ajax_params',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'contact_form_admin_nonce' ),
			)
		);
	}

	public function contact_form_admin_page_callback() {
		echo '<h1>Contact Form Submissions</h1>';
		echo '<p>Manage contact form submissions</p>';

		$results = $this->get_contact_form_submissions();

		if ( ! $results ) {
			echo '<div class="error">No messages</div>';
		} else {
			?>
			<div class="wrap">
				<!-- Full-Screen Loader -->
				<div id="loader">
					<div class="loader"></div>
					<p style="z-index:9999; text-align:center; color:white;">Loading...</p>
				</div>

				<table id="form-table" class="table widefat">
					<thead>
						<tr style="background-color:#f0f0f0 ">
							<th>ID</th>
							<th>Name</th>
							<th>Email</th>
							<th>Status</th>
							<th>Created At</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $results as $result ) : ?>
						<tr>
							<td data-id="<?php echo esc_attr( $result->id ); ?>"><?php echo esc_html( $result->id ); ?></td>
							<td class="record_name"><?php echo esc_html( $result->name ); ?></td>
							<td class="record_email"><?php echo esc_html( $result->email ); ?></td>
							<td class="record_status"><?php echo esc_html( $result->status ); ?></td>
							<td class="record_created_at"><?php echo esc_html( $result->created_at ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<!-- Dialog Popup -->
				<div id="dialog" title="Message Details" style="display:none;">
					<p id="dialog-content"></p>
				</div>
			</div>
			<?php
		}
	}

	private function get_contact_form_submissions() {

		$sql = "SELECT * FROM {$this->tablename_form_entries} ORDER BY created_at DESC";

		$results = $this->db->get_results( $sql );

		return $this->edit_results( $results );
	}

	public function edit_results( $arrayOfObjects ) {
		foreach ( $arrayOfObjects as $obj ) {
			$obj->created_at = date( 'M d, Y h:i A', strtotime( $obj->created_at ) );
		}
		return $arrayOfObjects;
	}

	public function contact_form_display_shortcode() {
		ob_start();
		echo '<div id="response-message">';
		if ( isset( $_GET['success'] ) ) {
			if ( $_GET['success'] == 1 ) {
				echo '<p style="color: green;">Message sent successfully!</p>';
			} else {
				echo '<p style="color: red;">There was a problem sending your message. Please try again.</p>';
			}
		}
		echo '</div>';
		?>

		<form id="agpta-contact-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST" class="my-10">
			<input type="hidden" name="action" value="agpta_contact_form" />
			<input type="hidden" name="agpta_nonce" value="<?php echo esc_attr( wp_create_nonce( 'agpta_nonce' ) ); ?>" />
			<div class="mb-8">
				<label for="name" class="w-full block">Name:</label>
				<input  class="w-full sm:w-1/2" type="text" name="form_name" id="name" required><br>
			</div>

			<div class="mb-8">
				<label for="email" class="w-full block">Email:</label>
				<input  class="w-full sm:w-1/2" type="email" name="form_email" id="email" required><br>
			</div>

			<div class="mb-8">
				<label for="message">Message:</label>
				<textarea  class="w-full" rows="10" name="form_message" id="message" required></textarea><br>
			</div>
			<div class="mb-0">
				<input type="submit" name="submit" value="Submit" class="rounded-md bg-red-700 px-3.5 py-2.5 text-xs font-semibold text-white shadow-sm ring-1 ring-inset ring-red-500 hover:bg-red-500">
			</div>
		</form>

		<?php
		return ob_get_clean();
	}

	public function agpta_contact_form_submission() {

			$referer = wp_get_referer();

		if ( ! isset( $_POST['agpta_nonce'] ) || ! wp_verify_nonce( $_POST['agpta_nonce'], 'agpta_nonce' ) ) {
			$redirect_url = add_query_arg(
				array(
					'success' => 0,
					'nonce'   => 0,
				),
				$referer
			) . '#response-message';
			wp_safe_redirect( $redirect_url );
			exit;
		}

		if ( ! isset( $_POST['form_name'] ) ) {
			$redirect_url = add_query_arg(
				array(
					'success' => 0,
					'name'    => 0,
				),
				$referer
			) . '#response-message';
			wp_safe_redirect( home_url( '/contact-us/?success=0&name=0#response-message' ) );
			exit;
		}

		if ( ! isset( $_POST['form_email'] ) ) {
			$redirect_url = add_query_arg(
				array(
					'success' => 0,
					'email'   => 0,
				),
				$referer
			) . '#response-message';
			wp_safe_redirect( home_url( '/contact-us/?success=0&email=0#response-message' ) );
			exit;
		}

		if ( ! isset( $_POST['form_message'] ) ) {
			$redirect_url = add_query_arg(
				array(
					'success' => 0,
					'message' => 0,
				),
				$referer
			) . '#response-message';
			wp_safe_redirect( home_url( '/contact-us/?success=0&message=0#response-message' ) );
			exit;
		}

			$name    = sanitize_text_field( $_POST['form_name'] );
			$email   = sanitize_email( $_POST['form_email'] );
			$message = sanitize_textarea_field( $_POST['form_message'] );

			$this->db->insert(
				$this->tablename_form_entries,
				array(
					'name'       => $name,
					'email'      => $email,
					'message'    => $message,
					'status'     => 'unread',
					'created_at' => current_time( 'mysql' ),
				)
			);

			$admin_email = get_option( 'admin_email' );
			$subject     = 'New Form Submission';
			$body        = "You have a new submission from {$name} ({$email}):\n\n {$message}";
			$headers     = array(
                'Content-Type: text/plain; charset=UTF-8',
                'From: AGPTA Site <no-replay@' . $_SERVER['HTTP_HOST'] . '>',
                'Reply-To: ' . $name . "<{$email}>"
            );

			$mailer = wp_mail( $admin_email, $subject, $body, $headers );

			$redirect_url = add_query_arg( 'success', $mailer ? 1 : 0, $referer ) . '#response-message';
			wp_safe_redirect( $redirect_url );
			exit;
	}

	public function get_form_message_ajax_callback() {

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'contact_form_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
		}

		if ( isset( $_POST['formId'] ) && is_numeric( $_POST['formId'] ) ) {
			$formId = absint( $_POST['formId'] );

			$sql = $this->db->prepare( "SELECT * FROM {$this->tablename_form_entries} WHERE id = %d", $formId );

			$result = $this->db->get_row( $sql );

			$email = array(
				'name'    => $result->name,
				'email'   => $result->email,
                'status'  => 'read',
				'message' => nl2br( $result->message ),
			);

			if ( $result ) {
                $this->db->query($this->db->prepare( "UPDATE {$this->tablename_form_entries} SET status = 'read' WHERE id = %d", $formId ));

				wp_send_json_success( array( 'data' => $email ) );
			} else {
				wp_send_json_error( array( 'message' => 'Message not found.' ) );
			}
		} else {
			wp_send_json_error( array( 'message' => 'Invalid form ID' ) );
		}

		exit;
	}
}
