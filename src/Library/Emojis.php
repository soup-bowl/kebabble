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
 * Slack emoji codes.
 */
class Emojis {
	public static function positive( bool $rand = false ) {
		$emojis = [
			':thumbsup:',
			':heavy_tick:',
			':ok_hand:',
			':muscle:',
			':smile:',
			':wink:'
		];

		if ( $rand ) {
			return $emojis[ array_rand( $emojis, 1 ) ];
		} else {
			return $emojis;
		}
	}

	public static function negative( bool $rand = false ) {
		$emojis = [
			':thumbsdown:',
			':heavy_multiplication_x:',
			':disappointed:',
			':flushed:',
			':cry:',
			':sad:'
		];

		if ( $rand ) {
			return $emojis[ array_rand( $emojis, 1 ) ];
		} else {
			return $emojis;
		}
	}
	
	public static function curious( bool $rand = false ) {
		$emojis = [
			':question:',
			':face_with_raised_eyebrow:',
			':face_with_monocle:'
		];

		if ( $rand ) {
			return $emojis[ array_rand( $emojis, 1 ) ];
		} else {
			return $emojis;
		}
	}

	public static function food( string $desc ):string {
		$items = [
			// Food types.
			'kebab'      => ':stuffed_flatbread:',
			'flatbread'  => ':stuffed_flatbread:',
			'pita'       => ':stuffed_flatbread:',
			'burrito'    => ':burrito:',
			'pizza'      => ':pizza:',
			'burger'     => ':hamburger:',
			'salad'      => ':green_salad:',
			'sushi'      => ':sushi:',
			'pudding'    => ':ice_cream:',
			'dessert'    => ':ice_cream:',
			// Lifestyles.
			'vegetarian' => ':green_salad:',
			'vegan'      => ':avocado:',
			// Places.
			'restaurant' => ':knife_fork_plate:',
			'cafe'       => ':knife_fork_plate:',
			// Familiar cultural stereotypes of UK.
			'chinese'    => ':takeout_box:',
			'indian'     => ':curry:',
			'mexican'    => ':taco:',
			'japanese'   => ':sushi:'
		];

		$desc = strtolower( $desc );
		if ( isset( $items[ $desc ] ) ) {
			return $items[ $desc ];
		} else {
			return ':question:';
		}
	}

	public static function misc( string $name ):string {
		switch ( strtolower( $name ) ) {
			case 'driver':
			case 'collector':
				return ':truck:';
			case 'money':
				return ':pound:';
			default:
				return ':question:';
		}
	}
}
