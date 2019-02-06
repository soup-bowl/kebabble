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
use Kebabble\Processes\Meta\Orderstore;
use Kebabble\Processes\Publish;

use WP_Post;
use WP_REST_Request;

/**
 * Handles Slack mentions, and processes the contents for relevant information.
 */
class Mention {
	/**
	 * Stores and retrieves order data.
	 *
	 * @var Orderstore
	 */
	protected $orderstore;

	protected $publish;

	/**
	 * Constructor.
	 *
	 * @param Orderstore $orderstore Stores and retrieves order data.
	 */
	public function __construct( Orderstore $orderstore, Publish $publish ) {
		$this->orderstore = $orderstore;
		$this->publish    = $publish;
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
			$this->process_input_request( $request['event']['user'], $request['event']['text'], $request['event']['ts'], $request['event']['channel'] );
			return [];
		}
	}

	/**
	 * Processes the request contents and modifies the order accordingly.
	 *
	 * @todo Filter custom message more efficiently. 
	 * @param string $user      TBA.
	 * @param string $request   TBA.
	 * @param string $timestamp TBA.
	 * @param string $channel   TBA.
	 * @return void
	 */
	private function process_input_request( string $user, string $request, string $timestamp, string $channel ):void {
		$order_obj = $this->get_latest_order();
		$place     = wp_get_object_terms( $order_obj->ID, 'kebabble_company' )[0]; // Returns NULL if no company.
		$order     = $this->orderstore->get( $order_obj->ID );
		$slack     = new Slack();
		
		// Friendly message to Kebabble? Also useful as a quick hello-world test.
		if ( strpos( strtolower( $request ), 'help' ) !== false ) {
			$slack->send_message( $this->help_message( $user ), null, $channel, $timestamp );
			return;
		}

		// Split up the presence of commas, and remove the @kebabble call. A better way would be appreciated.
		$message_split = explode( ',', str_replace( '<>', '', preg_replace( '/@\w+/', '', strtolower( $request ) ) ) );

		$messages = [];
		foreach ( $message_split as $message_segment ) {
			$messages[] = $this->decipher_order(
				$message_segment,
				( isset( $place ) ) ? $this->get_potentials( $place->term_id ) : []
			);
		}

		$order_items = $order['order'];
		$order_count = count( $order['order'] );
		// Each segment of a multiple request. Normally only one.
		foreach ( $messages as $message ) {
			$name = ( $message['for'] === 'me' ) ? "SLACK_{$user}" : $message['for'];
			// Iterate through existing order, check for duplicates and removals.
			$already_removed = false;
			for ( $i = 0; $i < $order_count; $i++ ) {
				error_log( "{$i} of {$order_count}" );
				switch ( $message['operator'] ) {
					default:
					case 'add':
						if ( ( $i + 1 ) === $order_count ) {
							$order_items[] = [
								'person' => $name,
								'food'   => $message['item']
							];
						}
						break;
					case 'remove':
						if ( strtolower( $order_items[$i]['person'] ) === strtolower( $name ) && ! $already_removed ) {
							unset( $order_items[$i] );
							$already_removed = true;
						}
						break;
				}
			}
		}

		unset( $order['order'] );
		$order['order'] = array_values( $order_items );
		
		$this->orderstore->update( $order_obj->ID, $order );
		$this->publish->handle_publish( $order_obj, false );
		$slack->react( $timestamp );

		//$slack->send_message( "```\n" . var_export($order_items, true) . "\n```", null, $channel );
		//$slack->send_message( ':x: I couldn\'t determine your order. Please try again or ask me for help.', null, $channel );
	}
	
	/**
	 * Processes the response and attempts to decipher what the contactee wants.
	 *
	 * @param string $segment    Request string (split multi-request prior to input).
	 * @param array  $potentials Simple array of all detectable options.
	 * @return array Collection of arrays, with params 'operator', 'item' and 'for'.
	 */
	private function decipher_order( string $segment, array $potentials ):array {
		$segment_split       = explode( ' ', $segment );
		$segment_split_count = count( $segment_split );

		$ops = [
			'operator' => 'add',
			'item'     => null,
			'for'      => 'me'
		];

		// Attempt to work out the item.
		foreach ( $potentials as $potential ) {
			if ( strpos( strtolower( $segment ), strtolower( $potential ) ) !== false ) {
				$ops['item'] = $potential;
			}
		}
		
		// now for the operator, and if this is for someone else.
		for ( $i = 0; $i < $segment_split_count; $i++ ) {
			switch( $segment_split[$i] ) {
				case 'remove':
					$ops['operator'] = 'remove';
					break;
				case 'for':
					$ops['for'] = $segment_split[($i + 1)];
					break;
			}
		}

		return $ops;
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

	/**
	 * Generic help message text.
	 *
	 * @param string $user Slack user ID, to @ them in the message.
	 * @return string Pre-formatted for Slack.
	 */
	private function help_message( string $user ):string {
		return "Hello, <@{$user}>! To help me help you, you can do the following:
		• To order a kebab, mention the name of a known food item.
		• To remove your order, say 'remove' before your food item.
		• To order for someone else, add 'for (name)' after one of the above.
		• To process many in one comment, separate with commas.\n
		I will respond with a :thumbsup: if I've added your order, or I'll message back if a problem occurs. Don't forget to @kebabble me!
		";
	}

	/**
	 * Collects an array of menu items listed against the provided company.
	 *
	 * @param int $company_id Company meta ID to obtain details from.
	 * @return array
	 */
	private function get_potentials( int $company_id ):array {
		$menu  = get_term_meta( $company_id, 'kebabble_ordpri', true );
		$items = [];

		foreach ( $menu as $food => $extra ) {
			$items[] = $food;
		}

		return $items;
	}
}
