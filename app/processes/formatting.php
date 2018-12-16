<?php
/**
 * Handles order form formatting prior to sending.
 *
 * @package kebabble
 * @author soup-bowl
 */

namespace kebabble\processes;

use kebabble\library\money;
use Carbon\Carbon;

/**
 * Handles order form formatting prior to sending.
 */
class formatting {
	protected $money;
	public function __construct( money $money ) {
		$this->money = $money;
	}
	/**
	 * Makes a kebabble menu listing status.
	 *
	 * @param string  $food     Dictates header. e.g. Kebab Mondays.
	 * @param string  $order    Displayed monospaced.
	 * @param string  $driver   Driver name.
	 * @param integer $tax      Additional charge for orders.
	 * @param Carbon  $date     Date of order, typically today.
	 * @param array   $payments Payment methods accepted.
	 * @param array   $pOpts    URL links for the above (matched by array key to payment type).
	 * @return string
	 */
	public function status( $food, $order, $driver, $tax = 0, $date = false, $payments = [ 'Cash' ], $pOpts = [], $place = 0 ) {
		$rolls    = ( '' === $rolls ) ? 'N/A' : $rolls;
		$location = get_term( $place, 'kebabble_company' );
		$loc_str  = ( ! is_wp_error( $location ) ) ? " at {$location->name}" : '';
		$order    = ( '' === $order ) ? 'N/A' : $this->orderFormatter( $order, $place, $tax );
		$driver   = ( '' === $driver ) ? 'unspecified' : $driver;
		$date     = ( false === $date ) ? Carbon::now() : $date;
		$evMoji   = $this->emojiPicker( $food );

		$formattedPosts = [
			"{$evMoji} *{$food} {$date->format('l')}{$loc_str} ({$date->format('jS F')})* {$evMoji}",
			'*Orders*',
			$order,
			"Polling @channel for orders. Today's driver is *{$driver}* :car:",
			( $tax > 0 ) ? ':pound: *Additional ' . $this->money->output( $tax ) . ' per person* to fund the driver.' : null,
			$this->acceptsPaymentFormatter( $payments, $pOpts ),
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
	 * @param array $acceptedPayments String array of accepted payment labels.
	 * @param array $pOpts            URL links for the above (matched by array key to payment type).
	 * @return string
	 */
	private function acceptsPaymentFormatter( $acceptedPayments, $pOpts = [] ) {
		$apCount = count( $acceptedPayments );

		if ( 0 === $apCount ) {
			return '';
		}

		$formatString = 'Driver accepts ';

		if ( 1 !== $apCount ) {
			for ( $i = 0; $i < $apCount; $i++ ) {
				$aOption  = ( empty( $pOpts[ $acceptedPayments[ $i ] ] ) ) ? false : $pOpts[ $acceptedPayments[ $i ] ];
				$aPayment = ( false !== $aOption ) ? "<{$aOption}|{$acceptedPayments[$i]}>" : $acceptedPayments[ $i ];

				if ( ( $i + 1 ) === $apCount ) {
					$formatString .= "& {$aPayment}.";
				} else {
					$formatString .= "{$aPayment}, ";
				}
			}
		} else {
			$aOption  = ( empty( $pOpts[ $acceptedPayments[0] ] ) ) ? false : $pOpts[ $acceptedPayments[0] ];
			$aPayment = ( false !== $aOption ) ? "<{$aOption}|{$acceptedPayments[0]}>" : $acceptedPayments[0];

			$formatString .= "{$aPayment}.";
		}
		return $formatString;
	}

	/**
	 * Chooses the emoji for the food type... yep, this exists.
	 *
	 * @param string $food Label of the order type.
	 * @return string colon-surrounded emoji format.
	 */
	private function emojiPicker( $food ) {
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
