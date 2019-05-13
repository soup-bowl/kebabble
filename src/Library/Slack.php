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
	protected $client;
	protected $token;
	/**
	 * Constructor.
	 */
	public function __construct( BotClient $client ) {
		$this->client = $client;
		$this->token  = get_option( 'kbfos_settings' )['kbfos_botkey'];
	}

	/**
	 * Sends a message to the Slack channel.
	 *
	 * @param string      $message            Desired message to be displayed.
	 * @param string|null $existing_timestamp If an existing timestamp is passed, that message is modified.
	 * @param string|null $override_channel   Change the Slack channel, if desired.
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
	
	public function remove_message( string $ts, string $channel ) {
		$this->client->connect( $this->token )->setChannel( $channel )->deleteMessage( $ts );

		return true;
	}
	
	public function pin( bool $pin, string $ts, string $channel ) {
		if ( $pin ) {
			$this->client->connect( $this->token )->setChannel( $channel )->pin( $ts );
		} else {
			$this->client->connect( $this->token )->setChannel( $channel )->unpin( $ts );
		}
	}
	
	public function react( string $reaction, string $ts, string $channel ) {
		$this->client->connect( $this->token )->setChannel( $channel )->react( $ts, $reaction );
	}

	public function info() {
		$info = $this->client->connect( $this->token )->botInfo();var_dump($info);
		return $info;
	}
}
