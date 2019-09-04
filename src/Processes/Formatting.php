<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@revive.today>
 * @license MIT
 */

namespace Kebabble\Processes;

use Kebabble\Library\Money;
use Kebabble\Library\Emojis;
use Carbon\Carbon;

/**
 * Handles order form formatting prior to sending.
 */
class Formatting {
	/**
	 * Handles monetary-based string formatting.
	 *
	 * @var Money
	 */
	protected $money;

	/**
	 * Constructor.
	 *
	 * @param Money $money Handles monetary-based string formatting.
	 */
	public function __construct( Money $money ) {
		$this->money = $money;
	}
	/**
	 * Makes a kebabble menu listing status.
	 *
	 * @param integer $id       ID of the post in question.
	 * @param string  $food     Dictates header. e.g. Kebab Mondays. Overridden by company if set.
	 * @param array   $order    Array of people and their orders ('person' and 'food' collection).
	 * @param string  $driver   Driver name.
	 * @param integer $tax      Additional charge for orders.
	 * @param Carbon  $date     Date of order, typically today.
	 * @param array   $payments Payment methods accepted.
	 * @param array   $pay_opts URL links for the above (matched by array key to payment type).
	 * @param string  $notes    Optional message displayed under order.
	 * @return string
	 */
	public function status( int $id, string $food, array $order, string $driver, int $tax = 0, ?Carbon $date = null, array $payments = [ 'Cash' ], array $pay_opts = [], string $notes = null ):string {
		$location    = ( ! empty( wp_get_object_terms( $id, 'kebabble_company' ) ) ) ? wp_get_object_terms( $id, 'kebabble_company' )[0] : null;
		$location_id = ( ! empty( $location ) ) ? $location->term_id : 0;
		$food        = ( $location_id !== 0 ) ? get_term_meta( $location_id, 'kebabble_place_type', true ) : $food;
		$loc_str     = ( ! empty( $location ) ) ? " @ {$location->name}" : '';
		$order       = ( empty( $order ) ) ? '_' . __( 'None yet!', 'kebabble' ) . '_' : $this->order_formatter( $order, $location_id, $tax );
		$driver      = ( '' === $driver ) ? __( 'Unknown Collector', 'kebabble' ) : $driver;
		$date        = ( empty( $date ) ) ? Carbon::now() : $date;
		$slack_emoji = Emojis::food( $food );

		$formatted_posts = [
			"{$slack_emoji} *{$food} {$date->format('l')}{$loc_str} ({$date->format('jS F')})* {$slack_emoji}",
			$order,
			sprintf( __( 'Polling %1$s for orders. Today\'s collector is %2$s', 'kebabble' ), '<!here>', "*{$driver}*" ) . Emojis::misc( 'collector' ),
			( $tax > 0 ) ? Emojis::misc( 'money' ) . ' *' . sprintf( __( 'Additional %1$s per person', 'kebabble' ), $this->money->output( $tax ) ) . '* ' . __( 'to fund the collector', 'kebabble' ) . '.' : null,
			$this->accepts_payment_formatter( $payments, $pay_opts ),
		];

		if ( isset( $notes ) ) {
			$formatted_posts[] = $notes;
		}

		return str_replace( "\n\n\n\n", "\n\n", implode( "\n\n", $formatted_posts ) );
	}

	/**
	 * New-style formatter, ditches the monospace style.
	 *
	 * @param array   $orders Array of order inputs.
	 * @param integer $place  Term ID of place. Blank disables this additional feature.
	 * @param integer $tax    Set tax value for calculation purposes.
	 * @return string
	 */
	private function order_formatter( array $orders, int $place = 0, int $tax = 0 ):string {
		$content    = '';
		$food_items = [];
		$items      = [];
		$place      = get_term_meta( $place, 'kebabble_ordpri', true );

		foreach ( $orders as $order ) {
			if ( ! in_array( $order['food'], $food_items, true ) ) {
				$food_items[] = $order['food'];
			}
		}

		foreach ( $food_items as $food_item ) {
			$obj = [
				'food'   => $food_item,
				'people' => [],
			];

			foreach ( $orders as $order ) {
				if ( $order['food'] === $obj['food'] ) {
					$obj['people'][] = $order['person'];
				}
			}

			$items[] = $obj;
		}

		$cost_overall = 0;
		$cost_tax     = 0;
		$people_taxed = [];
		foreach ( $items as $item ) {
			$cost_item = 0;
			$cost      = '';
			if ( false !== $place ) {
				$cost_item = ! empty( $place[ $item['food'] ] ) ? (int) $place[ $item['food'] ]['Price'] : 0;
				$cost      = ! empty( $cost_item ) ? " ({$this->money->output($cost_item)} each)" : '';
			}
			$order_count = count( $item['people'] );
			$content    .= "*{$order_count}x {$item['food']}{$cost}* \n>";
			foreach ( $item['people'] as $person ) {
				if ( ! in_array( $person, $people_taxed, true ) ) {
					$cost_tax       = $cost_tax + $tax;
					$people_taxed[] = $person;
				}
				if ( strpos( $person, 'SLACK_' ) !== false ) {
					$slack_conv = str_replace( 'SLACK_', '', $person );
					$content   .= "<@{$slack_conv}>, ";
				} else {
					$content .= "{$person}, ";
				}
				$cost_overall = $cost_overall + $cost_item;
			}
			$content  = substr( $content, 0, -2 );
			$content .= ".\n\n";
		}

		$cost_box = '';
		if ( $cost_overall > 0 ) {
			$cost_box  = "\n\n";
			$cost_box .= '*' . __( 'Cost', 'kebabble' ) . '*: ' . $this->money->output( $cost_overall ) . ' _' . __( 'for priced orders', 'kebabble' ) . "_.\n";
			$cost_box .= '*' . __( 'Tax', 'kebabble' ) . "*: {$this->money->output($cost_tax)}.";
		}

		return substr( $content, 0, -2 ) . $cost_box;
	}

	/**
	 * Returns a 'Driver accepts...' string with the given array displayed.
	 *
	 * @param array $accepted_payments String array of accepted payment labels.
	 * @param array $option_payments   URL links for the above (matched by array key to payment type).
	 * @return string
	 */
	private function accepts_payment_formatter( array $accepted_payments, array $option_payments = [] ):string {
		$ap_count = count( $accepted_payments );

		if ( 0 === $ap_count ) {
			return '';
		}

		$format_string = __( 'Driver accepts', 'kebabble' ) . ' ';

		if ( 1 !== $ap_count ) {
			for ( $i = 0; $i < $ap_count; $i++ ) {
				$indv_option = ( empty( $option_payments[ $accepted_payments[ $i ] ] ) ) ? false : $option_payments[ $accepted_payments[ $i ] ];
				$indv_paym   = ( false !== $indv_option ) ? "<{$indv_option}|{$accepted_payments[$i]}>" : $accepted_payments[ $i ];

				if ( ( $i + 1 ) === $ap_count ) {
					$format_string .= "& {$indv_paym}.";
				} else {
					$format_string .= "{$indv_paym}, ";
				}
			}
		} else {
			$indv_option = ( empty( $option_payments[ $accepted_payments[0] ] ) ) ? false : $option_payments[ $accepted_payments[0] ];
			$indv_paym   = ( false !== $indv_option ) ? "<{$indv_option}|{$accepted_payments[0]}>" : $accepted_payments[0];

			$format_string .= "{$indv_paym}.";
		}
		return $format_string;
	}
}
