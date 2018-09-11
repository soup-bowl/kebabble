<?php namespace kebabble\processes;

use kebabble\slack;
use kebabble\config\fields;
use SlackClient\botclient;
use Carbon\Carbon;

class delete {
	protected $slack;
	protected $fields;
	public function __construct( slack $slack, fields $fields ) {
		$this->slack  = $slack;
		$this->fields = $fields;
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
		
		$this->slack->slack->deleteMessage($existingMessage, $existingChannel);
		
		add_post_meta( $post_ID, 'kebabble-slack-deleted', true, true );
	}
	
	public function handleUndeletion($post_ID) {
		$post_obj = get_post($post_ID);
		$post_adt = unserialize(get_metadata('post', $post_ID)["kebabble-order"][0]);
		
		$timestamp = $this->slack->sendToSlack($post_ID, $post_adt);
		
		update_post_meta( $post_ID, 'kebabble-slack-ts', $timestamp );
		
		if($post_adt['pin']) {
			$this->slack->slack->pin($timestamp);
		} else {
			$this->slack->slack->unpin($timestamp);
		}
	}
}