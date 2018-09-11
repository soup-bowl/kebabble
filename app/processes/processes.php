<?php namespace kebabble\processes;

use SlackClient\botclient;
use Carbon\Carbon;

class processes {
	public $slack;
	public function __construct() {
		$this->slack = new botclient(
			get_option('kbfos_settings')["kbfos_botkey"], 
			get_option('kbfos_settings')["kbfos_botchannel"]
		);
	}
	/**
	 * Formats the POST outcome into an array for storage.
	 * @param array $response Input the outcome of the return (aka, $_POST).
	 * @return array|boolean
	 */
	public function formatOrderResponse($response) {
		if(!empty($response)) {
			return [
				'override' => [
					'enabled' => empty($response['kebabbleCustomMessageEnabled']) ? false : true,
					'message' => $response['kebabbleCustomMessageEntry']
				],
				'food'    => $response['kebabbleOrderTypeSelection'],
				'order'   => $response['kebabbleOrders'],
				'driver'  => $response['kebabbleDriver'],
				'tax'     => $response['kebabbleDriverTax'],
				'payment' => $response['paymentOpts'],
				'pin'     => empty($response['pinState']) ? false : true
			];
		} else {
			return false;
		}
	}
	
	/**
	 * Sends contents over to the Slack message API.
	 */
	public function sendToSlack($id, $foResponse, $existingTimestamp = false) {
		$timestamp = null;
		if ( $foResponse['override']['enabled'] ) {
			// Custom Message.
			$timestamp = $this->slack->message($foResponse['override']['message'], $existingTimestamp);
		} else {
			// Generated message.
			$timestamp = $this->slack->message(
				(new formatting)->status(
					$foResponse['food'],
					$foResponse['order'],
					$foResponse['driver'],
					$foResponse['tax'],
					Carbon::parse( get_the_date( 'Y-m-d H:i:s', $id ) ),
					$foResponse['payment']
				),
				$existingTimestamp
			);
		}
		
		return $timestamp;
	}
}