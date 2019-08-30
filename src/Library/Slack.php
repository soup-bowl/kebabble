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

use SlackClient\BotClient;
use Carbon\Carbon;

/**
 * Intermediary between Kebabble WP and Slack.
 *
 * @todo Become parent of botclient. Remove formatting dependency and declare in use.
 */
class Slack {
	/**
	 * Slack based communications library.
	 *
	 * @var Botclient
	 */
	protected $client;

	/**
	 * Authorisation key, either from WP options or environment.
	 *
	 * @var string
	 */
	protected $token;

	/**
	 * Constructor.
	 *
	 * @param BotClient $client Slack based communications library.
	 */
	public function __construct( BotClient $client ) {
		$this->client = $client;
		$this->token  = ( getenv( 'KEBABBLE_BOT_AUTH' ) === false ) ? get_option( 'kbfos_settings' )['kbfos_botkey'] : getenv( 'KEBABBLE_BOT_AUTH' );
	}

	/**
	 * Sends a message to the Slack channel.
	 *
	 * @param string      $message            Desired message to be displayed.
	 * @param string|null $existing_timestamp If an existing timestamp is passed, that message is modified.
	 * @param string|null $channel            Change the Slack channel, if desired.
	 * @param string|null $thread_timestamp   Timestamp of a thread, if desired.
	 * @return string Unique timestamp of the message, used for editing.
	 */
	public function send_message( string $message, ?string $existing_timestamp = null, ?string $channel = null, ?string $thread_timestamp = null ):string {
		return $this->client->connect( $this->token )
			->setChannel( ( empty( $channel ) ) ? get_option( 'kbfos_settings' )['kbfos_botchannel'] : $channel )
			->message(
				$message,
				( ! empty( $existing_timestamp ) ) ? $existing_timestamp : false,
				( ! empty( $thread_timestamp ) ) ? $thread_timestamp : false
			);
	}

	/**
	 * Removes the specified message from Slack.
	 *
	 * @param string $ts      Operation timestamp (basically an ID).
	 * @param string $channel Channel of operation.
	 * @return boolean
	 */
	public function remove_message( string $ts, string $channel ) {
		$this->client->connect( $this->token )->setChannel( $channel )->deleteMessage( $ts );

		return true;
	}

	/**
	 * Changes the pin status of the specified Slack message.
	 *
	 * @param boolean $pin    Represents the pin status.
	 * @param string  $ts      Operation timestamp (basically an ID).
	 * @param string  $channel Channel of operation.
	 * @return void
	 */
	public function pin( bool $pin, string $ts, string $channel ) {
		if ( $pin ) {
			$this->client->connect( $this->token )->setChannel( $channel )->pin( $ts );
		} else {
			$this->client->connect( $this->token )->setChannel( $channel )->unpin( $ts );
		}
	}

	/**
	 * Emoji-based reaction to a message.
	 *
	 * @param string $reaction Emoji to be used.
	 * @param string $ts       Operation timestamp (basically an ID).
	 * @param string $channel  Channel of operation.
	 * @return void
	 */
	public function react( string $reaction, string $ts, string $channel ) {
		$this->client->connect( $this->token )->setChannel( $channel )->react( $ts, $reaction );
	}

	/**
	 * Gets all available channels that kebabble can connect to.
	 *
	 * @return array
	 */
	public function channels() {
		$channels = get_transient( 'kebabble_channels' );

		if ( $channels === false ) {
			$response = $this->client->connect( $this->token )->findChannels();

			$channels = [];
			foreach ( $response['channels'] as $channel ) {
				$channels[] = [
					'key'     => $channel['id'],
					'channel' => '#' . $channel['name'],
					'member'  => $channel['is_member'],
				];
			}

			set_transient( 'kebabble_channels', $channels, 6 * HOUR_IN_SECONDS );
		}

		return $channels;
	}
}
