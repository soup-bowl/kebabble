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
 *
 * @todo Become parent of botclient. Remove formatting dependency and declare in use.
 */
class Slack {
	/**
	 * Pre-processing before publishing.
	 *
	 * @var Formatting
	 */
	public $formatting;

	/**
	 * Constructor.
	 *
	 * @param Formatting $formatting Pre-processing before publishing.
	 */
	public function __construct( Formatting $formatting ) {
		$this->formatting = $formatting;
	}

	/**
	 * Sends the complete order to the Slack channel.
	 *
	 * @param integer     $id                 Post ID.
	 * @param array       $order              Order array to be displayed on Slack.
	 * @param string|null $existing_timestamp If an existing timestamp is passed, that message is modified.
	 * @param string|null $override_channel   Change the Slack channel, if desired.
	 * @return string Unique timestamp of the message, used for editing.
	 */
	public function send_order( int $id, array $order, ?string $existing_timestamp = null, ?string $override_channel = null ):string {
		return $this->bot( $override_channel )->message(
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

	/**
	 * Sends a singular message to the Slack channel.
	 *
	 * @param string      $message            Desired message to be displayed.
	 * @param string|null $existing_timestamp If an existing timestamp is passed, that message is modified.
	 * @param string|null $override_channel   Change the Slack channel, if desired.
	 * @return string Unique timestamp of the message, used for editing.
	 */
	public function send_custom_message( string $message, ?string $existing_timestamp = null, ?string $override_channel = null ):string {
		return $this->bot( $override_channel )->message( $message, ( ! empty( $existing_timestamp ) ) ? $existing_timestamp : false );
	}

	/**
	 * Sets up the Slack bot client object.
	 *
	 * @param string|null $custom_channel Override the default option channel.
	 * @return botclient
	 */
	public function bot( ?string $custom_channel = null ):botclient {
		return new botclient(
			get_option( 'kbfos_settings' )['kbfos_botkey'],
			( empty( $custom_channel ) ) ? get_option( 'kbfos_settings' )['kbfos_botchannel'] : $custom_channel
		);
	}
}
