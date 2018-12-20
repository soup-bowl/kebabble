<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@revive.today>
 * @license MIT
 */

namespace Kebabble\Library;

use Kebabble\Processes\Formatting;

use SlackClient\botclient;
use Carbon\Carbon;

/**
 * Intermediary between Kebabble WP and Slack.
 */
class Slack {
	/**
	 * Pre-processing before publishing.
	 *
	 * @var Formatting
	 */
	public $formatting;

	/**
	 * Slack API communication handler.
	 *
	 * @var Slack
	 */
	public $slack;

	/**
	 * Constructor.
	 *
	 * @param Formatting $formatting Pre-processing before publishing.
	 */
	public function __construct( Formatting $formatting ) {
		$this->formatting = $formatting;
		$this->slack      = $this->generate_slackbot();
	}

	/**
	 * Sends contents over to the Slack message API.
	 *
	 * @param integer     $id                 Post ID.
	 * @param array       $order              Order array to be displayed on Slack.
	 * @param string|null $existing_timestamp If an existing timestamp is passed, that message is modified.
	 * @param string|null $override_channel   Change the Slack channel, if desired.
	 * @return string Unique timestamp of the message, used for editing.
	 */
	public function send_to_slack( int $id, array $order, ?string $existing_timestamp = null, ?string $override_channel = null ):string {
		$so = $this->slack;
		if ( ! empty( $override_channel ) ) {
			$so = $this->generate_slackbot( $override_channel );
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
	 * @param string $custom_channel Override the default option channel.
	 * @return botclient
	 */
	private function generate_slackbot( string $custom_channel = '' ):botclient {
		return new botclient(
			get_option( 'kbfos_settings' )['kbfos_botkey'],
			( empty( $custom_channel ) ) ? get_option( 'kbfos_settings' )['kbfos_botchannel'] : $custom_channel
		);
	}
}
