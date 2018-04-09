<?php namespace kebabble\config;

defined( 'ABSPATH' ) or die( 'Operation not permitted.' );

class fields {
	public function orderOptionsSetup( $post ) {
		add_meta_box( 
			'kebabbleorderdetails', 
			'Order', 
			function($post) { 
				$existing = get_post_meta( $post->ID, 'kebabble-order', true );
				//wp_nonce_field( basename( __FILE__ ) ); 
				echo $this->customMessageRenderer($post, $existing);
				?><div id="kebabbleOrder"><?php
				echo $this->foodSelection($post, $existing);
				echo $this->orderInput($post, $existing);
				echo $this->driverInput($post, $existing);
				echo $this->paymentOptions($post, $existing);
				?></div><?php
			}, 
			'kebabble_orders', 
			'normal', 
			'high' 
		);
	}
	
	public function customMessageRenderer($post, $existing) {
		$existingContents = (!empty($existing)) ? (object)$existing['override'] : false;
		$enabled = ($existingContents !== false && $existingContents->enabled) ? "checked" : "";
		$message = (!empty($existingContents->message)) ? $existingContents->message : "";
		//echo "<pre>";var_dump( get_post_meta($post->ID, 'kebabble-order', true) );echo "</pre>";
		?>
		<div>
			<p><input name="kebabbleCustomMessageEnabled" id="checkBox" type="checkbox" <?php echo $enabled; ?>> - Custom Message</p>
			<hr>
			<div id="kebabbleCustomMessage">
				<p class="label"><label for="kebabbleCustomMessageEntry">Custom Message</label></p>
				<textarea name="kebabbleCustomMessageEntry"><?php echo $message; ?></textarea>
			</div>
		</div>
		<?php
	}
	
	public function foodSelection($post, $existing) {
		$selected = (!empty($existing)) ? $existing['food'] : "";
		$options  = ['Kebab', 'Pizza', 'Burger', 'Resturant', 'Event', 'Other'];
		
		$select = '';
		foreach ($options as $option) {
			$markSelected = ($option == $selected) ? "selected" : "";
			$select .= "<option value='{$option}' {$markSelected}>{$option}</option>"; 
		} ?>
		<div>
			<p class="label"><label for="kebabbleOrderTypeSelection">Food</label></p>
			<select name="kebabbleOrderTypeSelection">
				<?php echo $select; ?>
			</select>
		</div>
		<?php
	}
	
	public function orderInput($post, $existing) {
		$existing = (!empty($existing)) ? $existing['order'] : "";
		?>
		<div>
			<p class="label"><label for="kebabbleOrders">Orders</label></p>
			<textarea name="kebabbleOrders" id="kebabbleOrders" class="mono"><?php echo $existing; ?></textarea>
		</div>
		<?php
	}
	
	public function driverInput($post, $existing) {
		$existing = (!empty($existing)) ? $existing['driver'] : "";
		?>
		<div>
			<p class="label"><label for="kebabbleDriver">Driver</label></p>
			<input type="text" name="kebabbleDriver" id="kebabbleDriver" value="<?php echo $existing; ?>">
		</div>
		<?php
	}
	
	public function paymentOptions($post, $existing) {
		$existing = (!empty($existing)) ? $existing['payment'] : "";
		$options = ['Cash', 'PayPal', 'Monzo'];
		
		$lists = '';
		foreach ($options as $option) {
			$markSelect = (!empty($existing) && in_array($option, $existing)) ? "checked" : "";
			$lists .= "<li><label><input name='paymentOpts[]' type='checkbox' value='{$option}' {$markSelect}> {$option}</label></li>";
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
}