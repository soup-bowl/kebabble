<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@soupbowl.io>
 * @license MIT
 */

namespace Kebabble\Config;

use Kebabble\Library\Slack;

/**
 * Displays the configuration options in the WordPress admin options.
 */
class Settings {
	/**
	 * Slack communication access.
	 *
	 * @var Slack
	 */
	protected $slack;

	/**
	 * Constructor.
	 *
	 * @param Slack $slack Slack communication access.
	 */
	public function __construct( Slack $slack ) {
		$this->slack = $slack;
	}

	/**
	 * Registers WordPress hooks.
	 *
	 * @return void
	 */
	public function hook_settings() {
		add_action( 'admin_menu', [ &$this, 'page' ] );
		add_action( 'admin_init', [ &$this, 'settings' ] );
	}

	/**
	 * Tells WordPress about Kebabble settngs, and adds them to as a submenu.
	 *
	 * @return void Prints on page.
	 */
	public function page():void {
		add_options_page(
			'Kebabble',
			'Kebabble',
			'manage_options',
			'kebabble',
			[ &$this, 'options_page' ]
		);
	}

	/**
	 * Defines the skeleton of the configuration screen.
	 *
	 * @return void Prints on page.
	 */
	public function options_page():void {
		?>
		<form action='options.php' method='post'>
			<h2>kebabble</h2>
			<?php
			settings_fields( 'pluginPage' );
			do_settings_sections( 'pluginPage' );
			submit_button();
			?>
		</form>
		<?php
	}

	/**
	 * Constructs the Kebabble WordPress settings page.
	 *
	 * @return void Prints on page.
	 */
	public function settings():void {
		register_setting( 'pluginPage', 'kbfos_settings' );

		$this->render_description();
		$this->render_slack_config();
	}

	/**
	 * Shows a settings page description.
	 *
	 * @return void Prints on page.
	 */
	public function render_description():void {
		add_settings_section(
			'kbfos_pluginPage_section',
			__( 'Slack Configuration', 'kebabble' ),
			function() {
					echo 'Configure Kebabble to use your Slack.';
			},
			'pluginPage'
		);
	}

	/**
	 * Shows Slack-related communication configurations.
	 *
	 * @return void Prints on page.
	 */
	public function render_slack_config():void {
		add_settings_field(
			'kbfos_appid',
			__( 'Slack app ID', 'kebabble' ),
			function () {
				if ( getenv( 'KEBABBLE_SLACK_APP_ID' ) !== false ) {
					?>
					<input type='text' class='regular-text disabled' name='kbfos_settings[kbfos_appid]' value='<?php echo esc_attr( getenv( 'KEBABBLE_SLACK_APP_ID' ) ); ?>' disabled>
					<?php
				} else {
					$options = get_option( 'kbfos_settings' );
					?>
					<input type='text' class='regular-text' name='kbfos_settings[kbfos_appid]' value='<?php echo esc_attr( $options['kbfos_appid'] ); ?>'>
					<?php
				}
			},
			'pluginPage',
			'kbfos_pluginPage_section'
		);

		add_settings_field(
			'kbfos_botkey',
			__( 'Slack Bot Auth key', 'kebabble' ),
			function () {
				if ( getenv( 'KEBABBLE_BOT_AUTH' ) !== false ) {
					?>
					<input type='text' class='regular-text disabled' name='kbfos_settings[kbfos_botkey]' value='<?php echo esc_attr( getenv( 'KEBABBLE_BOT_AUTH' ) ); ?>' disabled>
					<?php
				} else {
					$options = get_option( 'kbfos_settings' );
					?>
					<input type='text' class='regular-text' name='kbfos_settings[kbfos_botkey]' value='<?php echo esc_attr( $options['kbfos_botkey'] ); ?>'>
					<?php
				}
			},
			'pluginPage',
			'kbfos_pluginPage_section'
		);

		add_settings_field(
			'kbfos_botchannel',
			__( 'Default Slack Channel', 'kebabble' ),
			function() {
				$options  = get_option( 'kbfos_settings' );
				$channels = $this->slack->channels();
				?>
				<select name="kbfos_settings[kbfos_botchannel]">
					<?php foreach ( $channels as $channel ) : ?>
						<?php if ( $channel['member'] ) : ?>
						<option value='<?php echo esc_attr( $channel['key'] ); ?>' <?php selected( $options['kbfos_botchannel'], $channel['key'] ); ?>>
							<?php echo esc_attr( $channel['channel'] ); ?>
						</option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
				<?php
			},
			'pluginPage',
			'kbfos_pluginPage_section'
		);

		add_settings_field(
			'kbfos_payopts',
			__( 'Payment Formats', 'kebabble' ),
			function() {
				$options = get_option( 'kbfos_settings' );
				?>
				<input type='text' class='regular-text'  name='kbfos_settings[kbfos_payopts]' placeholder='Cash, PayPal, etc...' value='<?php echo esc_attr( $options['kbfos_payopts'] ); ?>'>
				<p class="description"><?php esc_html_e( 'Comma-seperated values accepted', 'kebabble' ); ?></p>
				<?php
			},
			'pluginPage',
			'kbfos_pluginPage_section'
		);

		add_settings_field(
			'kbfos_place_type',
			__( 'Place Types', 'kebabble' ),
			function() {
				$options = get_option( 'kbfos_settings' );
				?>
				<input type='text' class='regular-text'  name='kbfos_settings[kbfos_place_type]' placeholder='Kebab, Pizza, etc...' value='<?php echo esc_attr( $options['kbfos_place_type'] ); ?>'>
				<p class="description"><?php esc_html_e( 'Used primarily for emojis. Comma-seperated values accepted', 'kebabble' ); ?>.</p>
				<?php
			},
			'pluginPage',
			'kbfos_pluginPage_section'
		);
	}
}
