<?php namespace kebabble\processes;

defined( 'ABSPATH' ) or die( 'Operation not permitted.' );

use kebabble\slack\message as slackmessage;
use kebabble\slack\formatting;
use kebabble\config\fields\fields;

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
		/**
		 * ACF is not yet called. As a result, we can't use ACF functionality
		 * to get info. Instead, we hook into the POST fields for now.
		 * 
		 * https://support.advancedcustomfields.com/forums/topic/checking-post-status/ 
		 */

		$existingMessage = get_post_meta( $post_ID, 'kebabble-slack-ts', true );
		$existingChannel = get_post_meta( $post_ID, 'kebabble-slack-channel', true );
		$existingMessage = ($existingMessage == "") ? false : $existingMessage;
		$existingChannel = ($existingChannel == "") ? false : $existingChannel;

		$cfFields = $this->fields->processOrderResponse( $_POST["fields"] );

		//die(var_dump( $cfFields/*get_option('kbfos_settings')*/ ));
		
		$response = null;
		if ( $cfFields->override ) {
			// Custom Message.

			$slackMessage = new slackmessage;
			$response = json_decode( 
				$slackMessage->postMessage( 
					$cfFields->message, 
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
						$cfFields->rolls, 
						$cfFields->dishes, 
						$cfFields->misc, 
						get_the_date( 'jS F', $post_ID ),
						$cfFields->driver,
						$cfFields->payment
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
			$data['post_title'] = "Kebab order # - " . Carbon::now()->format('d/m/Y'); 
		}

		return $data;
	}
}