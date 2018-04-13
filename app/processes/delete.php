<?php namespace kebabble\processes;

defined( 'ABSPATH' ) or die( 'Operation not permitted.' );

use kebabble\slack\message as slackmessage;
use kebabble\slack\formatting;
use kebabble\config\fields;

use Carbon\Carbon;

class delete extends process {
	protected $fields;
	public function __construct() {
		$this->fields = new fields();
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
		$existingMessage = ($existingMessage == "") ? false : $existingMessage;
		$existingChannel = ($existingChannel == "") ? false : $existingChannel;
        
        $slackMessage = new slackmessage;
        
        $resp = $slackMessage->deleteMessage($existingMessage, $existingChannel);
	}
}