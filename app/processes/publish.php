<?php namespace kebabble\processes;

defined( 'ABSPATH' ) or die( 'Operation not permitted.' );

use kebabble\slack\message as slackmessage;
use kebabble\slack\formatting;
use kebabble\config\fields;

use Carbon\Carbon;

class publish extends process {
	protected $fields;
	public function __construct() {
		$this->fields = new fields();
	}
	/**
	 * Hooks on to the order publish process.
	 * @param integer $post_ID
	 * @param WP_Post $post_obj
	 * @todo improve calling ACF functions, requires an ACF update. See comment.
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
		
		$response = null;
		if ( $orderDetails['override']['enabled'] ) {
			// Custom Message.
			$slackMessage = new slackmessage;
			$response = json_decode( 
				$slackMessage->postMessage( 
					$orderDetails['override']['message'], 
					$existingMessage, 
					$existingChannel 
				) 
			);
		} else {
			// Generated message.

			$fm = new formatting;
			$slackMessage = new slackmessage;
			$response = json_decode( 
				$slackMessage->postMessage( 
					$fm->status(
						$orderDetails['food'],
						$orderDetails['order'],
						$orderDetails['driver'],
						Carbon::parse( get_the_date( 'Y-m-d H:i:s', $post_ID ) ),
						$orderDetails['payment']
					), 
					$existingMessage, 
					$existingChannel 
				) 
			);
		}
		if ($existingMessage == false) {
			add_post_meta( $post_ID, 'kebabble-slack-ts', $response->ts, true );
			add_post_meta( $post_ID, 'kebabble-slack-channel', $response->channel, true );
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
			'payment' => $response['paymentOpts']
		];
	}
}