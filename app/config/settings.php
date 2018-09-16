<?php
/**
 * Displays the configuration options in the WordPress admin options.
 *
 * @package kebabble
 * @author soup-bowl
 */

namespace kebabble\config;

/**
 * Displays the configuration options in the WordPress admin options.
 */
class settings {
	/**
	 * Tells WordPress about Kebabble settngs, and adds them to as a submenu.
	 *
	 * @return void
	 */
	public function page() {
		add_options_page(
			'Kebabble',
			'Kebabble',
			'manage_options',
			'kebabble',
			[ &$this, 'optionsPage' ]
		);
	}

	/**
	 * Defines the skeleton of the configuration screen.
	 *
	 * @return void
	 */
	public function optionsPage() {
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
	 * @return void
	 */
	public function settings() {
		register_setting( 'pluginPage', 'kbfos_settings' );

		$this->renderDescription();
		$this->renderSlackConfig();
	}

	/**
	 * Shows a settings page description.
	 *
	 * @return void
	 */
	public function renderDescription() {
		add_settings_section(
			'kbfos_pluginPage_section',
			__( 'Slack Configuration', 'text_domain' ),
			function() {
					echo __( 'Configure Kebabble to use your Slack.', 'text_domain' );
			},
			'pluginPage'
		);
	}

	/**
	 * Shows Slack-related communication configurations.
	 *
	 * @return void
	 */
	public function renderSlackConfig() {
		add_settings_field(
			'kbfos_botkey',
			__( 'Slack Bot Auth key', 'text_domain' ),
			function () {
				$options = get_option( 'kbfos_settings' );
				?>
				<input type='text' class='regular-text' name='kbfos_settings[kbfos_botkey]' value='<?php echo $options['kbfos_botkey']; ?>'>
				<?php
			},
			'pluginPage',
			'kbfos_pluginPage_section'
		);

		add_settings_field(
			'kbfos_botchannel',
			__( 'Slack Channel', 'text_domain' ),
			function() {
				$options = get_option( 'kbfos_settings' );
				?>
				<input type='text' name='kbfos_settings[kbfos_botchannel]' value='<?php echo $options['kbfos_botchannel']; ?>'>
				<?php
			},
			'pluginPage',
			'kbfos_pluginPage_section'
		);

		add_settings_field(
			'kbfos_pullthrough',
			__( 'Use Existing', 'text_domain' ),
			function() {
				$options = get_option( 'kbfos_settings' );
				?>
				<input type='checkbox' name='kbfos_settings[kbfos_pullthrough]' <?php checked( $options['kbfos_pullthrough'], 1 ); ?> value='1'>
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
				<input type='text' class='regular-text'  name='kbfos_settings[kbfos_payopts]' value='<?php echo $options['kbfos_payopts']; ?>'>
				<p class="description">Comma-seperated values accepted.</p>
				<?php
			},
			'pluginPage',
			'kbfos_pluginPage_section'
		);
	}
}
