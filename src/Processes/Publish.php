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
use Kebabble\Processes\Term\Save;
use Kebabble\Processes\Formatting;
use Kebabble\Library\Slack;
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
	public function hook_publish() {
		add_action( 'publish_kebabble_orders', [ &$this, 'hook_handle_publish' ], 10, 2 );
		add_filter( 'wp_insert_post_data', [ &$this, 'change_title' ], 99, 2 );
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
	 * @param WP_Post $post_obj  Whole post object.
	 * @param boolean $set_order Update the stored order in the process (also checks meta '_kebabble-dnr').
	 * @return void
	 */
	public function handle_publish( WP_Post $post_obj, bool $set_order = true ):void {
		// I'm sure there's a million better ways to do this, but for now it suffices.
		if ( empty( get_post_meta( $post_obj->ID, 'kebabble-slack-deleted', true ) ) ) {
			if ( $set_order ) {
				$no_rep = get_post_meta( $post_obj->ID, '_kebabble-dnr' );
				if ( ! empty( $no_rep ) ) {
					$set_order = false;
					delete_post_meta( $post_obj->ID, '_kebabble-dnr' );
				}
			}

			$order_details = ( $set_order ) ? $this->orderstore->set( $post_obj->ID ) : $this->orderstore->get( $post_obj->ID );

			$existing_message = get_post_meta( $post_obj->ID, 'kebabble-slack-ts', true );
			$existing_channel = get_post_meta( $post_obj->ID, 'kebabble-slack-channel', true );

			if ( empty( $existing_channel ) ) {
				$existing_channel = ( isset( $_POST['kebabbleOverrideChannel'] ) ) ? $_POST['kebabbleOverrideChannel'] : get_option( 'kbfos_settings' )['kbfos_botchannel'];
			}

			$timestamp = null;
			if ( $order_details['kebabble-is-custom'] ) {
				$timestamp = $this->slack->send_message(
					$this->slack->html_to_slack_string( $order_details['kebabble-custom-message'] ),
					$existing_message,
					$existing_channel
				);
			} else {
				$collector = $this->get_collector_details( $post_obj->ID );

				if ( ! empty( $collector ) ) {
					$collector_name  = $collector['name'];
					$collector_tax   = $collector['tax'];
					$collector_popts = $collector['payment_opts'];
				} else {
					$collector_name  = ( ! empty( $order_details['kebabble-driver'] ) ) ? $order_details['kebabble-driver'] : wp_get_current_user()->display_name;
					$collector_tax   = (int) $order_details['kebabble-tax'];
					$collector_popts = ( is_array( $order_details['kebabble-payment'] ) ) ? $order_details['kebabble-payment'] : [ $order_details['kebabble-payment'] ];
				}

				$timestamp = $this->slack->send_message(
					$this->formatting->status(
						$post_obj->ID,
						$order_details['kebabble-food'],
						( ! empty( $order_details['kebabble-order'] ) ) ? $order_details['kebabble-order'] : [],
						$collector_name,
						$collector_tax,
						Carbon::parse( get_the_date( 'Y-m-d H:i:s', $post_obj->ID ) ),
						$collector_popts,
						$order_details['kebabble-payment-link']
					),
					$existing_message,
					$existing_channel
				);
			}

			$this->slack->pin( $order_details['kebabble-pin'], $timestamp, $existing_channel );

			if ( empty( $existing_message ) ) {
				add_post_meta( $post_obj->ID, 'kebabble-slack-ts', $timestamp, true );
				add_post_meta( $post_obj->ID, 'kebabble-slack-channel', $existing_channel, true );
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
				if ( $contents['kebabble-is-custom'] ) {
					$message             = $contents['kebabble-custom-message'];
					$data['post_title']  = 'Custom message - "';
					$data['post_title'] .= ( strlen( $message ) > 25 ) ? substr( $message, 0, 25 ) . '...' : $message;
					$data['post_title'] .= '"';
				} else {
					$data['post_title'] = "{$contents['kebabble-food']} order - " . Carbon::now()->format( 'd/m/Y' );
				}
			}
		}

		return $data;
	}

	/**
	 * Gets stored collector details, if available.
	 *
	 * @param integer $post_id Post ID you want the details from.
	 * @return array|null
	 */
	private function get_collector_details( int $post_id ):?array {
		$collector = null;
		if ( ! empty( wp_get_object_terms( $post_id, 'kebabble_collector' ) ) ) {
			$collector = wp_get_object_terms( $post_id, 'kebabble_collector' )[0];
		} else {
			return null;
		}

		$tax       = get_term_meta( $collector->term_id, 'kebabble_collector_tax', true );
		$slackcode = get_term_meta( $collector->term_id, 'keabble_collector_slackcode', true );
		$payopts   = get_term_meta( $collector->term_id, 'kebabble_collector_payment_methods', true );

		return [
			'name'         => ( empty( $slackcode ) ) ? $collector->name : "<@{$slackcode}>",
			'tax'          => $tax,
			'payment_opts' => $payopts,
		];
	}
}
