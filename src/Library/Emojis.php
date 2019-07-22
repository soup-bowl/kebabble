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
	public static function positive() {
		return [
			':thumbsup:',
			':heavy_tick:',
			':ok_hand:',
			':muscle:',
			':smile:',
			':wink:'
		];
	}

	public static function negative() {
		return [
			':thumbsdown:',
			':heavy_multiplication_x:',
			':disappointed:',
			':flushed:',
			':cry:'
		];
	}

	public static function food( $desc ) {
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

		if ( isset( $items[ $desc ] ) ) {
			return $items[ $desc ];
		} else {
			return ':question:';
		}
	}
}
