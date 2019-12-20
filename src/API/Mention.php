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
use Carbon\Carbon;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\BotManFactory;
use BotMan\Drivers\Slack\SlackDriver;

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
	 * Handles the receipt of the API call, and passes into Botman.
	 *
	 * @param WP_REST_Request $request Object from WordPress API comms.
	 * @return array Will be JSON-ified and sent to the endpoint.
	 */
	public function main( WP_REST_Request $request ):array {
		// First setup verification.
		if ( ! empty( $request['challenge'] ) ) {
			return [ 'challenge' => $request['challenge'] ];
		}

		DriverManager::loadDriver( SlackDriver::class );

		// Create BotMan instance
		$botman = BotManFactory::create([
			'slack' => [
				'token' => $this->slack->get_auth(),
			]
		]);

		$order_obj = $this->get_latest_order( $request['event']['channel'] );
		$order     = $this->orderstore->get( $order_obj->ID );
		$places    = wp_get_object_terms( $order_obj->ID, 'kebabble_company' );
		$place     = ( isset( $places ) ) ? $places[0] : null;
		$menu      = '(' . implode( '|', $this->get_potentials( $place->term_id ) ) . ')';

		$kebabble_user = $this->slack->get_bot_details()['user_id'];
		$kebabble_tag  = "<@{$kebabble_user}>";

		$botman->hears( "{$kebabble_tag} help", function ( BotMan $bot ) {
			$help = $this->informational_commands( 'help', $bot->getUser()->getId() );
			$bot->reply( $help );
		});

		$botman->hears( "{$kebabble_tag} menu", function ( BotMan $bot ) {
			$info = $this->get_order_details(
				$bot->getMessage()->getPayload()['channel']
			);

			if ( ! empty( $info ) ) {
				$help = $this->informational_commands(
					'menu',
					$bot->getUser()->getId(),
					$info['place']
				);

				$bot->reply( $help );
			} else {
				$bot->reply( $this->message( 3 ) );
			}
		});

		$p = $this->publish;
		$c = true;
		$botman->hears( "{$kebabble_tag} new order at (.*)", function ( BotMan $bot, string $place ) use ( &$c, &$p, &$request ) {
			if ( $c === true ) {
				$this->new_order(
					$request['event']['channel'],
					$place,
					$request['event']['user']
				);

				$c = false;
			}
		});

		$botman->hears( "{$kebabble_tag} change (?:the )?(?:(?:collector)|(?:driver)) to (.*)", function ( BotMan $bot, string $collector ) use ( &$c, &$p, &$order_obj ) {
			if ( $c === true ) {
				update_post_meta( $order_obj->ID, 'kebabble-driver', $collector );
				$p->handle_publish( $order_obj, false );

				$c = false;
				$bot->reply( "Collector changed to {$collector}." );
			}
		});

		$botman->hears( "{$kebabble_tag} .*remove .*{$menu}.*", function ( BotMan $bot, string $order ) use ( &$c, &$p, &$order_obj, &$request ) {
			if ( $c === true ) {
				$response = $this->remove_from_order( $order_obj->ID, $order, $request['event']['user'] );
				$p->handle_publish( $order_obj, false );

				$c = false;
				if ( $response ) {
					$bot->reply( "Cool, your {$order} has been chucked off the list." );
				} else {
					$bot->reply( 'Oops! Something went wrong trying to process your removal.' );
				}
			}
		});
		
		$botman->hears( "{$kebabble_tag} .*{$menu}.*", function ( BotMan $bot, string $order ) use ( &$c, &$p, &$order_obj, &$request ) {
			if ( $c === true ) {
				$response = $this->add_to_order( $order_obj->ID, $order, $request['event']['user'] );
				$p->handle_publish( $order_obj, false );

				$c = false;
				if ( $response ) {
					$bot->reply( "{$order} added to the order!" );
				} else {
					$bot->reply( 'Something went wrong adding your order. Check the menu and try again!' );
				}
			}
		});

		$botman->hears( "{$kebabble_tag} close order", function ( BotMan $bot ) use ( &$c, &$p, &$order_obj, &$request ) {
			if ( $c === true ) {
				$collector = wp_get_object_terms( $order_obj->ID, 'kebabble_collector' );
				$collector = ( ! empty( $collector ) ) ? get_term_meta( $collector[0]->term_id, 'keabble_collector_slackcode', true ) : null;

				$c = false;
				if ( $collector === $request['event']['user'] ) {
					update_post_meta( $order_obj->ID, 'kebabble-timeout', Carbon::now()->timestamp );

					$bot->reply( 'Order concluded.' );
				} else {
					$bot->reply( 'You do not have permission to cancel this order.' );
				}
			}
		});

		// start listening
		$botman->listen();

		return [];
	}

	private function get_order_details( string $channel ) {
		$order_obj = $this->get_latest_order( $channel );
		if ( ! empty( $order_obj ) ) {
			$places = wp_get_object_terms( $order_obj->ID, 'kebabble_company' );
			$place  = ( isset( $places ) ) ? $places[0] : null;
			$order  = $this->orderstore->get( $order_obj->ID );

			return [
				'place' => $place,
				'order' => $order,
			];
		} else {
			return null;
		}
	}

	public function add_to_order( int $post_id, string $item, string $person ) {
		$order = get_post_meta( $post_id, 'kebabble-order', true );

		$order[] = [
			'person' => "SLACK_{$person}",
			'food'   => $item,
		];

		update_post_meta( $post_id, 'kebabble-order', $order );

		return true;
	}

	public function remove_from_order( int $post_id, string $item, string $person ) {
		$order = get_post_meta( $post_id, 'kebabble-order', true );

		foreach ( $order as $key => $food ) {
			if ( $food['person'] === "SLACK_{$person}" && $food['food'] === $item ) {
				unset( $order[$key] );

				update_post_meta( $post_id, 'kebabble-order', $order );

				return true;
			}
		}

		return false;
	}

	/**
	 * Starts a new order.
	 *
	 * @param string $channel   The Slack channel of operation.
	 * @param string $resturant The place chosen, if in the system.
	 * @param string $collector The name of the collector.
	 * @return void Pubishes a new WordPress post.
	 */
	public function new_order( string $channel, ?string $resturant = null, ?string $collector = null ) {
		$collector       = ( isset( $collector ) ) ? $collector : 'Unspecified';
		$company         = get_term_by( 'name', $resturant, 'kebabble_company' );
		$collector_match = $this->find_collector( $collector, true );

		$post_title = 'Slack-generated order - ';
		if ( isset( $resturant, $company ) ) {
			$post_title .= $company->name . ' - ';
		}
		$post_title .= Carbon::now()->format( 'd/m/Y' );

		$post_id = wp_insert_post(
			[
				'post_title'  => $post_title,
				'post_status' => 'draft',
				'post_type'   => 'kebabble_orders',
				'meta_input'  => [
					'kebabble-slack-channel' => $channel,
				],
			]
		);

		if ( is_wp_error( $post_id ) ) {
			// TODO.
			die();
		}

		$order_details = [
			'korder_name'                => [],
			'korder_food'                => [],
			'kebabbleOrderTypeSelection' => 'Kebab',
			'kebabbleCompanySelection'   => $company->term_id,
			'kebabbleDriver'             => "<@{$collector}>",
		];

		if ( ! empty( $collector_match ) ) {
			$order_details['kebabbleDriverTax'] = get_term_meta( $collector_match->term_id, 'kebabble_collector_tax', true );
			$order_details['paymentOpts']       = get_term_meta( $collector_match->term_id, 'kebabble_collector_payment_methods', true );
		}

		$this->orderstore->set( $post_id, $order_details );

		add_post_meta( $post_id, '_kebabble-dnr', 1 );

		wp_publish_post( $post_id );
	}

	/**
	 * Kebabble responds with an informational message.
	 *
	 * @param string       $request Slack request text.
	 * @param string       $user    Slack user.
	 * @param WP_Term|null $place   Place of order, if relevant.
	 * @return string|null
	 */
	public function informational_commands( string $request, string $user, ?WP_Term $place = null ):?string {
		// Friendly message to Kebabble? Also useful as a quick hello-world test.
		if ( strpos( strtolower( $request ), 'help' ) !== false ) {
			return sprintf( $this->message( 1 ), $user );
		}

		if ( strpos( strtolower( $request ), 'menu' ) !== false ) {
			return $this->menu( $user, $place->term_id );
		}

		return null;
	}

	/**
	 * Grabs the latest order, and ignores incomplete or custom messages.
	 *
	 * @param string $channel Channel Slack-tag.
	 * @return WP_Post|null
	 */
	public function get_latest_order( string $channel ):?WP_Post {
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
						'key'     => 'kebabble-custom-message',
						'compare' => 'NOT EXISTS',
					],
					[
						'key'     => 'kebabble-slack-channel',
						'value'   => $channel,
						'compare' => '=',
					],
					[
						'key'     => 'kebabble-timeout',
						'value'   => Carbon::now()->timestamp,
						'compare' => '>=',
					]
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
	 * Finds the driver in the WordPress database.
	 *
	 * @param string  $name       Name (or slack identifer) of the person.
	 * @param boolean $slack_code If true, the codes will be looked up instead.
	 * @return WP_Term|null
	 */
	private function find_collector( string $name, bool $slack_code = true ):?WP_Term {
		if ( $slack_code ) {
			$search_query = get_terms(
				[
					'hide_empty' => false,
					'meta_query' => [
						[
							'key'     => 'keabble_collector_slackcode',
							'value'   => $name,
							'compare' => '=',
						],
					],
					'taxonomy'   => 'kebabble_collector',
				]
			);
		} else {
			$search_query = get_terms(
				[
					'hide_empty' => false,
					'name'       => $name,
					'taxonomy'   => 'kebabble_collector',
				]
			);
		}

		if ( ! empty( $search_query ) ) {
			return $search_query[0];
		} else {
			return null;
		}
	}
	/**
	 * Generates a simple menu of items ready for automatic parsing.
	 *
	 * @param string   $user       Slack user.
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

			return sprintf( $this->message( 2 ), $user, $items );
		} else {
			return $this->message( 3 );
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

	/**
	 * Sends back a sprintf-compatible message, based on the code:
	 * 1 - Help message, %s for Slack name code.
	 * 2 - Menu message, first %s for Slack name code and second for menu.
	 * 3 - Unknown location response.
	 * 4 - No current order response.
	 *
	 * @param integer $c Changes the response based on the input number.
	 * @return string|null
	 */
	private function message( int $c ):?string {
		switch ( $c ) {
			case 1:
				return "Hello, <@%s>! To help me help you, you can do the following:
• To order a kebab, mention the name of a known food item.
• To remove your order, say 'remove' before your food item.
• To order for someone else, add 'for (name)' after one of the above.
• To process many in one comment, separate with commas.

I will respond with a :thumbsup: if I've added your order, a :question: if I'm unsure and a :x: if there's a problem.
				";
			case 2:
				return "Howdy <@%s>! I'm currently aware of the following menu items:\n\n%s\n\nMention one of these (ask me for help!) and I will handle it for you!";
			case 3:
				return Emojis::negative() . ' Drat, I don\'t know this place! Let the order manager know instead.';
			case 4:
				return Emojis::negative( 2 ) . ' I can\'t see an order, please check if the channel is correct!';
			default:
				return null;
		}
	}
}
