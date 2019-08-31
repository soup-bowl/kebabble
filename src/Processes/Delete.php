<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@soupbowl.io>
 * @license MIT
 */

namespace Kebabble\Processes;

use Kebabble\Processes\Meta\Orderstore;
use Kebabble\Library\Slack;
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
	 * Communication handler for Slack.
	 *
	 * @var Slack
	 */
	protected $slack;

	/**
	 * Constructor.
	 *
	 * @param Orderstore $orderstore Stores and retrieves order data.
	 * @param Formatting $formatting Pre-processing before publishing.
	 * @param Slack      $slack      Communication handler for Slack.
	 */
	public function __construct( Orderstore $orderstore, Formatting $formatting, Slack $slack ) {
		$this->orderstore = $orderstore;
		$this->formatting = $formatting;
		$this->slack      = $slack;
	}

	/**
	 * Registers WordPress hooks.
	 *
	 * @return void
	 */
	public function hook_deletion() {
		add_action( 'trash_kebabble_orders', [ &$this, 'handle_deletion' ], 10, 2 );
		add_action( 'untrash_post', [ &$this, 'handle_undeletion' ], 10, 2 );
	}

	/**
	 * Hooks on to the order deletion process.
	 *
	 * @param integer $post_ID  ID of the associated post.
	 * @param WP_Post $post_obj Whole post object.
	 * @return void No user feedback. Deletion result handled by WordPress.
	 */
	public function handle_deletion( int $post_ID, WP_Post $post_obj ):void {
		$this->slack->remove_message(
			get_post_meta( $post_ID, 'kebabble-slack-ts', true ),
			get_post_meta( $post_ID, 'kebabble-slack-channel', true )
		);

		add_post_meta( $post_ID, 'kebabble-slack-deleted', true, true );
	}

	/**
	 * Re-posts a message that has been undeleted (often recovered from the bin).
	 *
	 * @param integer $post_ID ID of the associated post.
	 * @return void Message is re-posted, and new TS stored.
	 */
	public function handle_undeletion( int $post_ID ):void {
		$post_obj = get_post( $post_ID );
		$post_adt = $this->orderstore->get( $post_ID );

		$timestamp = null;
		if ( $post_adt['override']['enabled'] ) {
			$timestamp = $this->slack->send_message( $post_adt['override']['message'] );
		} else {
			$timestamp = $this->slack->send_message(
				$this->formatting->status(
					$post_ID,
					$post_adt['food'],
					$post_adt['order'],
					( ! empty( $post_adt['driver'] ) ) ? $post_adt['driver'] : wp_get_current_user()->display_name,
					(int) $post_adt['tax'],
					Carbon::parse( get_the_date( 'Y-m-d H:i:s', $post_ID ) ),
					( is_array( $post_adt['payment'] ) ) ? $post_adt['payment'] : [ $post_adt['payment'] ],
					$post_adt['paymentLink']
				)
			);
		}

		update_post_meta( $post_ID, 'kebabble-slack-ts', $timestamp );

		$this->slack->pin( $post_adt['pin'], $timestamp, get_option( 'kbfos_settings' )['kbfos_botchannel'] );
	}
}
