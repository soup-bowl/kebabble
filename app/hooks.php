<?php
/**
 * Handles and translates the hooks into kebabble class functions.
 *
 * @todo Still some hook functionality embedded within, which needs work.
 * @package kebabble
 * @author soup-bowl
 */

namespace kebabble;

use kebabble\config\taxonomy;
use kebabble\processes\delete;
use kebabble\processes\publish;
use kebabble\processes\term\save;
use kebabble\config\settings;
use kebabble\config\order_fields;
use kebabble\config\company_fields;

use WP_Term;

/**
 * Handles and translates the hooks into kebabble class functions.
 */
class hooks {
	/**
	 * Connects with the system required taxonomies.
	 *
	 * @var taxonomy
	 */
	protected $taxonomy;

	/**
	 * Publish processes for order posts.
	 *
	 * @var publish
	 */
	protected $publish;

	/**
	 * Handles deletion of posted orders.
	 *
	 * @var delete
	 */
	protected $delete;

	/**
	 * Settings page handler.
	 *
	 * @var settings
	 */
	protected $settings;

	/**
	 * Fields for the order form.
	 *
	 * @var order_fields
	 */
	protected $order_fields;

	/**
	 * Fields for the company taxonomy.
	 *
	 * @var company_fields
	 */
	protected $company_fields;

	/**
	 * Storage processing for saved term items.
	 *
	 * @var save
	 */
	protected $save;

	/**
	 * Constructor.
	 *
	 * @param taxonomy       $taxonomy       Connects with the system required taxonomies.
	 * @param publish        $publish        Publish processes for order posts.
	 * @param delete         $delete         Handles deletion of posted orders.
	 * @param settings       $settings       Settings page handler.
	 * @param order_fields   $order_fields   Fields for the order form.
	 * @param company_fields $company_fields Fields for the company taxonomy.
	 * @param save           $save           Storage processing for saved term items.
	 */
	public function __construct( taxonomy $taxonomy, publish $publish, delete $delete, settings $settings, order_fields $order_fields, company_fields $company_fields, save $save ) {
		$this->taxonomy       = $taxonomy;
		$this->publish        = $publish;
		$this->delete         = $delete;
		$this->settings       = $settings;
		$this->order_fields   = $order_fields;
		$this->company_fields = $company_fields;
		$this->save           = $save;
	}

	/**
	 * First executed function of the whole plugin.
	 *
	 * @return void
	 */
	public function main() {
		$this->settings();

		// Register post type and data entries.
		add_action( 'init', [ &$this->taxonomy, 'orders' ], 0 );
		add_action( 'add_meta_boxes_kebabble_orders', [ &$this->order_fields, 'orderOptionsSetup' ] );
		add_action(
			'kebabble_company_add_form_fields',
			function() {
				$this->company_fields->companyOptionsSetup();
			}
		);
		add_action(
			'kebabble_company_edit_form_fields',
			function( WP_Term $term ) {
				$this->company_fields->companyOptionsSetup( $term->term_id );
			}
		);

		// Resource queue.
		add_action(
			'admin_enqueue_scripts',
			function() {
				$this->enqueuedScripts();
			}
		);

		// Order functionality.
		add_action( 'publish_kebabble_orders', [ &$this->publish, 'handlePublish' ], 10, 2 );
		add_filter( 'wp_insert_post_data', [ &$this->publish, 'changeTitle' ], 99, 2 );
		add_action( 'trash_kebabble_orders', [ &$this->delete, 'handleDeletion' ], 10, 2 );
		add_action( 'untrash_post', [ &$this->delete, 'handleUndeletion' ], 10, 2 );

		// Company functionality.
		add_action( 'created_kebabble_company', [ &$this->save, 'saveCustomCompanyDetails' ] );
		add_action( 'edited_kebabble_company', [ &$this->save, 'saveCustomCompanyDetails' ] );
	}

	/**
	 * Settings-related hook manager.
	 *
	 * @return void
	 */
	private function settings() {
		add_action( 'admin_menu', [ &$this->settings, 'page' ] );
		add_action( 'admin_init', [ &$this->settings, 'settings' ] );
	}

	/**
	 * JavaSript and style loader used for plugin operation.
	 *
	 * @return void
	 */
	private function enqueuedScripts() {
		if ( 'kebabble_orders' === get_current_screen()->id ) {
			wp_enqueue_style( 'kebabble-orders-css', plugins_url( '/../resource/orders.css', __FILE__ ), [], '1.1' );
			wp_enqueue_script( 'kebabble-orders-js', plugins_url( '/../resource/orders.js', __FILE__ ), [ 'jquery' ], '1.0', true );
		}
	}

}
