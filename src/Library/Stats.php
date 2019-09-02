<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@soupbowl.io>
 * @license MIT
 */

namespace Kebabble\Library;

use Carbon\Carbon;

/**
 * Calculations and analytics from the Kebabble environment.
 */
class Stats {
	/**
	 * Counts the amount of orders that have been made.
	 *
	 * @return array
	 */
	public function order_counts() {
		$collection = $this->get_all_orders();

		$company_counts = [];
		$counts         = [
			'max'                => count( $collection['orders'] ),
			'month'              => 0,
			'best_company'       => 'N/A',
			'best_company_count' => 0,
		];

		foreach ( $collection['orders'] as $item ) {
			if ( $item->post_date > Carbon::now()->subMonth() ) {
				$counts['month']++;
			}

			if ( isset( $collection['links'][ $item->ID ] ) ) {
				if ( array_key_exists( $collection['links'][ $item->ID ]->name, $company_counts ) ) {
					$company_counts[ $collection['links'][ $item->ID ]->name ]++;
				} else {
					$company_counts[ $collection['links'][ $item->ID ]->name ] = 1;
				}
			}
		}

		$counts['best_company']       = array_keys( $company_counts, max( $company_counts ), true )[0];
		$counts['best_company_count'] = max( $company_counts );

		return $counts;
	}

	/**
	 * Grabs a huge dump of data to process (transiently stored for 1 day).
	 *
	 * @return array
	 */
	private function get_all_orders() {
		$calc = get_transient( 'kebabble_stat_data' );

		if ( $calc === false ) {

			$calc = [];
			// phpcs:disable WordPress.DB.SlowDBQuery
			$calc['orders'] = get_posts(
				[
					'post_type'      => 'kebabble_orders',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'orderby'        => 'date',
					'order'          => 'DESC',
					'meta_query'     => [
						[
							'key'     => 'kebabble-order',
							'value'   => '',
							'compare' => '=',
						],
					],
				]
			);
			// phpcs:enable

			// Additional details.
			foreach ( $calc['orders'] as $post ) {
				$company                    = wp_get_object_terms( $post->ID, 'kebabble_company' );
				$calc['metas'][ $post->ID ] = get_post_meta( $post->ID );
				$calc['links'][ $post->ID ] = ( ! empty( $company ) ) ? $company[0] : null;
			}

			set_transient( 'kebabble_stat_data', $calc, DAY_IN_SECONDS );
		}

		return $calc;
	}
}
