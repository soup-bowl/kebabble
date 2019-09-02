<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@soupbowl.io>
 * @license MIT
 */

namespace Kebabble\Config;

use Kebabble\Library\Stats;

/**
 * Handles the Kebabble-related dashboard widgets.
 */
class Dashboard {
	/**
	 * Calculations and analytics from the Kebabble environment.
	 *
	 * @var Stats
	 */
	protected $stats;

	/**
	 * Constructor.
	 *
	 * @param Stats $stats Calculations and analytics from the Kebabble environment.
	 */
	public function __construct( Stats $stats ) {
		$this->stats = $stats;
	}

	/**
	 * WordPress hooks for class functions.
	 *
	 * @return void
	 */
	public function hook_dashboard() {
		add_action( 'wp_dashboard_setup', [ &$this, 'info_widget' ] );
	}

	/**
	 * Registers WordPress dashboard widgets.
	 *
	 * @return void
	 */
	public function info_widget() {
		wp_add_dashboard_widget(
			'kebabble_info',
			'Kebabble',
			[ &$this, 'info_widget_content' ]
		);
	}

	/**
	 * Returns HTML with statistical information.
	 *
	 * @return void
	 */
	public function info_widget_content() {
		$counts     = $this->stats->order_counts();
		$kses_allow = [
			'a' => [
				'href'  => [],
				'class' => [],
			],
			'p' => [],
		];

		/* Translators: %1$s is the total order number from the system lifetime, and %2$s is a month previous from now. */
		printf( wp_kses( '<p>' . __( 'There has been a total of %1$s orders, %2$s within the previous month.', 'kebabble' ) . '</p>', $kses_allow ), esc_html( $counts['max'] ), esc_html( $counts['month'] ) );
		/* Translators: %1$s is the most popular company name, and %2$s is it's linked custom post type count. */
		printf( wp_kses( '<p>' . __( 'The most popular company is %1$s with %2$s orders.', 'kebabble' ) . '</p>', $kses_allow ), esc_html( $counts['best_company'] ), esc_html( $counts['best_company_count'] ) );
		echo wp_kses( '<p>' . __( 'Statistics reset daily.', 'kebabble' ) . '</p>', $kses_allow );
		printf( wp_kses( '<p><a href="%1$spost-new.php?post_type=kebabble_orders" class="button button-primary">' . __( 'Create Order', 'kebabble' ) . '</a></p>', $kses_allow ), esc_url( get_admin_url() ) );
	}
}
