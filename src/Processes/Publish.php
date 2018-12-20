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
use Kebabble\Processes\Term\Save;
use Kebabble\Processes\Formatting;
use Kebabble\Library\Slack;
use SlackClient\botclient;
use Carbon\Carbon;

use WP_Post;

/**
 * Publishes authored orders to the Slack channel.
 */
class Publish {
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
	 * Hooks on to the order publish process.
	 *
	 * @param integer $post_ID  ID of the associated post.
	 * @param WP_Post $post_obj Whole post object.
	 * @return void
	 */
	public function handle_publish( int $post_ID, WP_Post $post_obj ):void {
		// I'm sure there's a million better ways to do this, but for now it suffices.
		if ( empty( get_post_meta( $post_ID, 'kebabble-slack-deleted', true ) ) ) {
			$order_details = $this->orderstore->set( $post_ID );

			$existing_message = get_post_meta( $post_ID, 'kebabble-slack-ts', true );
			$existing_channel = get_post_meta( $post_ID, 'kebabble-slack-channel', true );

			$timestamp = $this->slack->send_to_slack( $post_ID, $order_details, $existing_message, $existing_channel );

			if ( $order_details['pin'] ) {
				$this->slack->slack->pin( $timestamp );
			} else {
				$this->slack->slack->unpin( $timestamp );
			}

			if ( false === $existing_message ) {
				add_post_meta( $post_ID, 'kebabble-slack-ts', $timestamp, true );
				add_post_meta( $post_ID, 'kebabble-slack-channel', get_option( 'kbfos_settings' )['kbfos_botchannel'], true );
			}
		} else {
			delete_post_meta( $post_ID, 'kebabble-slack-deleted' );
		}
	}

	/**
	 * Changes the kebab order title.
	 *
	 * @param array $data    Clean, must be returned.
	 * @param array $postarr Unclean return, apparently.
	 * @return array $data
	 */
	public function change_title( array $data, array $postarr ):array {
		if (
			isset( $_POST['post_ID'], $_POST['kebabbleNonce'] ) &&
			false !== wp_verify_nonce( sanitize_key( $_POST['kebabbleNonce'] ), 'kebabble_nonce' ) &&
			'kebabble_orders' === $data['post_type'] &&
			'publish' === $data['post_status']
			) {
			$post_id  = intval( wp_unslash( $_POST['post_ID'] ) );
			$contents = $this->orderstore->set( $post_id );

			if ( false !== $contents ) {
				if ( $contents['override']['enabled'] ) {
					$message             = $contents['override']['message'];
					$data['post_title']  = 'Custom message - "';
					$data['post_title'] .= ( strlen( $message ) > 25 ) ? substr( $message, 0, 25 ) . '...' : $message;
					$data['post_title'] .= '"';
				} else {
					$data['post_title'] = "{$contents['food']} order - " . Carbon::now()->format( 'd/m/Y' );
				}
			}
		}

		return $data;
	}
}
