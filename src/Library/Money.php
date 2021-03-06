<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@revive.today>
 * @license MIT
 */

namespace Kebabble\Library;

/**
 * Handles currency formatting.
 */
class Money {
	/**
	 * Formats the integer input into a currency string.
	 *
	 * @param integer $value       Non-decimal representation of the currency (e.g. 100p for £1.00).
	 * @param string  $high_symbol Symbol for the highest decimal format (defaults to £).
	 * @param string  $low_symbol  Symbol for low format (defaults to p).
	 * @return string Formatted display version of the currency input.
	 */
	public function output( int $value, string $high_symbol = '£', string $low_symbol = 'p' ):string {
		if ( $value <= 0 ) {
			return "0{$low_symbol}";
		}

		$money_string = strval( $value / 100 );
		$expl         = explode( '.', $money_string );
		$expl[1]      = ( empty( $expl[1] ) ) ? '0' : $expl[1];

		$dec = ( strlen( $expl[1] ) === 1 ) ? $expl[1] . '0' : $expl[1];

		if ( 0 === (int) $expl[0] ) {
			if ( $value < 10 ) {
				return $value . $low_symbol;
			} else {
				return $dec . $low_symbol;
			}
		} else {
			if ( empty( $expl[1] ) ) {
				return $high_symbol . $expl[0] . '.00';
			} else {
				return $high_symbol . $expl[0] . '.' . $dec;
			}
		}
	}
}
