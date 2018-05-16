<?php namespace kebabble\processes;

defined( 'ABSPATH' ) or die( 'Operation not permitted.' );

use kebabble\slack\message as slackmessage;
use kebabble\slack\formatting;
use kebabble\config\fields;
use SlackClient\botclient;

use Carbon\Carbon;

class publish extends process {
	protected $fields;
	protected $slack;
	public function __construct() {
		$this->fields = new fields();
		$this->slack  = new botclient(
			get_option('kbfos_settings')["kbfos_botkey"], 
			get_option('kbfos_settings')["kbfos_botchannel"]
		);
	}
	/**
	 * Hooks on to the order publish process.
	 * @param integer $post_ID
	 * @param WP_Post $post_obj
	 * @return void
	 */
	public function handlePublish($post_ID, $post_obj) {
		// Save plugin-owned custom meta.
		update_post_meta( $post_ID, 'kebabble-order', $this->formatOrderResponse( $_POST ) );
		$orderDetails = get_post_meta( $post_ID, 'kebabble-order', true );
		
		$existingMessage = get_post_meta( $post_ID, 'kebabble-slack-ts', true );
		$existingChannel = get_post_meta( $post_ID, 'kebabble-slack-channel', true );
		$existingMessage = ($existingMessage == "") ? false : $existingMessage;
		$existingChannel = ($existingChannel == "") ? false : $existingChannel;
		
		$timestamp = null;
		if ( $orderDetails['override']['enabled'] ) {
			// Custom Message.
			$timestamp = $this->slack->message($orderDetails['override']['message'], $existingMessage);
		} else {
			// Generated message.
			$timestamp = $this->slack->message(
				(new formatting)->status(
					$orderDetails['food'],
					$orderDetails['order'],
					$orderDetails['driver'],
					Carbon::parse( get_the_date( 'Y-m-d H:i:s', $post_ID ) ),
					$orderDetails['payment']
				),
				$existingMessage
			);
		}
		
		if($orderDetails['pin']) {
			$this->slack->pin($timestamp);
		} else {
			$this->slack->unpin($timestamp);
		}
		
		if ($existingMessage == false) {
			add_post_meta( $post_ID, 'kebabble-slack-ts', $timestamp, true );
			add_post_meta( $post_ID, 'kebabble-slack-channel', get_option('kbfos_settings')["kbfos_botchannel"], true );
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
			$contents = $this->formatOrderResponse($_POST);
			
			if ( $contents['override']['enabled'] ) {
				$data['post_title'] = "Custom message"; 
			} else {
				$data['post_title'] = "{$contents['food']} order - " . Carbon::now()->format('d/m/Y');  
			}
		}
		
		return $data;
	}
	
	/**
	 * Formats the POST outcome into an array for storage.
	 * @param array $response Input the outcome of the return (aka, $_POST).
	 * @return array
	 */
	public function formatOrderResponse($response) {
		return [
			'override' => [
				'enabled' => empty($response['kebabbleCustomMessageEnabled']) ? false : true,
				'message' => $response['kebabbleCustomMessageEntry']
			],
			'food'    => $response['kebabbleOrderTypeSelection'],
			'order'   => $response['kebabbleOrders'],
			'driver'  => $response['kebabbleDriver'],
			'payment' => $response['paymentOpts'],
			'pin'     => empty($response['pinState']) ? false : true
		];
	}
}