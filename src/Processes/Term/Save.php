<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@revive.today>
 * @license MIT
 */

namespace Kebabble\Processes\Term;

/**
 * Handles processing of term data.
 */
class Save {
	/**
	 * Saves the current custom company fields.
	 *
	 * @param integer $term_id The current term being processed.
	 * @return void Stores the expected data (got from POST) in the database.
	 */
	public function save_custom_company_details( int $term_id ):void {
		if ( isset( $_POST['ctOrderPricing'] ) ) {
			$foodstr = sanitize_text_field( wp_unslash( $_POST['ctOrderPricing'] ) );

			update_term_meta( $term_id, 'kebabble_ordpri_org', $foodstr );
			update_term_meta( $term_id, 'kebabble_ordpri', $this->parse_food_options( $foodstr ) );
		}
	}

	/**
	 * Parses the food|option,food|option input.
	 *
	 * @param string $input The string formatted like above.
	 * @return array Returns the serialised string as an array.
	 */
	public function parse_food_options( string $input ):array {
		$options    = [];
		$collection = explode( ',', $input );

		foreach ( $collection as $item ) {
			$decy                = explode( '|', $item );
			$options[ $decy[0] ] = [
				'Price' => isset( $decy[1] ) ? $decy[1] : 0,
			];
		}

		return $options;
	}
}
