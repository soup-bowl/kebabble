<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@soupbowl.io>
 * @license MIT
 */

namespace Kebabble;

use Kebabble\Processes\Delete;
use Kebabble\Processes\Publish;
use Kebabble\Processes\Term\Save;
use Kebabble\Config\Registration;
use Kebabble\Config\Settings;
use Kebabble\Config\OrderFields;
use Kebabble\Config\TaxonomyFields;
use Kebabble\Config\Table;
use Kebabble\Config\Dashboard;
use Kebabble\API\Mention;

use WP_Error;
use WP_REST_Request;

/**
 * Handles and translates the hooks into kebabble class functions.
 */
class Hooks {
	/**
	 * Connects with the system required objects.
	 *
	 * @var Registration
	 */
	protected $registration;

	/**
	 * Publish processes for order posts.
	 *
	 * @var Publish
	 */
	protected $publish;

	/**
	 * Handles deletion of posted orders.
	 *
	 * @var Delete
	 */
	protected $delete;

	/**
	 * Settings page handler.
	 *
	 * @var Settings
	 */
	protected $settings;

	/**
	 * Fields for the order form.
	 *
	 * @var OrderFields
	 */
	protected $order_fields;

	/**
	 * Fields for the taxonomy.
	 *
	 * @var TaxonomyFields
	 */
	protected $taxonomy_fields;

	/**
	 * Table configurations.
	 *
	 * @var Table
	 */
	protected $table;

	/**
	 * Storage processing for saved term items.
	 *
	 * @var Save
	 */
	protected $save;

	/**
	 * Handles mentions from and to the Slack API.
	 *
	 * @var Mention
	 */
	protected $api_mention;

	/**
	 * Handles the Kebabble-related dashboard widgets.
	 *
	 * @var Dashboard
	 */
	protected $dashboard;

	/**
	 * Constructor.
	 *
	 * @param Registration   $registration    Connects with the system required objects.
	 * @param Publish        $publish         Publish processes for order posts.
	 * @param Delete         $delete          Handles deletion of posted orders.
	 * @param Settings       $settings        Settings page handler.
	 * @param OrderFields    $order_fields    Fields for the order form.
	 * @param TaxonomyFields $taxonomy_fields Fields for the taxonomy.
	 * @param Table          $table           Table configurations.
	 * @param Save           $save            Storage processing for saved term items.
	 * @param Mention        $api_mention     Handles mentions from and to the Slack API.
	 * @param Dashboard      $dashboard       Handles the Kebabble-related dashboard widgets.
	 */
	public function __construct(
			Registration $registration,
			Publish $publish,
			Delete $delete,
			Settings $settings,
			OrderFields $order_fields,
			TaxonomyFields $taxonomy_fields,
			Table $table,
			Save $save,
			Mention $api_mention,
			Dashboard $dashboard
		) {
		$this->registration    = $registration;
		$this->publish         = $publish;
		$this->delete          = $delete;
		$this->settings        = $settings;
		$this->order_fields    = $order_fields;
		$this->taxonomy_fields = $taxonomy_fields;
		$this->table           = $table;
		$this->save            = $save;
		$this->api_mention     = $api_mention;
		$this->dashboard       = $dashboard;
	}

	/**
	 * First executed function of the whole plugin.
	 *
	 * @return void
	 */
	public function main():void {
		// Register post type and data entries.
		$this->registration->hook_registration();
		$this->order_fields->hook_order_fields();
		$this->taxonomy_fields->hook_taxonomy_fields();

		// Order functionality.
		$this->publish->hook_publish();
		$this->delete->hook_deletion();

		// Taxonomy functionality.
		$this->save->hook_save_terms();

		// API Hooks.
		add_action( 'rest_api_init', [ &$this, 'api_endpoints' ] );

		// Admin area loaders.
		if ( is_admin() ) {
			$this->dashboard->hook_dashboard();

			// Settings API hooks.
			$this->settings->hook_settings();

			// Resource queue.
			add_action( 'admin_enqueue_scripts', [ &$this, 'enqueued_scripts' ] );

			// Tables.
			$this->table->hook_table();
		}
	}

	/**
	 * JavaSript and style loader used for plugin operation.
	 *
	 * @return void
	 */
	public function enqueued_scripts():void {
		if ( 'kebabble_orders' === get_current_screen()->id ) {
			wp_enqueue_style( 'kebabble-orders-css', plugins_url( '/../assets/orders.css', __FILE__ ), [], '1.1' );
			wp_enqueue_script( 'kebabble-orders-js', plugins_url( '/../assets/orders.js', __FILE__ ), [ 'jquery' ], '1.1', true );
		}
	}

	/**
	 * Registers the Kebabble API endpoints.
	 *
	 * @return void
	 */
	public function api_endpoints():void {
		// ?rest_route=/kebabble/v1/test
		// Just a comical test message, as I've had problems with the API before (permalinks, normally).
		register_rest_route(
			'kebabble/v1',
			'/test',
			[
				'methods'  => 'GET',
				'callback' => function ( WP_REST_Request $r ) {
					return new WP_Error( 'kebabble_called_test', "I'm a little teapot, short and stout. Here's my handle, here's my spout. When I see the tea cups hear me shout. Tip me up and pour me out.", [ 'status' => 418 ] );
				},
			]
		);

		// ?rest_route=/kebabble/v1/slack
		// All Slack-based comms via the Events API are sent to only one endpoint.
		register_rest_route(
			'kebabble/v1',
			'slack',
			[
				'methods'  => 'POST',
				'callback' => [ &$this->api_mention, 'main' ],
			]
		);
	}
}
