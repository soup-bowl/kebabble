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
	 * Stores and retrieves order data.
	 *
	 * @var Orderstore
	 */
	protected $orderstore;

	/**
	 * Pre-processing before publishing.
	 *
	 * @var Formatting
	 */
	protected $formatting;

	/**
	 * Constructor.
	 *
	 * @param Orderstore $orderstore Stores and retrieves order data.
	 * @param Formatting $formatting Pre-processing before publishing.
	 */
	public function __construct( Orderstore $orderstore, Formatting $formatting ) {
		$this->orderstore = $orderstore;
		$this->formatting = $formatting;
	}

	/**
	 * Hooks on to the order deletion process.
	 *
	 * @param integer $post_ID  ID of the associated post.
	 * @param WP_Post $post_obj Whole post object.
	 * @return void No user feedback. Deletion result handled by WordPress.
	 */
	public function handle_deletion( int $post_ID, WP_Post $post_obj ):void {
		$existing_message = get_post_meta( $post_ID, 'kebabble-slack-ts', true );
		$existing_channel = get_post_meta( $post_ID, 'kebabble-slack-channel', true );

		( new Slack() )->deleteMessage( $existing_message, $existing_channel );

		add_post_meta( $post_ID, 'kebabble-slack-deleted', true, true );
	}

	/**
	 * Re-posts a message that has been undeleted (often recovered from the bin).
	 *
	 * @param integer $post_ID ID of the associated post.
	 * @return void Message is re-posted, and new TS stored.
	 */
	public function handle_undeletion( int $post_ID ):void {
		$post_obj   = get_post( $post_ID );
		$post_adt   = $this->orderstore->get( $post_ID );
		
		$slack = new Slack();

		$timestamp = null;
		if ( $post_adt['override']['enabled'] ) {
			$timestamp = $slack->send_message( $post_adt['override']['message'] );
		} else {
			$timestamp = $slack->send_message( $this->formatting->status(
				$post_ID,
				$post_adt['food'],
				$post_adt['order'],
				$post_adt['driver'],
				(int) $post_adt['tax'],
				Carbon::parse( get_the_date( 'Y-m-d H:i:s', $post_ID ) ),
				( is_array( $post_adt['payment'] ) ) ? $post_adt['payment'] : [ $post_adt['payment'] ],
				$post_adt['paymentLink']
			));
		}

		update_post_meta( $post_ID, 'kebabble-slack-ts', $timestamp );

		if ( $post_adt['pin'] ) {
			$slack->pin( $timestamp );
		} else {
			$slack->unpin( $timestamp );
		}
	}
}
