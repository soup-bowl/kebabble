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
use Kebabble\Library\Money;

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

	/**
	 * Handles the communication with the WordPress post.
	 *
	 * @var Publish
	 */
	protected $publish;

	/**
	 * Formattings money representations.
	 *
	 * @var Money
	 */
	protected $money;

	/**
	 * Communication handler for Slack.
	 *
	 * @var Slack
	 */
	protected $slack;

	/**
	 * Constructor.
	 *
	 * @param Orderstore $orderstore Stores and retrieves order data.
	 * @param Publish    $publish    Handles the communication with the WordPress post.
	 * @param Money      $money      Formattings money representations.
	 * @param Slack      $slack      Communication handler for Slack.
	 */
	public function __construct( Orderstore $orderstore, Publish $publish, Money $money, Slack $slack ) {
		$this->orderstore = $orderstore;
		$this->publish    = $publish;
		$this->money      = $money;
		$this->slack      = $slack;
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
			return [ 'challenge' => $request['challenge'] ];
		}

		if ( ! empty( $request['event'] ) ) {
			if ( $request['event']['type'] === 'message' && ! ( isset( $request['event']['subtype'] ) && $request['event']['subtype'] === 'message_changed' ) ) {
				$this->process_input_request( $request['event']['user'], $request['event']['text'], $request['event']['ts'], $request['event']['channel'] );
			}

			return [];
		}
	}

	/**
	 * Processes the request contents and modifies the order accordingly.
	 *
	 * @todo Filter custom message more efficiently.
	 * @param string $user      Slack user code.
	 * @param string $request   The whole message string that's been sent to our bot.
	 * @param string $timestamp Message timestamp.
	 * @param string $channel   Slack channel of communications.
	 * @return void Choices reflect on the current post and the Slack channel.
	 */
	public function process_input_request( string $user, string $request, string $timestamp, string $channel ):void {
		$order_obj = $this->get_latest_order();
		$places    = wp_get_object_terms( $order_obj->ID, 'kebabble_company' );
		$place     = ( isset( $places ) ) ? $places[0] : null;
		$order     = $this->orderstore->get( $order_obj->ID );

		// Friendly message to Kebabble? Also useful as a quick hello-world test.
		if ( strpos( strtolower( $request ), 'help' ) !== false ) {
			$this->slack->send_message( $this->help_message( $user ), null, $channel, $timestamp );
			return;
		}

		if ( strpos( strtolower( $request ), 'menu' ) !== false ) {
			$this->slack->send_message( $this->menu( $place->term_id ), null, $channel, $timestamp );
			return;
		}

		// Split up the presence of commas, and remove the @kebabble call. A better way would be appreciated.
		$message_split = explode( ',', str_replace( '<>', '', preg_replace( '/@\w+/', '', strtolower( $request ), 1 ) ) );

		$messages = [];
		foreach ( $message_split as $message_segment ) {
			$messages[] = $this->decipher_order(
				$message_segment,
				( isset( $place ) ) ? $this->get_potentials( $place->term_id ) : []
			);
		}

		$order_items   = $order['order'];
		$order_count   = count( $order['order'] );
		$success_count = 0;
		// Each segment of a multiple request. Normally only one.
		foreach ( $messages as $message ) {
			if ( empty( $message ) ) {
				$this->slack->react( 'question', $timestamp, $channel );
				continue;
			}

			$name = ( 'me' === $message['for'] ) ? "SLACK_{$user}" : $message['for'];
			// Iterate through existing order, check for duplicates and removals.
			$already_removed = false;
			for ( $i = 0; $i < $order_count; $i++ ) {
				switch ( $message['operator'] ) {
					default:
					case 'add':
						if ( ( $i + 1 ) === $order_count ) {
							$order_items[] = [
								'person' => $name,
								'food'   => $message['item'],
							];
						}
						break;
					case 'remove':
						if ( isset( $order_items[ $i ] ) && strtolower( $order_items[ $i ]['person'] ) === strtolower( $name ) && ! $already_removed ) {
							unset( $order_items[ $i ] );
							$already_removed = true;
						}
						break;
				}
				$success_count++;
			}

			if ( 0 === $order_count && 'add' === $message['operator'] ) {
				$order_items[] = [
					'person' => $name,
					'food'   => $message['item'],
				];
				$success_count++;
			}
		}

		if ( $success_count > 0 ) {
			unset( $order['order'] );
			$order['order'] = array_values( $order_items );

			$this->orderstore->update( $order_obj->ID, $order );
			$this->publish->handle_publish( $order_obj, false );
			$this->slack->react( 'thumbsup', $timestamp, $channel );
		}
	}

	/**
	 * Processes the response and attempts to decipher what the contactee wants.
	 *
	 * @todo Implement ordering on behalf of other Slack users.
	 * @param string $segment    Request string (split multi-request prior to input).
	 * @param array  $potentials Simple array of all detectable options.
	 * @return array|null Collection of arrays, with params 'operator', 'item' and 'for'.
	 */
	public function decipher_order( string $segment, array $potentials ):?array {
		$segment_split       = explode( ' ', $segment );
		$segment_split_count = count( $segment_split );

		$ops = [
			'operator' => 'add',
			'item'     => null,
			'for'      => 'me',
		];

		// Attempt to work out the item.
		foreach ( $potentials as $potential ) {
			if ( strpos( strtolower( $segment ), strtolower( $potential ) ) !== false ) {
				$ops['item'] = $potential;
			}
		}

		// now for the operator, and if this is for someone else.
		for ( $i = 0; $i < $segment_split_count; $i++ ) {
			switch ( $segment_split[ $i ] ) {
				case 'no':
				case 'delete':
				case 'remove':
				case 'x':
				case '-':
					$ops['operator'] = 'remove';
					break;
				case 'for':
					$for_person = ucfirst( $segment_split[ ( $i + 1 ) ] );
					if ( false !== strpos( $for_person, '@' ) ) {
						return null;
					} else {
						$ops['for'] = $for_person;
					}
					break;
			}
		}

		if ( isset( $ops['item'] ) ) {
			return $ops;
		} else {
			return null;
		}
	}

	/**
	 * Grabs the latest order, and ignores incomplete or custom messages.
	 *
	 * @return WP_Post|null
	 */
	public function get_latest_order():?WP_Post {
		// phpcs:disable WordPress.DB.SlowDBQuery
		$order = get_posts(
			[
				'post_type'      => 'kebabble_orders',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_query'     => [
					[
						'key'     => 'kebabble-order',
						'value'   => '"override":{"enabled":false',
						'compare' => 'LIKE',
					],
				],
			]
		);
		// phpcs:enable

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
		I will respond with a :thumbsup: if I've added your order, a :question: if I'm unsure and a :x: if there's a problem.
		";
	}

	/**
	 * Generates a simple menu of items ready for automatic parsing.
	 *
	 * @param int|null $company_id Company to collect the list from.
	 * @return string
	 */
	private function menu( ?int $company_id = null ):string {
		$menu = get_term_meta( $company_id, 'kebabble_ordpri', true );

		if ( isset( $menu ) && false !== $menu ) {
			$items = '';
			foreach ( $menu as $food => $extra ) {
				$items .= sprintf( '*%1$s* %2$s', $food, $this->money->output( $extra['Price'] ) ) . PHP_EOL;
			}

			return sprintf(
				"I'm currently aware of the following menu items:\n\n%s\n\nMention one of these (ask me for help!) and I will handle it for you!",
				$items
			);
		} else {
			return ":disappointed: Drat, I don't know this place! Let the order manager know instead.";
		}
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
