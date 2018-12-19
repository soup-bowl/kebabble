<?php
/**
 * Handles currency formatting.
 *
 * @package kebabble
 * @author soup-bowl
 */

namespace kebabble\processes\meta;

/**
 * Stores and retrieves the order field data.
 */
class orderstore {
	public function get( int $post_id ) {
		return json_decode( get_post_meta( $post_id, 'kebabble-order', true ), true );
	}

	/**
	 * Sets the configuration details stored per-post.
	 *
	 * @param integer $post_id  ID of the post.
	 * @param array   $response Overrides the POST scrape.
	 * @return array
	 */
	public function set( int $post_id, array $response = null ) {
		if ( empty( $response ) ) {
			$response = $_POST;

			if ( empty( $response ) ) {
				throw new Exception( 'Invalid input - Response and POST empty.' );
			}
		}

		if ( $response['kebabbleCompanySelection'] > 0 ) {
			wp_set_object_terms(
				$response[ $post_id ],
				(int) $response['kebabbleCompanySelection'],
				'kebabble_company'
			);
		} else {
			wp_delete_object_term_relationships( $response[ $post_id ], 'kebabble_company' );
		}

		$confArray = [
			'override'      => [
				'enabled' => empty( $response['kebabbleCustomMessageEnabled'] ) ? false : true,
				'message' => $response['kebabbleCustomMessageEntry'],
			],
			'food'          => $response['kebabbleOrderTypeSelection'],
			'order'         => $this->orderListCollator( $response['korder_name'], $response['korder_food'] ),
			'order_classic' => $response['kebabbleOrders'],
			'driver'        => $response['kebabbleDriver'],
			'tax'           => $response['kebabbleDriverTax'],
			'payment'       => $response['paymentOpts'],
			'paymentLink'   => [],
			'pin'           => empty( $response['pinState'] ) ? false : true,
		];

		$opts    = get_option( 'kbfos_settings' );
		$options = ( empty( $opts['kbfos_payopts'] ) ) ? [ 'Cash' ] : explode( ',', $opts['kbfos_payopts'] );

		foreach ( $options as $option ) {
			$confArray['paymentLink'][ $option ] = $response[ "kopt{$option}" ];
		}

		update_post_meta( $post_id, 'kebabble-order', json_encode( $confArray ) );

		return $this->get( $post_id );
	}

	/**
	 * Collates the dizzying array of values from the order page into easier to handle values.
	 *
	 * @param array $names The name list array.
	 * @param array $food  The food order array.
	 * @return array
	 */
	private function orderListCollator( array $names, array $food ):array {
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