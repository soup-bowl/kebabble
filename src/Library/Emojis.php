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
	/**
	 * Numerical index of various positively-viewed emojis.
	 *
	 * @param int|null $index Return a specific emoji, leave blank for random.
	 * @return string Emoji in slack-code.
	 */
	public static function positive( int $index = null ):string {
		$emojis = [
			':thumbsup:',
			':heavy_tick:',
			':ok_hand:',
			':muscle:',
			':smile:',
			':wink:',
		];

		if ( isset( $index ) ) {
			return $emojis[ $index ];
		} else {
			return $emojis[ array_rand( $emojis, 1 ) ];
		}
	}

	/**
	 * Numerical index of various negatively-viewed emojis.
	 *
	 * @param int|null $index Return a specific emoji, leave blank for random.
	 * @return string Emoji in slack-code.
	 */
	public static function negative( int $index = null ):string {
		$emojis = [
			':thumbsdown:',
			':heavy_multiplication_x:',
			':disappointed:',
			':flushed:',
			':cry:',
			':sad:',
		];

		if ( isset( $index ) ) {
			return $emojis[ $index ];
		} else {
			return $emojis[ array_rand( $emojis, 1 ) ];
		}
	}

	/**
	 * Numerical index of various unexpected emojis.
	 *
	 * @param int|null $index Return a specific emoji, leave blank for random.
	 * @return string Emoji in slack-code.
	 */
	public static function curious( int $index = null ):string {
		$emojis = [
			':question:',
			':face_with_raised_eyebrow:',
			':face_with_monocle:',
		];

		if ( isset( $index ) ) {
			return $emojis[ $index ];
		} else {
			return $emojis[ array_rand( $emojis, 1 ) ];
		}
	}

	/**
	 * Matches a string with an approximate emoji.
	 *
	 * @param string $desc String to be matched.
	 * @return string Emoji in slack-code.
	 */
	public static function food( string $desc ):string {
		$items = [
			// Food types.
			__( 'kebab' )      => ':stuffed_flatbread:',
			__( 'flatbread' )  => ':stuffed_flatbread:',
			__( 'pita' )       => ':stuffed_flatbread:',
			__( 'burrito' )    => ':burrito:',
			__( 'pizza' )      => ':pizza:',
			__( 'burger' )     => ':hamburger:',
			__( 'salad' )      => ':green_salad:',
			__( 'sushi' )      => ':sushi:',
			__( 'pudding' )    => ':ice_cream:',
			__( 'dessert' )    => ':ice_cream:',
			// Lifestyles.
			__( 'vegetarian' ) => ':green_salad:',
			__( 'vegan' )      => ':avocado:',
			// Places.
			__( 'restaurant' ) => ':knife_fork_plate:',
			__( 'cafe' )       => ':knife_fork_plate:',
			// Familiar cultural stereotypes of UK.
			__( 'chinese' )    => ':takeout_box:',
			__( 'indian' )     => ':curry:',
			__( 'mexican' )    => ':taco:',
			__( 'japanese' )   => ':sushi:',
		];

		$desc = strtolower( $desc );
		if ( isset( $items[ $desc ] ) ) {
			return $items[ $desc ];
		} else {
			return ':question:';
		}
	}

	/**
	 * Uncategorised string match emojis.
	 *
	 * @param string $name String to be matched.
	 * @return string Emoji in slack-code.
	 */
	public static function misc( string $name ):string {
		switch ( strtolower( $name ) ) {
			case __( 'driver', 'kebbable' ):
			case __( 'collector', 'kebbable' ):
				return ':truck:';
			case __( 'money', 'kebbable' ):
				return ':pound:';
			default:
				return ':question:';
		}
	}
}
