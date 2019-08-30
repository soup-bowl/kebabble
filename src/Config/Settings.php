<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@revive.today>
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
	public function __construct(Slack $slack) {
		$this->slack = $slack;
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
			__( 'Slack Configuration', 'text_domain' ),
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
			'kbfos_botkey',
			__( 'Slack Bot Auth key', 'text_domain' ),
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
			__( 'Default Slack Channel', 'text_domain' ),
			function() {
				$options  = get_option( 'kbfos_settings' );
				$channels = $this->slack->channels();
				?>
				<select name="kbfos_settings[kbfos_botchannel]">
					<?php foreach ( $channels as $channel ) : ?>
						<?php if ( $channel['member'] ) : ?>
						<option value='<?php echo $channel['key']; ?>' <?php selected( $options['kbfos_botchannel'], $channel['key'] ); ?>>
							<?php echo $channel['channel']; ?>
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
			'kbfos_pullthrough',
			__( 'Use Existing', 'text_domain' ),
			function() {
				$options     = get_option( 'kbfos_settings' );
				$pullthrough = ( isset( get_option( 'kbfos_settings' )['kbfos_pullthrough'] ) ) ? get_option( 'kbfos_settings' )['kbfos_pullthrough'] : 0;
				?>
				<input type='checkbox' name='kbfos_settings[kbfos_pullthrough]' <?php checked( $pullthrough, 1 ); ?> value='1'>
				<p class="description">Pulls through the previously used values, excluding the order.</p>
				<?php
			},
			'pluginPage',
			'kbfos_pluginPage_section'
		);

		add_settings_field(
			'kbfos_payopts',
			__( 'Payment Formats', 'text_domain' ),
			function() {
				$options = get_option( 'kbfos_settings' );
				?>
				<input type='text' class='regular-text'  name='kbfos_settings[kbfos_payopts]' value='<?php echo esc_attr( $options['kbfos_payopts'] ); ?>'>
				<p class="description">Comma-seperated values accepted.</p>
				<?php
			},
			'pluginPage',
			'kbfos_pluginPage_section'
		);
	}
}
