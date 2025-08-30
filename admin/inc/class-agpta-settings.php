<?php
/*
 * File: class-agpta-settings.php
 *
 * @author Gabriel Castillo <gabriel@gabrielcastillo.net>
 * Copyright (c) 2025.
 */


class AGPTA_Settings {

	public string $plugin_name;

	public string $page_slug;

	public array $options;

	public function __construct( $plugin_name ) {
		$this->plugin_name = $plugin_name;
		$this->page_slug = $plugin_name . '-general-settings';
		$this->options = get_option( 'agpta_settings' );
	}

	public function agpta_admin_menu_settings_page_init() {
		add_menu_page( __( 'AGPTA Settings', $this->plugin_name ), __( 'AGPTA Settings', $this->plugin_name ), 'manage_options', $this->page_slug, array($this, 'agpta_admin_menu_page'), 'dashicons-admin-generic', 80 );
	}


	public function agpta_admin_menu_page() : void {
		?>
        <div class="wrap">
            <h1>AGPTA General Settings</h1>

            <h2 class="nav-tab-wrapper">
                <a href="#tab-social" class="nav-tab nav-tab-active">Social Media</a>
                <a href="#tab-stripe" class="nav-tab">Stripe</a>
            </h2>

            <form method="POST" action="options.php">
				<?php
				settings_fields('agpta_settings_group');
				do_settings_sections($this->page_slug);
				submit_button();
				?>
            </form>
        </div>

        <style>
            .agpta-tab-content { display: none; }
            .agpta-tab-content.active { display: block; }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const tabs = document.querySelectorAll('.nav-tab');
                const sections = document.querySelectorAll('.agpta-tab-content');

                tabs.forEach(tab => {
                    tab.addEventListener('click', function (e) {
                        e.preventDefault();

                        tabs.forEach(t => t.classList.remove('nav-tab-active'));
                        this.classList.add('nav-tab-active');

                        const target = this.getAttribute('href');
                        sections.forEach(s => s.classList.remove('active'));
                        document.querySelector(target).classList.add('active');
                    });
                });

                // Set default visible tab
                document.querySelector('#tab-social').classList.add('active');
            });
        </script>
		<?php
	}



	public function agpta_settings_init() {
		register_setting( 'agpta_settings_group', 'agpta_settings' );

		add_settings_section(
			'agpta_settings_social_media_section',
			'',
			array( $this, 'agpta_settings_social_media_section_callback'),
			$this->page_slug,
		);

		add_settings_section(
			'agpta_settings_stripe_section',
			'',
			array( $this, 'agpta_settings_stripe_section_callback'),
			$this->page_slug,
		);

		$this->agpta_stripe_input_fields();
		$this->agpta_social_media_input_fields();


	}

	// Social Media Section

	public function agpta_settings_social_media_section_callback() {
		echo '<div id="tab-social" class="agpta-tab-content">';
		$value = $this->options;

		$facebook_url =  ( isset( $this->options['facebook_url'] ) ) ? esc_url( $this->options['facebook_url'] ) : '';
		$youtube_url = ( isset( $this->options['youtube_url'] ) ) ? esc_url( $this->options['youtube_url'] ) : '';
		$instagram_url = ( isset( $this->options['instagram_url'] ) ) ? esc_url( $this->options['instagram_url'] ) : '';
		$twitter_url = ( isset( $this->options['twitter_url'] ) ) ? esc_url( $this->options['twitter_url'] ) : '';

		$html = '';

		$html .= '<table>';
		$html .= '<tbody>';
		$html .= '<tr>
							<td><label>Facebook</label></td>
							<td><input type="text" name="agpta_settings[facebook_url]" value="'. $facebook_url .'" /></td>
					  </tr>';
		$html .= '<tr>
							<td><label>Instagram</label></td>
							<td><input type="text" name="agpta_settings[instagram_url]" value="'. $instagram_url .'" /></td>
					  </tr>';
		$html .= '<tr>
							<td><label>Youtube</label></td>
							<td><input type="text" name="agpta_settings[youtube_url]" value="'. $youtube_url .'" /></td>
					  </tr>';
		$html .= '<tr>
							<td><label>Twitter / X</label></td>
							<td><input type="text" name="agpta_settings[twitter_url]" value="'. $twitter_url .'" /></td>
					  </tr>';
		$html .= '</tbody>';
		$html .= '</table>';
		echo $html;
        echo '</div>';
	}

	public function agpta_social_media_input_fields() {

		add_settings_field(
			'agpta_settings_social_media_url',
			'',
			function() {
				return;
			},
			$this->page_slug,
			'agpta_settings_social_media_section',
		);
	}


	// Stripe Section

	public function agpta_settings_stripe_section_callback() {
		echo '<div id="tab-stripe" class="agpta-tab-content">';
		$live_public_key = ( isset( $this->options['live_public_key'] ) ) ? esc_html( $this->options['live_public_key'] ) : '';
		$live_secret_key = ( isset( $this->options['live_secret_key'] ) ) ? esc_html( $this->options['live_secret_key'] ) : '';

		$test_public_key = ( isset( $this->options['test_public_key'] ) ) ? esc_html( $this->options['test_public_key'] ) : '';
		$test_secret_key = ( isset( $this->options['test_secret_key'] ) ) ? esc_html( $this->options['test_secret_key'] ) : '';

        $webhook_secret  = ( isset( $this->options['webhook_secret'] ) ) ? esc_html( $this->options['webhook_secret'] ) : '';

		$enable_test_mode = ( isset( $this->options['enable_stripe_test'] ) ) ? esc_html( $this->options['enable_stripe_test'] ) : '';

		$is_checked = ( (int) $enable_test_mode === 1 ) ? 'checked="checked"' : '';


		$html = '';

		$html .= '<table class="table wide">';
		$html .= '<tbody>';
		$html .= '<tr>
                            <td><label>Live Public Key</label></td>
                            <td><input type="text" name="agpta_settings[live_public_key]" value="'. $live_public_key .'" /></td>
                          </tr>';
		$html .= '<tr>
                            <td><label>Live Secret Key</label></td>
                            <td><input type="text" name="agpta_settings[live_secret_key]" value="'. $live_secret_key .'" /></td>
                          </tr>';
		$html .= '<tr>
                            <td><label>Enable Test Mode</label></td>
                            <td><input type="checkbox" name="agpta_settings[enable_stripe_test]" value="1" '.$is_checked.'/></td>
                          </tr>';
		$html .= '<tr>
                            <td><label>Test Public Key</label></td>
                            <td><input type="text" name="agpta_settings[test_public_key]" value="'. $test_public_key .'" /></td>
                          </tr>';
		$html .= '<tr>
                            <td><label>Test Secret Key</label></td>
                            <td><input type="text" name="agpta_settings[test_secret_key]" value="'. $test_secret_key .'" /></td>
                          </tr>';
		$html .= '<tr>
                            <td><label>Webhook Secret Key</label></td>
                            <td><input type="text" name="agpta_settings[webhook_secret]" value="'. $webhook_secret .'" /></td>
                          </tr>';
		$html .= '</tbody>';
		$html .= '</table>';
		echo $html;
        echo '</div>';
	}

	public function agpta_stripe_input_fields() {
		add_settings_field(
			'agpta_settings_stripe_settings',
			'',
			function() {
				return;
			},
			$this->page_slug,
			'agpta_settings_stripe_section',
		);
	}
}
