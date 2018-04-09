<?php namespace kebabble;

defined( 'ABSPATH' ) or die( 'Operation not permitted.' );

use kebabble\processes\publish;
use kebabble\settings;
use kebabble\config\fields;

class hooks {
	protected $publish;
	protected $settings;
	protected $fields;

	public function __construct() {
		$this->publish       = new publish();
		$this->settings      = new settings();
		$this->fields        = new fields();
		
	}

	public function main() {
		$this->settings();
		
		add_action( 'add_meta_boxes_kebabble_orders', [&$this->fields, 'orderOptionsSetup'] );
		
		add_action( 'admin_enqueue_scripts', function() { $this->enqueuedScripts(); });
		
		add_action( 'init', ['kebabble\config\taxonomy', 'orders'], 0 );
		
		add_action( 'publish_kebabble_orders', array(&$this->publish, 'handlePublish'), 10, 2 );
		add_filter( 'wp_insert_post_data', array(&$this->publish, 'changeTitle'), '99', 2 );
	}

	private function settings() {
		$newSettings = new settings();
		add_action( 'admin_menu', [&$this->settings, 'page'] );
		add_action( 'admin_init', [&$this->settings, 'settings'] );
	}
	
	private function enqueuedScripts() {
		wp_enqueue_style( 'kebabble-orders-css', plugins_url('/../resource/orders.css', __FILE__), array(), '1.1' );
	}
	
}