<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@soupbowl.io>
 * @license MIT
 */

namespace Kebabble\Processes\Term;

/**
 * Handles processing of term data.
 */
class Save {
	/**
	 * Registers the class hooks with WordPress.
	 *
	 * @return void
	 */
	public function hook_save_terms() {
		add_action( 'created_kebabble_company', [ &$this, 'save_custom_company_details' ] );
		add_action( 'edited_kebabble_company', [ &$this, 'save_custom_company_details' ] );
		add_action( 'created_kebabble_collector', [ &$this, 'save_collector_options' ] );
		add_action( 'edited_kebabble_collector', [ &$this, 'save_collector_options' ] );
	}

	/**
	 * Saves the current custom company fields.
	 *
	 * @param integer $term_id The current term being processed.
	 * @return void Stores the expected data (got from POST) in the database.
	 */
	public function save_custom_company_details( int $term_id ):void {
		if ( isset( $_POST['ctOrderPricing'], $_POST['kebabbleNonce'] ) && wp_verify_nonce( sanitize_key( $_POST['kebabbleNonce'] ), 'kebabble_nonce' ) ) {
			$foodstr = sanitize_text_field( wp_unslash( $_POST['ctOrderPricing'] ) );

			update_term_meta( $term_id, 'kebabble_ordpri_org', $foodstr );
			update_term_meta( $term_id, 'kebabble_ordpri', ( ! empty( $foodstr ) ) ? $this->parse_food_options( $foodstr ) : null );
		}
	}

	/**
	 * Saves the current custom collector fields.
	 *
	 * @param integer $term_id The current term being processed.
	 * @return void Stores the expected data (got from POST) in the database.
	 */
	public function save_collector_options( int $term_id ):void {
		if ( isset( $_POST['kebabble-collector-payopts'], $_POST['kebabble-collector-charge'], $_POST['kebabbleNonce'] ) 
		&& wp_verify_nonce( sanitize_key( $_POST['kebabbleNonce'] ), 'kebabble_nonce' ) ) {
			$payment_opts       = explode( ',', sanitize_text_field( wp_unslash( $_POST['kebabble-collector-payopts'] ) ) );
			$payment_opts_clean = array_filter( array_map( 'trim', $payment_opts ) );
			$payment_charge     = (int) wp_unslash( $_POST['kebabble-collector-charge'] );
			$payment_slackusr   = sanitize_text_field( wp_unslash( $_POST['kebabble-collector-slackcode'] ) );

			update_term_meta( $term_id, 'kebabble_collector_tax', $payment_charge );
			update_term_meta( $term_id, 'keabble_collector_slackcode', $payment_slackusr );
			update_term_meta( $term_id, 'kebabble_collector_payment_methods', $payment_opts_clean );
			update_term_meta( $term_id, '_kebabble_collector_payment_methods_org', $_POST['kebabble-collector-payopts'] );
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
