<?php
/**
 * Intermediary between Kebabble WP and Slack.
 *
 * @package kebabble
 * @author soup-bowl
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
	 * @param integer $id                Post ID.
	 * @param array   $foResponse        Message array to be displayed on Slack.
	 * @param boolean $existingTimestamp If an existing timestamp is passed, that message is modified.
	 * @param string  $overrideChannel   Change the Slack channel, if desired.
	 * @return string Unique timestamp of the message, used for editing.
	 */
	public function sendToSlack( $id, $foResponse, $existingTimestamp = false, $overrideChannel = '' ) {
		$so = $this->slack;
		if ( ! empty( $overrideChannel ) ) {
			$so = $this->generateSlackbot( $overrideChannel );
		}

		$timestamp = null;
		if ( $foResponse['override']['enabled'] ) {
			// Custom Message.
			$timestamp = $so->message( $foResponse['override']['message'], $existingTimestamp );
		} else {
			// Generated message.
			$timestamp = $so->message(
				$this->formatting->status(
					$id,
					$foResponse['food'],
					$foResponse['order'],
					$foResponse['driver'],
					$foResponse['tax'],
					Carbon::parse( get_the_date( 'Y-m-d H:i:s', $id ) ),
					( is_array( $foResponse['payment'] ) ) ? $foResponse['payment'] : [ $foResponse['payment'] ],
					$foResponse['paymentLink']
				),
				$existingTimestamp
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
