<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@revive.today>
 * @license MIT
 */

namespace kebabble\library;

use kebabble\processes\formatting;

use SlackClient\botclient;
use Carbon\Carbon;

/**
 * Intermediary between Kebabble WP and Slack.
 */
class slack {
	/**
	 * Pre-processing before publishing.
	 *
	 * @var formatting
	 */
	public $formatting;

	/**
	 * Slack API communication handler.
	 *
	 * @var slack
	 */
	public $slack;

	/**
	 * Constructor.
	 *
	 * @param formatting $formatting Pre-processing before publishing.
	 */
	public function __construct( formatting $formatting ) {
		$this->formatting = $formatting;
		$this->slack      = $this->generateSlackbot();
	}

	/**
	 * Sends contents over to the Slack message API.
	 *
	 * @param integer $id                 Post ID.
	 * @param array   $order              Order array to be displayed on Slack.
	 * @param boolean $existing_timestamp If an existing timestamp is passed, that message is modified.
	 * @param string  $override_channel   Change the Slack channel, if desired.
	 * @return string Unique timestamp of the message, used for editing.
	 */
	public function sendToSlack( int $id, array $order, ?string $existing_timestamp = null, ?string $override_channel = null ):string {
		$so = $this->slack;
		if ( ! empty( $override_channel ) ) {
			$so = $this->generateSlackbot( $override_channel );
		}

		$timestamp = null;
		if ( $order['override']['enabled'] ) {
			// Custom Message.
			$timestamp = $so->message( $order['override']['message'], ( ! empty( $existing_timestamp ) ) ? $existing_timestamp : false );
		} else {
			// Generated message.
			$timestamp = $so->message(
				$this->formatting->status(
					$id,
					$order['food'],
					$order['order'],
					$order['driver'],
					(int) $order['tax'],
					Carbon::parse( get_the_date( 'Y-m-d H:i:s', $id ) ),
					( is_array( $order['payment'] ) ) ? $order['payment'] : [ $order['payment'] ],
					$order['paymentLink']
				),
				( ! empty( $existing_timestamp ) ) ? $existing_timestamp : false
			);
		}

		return $timestamp;
	}

	/**
	 * Sets up the Slack bot client object.
	 *
	 * @param string $customChannel Override the default option channel.
	 * @return botclient
	 */
	private function generateSlackbot( string $customChannel = '' ):botclient {
		return new botclient(
			get_option( 'kbfos_settings' )['kbfos_botkey'],
			( empty( $customChannel ) ) ? get_option( 'kbfos_settings' )['kbfos_botchannel'] : $customChannel
		);
	}
}
