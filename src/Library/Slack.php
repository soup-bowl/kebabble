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
class Slack extends botclient {
	/**
	 * Constructor.
	 *
	 * @param string $channel Optional channel override.
	 */
	public function __construct( ?string $channel = null ) {
		parent::__construct(
			get_option( 'kbfos_settings' )['kbfos_botkey'],
			( empty( $channel ) ) ? get_option( 'kbfos_settings' )['kbfos_botchannel'] : $channel
		);
	}

	/**
	 * Sends a message to the Slack channel.
	 *
	 * @param string      $message            Desired message to be displayed.
	 * @param string|null $existing_timestamp If an existing timestamp is passed, that message is modified.
	 * @param string|null $override_channel   Change the Slack channel, if desired.
	 * @return string Unique timestamp of the message, used for editing.
	 */
	public function send_message( string $message, ?string $existing_timestamp = null, ?string $override_channel = null ):string {
		return $this->message( $message, ( ! empty( $existing_timestamp ) ) ? $existing_timestamp : false );
	}
}
