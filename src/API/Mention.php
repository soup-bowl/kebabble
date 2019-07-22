<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@revive.today>
 * @license MIT
 */

namespace Kebabble\API;

use Kebabble\Processes\Meta\Orderstore;
use Kebabble\Processes\Publish;
use Kebabble\Library\Money;
use Kebabble\Library\Slack;
use Kebabble\Library\Emojis;
use KOrderParser\Parser;

use WP_Post;
use WP_Term;
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
	 * Natural-language order parser.
	 *
	 * @var Parser
	 */
	protected $order_parser;

	/**
	 * Constructor.
	 *
	 * @param Orderstore $orderstore Stores and retrieves order data.
	 * @param Publish    $publish    Handles the communication with the WordPress post.
	 * @param Money      $money      Formattings money representations.
	 * @param Slack      $slack      Communication handler for Slack.
	 * @param Parser     $parser     Natural-language order parser.
	 */
	public function __construct( Orderstore $orderstore, Publish $publish, Money $money, Slack $slack, Parser $parser ) {
		$this->orderstore   = $orderstore;
		$this->publish      = $publish;
		$this->money        = $money;
		$this->slack        = $slack;
		$this->order_parser = $parser;
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

		if ( ! empty( $request['event'] ) &&  $request['event']['type'] === 'app_mention' ) {
			$order_obj = $this->get_latest_order();
			$places    = wp_get_object_terms( $order_obj->ID, 'kebabble_company' );
			$place     = ( isset( $places ) ) ? $places[0] : null;
			$order     = $this->orderstore->get( $order_obj->ID );

			$response = $this->informational_commands(
				$request['event']['text'],
				$request['event']['user'],
				$place
			);

			// Detect command to change the collector.
			$new_driver = $this->change_collector( $request['event']['text'], $order );
			if ( isset( $new_driver ) ) {
				$order['driver'] = $new_driver;

				$this->orderstore->update( $order_obj->ID, $order );
				$this->publish->handle_publish( $order_obj, false );
				$this->slack->react( Emojis::positive()[2], $request['event']['ts'], $request['event']['channel'] );

				return [];
			}

			if ( isset( $response ) ) {
				$this->slack->send_message(
					$response,
					null,
					$request['event']['channel'],
					$request['event']['ts']
				);
			} else {
				$result = $this->process_input_request(
					$request['event']['user'],
					$request['event']['text'],
					$place,
					$order
				);

				if ( $result['success'] ) {
					$this->orderstore->update( $order_obj->ID, $result['order'] );
					$this->publish->handle_publish( $order_obj, false );
					$this->slack->react( Emojis::positive()[0], $request['event']['ts'], $request['event']['channel'] );
				} else {
					$this->slack->react( Emojis::negative()[0], $request['event']['ts'], $request['event']['channel'] );
				}
			}

			return [];
		}
	}

	/**
	 * Processes the request contents and modifies the order accordingly.
	 *
	 * @param string $user    Slack user code.
	 * @param string $request The whole message string that's been sent to our bot.
	 * @param WP_Term $place  The place to operate with.
	 * @param array $order    The existing order entity to modify, if necessary.
	 * @return array Choices reflect on the current post and the Slack channel.
	 */
	public function process_input_request( string $user, string $request, ?WP_Term $place, ?array $order ):array {
		// Split up the presence of commas, and remove the @kebabble call. A better way would be appreciated.
		$message_split = explode( ',', str_replace( '<>', '', preg_replace( '/@\w+/', '', strtolower( $request ), 1 ) ) );

		$messages = [];
		foreach ( $message_split as $message_segment ) {
			$messages[] = $this->order_parser->decipherOrder(
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
				return [ 'success' => false, 'order' => $order ];
			}

			$name = ( null === $message->getFor() ) ? "SLACK_{$user}" : $message->getFor();
			// Iterate through existing order, check for duplicates and removals.
			$already_removed = false;
			for ( $i = 0; $i < $order_count; $i++ ) {
				switch ( $message->getOperator() ) {
					default:
					case 'add':
						if ( ( $i + 1 ) === $order_count ) {
							$order_items[] = [
								'person' => $name,
								'food'   => $message->getItem(),
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

			if ( 0 === $order_count && 'add' === $message->getOperator() ) {
				$order_items[] = [
					'person' => $name,
					'food'   => $message->getItem(),
				];
				$success_count++;
			}
		}

		if ( $success_count > 0 ) {
			unset( $order['order'] );
			$order['order'] = array_values( $order_items );

			return [ 'success' => true, 'order' => $order ];
		} else {
			return [ 'success' => false, 'order' => $order ];
		}
	}

	/**
	 * Detects a request to change the driver, and returns their name.
	 *
	 * @param string $request Request to parse.
	 * @return string|null Name of the collector, or null if no request was made.
	 */
	public function change_collector( string $request ):?string {
		$confirm = preg_match('/change (?:the )?(?:(?:collector)|(?:driver)) to (.*)/i', $request, $result );
		if ( $confirm ) {
			return $result[1];
		}

		return null;
	}
	
	public function informational_commands( string $request, string $user, ?WP_Term $place = null ):?string {
		// Friendly message to Kebabble? Also useful as a quick hello-world test.
		if ( strpos( strtolower( $request ), 'help' ) !== false ) {
			return $this->help_message( $user );
		}

		if ( strpos( strtolower( $request ), 'menu' ) !== false ) {
			return $this->menu( $user, $place->term_id );
		}

		return null;
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
	private function menu( string $user, ?int $company_id = null ):string {
		$menu = get_term_meta( $company_id, 'kebabble_ordpri', true );

		if ( isset( $menu ) && false !== $menu ) {
			$items = '';
			foreach ( $menu as $food => $extra ) {
				$items .= sprintf( '*%1$s* %2$s', $food, $this->money->output( $extra['Price'] ) ) . PHP_EOL;
			}

			return sprintf(
				"Howdy <@{$user}>! I'm currently aware of the following menu items:\n\n%s\n\nMention one of these (ask me for help!) and I will handle it for you!",
				$items
			);
		} else {
			return  Emojis::negative( true ) . " Drat, I don't know this place! Let the order manager know instead.";
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
