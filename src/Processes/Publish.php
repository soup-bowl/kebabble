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
	 * Hooks on to the order publish process.
	 *
	 * @param integer $post_ID  ID of the associated post.
	 * @param WP_Post $post_obj Whole post object.
	 * @return void
	 */
	public function hook_handle_publish( int $post_ID, WP_Post $post_obj ):void {
		$this->handle_publish( $post_obj, true );
	}

	/**
	 * Post-process handling and formatting for the message.
	 *
	 * @param WP_Post $post_obj Whole post object.
	 * @return void
	 */
	public function handle_publish( WP_Post $post_obj, bool $set_order = true ):void {
		// I'm sure there's a million better ways to do this, but for now it suffices.
		if ( empty( get_post_meta( $post_obj->ID, 'kebabble-slack-deleted', true ) ) ) {
			$order_details = ( $set_order ) ? $this->orderstore->set( $post_obj->ID ) : $this->orderstore->get( $post_obj->ID );

			$existing_message = get_post_meta( $post_obj->ID, 'kebabble-slack-ts', true );
			$existing_channel = get_post_meta( $post_obj->ID, 'kebabble-slack-channel', true );

			$slack = new Slack( $existing_channel );

			$timestamp = null;
			if ( $order_details['override']['enabled'] ) {
				$timestamp = $slack->send_message( $order_details['override']['message'], $existing_message, $existing_channel );
			} else {
				$timestamp = $slack->send_message(
					$this->formatting->status(
						$post_obj->ID,
						$order_details['food'],
						$order_details['order'],
						$order_details['driver'],
						(int) $order_details['tax'],
						Carbon::parse( get_the_date( 'Y-m-d H:i:s', $post_obj->ID ) ),
						( is_array( $order_details['payment'] ) ) ? $order_details['payment'] : [ $order_details['payment'] ],
						$order_details['paymentLink']
					),
					$existing_message,
					$existing_channel
				);
			}

			if ( $order_details['pin'] ) {
				$slack->pin( $timestamp );
			} else {
				$slack->unpin( $timestamp );
			}

			if ( empty( $existing_message ) ) {
				add_post_meta( $post_obj->ID, 'kebabble-slack-ts', $timestamp, true );
				add_post_meta( $post_obj->ID, 'kebabble-slack-channel', get_option( 'kbfos_settings' )['kbfos_botchannel'], true );
			}
		} else {
			delete_post_meta( $post_obj->ID, 'kebabble-slack-deleted' );
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
