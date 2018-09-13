<?php
/**
 * Display formatting on the kebabble Custom Post Type.
 *
 * @package kebabble
 * @author soup-bowl
 */

namespace kebabble\config;

/**
 * Display formatting on the kebabble Custom Post Type.
 */
class fields {
	/**
	 * Calls add_meta_box for kebabble order to display the custom form.
	 *
	 * @param WP_Post $post The post object being created/edited.
	 * @return void
	 */
	public function orderOptionsSetup( $post ) {
		add_meta_box(
			'kebabbleorderdetails',
			'Order',
			function( $post ) {
				$existing = get_post_meta( $post->ID, 'kebabble-order', true );
				// wp_nonce_field( basename( __FILE__ ) );
				echo $this->customMessageRenderer( $post, $existing );
				?><div id="kebabbleOrder">
				<?php
				echo $this->foodSelection( $post, $existing );
				echo $this->orderInput( $post, $existing );
				echo $this->driverInput( $post, $existing );
				echo $this->driverTaxInput( $post, $existing );
				echo $this->paymentOptions( $post, $existing );
				?>
				</div>
				<?php
				echo $this->pinStatus( $post, $existing );
			},
			'kebabble_orders',
			'normal',
			'high'
		);
	}

	/**
	 * Input block for a custom override message.
	 *
	 * @param WP_Post $post     The post object being created/edited.
	 * @param string  $existing Existing value in the database.
	 * @return void Constructs on page where called.
	 */
	public function customMessageRenderer( $post, $existing ) {
		$existingContents = ( ! empty( $existing ) ) ? (object) $existing['override'] : false;
		$enabled          = ( false !== $existingContents && $existingContents->enabled ) ? 'checked' : '';
		$message          = ( ! empty( $existingContents->message ) ) ? $existingContents->message : '';
		?>
		<div>
			<p><input name="kebabbleCustomMessageEnabled" id="cmCheckBox" type="checkbox" <?php echo $enabled; ?>> - Custom Message</p>
			<hr>
			<div id="kebabbleCustomMessage">
				<p class="label"><label for="kebabbleCustomMessageEntry">Custom Message</label></p>
				<textarea name="kebabbleCustomMessageEntry"><?php echo $message; ?></textarea>
			</div>
		</div>
		<?php
	}

	/**
	 * Shows a dropdown menu with all available food-type selections.
	 *
	 * @param WP_Post $post     The post object being created/edited.
	 * @param string  $existing Existing value in the database.
	 * @return void Constructs on page where called.
	 */
	public function foodSelection( $post, $existing ) {
		$selected = ( ! empty( $existing ) ) ? $existing['food'] : '';
		$options  = [ 'Kebab', 'Pizza', 'Burger', 'Resturant', 'Event', 'Other' ];

		$select = '';
		foreach ( $options as $option ) {
			$markSelected = ( $option === $selected ) ? 'selected' : '';
			$select      .= "<option value='{$option}' {$markSelected}>{$option}</option>";
		}
		?>
		<div>
			<p class="label"><label for="kebabbleOrderTypeSelection">Food</label></p>
			<select name="kebabbleOrderTypeSelection">
				<?php echo $select; ?>
			</select>
		</div>
		<?php
	}

	/**
	 * Creates a monospaced order input form.
	 *
	 * @param WP_Post $post     The post object being created/edited.
	 * @param string  $existing Existing value in the database.
	 * @return void Constructs on page where called.
	 */
	public function orderInput( $post, $existing ) {
		$existing = ( ! empty( $existing ) ) ? $existing['order'] : '';
		?>
		<div>
			<p class="label"><label for="kebabbleOrders">Orders</label></p>
			<textarea name="kebabbleOrders" id="kebabbleOrders" class="mono"><?php echo $existing; ?></textarea>
		</div>
		<?php
	}

	/**
	 * Field for allocating the driver.
	 *
	 * @param WP_Post $post     The post object being created/edited.
	 * @param string  $existing Existing value in the database.
	 * @return void Constructs on page where called.
	 */
	public function driverInput( $post, $existing ) {
		$existing = ( ! empty( $existing ) ) ? $existing['driver'] : '';
		?>
		<div>
			<p class="label"><label for="kebabbleDriver">Driver</label></p>
			<input type="text" name="kebabbleDriver" id="kebabbleDriver" value="<?php echo $existing; ?>">
		</div>
		<?php
	}

	/**
	 * Allows the orderer to specify a per-person tax, if desired.
	 *
	 * @param WP_Post $post     The post object being created/edited.
	 * @param string  $existing Existing value in the database.
	 * @return void Constructs on page where called.
	 */
	public function driverTaxInput( $post, $existing ) {
		$existing = ( ! empty( $existing ) ) ? $existing['tax'] : '';
		?>
		<div>
			<p class="label"><label for="kebabbleDriverTax">Driver Charge (in pence)</label></p>
			<input type="text" name="kebabbleDriverTax" id="kebabbleDriverTax" value="<?php echo $existing; ?>">
		</div>
		<?php
	}

	/**
	 * Shows an inline list of all the payment options the driver accepts.
	 *
	 * @param WP_Post $post     The post object being created/edited.
	 * @param string  $existing Existing value in the database.
	 * @return void Constructs on page where called.
	 */
	public function paymentOptions( $post, $existing ) {
		$existing = ( ! empty( $existing ) ) ? $existing['payment'] : '';
		$options  = [ 'Cash', 'PayPal', 'Monzo' ];

		$lists = '';
		foreach ( $options as $option ) {
			$markSelect = ( ! empty( $existing ) && in_array( $option, $existing ) ) ? 'checked' : '';
			$lists     .= "<li><label><input name='paymentOpts[]' type='checkbox' value='{$option}' {$markSelect}> {$option}</label></li>";
		}

		?>
		<div>
			<p class="label"><label for="kebabblePaymentOptions">Payment Options</label></p>
			<ul>
				<?php echo $lists; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Slack-specific checkbox to pin the message to the chat.
	 *
	 * @param WP_Post $post     The post object being created/edited.
	 * @param string  $existing Existing value in the database.
	 * @return void Constructs on page where called.
	 */
	public function pinStatus( $post, $existing ) {
		$existing = ( ! empty( $existing ) ) ? $existing['pin'] : false;
		?>
		<div>
			<p class="label"><label>Pin to Slack Channel</label></p>
			<ul>
				<input name='pinState' type='checkbox' <?php checked( $existing ); ?> value='1'>
			</ul>
		</div>
		<?php
	}
}
