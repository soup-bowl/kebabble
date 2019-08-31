<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@revive.today>
 * @license MIT
 */

namespace Kebabble\Processes\Meta;

/**
 * Stores and retrieves the order field data.
 */
class Orderstore {
	/**
	 * Obtains the order details stored with the post, or none if unavailable.
	 *
	 * @param integer $post_id WordPress Post ID.
	 * @return array|null
	 */
	public function get( int $post_id ):?array {
		return json_decode( get_post_meta( $post_id, 'kebabble-order', true ), true );
	}

	/**
	 * Sets the configuration details stored per-post.
	 *
	 * @todo Refactor due to $_POST hijack. Either accept no override, or an intermediary.
	 * @param integer $post_id  ID of the post.
	 * @param array   $response Overrides the POST scrape.
	 * @throws Exception If no valid response could be scraped.
	 * @return array The information stored in the database.
	 */
	public function set( int $post_id, array $response = null ):array {
		$override = empty( $response['kebabbleCustomMessageEnabled'] ) ? false : true;

		if ( empty( $response ) ) {
			// phpcs:disable WordPress.Security.ValidatedSanitizedInput
			if ( isset( $_POST ) && false !== wp_verify_nonce( sanitize_key( $_POST['kebabbleNonce'] ), 'kebabble_nonce' ) ) {
				$response = $_POST;
			} else {
				throw new Exception( 'Invalid input - Response and POST empty.' );
			}
			//phpcs:enable
		}

		if ( (int) $response['kebabbleCompanySelection'] > 0 ) {
			wp_set_object_terms(
				$post_id,
				(int) $response['kebabbleCompanySelection'],
				'kebabble_company'
			);
		} else {
			wp_delete_object_term_relationships( $post_id, 'kebabble_company' );
		}

		if ( (int) $response['kebabbleCollectorSelection'] > 0 ) {
			wp_set_object_terms(
				$post_id,
				(int) $response['kebabbleCollectorSelection'],
				'kebabble_collector'
			);
		} else {
			wp_delete_object_term_relationships( $post_id, 'kebabble_collector' );
		}

		$conf_array = [
			'override'    => [
				'enabled' => $override,
				'message' => ( isset( $response['kebabbleCustomMessageEntry'] ) ) ? $response['kebabbleCustomMessageEntry'] : null,
			],
			'food'        => $response['kebabbleOrderTypeSelection'],
			'order'       => $this->order_list_collator( $response['korder_name'], $response['korder_food'] ),
			'driver'      => ( isset( $response['kebabbleDriver'] ) ) ? $response['kebabbleDriver'] : wp_get_current_user()->display_name,
			'tax'         => ( isset( $response['kebabbleDriverTax'] ) ) ? $response['kebabbleDriverTax'] : 0,
			'payment'     => ( isset( $response['paymentOpts'] ) ) ? $response['paymentOpts'] : [],
			'paymentLink' => [],
			'pin'         => empty( $response['pinState'] ) ? false : true,
		];

		$opts    = get_option( 'kbfos_settings' );
		$options = ( empty( $opts['kbfos_payopts'] ) ) ? [ 'Cash' ] : explode( ',', $opts['kbfos_payopts'] );

		foreach ( $options as $option ) {
			$conf_array['paymentLink'][ $option ] = $response[ "kopt{$option}" ];
		}

		update_post_meta( $post_id, 'kebabble-order', wp_json_encode( $conf_array ) );

		return $this->get( $post_id );
	}

	/**
	 * Replaces the stored order details with a new copy. Recommended use is to modify the
	 * received initial data and pass back for true update.
	 *
	 * @param integer $post_id ID of the post being modified.
	 * @param array   $updated The new replacement fields.
	 * @return void
	 */
	public function update( int $post_id, array $updated ) {
		update_post_meta( $post_id, 'kebabble-order', wp_json_encode( $updated ) );
	}

	/**
	 * Collates the dizzying array of values from the order page into easier to handle values.
	 *
	 * @param array $names The name list array.
	 * @param array $food  The food order array.
	 * @return array
	 */
	private function order_list_collator( array $names, array $food ):array {
		$orderlist = [];
		$counter   = count( $names );

		for ( $i = 0; $i < $counter; $i++ ) {
			if ( empty( $names[ $i ] ) ) {
				continue;
			}

			$orderlist[] = [
				'person' => $names[ $i ],
				'food'   => $food[ $i ],
			];
		}

		return $orderlist;
	}
}
