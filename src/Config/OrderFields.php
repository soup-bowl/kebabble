<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@revive.today>
 * @license MIT
 */

namespace Kebabble\Config;

use Kebabble\Processes\Meta\Orderstore;

use WP_Post;
use WP_Term;

/**
 * Display formatting on the kebabble order form.
 *
 * @todo Refactor the messy 'existing' inputs. Maybe consider object-splitting?
 */
class OrderFields {
	/**
	 * Stores and retrieves order data.
	 *
	 * @var Orderstore
	 */
	protected $orderstore;

	/**
	 * Constructor.
	 *
	 * @param Orderstore $orderstore Stores and retrieves order data.
	 */
	public function __construct( Orderstore $orderstore ) {
		$this->orderstore = $orderstore;
	}

	/**
	 * Calls add_meta_box for kebabble order to display the custom form.
	 *
	 * @param WP_Post $post The post object being created/edited.
	 * @return void Prints on the page.
	 */
	public function order_options_setup( WP_Post $post ):void {
		add_meta_box(
			'kebabbleorderdetails',
			'Order',
			function( $post ) {
				$existing         = $this->orderstore->get( $post->ID );
				$existing_company = wp_get_post_terms( $post->ID, 'kebabble_company' );

				// Non-strict comparison needed here, until checkbox sanitization on meta is done.
				if ( empty( $existing ) && 1 == get_option( 'kbfos_settings' )['kbfos_pullthrough'] ) {
					$existing         = $this->orderstore->get( get_previous_post()->ID );
					$existing_company = wp_get_post_terms( get_previous_post()->ID, 'kebabble_company' );

					$existing['override']['enabled'] = false;
					$existing['override']['message'] = '';
				}

				$this->custom_message_renderer( $post, $existing );
				?><div id="kebabbleOrder">
				<input type="hidden" name="kebabbleNonce" value="<?php echo esc_attr( wp_create_nonce( 'kebabble_nonce' ) ); ?>">
				<?php
				$this->company_menu_selector( $post, ( ! empty( $existing_company ) ) ? $existing_company[0] : null );
				$this->food_selection( $post, $existing );
				$this->order_input( $post, $existing );
				$this->driver_input( $post, $existing );
				$this->driver_tax_input( $post, $existing );
				$this->payment_options( $post, $existing );
				?>
				</div>
				<?php
				$this->pin_status( $post, $existing );
			},
			'kebabble_orders',
			'normal',
			'high'
		);

		add_meta_box(
			'kebabbleorderoverrides',
			'Slack',
			function( $post ) {
				$c_output = get_post_meta( $post->ID, 'kebabble-slack-channel', true );
				$channel  = ( empty( $c_output ) ) ? 'N/A' : $c_output;
				?>
				<div>
					<p class="label"><label for="kebabbleOverrideChannel">Channel</label></p>
					<input type="text" name="kebabbleOverrideChannel" id="kebabbleOverrideChannel" value="<?php echo esc_attr( $channel ); ?>" readonly>
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
	public function custom_message_renderer( WP_Post $post, ?array $existing = null ):void {
		$existing_contents = ( ! empty( $existing ) ) ? (object) $existing['override'] : false;
		$enabled           = ( false !== $existing_contents && $existing_contents->enabled ) ? 'checked' : '';
		$message           = ( ! empty( $existing_contents->message ) ) ? $existing_contents->message : '';
		?>
		<div>
			<p><input name="kebabbleCustomMessageEnabled" id="cmCheckBox" type="checkbox" <?php echo esc_attr( $enabled ); ?>> - Custom Message</p>
			<hr>
			<div id="kebabbleCustomMessage">
				<p class="label"><label for="kebabbleCustomMessageEntry">Custom Message</label></p>
				<textarea name="kebabbleCustomMessageEntry"><?php echo esc_textarea( $message ); ?></textarea>
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
	public function company_menu_selector( WP_Post $post, ?WP_Term $existing = null ):void {
		$selected          = ( ! empty( $existing ) ) ? $existing->term_id : 0;
		$options_available = get_terms(
			[
				'taxonomy'   => 'kebabble_company',
				'hide_empty' => false,
			]
		);

		$select = '<option value=\'0\'>None</option>';
		foreach ( $options_available as $option ) {
			$mark_selected = ( $option->term_id === $selected ) ? 'selected' : '';
			$select       .= "<option value='{$option->term_id}' {$mark_selected}>{$option->name}</option>";
		}

		// phpcs:disable WordPress.Security.EscapeOutput
		?>
		<div>
			<p class="label"><label for="kebabbleCompanySelection">Company</label></p>
			<select name="kebabbleCompanySelection">
				<?php echo $select; ?>
			</select>
		</div>
		<?php
		// phpcs:enable
	}

	/**
	 * Shows a dropdown menu with all available food-type selections.
	 *
	 * @param WP_Post    $post     The post object being created/edited.
	 * @param array|null $existing Existing value (from orderstore) in the database.
	 * @return void Constructs on page where called.
	 */
	public function food_selection( WP_Post $post, ?array $existing = null ):void {
		$selected = ( ! empty( $existing ) ) ? $existing['food'] : '';
		$options  = [ 'Kebab', 'Pizza', 'Burger', 'Resturant', 'Event', 'Other' ];

		$select = '';
		foreach ( $options as $option ) {
			$mark_selected = ( $option === $selected ) ? 'selected' : '';
			$select       .= "<option value='{$option}' {$mark_selected}>{$option}</option>";
		}

		// phpcs:disable WordPress.Security.EscapeOutput
		?>
		<div>
			<p class="label"><label for="kebabbleOrderTypeSelection">Food</label></p>
			<select name="kebabbleOrderTypeSelection">
				<?php echo $select; ?>
			</select>
		</div>
		<?php
		// phpcs:enable
	}

	/**
	 * Creates a monospaced order input form.
	 *
	 * @param WP_Post    $post      The post object being created/edited.
	 * @param array|null $existings Existing value (from orderstore) in the database.
	 * @return void Constructs on page where called.
	 */
	public function order_input( WP_Post $post, ?array $existings = null ):void {
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
					$this->order_snippet( 'korder_examplerow', 'hidden' );
					if ( empty( $existings ) ) {
						$this->order_snippet();
					} else {
						foreach ( $existings as $existing ) {
							$this->order_snippet( '', '', $existing['person'], $existing['food'] );
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
	public function driver_input( WP_Post $post, ?array $existing = null ) {
		$existing = ( ! empty( $existing ) ) ? $existing['driver'] : '';
		?>
		<div>
			<p class="label"><label for="kebabbleDriver">Driver</label></p>
			<input type="text" name="kebabbleDriver" id="kebabbleDriver" value="<?php echo esc_attr( $existing ); ?>">
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
	public function driver_tax_input( WP_Post $post, ?array $existing = null ):void {
		$existing = ( ! empty( $existing ) ) ? $existing['tax'] : '';
		?>
		<div>
			<p class="label"><label for="kebabbleDriverTax">Driver Charge (in pence)</label></p>
			<input type="text" name="kebabbleDriverTax" id="kebabbleDriverTax" value="<?php echo esc_attr( $existing ); ?>">
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
	public function payment_options( WP_Post $post, ?array $existing = null ):void {
		$existing_pm = ( ! empty( $existing ) ) ? $existing['payment'] : '';
		$existing_pl = ( ! empty( $existing ) ) ? $existing['paymentLink'] : [];
		$opts        = get_option( 'kbfos_settings' );
		$options     = ( empty( $opts['kbfos_payopts'] ) ) ? [ 'Cash' ] : explode( ',', $opts['kbfos_payopts'] );

		$lists         = '';
		$options_count = count( $options );
		for ( $i = 0; $i < $options_count; $i++ ) {
			$option      = $options[ $i ];
			$mark_select = ( ! empty( $existing_pm ) && in_array( $option, $existing_pm, true ) ) ? 'checked' : '';
			$lists      .= "<li><label><input name='paymentOpts[]' type='checkbox' value='{$option}' {$mark_select}> {$option}</label>";
			$lists      .= ' - ';
			$lists      .= "<input type='text' class='subtext' name='kopt{$option}' id='kopt{$option}' value='{$existing_pl[$option]}'></li>";
		}

		// phpcs:disable WordPress.Security.EscapeOutput
		?>
		<div>
			<p class="label"><label for="kebabblePaymentOptions">Payment Options</label></p>
			<ul>
				<?php echo $lists; ?>
			</ul>
		</div>
		<?php
		// phpcs:enable
	}

	/**
	 * Slack-specific checkbox to pin the message to the chat.
	 *
	 * @param WP_Post    $post     The post object being created/edited.
	 * @param array|null $existing Existing value (from orderstore) in the database.
	 * @return void Constructs on page where called.
	 */
	public function pin_status( WP_Post $post, ?array $existing = null ):void {
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
	private function order_snippet( string $id = '', string $class = '', string $name = '', string $food = '' ):void {
		?>
		<tr id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $class ); ?>">
			<td><input type="text" name="korder_name[]" id="korder_name[]" value="<?php echo esc_attr( $name ); ?>"></td>
			<td><input type="text" name="korder_food[]" id="korder_food[]" value="<?php echo esc_attr( $food ); ?>"></td>
			<td><a class="btnRemkorder button" href="#">Remove</a></td>
		</tr>
		<?php
	}
}
