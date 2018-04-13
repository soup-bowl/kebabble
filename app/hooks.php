<?php namespace kebabble;

defined( 'ABSPATH' ) or die( 'Operation not permitted.' );

use kebabble\config\taxonomy;
use kebabble\processes\delete;
use kebabble\processes\publish;
use kebabble\settings;
use kebabble\config\fields;

class hooks {
	protected $taxonomy;
	protected $publish;
	protected $delete;
	protected $settings;
	protected $fields;

	public function __construct() {
		$this->taxonomy = new taxonomy();
		$this->publish  = new publish();
		$this->delete   = new delete();
		$this->settings = new settings();
		$this->fields   = new fields();
		
	}

	public function main() {
		$this->settings();
		
		// Register post type and data entries.
		add_action( 'init', [&$this->taxonomy, 'orders'], 0 );
		add_action( 'add_meta_boxes_kebabble_orders', [&$this->fields, 'orderOptionsSetup'] );
		
		// Resource queue.
		add_action( 'admin_enqueue_scripts', function() { $this->enqueuedScripts(); });
		
		// Order functionalities.
		add_action( 'publish_kebabble_orders', [&$this->publish, 'handlePublish'], 10, 2 );
		add_filter( 'wp_insert_post_data',     [&$this->publish, 'changeTitle'],   99, 2 );
		add_action( 'trash_kebabble_orders',   [&$this->delete, 'handleDeletion'], 10, 2 );
	}

	private function settings() {
		$newSettings = new settings();
		add_action( 'admin_menu', [&$this->settings, 'page'] );
		add_action( 'admin_init', [&$this->settings, 'settings'] );
	}
	
	private function enqueuedScripts() {
		if(get_current_screen()->id == 'kebabble_orders') {
			wp_enqueue_style( 'kebabble-orders-css', plugins_url('/../resource/orders.css', __FILE__), array(), '1.1' );
			wp_enqueue_script('kebabble-orders-js', plugins_url('/../resource/orders.js', __FILE__), array('jquery'));
		}
	}
	
}