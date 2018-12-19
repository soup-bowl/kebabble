<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@revive.today>
 * @license MIT
 */

namespace Kebabble\Processes;

use Kebabble\Processes\Meta\Orderstore;
use Kebabble\Library\Slack;
use SlackClient\botclient;
use Carbon\Carbon;

use WP_Post;

/**
 * Removes the message from the Slack channel.
 */
class Delete {
	/**
	 * Slack API communication handler.
	 *
	 * @var Slack
	 */
	protected $slack;

	/**
	 * Stores and retrieves order data.
	 *
	 * @var Orderstore
	 */
	protected $orderstore;

	/**
	 * Constructor.
	 *
	 * @param Slack      $slack      Slack API communication handler.
	 * @param Orderstore $orderstore Stores and retrieves order data.
	 */
	public function __construct( Slack $slack, Orderstore $orderstore ) {
		$this->slack      = $slack;
		$this->orderstore = $orderstore;
	}

	/**
	 * Hooks on to the order deletion process.
	 *
	 * @param integer $post_ID  ID of the associated post.
	 * @param WP_Post $post_obj Whole post object.
	 * @return void No user feedback. Deletion result handled by WordPress.
	 */
	public function handleDeletion( int $post_ID, WP_Post $post_obj ):void {
		$existingMessage = get_post_meta( $post_ID, 'kebabble-slack-ts', true );
		$existingChannel = get_post_meta( $post_ID, 'kebabble-slack-channel', true );

		$this->slack->slack->deleteMessage( $existingMessage, $existingChannel );

		add_post_meta( $post_ID, 'kebabble-slack-deleted', true, true );
	}

	/**
	 * Re-posts a message that has been undeleted (often recovered from the bin).
	 *
	 * @param integer $post_ID ID of the associated post.
	 * @return void Message is re-posted, and new TS stored.
	 */
	public function handleUndeletion( int $post_ID ):void {
		$post_obj = get_post( $post_ID );
		$post_adt = $this->orderstore->get( $post_ID );

		$timestamp = $this->slack->sendToSlack( $post_ID, $post_adt );

		update_post_meta( $post_ID, 'kebabble-slack-ts', $timestamp );

		if ( $post_adt['pin'] ) {
			$this->slack->slack->pin( $timestamp );
		} else {
			$this->slack->slack->unpin( $timestamp );
		}
	}
}
