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
	 * @return string
	 */
	public function status( $food, $order, $driver, $tax = 0, $date = false, $payments = [ 'Cash' ] ) {
		$rolls  = ( '' === $rolls ) ? 'N/A' : $rolls;
		$order  = ( '' === $order ) ? 'N/A' : $order;
		$driver = ( '' === $driver ) ? 'unspecified' : $driver;
		$date   = ( false === $date ) ? Carbon::now() : $date;
		$evMoji = $this->emojiPicker( $food );

		$taxCalc = $this->money->output( $tax );

		$taxSlogan = ( false !== $taxCalc ) ? ":pound: *Additional {$taxCalc} per person* to fund the driver." : null;

		$formattedPosts = [
			"{$evMoji} *{$food} {$date->format('l')} ({$date->format('jS F')})* {$evMoji}",
			"*Orders*```{$order}```",
			"Polling @channel for orders. Today's driver is *{$driver}* :car:",
			$taxSlogan,
			$this->acceptsPaymentFormatter( $payments ),
		];

		return implode( "\n\n", $formattedPosts );
	}

	/**
	 * Returns a 'Driver accepts...' string with the given array displayed.
	 *
	 * @param array $acceptedPayments String array of accepted payment labels.
	 * @return string
	 */
	private function acceptsPaymentFormatter( $acceptedPayments ) {
		$apCount = count( $acceptedPayments );

		if ( 0 === $apCount ) {
			return '';
		}

		$formatString = 'Driver accepts ';

		if ( 1 !== $apCount ) {
			for ( $i = 0; $i < $apCount; $i++ ) {
				if ( ( $i + 1 ) === $apCount ) {
					$formatString .= "& {$acceptedPayments[$i]}.";
				} else {
					$formatString .= "{$acceptedPayments[$i]}, ";
				}
			}
		} else {
			$formatString .= "{$acceptedPayments[0]}.";
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
