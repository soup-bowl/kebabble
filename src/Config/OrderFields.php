<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@soupbowl.io>
 * @license MIT
 */

namespace Kebabble\Config;

use Kebabble\Processes\Meta\Orderstore;
use Kebabble\Library\Slack;

use WP_Post;
use WP_Term;
use stdClass;

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
	 * Slack communication access.
	 *
	 * @var Slack
	 */
	protected $slack;

	/**
	 * Constructor.
	 *
	 * @param Orderstore $orderstore Stores and retrieves order data.
	 * @param Slack      $slack      Slack communication access.
	 */
	public function __construct( Orderstore $orderstore, Slack $slack ) {
		$this->orderstore = $orderstore;
		$this->slack      = $slack;
	}

	/**
	 * Registers WordPress hooks.
	 *
	 * @return void
	 */
	public function hook_order_fields() {
		add_action( 'add_meta_boxes_kebabble_orders', [ &$this, 'order_options_setup' ] );
	}

	/**
	 * Calls add_meta_box for kebabble order to display the custom form.
	 *
	 * @param WP_Post $post The post object being created/edited.
	 * @return void Prints on the page.
	 */
	public function order_options_setup( WP_Post $post ):void {
		$existing_order_details = $this->get_existing_details( $post->ID );

		add_meta_box(
			'kebabbleorderdetails',
			'Order',
			function( $post ) use ( &$existing_order_details ) {
				$this->custom_message_renderer( $existing_order_details->existing );
				?>
				<div id="kebabbleOrder">
					<input type="hidden" name="kebabbleNonce" value="<?php echo esc_attr( wp_create_nonce( 'kebabble_nonce' ) ); ?>">
					<?php
					$this->company_menu_selector( ( ! empty( $existing_order_details->existing_company ) ) ? $existing_order_details->existing_company[0] : null );
					$this->food_selection( $existing_order_details->existing );
					$this->order_input( $existing_order_details->existing );
					$this->collector_selector( ( ! empty( $existing_order_details->existing_collector ) ) ? $existing_order_details->existing_collector[0] : null );
					?>
					<div id="sctDriver">
					<?php
						$this->driver_input( $existing_order_details->existing );
						$this->driver_tax_input( $existing_order_details->existing );
						$this->payment_options( $existing_order_details->existing );
					?>
					</div></div>
				<?php
			},
			'kebabble_orders',
			'normal',
			'high'
		);

		add_meta_box(
			'kebabbleorderoverrides',
			'Slack',
			function( $post ) use ( &$existing_order_details ) {
				$this->pin_status( $existing_order_details->existing );

				$default  = get_option( 'kbfos_settings' )['kbfos_botchannel'];
				$chosen   = get_post_meta( $post->ID, 'kebabble-slack-channel', true );
				$channels = $this->slack->channels();

				$selected = null;
				foreach ( $channels as $channel ) {
					if ( $channel['key'] === $chosen ) {
						$selected = $channel['channel'];
					}
				}

				// If set, show a read-only box. If not, show a selection box.
				?>
				<div>
					<p class="label"><label for="kebabbleOverrideChannel">Channel</label></p>
					<?php if ( ! empty( $chosen ) ) : ?>
					<input type="text" name="kebabbleOverrideChannel" id="kebabbleOverrideChannel" value="<?php echo esc_attr( $selected ); ?>" readonly>
					<?php else : ?>
					<select name="kebabbleOverrideChannel">
						<?php foreach ( $channels as $channel ) : ?>
						<option value='<?php echo esc_attr( $channel['key'] ); ?>' <?php selected( $default, $channel['key'] ); ?>>
							<?php echo esc_attr( $channel['channel'] ); ?>
						</option>
						<?php endforeach; ?>
					</select>
					<?php endif; ?>
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
	 * @param array|null $existing Existing value (from orderstore) in the database.
	 * @return void Constructs on page where called.
	 */
	public function custom_message_renderer( ?array $existing = null ):void {
		$enabled = ( $existing['kebabble-is-custom'] ) ? 'checked' : '';
		?>
		<div>
			<div>
				<p class="label"><label>Custom Message</label></p>
				<ul>
					<input name="kebabbleCustomMessageEnabled" id="cmCheckBox" type="checkbox" <?php echo esc_attr( $enabled ); ?>>
				</ul>
			</div>
			<div id="kebabbleCustomMessage">
				<p class="label"><label for="kebabbleCustomMessageEntry">Custom Message</label></p>
				<?php wp_editor(
					$existing['kebabble-custom-message'],
					'kebabbleCustomMessageEntry',
					[
						'media_buttons' => false,
						'quicktags'     => false,
					]
				); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Select what resturant you're ordering from, if stored. Allows for price and
	 * location information pullthroughs.
	 *
	 * @param WP_Term|null $existing Existing values in the database.
	 * @return void Prints on the page.
	 */
	public function company_menu_selector( ?WP_Term $existing = null ):void {
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
	 * @param array|null $existing Existing value (from orderstore) in the database.
	 * @return void Constructs on page where called.
	 */
	public function food_selection( ?array $existing = null ):void {
		$selected = ( ! empty( $existing ) ) ? $existing['kebabble-food'] : '';
		$options  = [ 'Kebab', 'Pizza', 'Burger', 'Restaurant', 'Event', 'Other' ];

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
	 * @param array|null $existings Existing value (from orderstore) in the database.
	 * @return void Constructs on page where called.
	 */
	public function order_input( ?array $existings = null ):void {
		$existings = ( ! empty( $existings ) ) ? $existings['kebabble-order'] : '';
		?>
		<div>
			<p class="label"><label for="kebabbleOrders">Orders</label></p>
			<table class="wp-list-table widefat fixed striped">
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
	 * Select the person collecting the order.
	 *
	 * @param WP_Term|null $existing Existing values in the database.
	 * @return void Prints on the page.
	 */
	public function collector_selector( ?WP_Term $existing = null ):void {
		$selected          = ( ! empty( $existing ) ) ? $existing->term_id : 0;
		$options_available = get_terms(
			[
				'taxonomy'   => 'kebabble_collector',
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
			<p class="label"><label for="kebabbleCollectorSelection">Collector</label></p>
			<select name="kebabbleCollectorSelection" id="selCollector">
				<?php echo $select; ?>
			</select>
		</div>
		<?php
		// phpcs:enable
	}

	/**
	 * Field for allocating the driver.
	 *
	 * @param array|null $existing Existing value (from orderstore) in the database.
	 * @return void Constructs on page where called.
	 */
	public function driver_input( ?array $existing = null ) {
		$existing = ( ! empty( $existing ) ) ? $existing['kebabble-driver'] : '';
		?>
		<div>
			<p class="label"><label for="kebabbleDriver">Collector Name</label></p>
			<input type="text" name="kebabbleDriver" id="kebabbleDriver" value="<?php echo esc_attr( $existing ); ?>" placeholder="Leave blank to use your WordPress name.">
		</div>
		<?php
	}

	/**
	 * Allows the orderer to specify a per-person tax, if desired.
	 *
	 * @param array|null $existing Existing value (from orderstore) in the database.
	 * @return void Constructs on page where called.
	 */
	public function driver_tax_input( ?array $existing = null ):void {
		$existing = ( ! empty( $existing ) ) ? $existing['kebabble-tax'] : '';
		?>
		<div>
			<p class="label"><label for="kebabbleDriverTax">Collector Charge (in pence)</label></p>
			<input type="number" name="kebabbleDriverTax" id="kebabbleDriverTax" value="<?php echo intval( $existing ); ?>">
		</div>
		<?php
	}

	/**
	 * Shows an inline list of all the payment options the driver accepts.
	 *
	 * @param array|null $existing Existing value (from orderstore) in the database.
	 * @return void Constructs on page where called.
	 */
	public function payment_options( ?array $existing = null ):void {
		$existing_pm = ( ! empty( $existing ) ) ? $existing['kebabble-payment'] : '';
		$existing_pl = ( ! empty( $existing ) ) ? $existing['kebabble-payment-link'] : [];
		$opts        = get_option( 'kbfos_settings' );
		$options     = ( empty( $opts['kbfos_payopts'] ) ) ? [ 'Cash' ] : explode( ',', $opts['kbfos_payopts'] );

		$lists         = '';
		$options_count = count( $options );
		for ( $i = 0; $i < $options_count; $i++ ) {
			$option      = $options[ $i ];
			$option_ex   = ( isset( $existing_pl[ $option ] ) ) ? $existing_pl[ $option ] : '';
			$mark_select = ( ! empty( $existing_pm ) && in_array( $option, $existing_pm, true ) ) ? 'checked' : '';
			$lists      .= "<li><label><input name='paymentOpts[]' type='checkbox' value='{$option}' {$mark_select}> {$option}</label>";
			$lists      .= ' - ';
			$lists      .= "<input type='text' class='subtext' name='kopt{$option}' id='kopt{$option}' value='{$option_ex}'></li>";
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
	 * @param array|null $existing Existing value (from orderstore) in the database.
	 * @return void Constructs on page where called.
	 */
	public function pin_status( ?array $existing = null ):void {
		$existing = ( ! empty( $existing ) ) ? $existing['kebabble-pin'] : false;
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

	/**
	 * Gets the existing details, or pulls through previous if enabled in settings.
	 *
	 * @param integer $post_id ID of the post in question.
	 * @return stdClass
	 */
	private function get_existing_details( int $post_id ):stdClass {
		$existing           = $this->orderstore->get( $post_id );
		$existing_company   = wp_get_post_terms( $post_id, 'kebabble_company' );
		$existing_collector = wp_get_post_terms( $post_id, 'kebabble_collector' );

		return (object) [
			'existing'           => $existing,
			'existing_company'   => $existing_company,
			'existing_collector' => $existing_collector,
		];
	}
}
