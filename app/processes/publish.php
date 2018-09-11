<?php namespace kebabble\processes;

use kebabble\processes\formatting;
use kebabble\slack;
use kebabble\config\fields;
use SlackClient\botclient;
use Carbon\Carbon;

class publish {
	protected $slack;
	protected $fields;
	public function __construct( slack $slack, fields $fields ) {
		$this->slack  = $slack;
		$this->fields = $fields;
	}
	/**
	 * Hooks on to the order publish process.
	 * @param integer $post_ID
	 * @param WP_Post $post_obj
	 * @return void
	 */
	public function handlePublish($post_ID, $post_obj) {
		// I'm sure there's a million better ways to do this, but for now it suffices.
		if (empty( get_post_meta( $post_ID, 'kebabble-slack-deleted', true ) )) {
			update_post_meta( $post_ID, 'kebabble-order', $this->slack->formatOrderResponse( $_POST ) );
			$orderDetails = get_post_meta( $post_ID, 'kebabble-order', true );
			
			$existingMessage = get_post_meta( $post_ID, 'kebabble-slack-ts', true );
			$existingChannel = get_post_meta( $post_ID, 'kebabble-slack-channel', true );
			
			$timestamp = $this->slack->sendToSlack($post_ID, $orderDetails, $existingMessage);
			
			if($orderDetails['pin']) {
				$this->slack->slack->pin($timestamp);
			} else {
				$this->slack->slack->unpin($timestamp);
			}
			
			if ($existingMessage == false) {
				add_post_meta( $post_ID, 'kebabble-slack-ts', $timestamp, true );
				add_post_meta( $post_ID, 'kebabble-slack-channel', get_option('kbfos_settings')["kbfos_botchannel"], true );
			}
		} else {
			delete_post_meta( $post_ID, 'kebabble-slack-deleted' );
		}
	}

	/**
	 * Changes the kebab order title.
	 * @param array $data Clean, must be returned.
	 * @param array $postarr
	 * @return array $data
	 */
	public function changeTitle($data, $postarr) {	
		if($data['post_type'] == 'kebabble_orders' && $data['post_status'] == 'publish') {
			$contents = $this->slack->formatOrderResponse($_POST);
			
			if ($contents !== false) {
				if ( $contents['override']['enabled'] ) {
					$data['post_title'] = "Custom message"; 
				} else {
					$data['post_title'] = "{$contents['food']} order - " . Carbon::now()->format('d/m/Y');  
				}
			}
		}
		
		return $data;
	}
}