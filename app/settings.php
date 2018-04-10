<?php namespace kebabble;

defined( 'ABSPATH' ) or die( 'Operation not permitted.' );

class settings {
	public function page() {
		add_options_page( 
			'Kebabble', 
			'Kebabble', 
			'manage_options', 
			'kebabble', 
			[&$this, 'optionsPage']
		);
	}

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
	 */
	public function settings() {
		register_setting( 'pluginPage', 'kbfos_settings' );
		
		$this->renderDescription();
		$this->renderSlackConfig();
		$this->renderKebabbleConfig();
	}
	
	/**
	 * Shows a settings page description.
	 */
	function renderDescription() {
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
	 */
	function renderSlackConfig() {
		add_settings_field( 
			'kbfos_botkey', 
			__( 'Slack Bot Auth key', 'text_domain' ), 
			function () {
				$options = get_option( 'kbfos_settings' );
				?>
				<input type='text' name='kbfos_settings[kbfos_botkey]' value='<?php echo $options['kbfos_botkey']; ?>'>
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
	}
	
	/**
	 * Shows other settings relating to how Kebabble operates.
	 */
	function renderKebabbleConfig() {
		add_settings_field( 
			'kbfos_drivertax', 
			__( 'Driver Tax', 'text_domain' ), 
			function() {
				$options = get_option( 'kbfos_settings' );
				?>
				<input type="number" min="0.00" max="50.00" step="0.01" name='kbfos_settings[kbfos_drivertax]' value='<?php echo $options['kbfos_drivertax']; ?>'/>
				<?php
			}, 
			'pluginPage', 
			'kbfos_pluginPage_section' 
		);
	}
}