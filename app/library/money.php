<?php
/**
 * Handles currency formatting.
 *
 * @package kebabble
 * @author soup-bowl
 */

namespace kebabble\library;

/**
 * Handles currency formatting.
 */
class money {
	/**
	 * Formats the integer input into a currency string.
	 *
	 * @param integer $value      Non-decimal representation of the currency (e.g. 100p for £1.00).
	 * @param string  $highSymbol Symbol for the highest decimal format (defaults to £).
	 * @param string  $lowSymbol  Symbol for low format (defaults to p).
	 * @return string Formatted display version of the currency input.
	 */
	public function output( int $value, string $highSymbol = '£', string $lowSymbol = 'p' ):string {
		$moneyString = strval( $value / 100 );
		$expl        = explode( '.', $moneyString );

		$dec = ( strlen( $expl[1] ) == 1 ) ? $expl[1] . '0' : $expl[1];

		if ( 0 == $expl[0] ) {
			if ( $value < 10 ) {
				return $value . $lowSymbol;
			} else {
				return $dec . $lowSymbol;
			}
		} else {
			if ( empty( $expl[1] ) ) {
				return $highSymbol . $expl[0] . '.00';
			} else {
				return $highSymbol . $expl[0] . '.' . $dec;
			}
		}
	}
}
