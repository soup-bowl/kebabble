<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@revive.today>
 * @license MIT
 */

namespace Kebabble\API;

use Kebabble\Library\Slack;

use WP_Post;
use WP_REST_Request;

/**
 * Handles Slack mentions, and processes the contents for relevant information.
 */
class Mention {
	/**
	 * Handles outgoing Slack communications.
	 *
	 * @var Slack
	 */
	protected $slack;

	/**
	 * Constructor.
	 *
	 * @param Slack $slack Handles outgoing Slack communications.
	 */
	public function __construct( Slack $slack ) {
		$this->slack = $slack;
	}

	/**
	 * Initial function for the Slack Events segment of the API.
	 *
	 * @param WP_REST_Request $request Object from WordPress API comms.
	 * @return array Will be JSON-ified and sent to the endpoint.
	 */
	public function main( WP_REST_Request $request ):array {
		// First setup verification.
		if ( ! empty( $request['challenge'] ) ) {
			return ['challenge' => $request['challenge']];
		}

		if ( ! empty( $request['event'] ) ) {
			$this->process_input_request( $request['event']['user'], $request['event']['text'] );
			return [];
		}
	}

	/**
	 * Processes the request contents and modifies the order accordingly.
	 *
	 * @todo Filter custom message more efficiently. 
	 * @param string $user    TBA.
	 * @param string $request TBA.
	 * @return void
	 */
	private function process_input_request( string $user, string $request ):void {
		$order_obj = $this->get_latest_order();
		
		// Friendly message to Kebabble? Also useful as a quick hello-world test.
		if ( strpos( strtolower( $request ), 'hello' ) !== false ) {
		    $this->slack->send_custom_message( "Hello <@{$user}>!" );
		    return;
		}
		
		error_log( var_export($order_obj->ID . ': ' . $order_obj->post_title, true) );
	}

	/**
	 * Grabs the latest order, and ignores incomplete or custom messages.
	 *
	 * @return WP_Post|null
	 */
	private function get_latest_order():?WP_Post {
		$order = get_posts([
			'post_type'      => 'kebabble_orders',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => [
				[
					'key'     => 'kebabble-order',
					'value'   => '"override":{"enabled":false',
					'compare' => 'LIKE'
				]
			]
		]);

		if ( ! empty( $order ) ) {
			return $order[0];
		} else {
			return null;
		}
	}
}
