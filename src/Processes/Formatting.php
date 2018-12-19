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
	 * @param string  $food     Dictates header. e.g. Kebab Mondays.
	 * @param array   $order    Array of people and their orders ('person' and 'food' collection).
	 * @param string  $driver   Driver name.
	 * @param integer $tax      Additional charge for orders.
	 * @param Carbon  $date     Date of order, typically today.
	 * @param array   $payments Payment methods accepted.
	 * @param array   $pay_opts URL links for the above (matched by array key to payment type).
	 * @return string
	 */
	public function status( int $id, string $food, array $order, string $driver, int $tax = 0, ?Carbon $date = null, array $payments = [ 'Cash' ], array $pay_opts = [] ):string {
		$rolls       = ( '' === $rolls ) ? 'N/A' : $rolls;
		$location    = wp_get_object_terms($id, 'kebabble_company')[0];
		$location_id = ( ! empty( $location ) ) ? $location->term_id : 0;
		$loc_str     = ( ! empty( $location ) ) ? " at {$location->name}" : '';
		$order       = ( empty( $order ) ) ? '_None yet!_' : $this->orderFormatter( $order, $location_id, $tax );
		$driver      = ( '' === $driver ) ? 'unspecified' : $driver;
		$date        = ( empty( $date ) ) ? Carbon::now() : $date;
		$slack_emoji = $this->emojiPicker( $food );

		$formattedPosts = [
			"{$slack_emoji} *{$food} {$date->format('l')}{$loc_str} ({$date->format('jS F')})* {$slack_emoji}",
			'*Orders*',
			$order,
			"Polling @channel for orders. Today's driver is *{$driver}* :car:",
			( $tax > 0 ) ? ':pound: *Additional ' . $this->money->output( $tax ) . ' per person* to fund the driver.' : null,
			$this->acceptsPaymentFormatter( $payments, $pay_opts ),
		];

		return str_replace( "\n\n\n\n", "\n\n", implode( "\n\n", $formattedPosts ) );
	}

	/**
	 * New-style formatter, ditches the monospace style.
	 *
	 * @param array   $orders Array of order inputs.
	 * @param integer $place  Term ID of place. Blank disables this additional feature.
	 * @return string
	 */
	private function orderFormatter( array $orders, int $place = 0, int $tax = 0 ):string {
		$content   = '';
		$foodItems = $items = [];
		$place     = get_term_meta( $place, 'kebabble_ordpri', true ); 
		
		foreach ( $orders as $order ) {
			if ( ! in_array( $order['food'], $foodItems ) ) {
				$foodItems[] = $order['food'];
			}
		}

		foreach ( $foodItems as $foodItem ) {
			$obj = [
				'food'   => $foodItem,
				'people' => [],
			];

			foreach ( $orders as $order ) {
				if ( $order['food'] === $obj['food'] ) {
					$obj['people'][] = $order['person'];
				}
			}

			$items[] = $obj;
		}

		$cost_overall = $cost_tax = 0;		
		foreach ( $items as $item ) {
			$cost_item = 0;
			$cost      = '';
			if ( $place !== false ) {
				$cost_item = ! empty( $place[ $item['food'] ] ) ? (int) $place[ $item['food'] ]['Price'] : 0;
				$cost      = ! empty( $cost_item ) ? " ({$this->money->output($cost_item)} each)" : '';
			}
			$content .= "*{$item['food']}{$cost}* \n>";
			foreach ( $item['people'] as $person ) {
				$content .= "{$person}, ";
				$cost_overall = $cost_overall + $cost_item;
				$cost_tax     = $cost_tax + $tax;
			}
			$content  = substr( $content, 0, -2 );
			$content .= ".\n\n";
		}

		$cost_box = '';
		if ( $cost_overall > 0 ) {
			$cost_box = "\n\n";
			$cost_box .= "*Cost*: {$this->money->output($cost_overall)} _for priced orders_.\n";
			$cost_box .= "*Tax*: {$this->money->output($cost_tax)}.";
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
	private function acceptsPaymentFormatter( array $accepted_payments, array $option_payments = [] ):string {
		$ap_count = count( $accepted_payments );

		if ( 0 === $ap_count ) {
			return '';
		}

		$format_string = 'Driver accepts ';

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

	/**
	 * Chooses the emoji for the food type... yep, this exists.
	 *
	 * @param string $food Label of the order type.
	 * @return string colon-surrounded emoji format.
	 */
	private function emojiPicker( string $food ):string {
		switch ( strtolower( $food ) ) {
			case 'burger':
				return ':hamburger:';
			case 'pizza':
				return ':pizza:';
			case 'event':
				return ':popcorn:';
			default:
				return ':burrito:';
		}
	}
}
