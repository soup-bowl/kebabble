<?php namespace kebabble\processes;

defined( 'ABSPATH' ) or die( 'Operation not permitted.' );

use SlackClient\botclient;

use Carbon\Carbon;

class delete {
	protected $slack;
	public function __construct() {
		$this->slack  = new botclient(
			get_option('kbfos_settings')["kbfos_botkey"], 
			get_option('kbfos_settings')["kbfos_botchannel"]
		);
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
	}
}