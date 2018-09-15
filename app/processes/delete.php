<?php
/**
 * Removes the message from the Slack channel.
 *
 * @package kebabble
 * @author soup-bowl
 */

namespace kebabble\processes;

use kebabble\library\slack;
use kebabble\config\fields;
use SlackClient\botclient;
use Carbon\Carbon;

/**
 * Removes the message from the Slack channel.
 */
class delete {
	/**
	 * Slack API communication handler.
	 *
	 * @var slack
	 */
	protected $slack;

	/**
	 * Field display class.
	 *
	 * @var fields
	 */
	protected $fields;

	/**
	 * Constructor.
	 *
	 * @param slack  $slack  Slack API communication handler.
	 * @param fields $fields Field display class.
	 */
	public function __construct( slack $slack, fields $fields ) {
		$this->slack  = $slack;
		$this->fields = $fields;
	}

	/**
	 * Hooks on to the order deletion process.
	 *
	 * @param integer $post_ID  ID of the associated post.
	 * @param WP_Post $post_obj Whole post object.
	 * @return void
	 */
	public function handleDeletion( $post_ID, $post_obj ) {
		$existingMessage = get_post_meta( $post_ID, 'kebabble-slack-ts', true );
		$existingChannel = get_post_meta( $post_ID, 'kebabble-slack-channel', true );

		$this->slack->slack->deleteMessage( $existingMessage, $existingChannel );

		add_post_meta( $post_ID, 'kebabble-slack-deleted', true, true );
	}

	/**
	 * Re-posts a message that has been undeleted (often recovered from the bin).
	 *
	 * @param integer $post_ID ID of the associated post.
	 * @return void
	 */
	public function handleUndeletion( $post_ID ) {
		$post_obj = get_post( $post_ID );
		$post_adt = get_post_meta( $post_ID, 'kebabble-order', true );

		$timestamp = $this->slack->sendToSlack( $post_ID, $post_adt );

		update_post_meta( $post_ID, 'kebabble-slack-ts', $timestamp );

		if ( $post_adt['pin'] ) {
			$this->slack->slack->pin( $timestamp );
		} else {
			$this->slack->slack->unpin( $timestamp );
		}
	}
}
