<?php namespace kebabble\slack;

defined( 'ABSPATH' ) or die( 'Operation not permitted.' );

class slack {
	protected $token;
	protected $channel;
	public function __construct() {
		$this->token   = get_option('kbfos_settings')["kbfos_botkey"]; // TODO - Env it.
		$this->channel = get_option('kbfos_settings')["kbfos_botchannel"];
	}

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