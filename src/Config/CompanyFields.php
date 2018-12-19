<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@revive.today>
 * @license MIT
 */

namespace Kebabble\Config;

use WP_Term;

/**
 * Display formatting on the kebabble company taxonomy.
 */
class CompanyFields {
	/**
	 * Runs company_options without arguments, for hook purposes.
	 *
	 * @return void Prints return on the page.
	 */
	public function company_options_empty() {
		$this->company_options();
	}
	
	/**
	 * Shows the menu specification input field.
	 *
	 * @param WP_Term|null $term ID of the term being edited, if already existing.
	 * @return void Prints return on the page.
	 */
	public function company_options( ?WP_Term $term = null ):void {
		$existing = ( ! empty( $term ) ) ? get_term_meta( $term->term_id, 'kebabble_ordpri_org', true ) : '';
		?>
		<div class="form-field">
			<label for="tag-kebabble-pricing">Options & Pricing</label>
			<input name="ctOrderPricing" id="tag-kebabble-pricing" value="<?php echo $existing; ?>" size="40" type="text" placeholder="food|price,food|price...">
			<p>Comma-separated list of menu items, with a pipe-separated option of a price.</p>
		</div>
		<?php
	}
}
