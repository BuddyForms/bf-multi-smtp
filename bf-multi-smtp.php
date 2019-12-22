<?php
/**
 *
 * @since             1.0.0
 * @package           BFMultiSMTP
 *
 * @wordpress-plugin
 * Plugin Name:       BuddyForms Multi SMTP
 * Description:       This plugin integrate BuddyForms with WP Simple SMTP to setup different senders and credentials from each forms.
 * Version:           1.0.0
 * Author:            gfirem
 * License:           Apache License 2.0
 * License URI:       http://www.apache.org/licenses/
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'BFMultiSMTP' ) ) {

	class BFMultiSMTP {

		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		protected static $instance = null;

		public static $slug = 'bfmultismtp';
		public static $version = '1.0.0';
		public static $view;
		public static $assets;

		/**
		 * Initialize the plugin.
		 */
		private function __construct() {
			self::$assets = plugin_dir_url( __FILE__ ) . 'assets/';
			self::$view   = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;

			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}

			// Search for old plugin name.
			if ( ! is_plugin_active( 'wp-mail-smtp/wp_mail_smtp.php' ) || ! is_plugin_active( 'buddyforms-premium/BuddyForms.php' ) ) {
				add_action( 'admin_notices', array( $this, 'requirement_notice' ) );
			} else {
				add_action( 'buddyforms_form_setup_nav_li_last', array( $this, 'add_nav' ) );
				add_action( 'buddyforms_form_setup_tab_pane_last', array( $this, 'add_setup_tab' ) );
				add_filter( 'wp_mail_smtp_custom_options', array( $this, 'smtp_custom_options' ), 999, 1 );
			}
		}

		public function smtp_custom_options( $phpmailer ) {
			global $buddyforms, $form_slug;
			if ( ! empty( $buddyforms ) && ! empty( $form_slug ) && ! empty( $_POST['post_id'] ) && ! empty( $_POST['form_slug'] ) && $_POST['form_slug'] == $form_slug && ! empty( $buddyforms[ $form_slug ] ) && ! empty( $phpmailer ) ) {
				$current_form             = $buddyforms[ $form_slug ];
				$multi_smtp_enabled_value = isset( $current_form['multi_smtp_enabled'] ) ? $current_form['multi_smtp_enabled'] : 'no';
				if ( $multi_smtp_enabled_value === 'yes' ) {
					$multi_smtp_from_value     = isset( $current_form['multi_smtp_from'] ) ? $current_form['multi_smtp_from'] : '';
					$multi_smtp_user_value     = isset( $current_form['multi_smtp_user'] ) ? $current_form['multi_smtp_user'] : '';
					$multi_smtp_password_value = isset( $current_form['multi_smtp_pass'] ) ? $current_form['multi_smtp_pass'] : '';
					if ( ! empty( $multi_smtp_from_value ) ) {
						$phpmailer->From = $multi_smtp_from_value;
					}
					if ( ! empty( $multi_smtp_user_value ) && ! empty( $multi_smtp_password_value ) ) {
						$options = new WPMailSMTP\Options();
						$mailer  = $options->get( 'mail', 'mailer' );
						if ( 'smtp' === $mailer ) {
							// If we're using smtp auth, set the username & password.
							if ( $options->get( $mailer, 'auth' ) ) {
								$phpmailer->SMTPAuth = true;
								$phpmailer->Username = $multi_smtp_user_value;
								$phpmailer->Password = $multi_smtp_password_value;
							}
						}
					}
				}
			}

			return $phpmailer;
		}

		public function requirement_notice() {
			echo '<div class="notice notice-warning"><p><strong>BF Muti SMTP</strong> needs <strong>WP Simple SMTP</strong> and <strong>BuddyForms Pro</strong>, please check the dependencies!</p></div>';
		}

		public function add_nav() {
			echo '<li class="simple_smtp_nav"><a class="simple_smtp" href="#simple_smtp" data-toggle="tab">Multi SMTP</a></li>';
		}

		public function add_setup_tab() {
			global $buddyform;

			$form_setup = array();

			if ( ! $buddyform ) {
				$buddyform = get_post_meta( get_the_ID(), '_buddyforms_options', true );
			}

			ob_start();
			?>
            <div class="tab-pane fade in" id="simple_smtp">
            <div class="buddyforms_accordion_simple_smtp">
                <h3>Override WP Simple SMTP options</h3><br>
				<?php
				$multi_smtp_enabled_value  = isset( $buddyform['multi_smtp_enabled'] ) ? $buddyform['multi_smtp_enabled'] : 'no';
				$form_setup[]              = new Element_Radio( '<b>Enabled SMTP Override</b>', "buddyforms_options[multi_smtp_enabled]",
					array(
						'yes' => 'YES',
						'no'  => 'NO'
					), array(
						'value' => $multi_smtp_enabled_value,
					)
				);
				$multi_smtp_from_value     = isset( $buddyform['multi_smtp_from'] ) ? $buddyform['multi_smtp_from'] : '';
				$form_setup[]              = new Element_Textbox( '<b>Sender</b>', "buddyforms_options[multi_smtp_from]", array(
					'value'     => $multi_smtp_from_value,
					'shortDesc' => 'Set the SENDER to override the global setup of the WP Simple SMTP.',
				) );
				$multi_smtp_user_value     = isset( $buddyform['multi_smtp_user'] ) ? $buddyform['multi_smtp_user'] : '';
				$form_setup[]              = new Element_Textbox( '<b>User</b>', "buddyforms_options[multi_smtp_user]", array(
					'value'     => $multi_smtp_user_value,
					'shortDesc' => 'Set the USER to override the global setup of the WP Simple SMTP.',
				) );
				$multi_smtp_password_value = isset( $buddyform['multi_smtp_pass'] ) ? $buddyform['multi_smtp_pass'] : '';
				$form_setup[]              = new Element_Textbox( '<b>Password</b>', "buddyforms_options[multi_smtp_pass]", array(
					'type'      => 'password',
					'style'     => 'width:100%;',
					'value'     => $multi_smtp_password_value,
					'shortDesc' => 'Set the PASSWORD to override the global setup of the WP Simple SMTP.',
				) );
				buddyforms_display_field_group_table( $form_setup );
				?>
            </div>
            </div><?php
			$content = ob_get_clean();

			echo $content;
		}

		/**
		 * Get plugin version
		 *
		 * @return string
		 */
		static function getVersion() {
			return self::$version;
		}

		/**
		 * Get plugins slug
		 *
		 * @return string
		 */
		static function getSlug() {
			return self::$slug;
		}

		/**
		 * Return an instance of this class.
		 *
		 * @return object A single instance of this class.
		 */
		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}
	}

	add_action( 'plugins_loaded', array( 'BFMultiSMTP', 'get_instance' ) );
}
