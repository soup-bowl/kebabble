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
		$counts = $this->stats->order_counts();
		?>
		<p>There has been a <b>total of <?php echo esc_html( $counts['max'] ); ?></b> orders, <b><?php echo esc_html( $counts['month'] ); ?></b> within the previous month.</p>
		<p>The most popular company is <b><?php echo esc_html( $counts['best_company'] ); ?></b> with <b><?php echo esc_html( $counts['best_company_count'] ); ?></b> orders.</p>
		<p><i>(Statistics reset daily).</i></p>
		<p><a href="<?php echo esc_url( get_admin_url() ); ?>post-new.php?post_type=kebabble_orders" class="button button-primary">Create Order</a></p>
		<?php
	}
}
