<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@revive.today>
 * @license MIT
 */

namespace Kebabble\Processes\Meta;

use Carbon\Carbon;

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
		$is_custom = ( empty( get_post_meta( $post_id, 'kebabble-custom-message', true ) ) ) ? false : true;
		$options   = [
			'kebabble-is-custom'      => $is_custom,
			'kebabble-custom-message' => get_post_meta( $post_id, 'kebabble-custom-message', true ),
			'kebabble-food'           => get_post_meta( $post_id, 'kebabble-food', true ),
			'kebabble-order'          => get_post_meta( $post_id, 'kebabble-order', true ),
			'kebabble-driver'         => get_post_meta( $post_id, 'kebabble-driver', true ),
			'kebabble-tax'            => get_post_meta( $post_id, 'kebabble-tax', true ),
			'kebabble-payment'        => get_post_meta( $post_id, 'kebabble-payment', true ),
			'kebabble-payment-link'   => get_post_meta( $post_id, 'kebabble-payment-link', true ),
			'kebabble-pin'            => get_post_meta( $post_id, 'kebabble-pin', true ),
		];

		return $options;
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

		$items = [
			'order'        => $this->order_list_collator( $response['korder_name'], $response['korder_food'] ),
			'food'         => ( isset( $response['kebabbleOrderTypeSelection'] ) ) ? $response['kebabbleOrderTypeSelection'] : null,
			'driver'       => ( isset( $response['kebabbleDriver'] ) ) ? $response['kebabbleDriver'] : null,
			'tax'          => ( isset( $response['kebabbleDriverTax'] ) ) ? $response['kebabbleDriverTax'] : null,
			'payment'      => ( isset( $response['paymentOpts'] ) ) ? $response['paymentOpts'] : null,
			'payment-link' => [],
			'timeout'      => Carbon::createMidnightDate()->addDay()->timestamp,
		];

		if ( ! empty( $response['pinState'] ) ) {
			update_post_meta( $post_id, 'kebabble-pin', true );
		} else {
			delete_post_meta( $post_id, 'kebabble-pin' );
		}

		foreach ( $items as $item_key => $item_value ) {
			update_post_meta( $post_id, 'kebabble-' . $item_key, $item_value );
		}

		$custom = ( ! empty( $response['kebabbleCustomMessageEntry'] ) ) ? $response['kebabbleCustomMessageEntry'] : null;
		if ( isset( $custom ) ) {
			update_post_meta( $post_id, 'kebabble-custom-message', $custom );
		} else {
			delete_post_meta( $post_id, 'kebabble-custom-message' );
		}

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
