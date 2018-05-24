<?php namespace kebabble\processes;

defined( 'ABSPATH' ) or die( 'Operation not permitted.' );

use SlackClient\botclient;
use Carbon\Carbon;

class delete extends processes {
	public function __construct() {
		parent::__construct();
	}
	/**
	 * Hooks on to the order deletion process.
	 * @param integer $post_ID
	 * @param WP_Post $post_obj
	 * @return void
	 */
	public function handleDeletion($post_ID, $post_obj) {
		$existingMessage = get_post_meta( $post_ID, 'kebabble-slack-ts', true );
		$existingChannel = get_post_meta( $post_ID, 'kebabble-slack-channel', true );
		
		$this->slack->deleteMessage($existingMessage, $existingChannel);
		
		add_post_meta( $post_ID, 'kebabble-slack-deleted', true, true );
	}
	
	public function handleUndeletion($post_ID) {
		$post_obj = get_post($post_ID);
		$post_adt = unserialize(get_metadata('post', $post_ID)["kebabble-order"][0]);
		
		$timestamp = $this->sendToSlack($post_ID, $post_adt);
		
		update_post_meta( $post_ID, 'kebabble-slack-ts', $timestamp );
		
		if($post_adt['pin']) {
			$this->slack->pin($timestamp);
		} else {
			$this->slack->unpin($timestamp);
		}
	}
}