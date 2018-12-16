<?php
/**
 * Publishes authored orders to the Slack channel.
 *
 * @package kebabble
 * @author soup-bowl
 */

namespace kebabble\processes;

use kebabble\processes\term\save;
use kebabble\processes\formatting;
use kebabble\library\slack;
use kebabble\config\fields;
use SlackClient\botclient;
use Carbon\Carbon;

/**
 * Publishes authored orders to the Slack channel.
 */
class publish {
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
	 * @param save   $company_save 
	 */
	public function __construct( slack $slack, fields $fields ) {
		$this->slack        = $slack;
		$this->fields       = $fields;
	}

	/**
	 * Hooks on to the order publish process.
	 *
	 * @param integer $post_ID  ID of the associated post.
	 * @param WP_Post $post_obj Whole post object.
	 * @return void
	 */
	public function handlePublish( $post_ID, $post_obj ) {
		// I'm sure there's a million better ways to do this, but for now it suffices.
		if ( empty( get_post_meta( $post_ID, 'kebabble-slack-deleted', true ) ) ) {
			update_post_meta( $post_ID, 'kebabble-order', $this->slack->formatOrderResponse( $_POST ) );
			$orderDetails = get_post_meta( $post_ID, 'kebabble-order', true );

			$existingMessage = get_post_meta( $post_ID, 'kebabble-slack-ts', true );
			$existingChannel = get_post_meta( $post_ID, 'kebabble-slack-channel', true );

			$timestamp = $this->slack->sendToSlack( $post_ID, $orderDetails, $existingMessage, $existingChannel );

			if ( $orderDetails['pin'] ) {
				$this->slack->slack->pin( $timestamp );
			} else {
				$this->slack->slack->unpin( $timestamp );
			}

			if ( false == $existingMessage ) {
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
	public function changeTitle( $data, $postarr ) {
		if ( 'kebabble_orders' === $data['post_type'] && 'publish' === $data['post_status'] ) {
			$contents = $this->slack->formatOrderResponse( $_POST );

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
