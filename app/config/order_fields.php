<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@revive.today>
 * @license MIT
 */

namespace kebabble\config;

use kebabble\processes\meta\orderstore;

use WP_Post;
use WP_Term;

/**
 * Display formatting on the kebabble order form.
 *
 * @todo Refactor the messy 'existing' inputs. Maybe consider object-splitting?
 */
class order_fields {
	/**
	 * Stores and retrieves order data.
	 *
	 * @var orderstore
	 */
	protected $orderstore;

	/**
	 * Constructor.
	 *
	 * @param orderstore $orderstore Stores and retrieves order data.
	 */
	public function __construct( orderstore $orderstore ) {
		$this->orderstore = $orderstore;
	}

	/**
	 * Calls add_meta_box for kebabble order to display the custom form.
	 *
	 * @param WP_Post $post The post object being created/edited.
	 * @return void Prints on the page.
	 */
	public function orderOptionsSetup( WP_Post $post ):void {
		add_meta_box(
			'kebabbleorderdetails',
			'Order',
			function( $post ) {
				$existing         = $this->orderstore->get( $post->ID );
				$existing_company = wp_get_post_terms( $post->ID, 'kebabble_company' );

				// Non-strict comparison needed here, until checkbox sanitization on meta is done.
				if ( empty( $existing ) && 1 == get_option( 'kbfos_settings' )['kbfos_pullthrough'] ) {
					$existing          = $this->orderstore->get( get_previous_post()->ID );
					$existing_company  = wp_get_post_terms( get_previous_post()->ID, 'kebabble_company' );

					$existing['override']['enabled'] = false;
					$existing['override']['message'] = '';
				}

				// wp_nonce_field( 'kebabble-order-form' );
				echo $this->customMessageRenderer( $post, $existing );
				?><div id="kebabbleOrder">
				<?php
				echo $this->companyMenuSelector( $post, ( ! empty( $existing_company ) ) ? $existing_company[0] : null );
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

		add_meta_box(
			'kebabbleorderoverrides',
			'Slack',
			function( $post ) {
				 $cOutput = get_post_meta( $post->ID, 'kebabble-slack-channel', true );
				 $channel = ( empty( $cOutput ) ) ? 'N/A' : $cOutput;
				?>
				 <div>
					<p class="label"><label for="kebabbleOverrideChannel">Channel</label></p>
					<input type="text" name="kebabbleOverrideChannel" id="kebabbleOverrideChannel" value="<?php echo $channel; ?>" readonly>
				</div>
				<?php
			},
			'kebabble_orders',
			'side',
			'low'
		);
	}

	/**
	 * Input block for a custom override message.
	 *
	 * @param WP_Post    $post     The post object being created/edited.
	 * @param array|null $existing Existing value (from orderstore) in the database.
	 * @return void Constructs on page where called.
	 */
	public function customMessageRenderer( WP_Post $post, ?array $existing = null ):void {
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
	 * Select what resturant you're ordering from, if stored. Allows for price and
	 * location information pullthroughs.
	 *
	 * @param WP_Post      $post     The post object being created/edited.
	 * @param WP_Term|null $existing Existing values in the database.
	 * @return void Prints on the page.
	 */
	public function companyMenuSelector( WP_Post $post, ?WP_Term $existing = null ):void {
		$selected = ( ! empty( $existing ) ) ? $existing->term_id : 0;
		$options_available = get_terms([
			'taxonomy'   => 'kebabble_company',
			'hide_empty' => false,
		]);

		$select = '<option value=\'0\'>None</option>';
		foreach ( $options_available as $option ) {
			$markSelected = ( $option->term_id === $selected ) ? 'selected' : '';
			$select      .= "<option value='{$option->term_id}' {$markSelected}>{$option->name}</option>";
		}
		?>
		<div>
			<p class="label"><label for="kebabbleCompanySelection">Company</label></p>
			<select name="kebabbleCompanySelection">
				<?php echo $select; ?>
			</select>
		</div>
		<?php
	}

	/**
	 * Shows a dropdown menu with all available food-type selections.
	 *
	 * @param WP_Post    $post     The post object being created/edited.
	 * @param array|null $existing Existing value (from orderstore) in the database.
	 * @return void Constructs on page where called.
	 */
	public function foodSelection( WP_Post $post, ?array $existing = null ):void {
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
	 * @param WP_Post    $post      The post object being created/edited.
	 * @param array|null $existings Existing value (from orderstore) in the database.
	 * @return void Constructs on page where called.
	 */
	public function orderInput( WP_Post $post, ?array $existings = null ):void {
		$existings = ( ! empty( $existings ) ) ? $existings['order'] : '';
		?>
		<div>
			<p class="label"><label for="kebabbleOrders">Orders</label></p>
			<table>
				<thead>
					<tr>
						<th>Person</th>
						<th>Order</th>
						<th>Options</th>
					</tr>
				</thead>
				<tbody id="korder_tablelist">
					<?php
					$this->orderSnippet( 'korder_examplerow', 'hidden' );
					if ( empty( $existings ) ) {
						$this->orderSnippet();
					} else {
						foreach ( $existings as $existing ) {
							$this->orderSnippet( '', '', $existing['person'], $existing['food'] );
						}
					}
					?>
				</tbody>
			</table>
			<a class="btnAddkorder button" href="#">Add</a>
			<input type=hidden value="" />
		</div>
		<?php
	}

	/**
	 * Field for allocating the driver.
	 *
	 * @param WP_Post    $post     The post object being created/edited.
	 * @param array|null $existing Existing value (from orderstore) in the database.
	 * @return void Constructs on page where called.
	 */
	public function driverInput( WP_Post $post, ?array $existing = null ) {
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
	 * @param WP_Post    $post     The post object being created/edited.
	 * @param array|null $existing Existing value (from orderstore) in the database.
	 * @return void Constructs on page where called.
	 */
	public function driverTaxInput( WP_Post $post, ?array $existing = null ):void {
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
	 * @param WP_Post    $post     The post object being created/edited.
	 * @param array|null $existing Existing value (from orderstore) in the database.
	 * @return void Constructs on page where called.
	 */
	public function paymentOptions( WP_Post $post, ?array $existing = null ):void {
		$existingPM = ( ! empty( $existing ) ) ? $existing['payment'] : '';
		$existingPL = ( ! empty( $existing ) ) ? $existing['paymentLink'] : [];
		$opts       = get_option( 'kbfos_settings' );
		$options    = ( empty( $opts['kbfos_payopts'] ) ) ? [ 'Cash' ] : explode( ',', $opts['kbfos_payopts'] );

		$lists = '';
		for ( $i = 0; $i < count( $options ); $i++ ) {
			$option     = $options[ $i ];
			$markSelect = ( ! empty( $existingPM ) && in_array( $option, $existingPM ) ) ? 'checked' : '';
			$lists     .= "<li><label><input name='paymentOpts[]' type='checkbox' value='{$option}' {$markSelect}> {$option}</label>";
			$lists     .= ' - ';
			$lists     .= "<input type='text' class='subtext' name='kopt{$option}' id='kopt{$option}' value='{$existingPL[$option]}'></li>";
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
	 * @param WP_Post    $post     The post object being created/edited.
	 * @param array|null $existing Existing value (from orderstore) in the database.
	 * @return void Constructs on page where called.
	 */
	public function pinStatus( WP_Post $post, ?array $existing = null ):void {
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

	/**
	 * Snippet of the repeated order segment.
	 *
	 * @param string $id    TR elment ID.
	 * @param string $class TR element class.
	 * @param string $name  Existing name entry.
	 * @param string $food  Existing food entry.
	 * @return void Constructs on page where called.
	 */
	private function orderSnippet( string $id = '', string $class = '', string $name = '', string $food = '' ):void {
		?>
		<tr id="<?php echo $id; ?>" class="<?php echo $class; ?>">
			<td><input type="text" name="korder_name[]" id="korder_name[]" value="<?php echo $name; ?>"></td>
			<td><input type="text" name="korder_food[]" id="korder_food[]" value="<?php echo $food; ?>"></td>
			<td><a class="btnRemkorder button" href="#">Remove</a></td>
		</tr>
		<?php
	}
}
