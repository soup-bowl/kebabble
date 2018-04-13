<?php namespace kebabble\slack;

defined( 'ABSPATH' ) or die( 'Operation not permitted.' );

class slack {
	protected $token;
	protected $channel;
	public function __construct() {
		$this->token   = get_option('kbfos_settings')["kbfos_botkey"];
		$this->channel = get_option('kbfos_settings')["kbfos_botchannel"];
	}

	/**
	 * Posts a new message to Slack. If a pre-existing timestamp is provided, it will be updated.
	 * @param string $message Message desired in Slack formatting
	 * @param string $timestamp Existing message to overwrite, if desired
	 * @param string $channel
	 */
	public function postMessage($message, $timestamp = false, $channel = false) {
		if ($timestamp) {
			return $this->connection("https://slack.com/api/chat.update",[
				"text"    => $message,
				"ts"      => $timestamp,
				"channel" => $channel
			]);
		} else {
			return $this->connection("https://slack.com/api/chat.postMessage",[
				"text"    => $message,
				"channel" => $this->channel
			]);
		}	
	}
	
	/**
	 * Delete the timestamped message from Slack.
	 * @param string $timestamp Existing message identification
	 * @param string $channel
	 * @param boolean $softDelete true means a message placeholder is left behind
	 */
	public function deleteMessage($timestamp, $channel = false, $softDelete = true) {
		$result = null;
		
		if ($softDelete) {
			$result = $this->connection("https://slack.com/api/chat.update",[
				"text"    => '_Message retracted._',
				"ts"      => $timestamp,
				"channel" => $channel
			]);
		} else {
			$result = $this->connection("https://slack.com/api/chat.delete",[
				"ts"      => $timestamp,
				"channel" => $channel
			]);
		}
		
		if (!empty($result) && $result['ok']) {
			return true;
		} else {
			if (!empty($result['error'])) {
				error_log("Slack API threw an error: {$result['error']}.");
			} else {
				error_log("An unexpected error occurred.");
			}
			return false;
		}
	}

	private function connection($url, $data) {
		$data['token']      = $this->token;
		$data['link_names'] = 1;

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($ch);
		curl_close($ch);
		
		return $result;
	}
}