<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@revive.today>
 * @license MIT
 */

namespace Kebabble;

use Kebabble\Processes\Delete;
use Kebabble\Processes\Publish;
use Kebabble\Processes\Term\Save;
use Kebabble\Config\Registration;
use Kebabble\Config\Settings;
use Kebabble\Config\OrderFields;
use Kebabble\Config\CompanyFields;
use Kebabble\API\Mention;

use WP_Term;
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
	 * Fields for the company taxonomy.
	 *
	 * @var CompanyFields
	 */
	protected $company_fields;

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
	 * Constructor.
	 *
	 * @param Registration  $registration   Connects with the system required objects.
	 * @param Publish       $publish        Publish processes for order posts.
	 * @param Delete        $delete         Handles deletion of posted orders.
	 * @param Settings      $settings       Settings page handler.
	 * @param OrderFields   $order_fields   Fields for the order form.
	 * @param CompanyFields $company_fields Fields for the company taxonomy.
	 * @param Save          $save           Storage processing for saved term items.
	 * @param Mention       $api_mention    Handles mentions from and to the Slack API.
	 */
	public function __construct( Registration $registration, Publish $publish, Delete $delete, Settings $settings, OrderFields $order_fields, CompanyFields $company_fields, Save $save, Mention $api_mention ) {
		$this->registration   = $registration;
		$this->publish        = $publish;
		$this->delete         = $delete;
		$this->settings       = $settings;
		$this->order_fields   = $order_fields;
		$this->company_fields = $company_fields;
		$this->save           = $save;
		$this->api_mention    = $api_mention;
	}

	/**
	 * First executed function of the whole plugin.
	 *
	 * @return void
	 */
	public function main():void {
		// Settings API hooks.
		add_action( 'admin_menu', [ &$this->settings, 'page' ] );
		add_action( 'admin_init', [ &$this->settings, 'settings' ] );

		// Register post type and data entries.
		add_action( 'init', [ &$this->registration, 'orders' ], 0 );
		add_action( 'add_meta_boxes_kebabble_orders', [ &$this->order_fields, 'order_options_setup' ] );
		add_action( 'kebabble_company_add_form_fields', [ &$this->company_fields, 'company_options_empty' ] );
		add_action( 'kebabble_company_edit_form_fields', [ &$this->company_fields, 'company_options' ] );

		// Resource queue.
		add_action( 'admin_enqueue_scripts', [ &$this, 'enqueued_scripts' ] );

		// Order functionality.
		add_action( 'publish_kebabble_orders', [ &$this->publish, 'hook_handle_publish' ], 10, 2 );
		add_filter( 'wp_insert_post_data', [ &$this->publish, 'change_title' ], 99, 2 );
		add_action( 'trash_kebabble_orders', [ &$this->delete, 'handle_deletion' ], 10, 2 );
		add_action( 'untrash_post', [ &$this->delete, 'handle_undeletion' ], 10, 2 );

		// Company functionality.
		add_action( 'created_kebabble_company', [ &$this->save, 'save_custom_company_details' ] );
		add_action( 'edited_kebabble_company', [ &$this->save, 'save_custom_company_details' ] );

		// API Hooks.
		add_action( 'rest_api_init', [ &$this, 'api_endpoints' ] );
	}

	/**
	 * JavaSript and style loader used for plugin operation.
	 *
	 * @return void
	 */
	public function enqueued_scripts():void {
		if ( 'kebabble_orders' === get_current_screen()->id ) {
			wp_enqueue_style( 'kebabble-orders-css', plugins_url( '/../resource/orders.css', __FILE__ ), [], '1.1' );
			wp_enqueue_script( 'kebabble-orders-js', plugins_url( '/../resource/orders.js', __FILE__ ), [ 'jquery' ], '1.0', true );
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
