<?php
/**
 * Handles processing of term data.
 *
 * @package kebabble
 * @author soup-bowl
 */

namespace kebabble\processes\term;

/**
 * Handles processing of term data.
 */
class save {
	/**
	 * Saves the current custom company fields.
	 *
	 * @param integer $term_id The current term being processed.
	 */
	public function saveCustomCompanyDetails( int $term_id ) {
		update_term_meta( $term_id, 'kebabble_ordpri_org', $_POST['ctOrderPricing'] );
		update_term_meta( $term_id, 'kebabble_ordpri', $this->parseFoodOptions( $_POST['ctOrderPricing'] ) );
	}

	/**
	 * Parses the food|option,food|option input.
	 *
	 * @param string $input The string formatted like above.
	 * @return array
	 */
	private function parseFoodOptions( string $input ):array {
		$options    = [];
		$collection = explode( ',', $input );

		foreach ( $collection as $item ) {
			$decy      = explode( '|', $item );
			$options[] = [
				'Item'  => $decy[0],
				'Price' => isset( $decy[1] ) ? $decy[1] : 0,
			];
		}

		return $options;
	}
}
