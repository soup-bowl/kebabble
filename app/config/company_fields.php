<?php
/**
 * Display formatting on the kebabble company taxonomy.
 *
 * @package kebabble
 * @author soup-bowl
 */

namespace kebabble\config;

/**
 * Display formatting on the kebabble company taxonomy.
 */
class company_fields {
	/**
	 * Shows the menu specification input field.
	 *
	 * @param int $term_id ID of the term being edited, if already existing.
	 * return void
	 */
	public function companyOptionsSetup( int $term_id = 0 ) {
		$existing = get_term_meta( $term_id, 'kebabble_ordpri_org', true );
		?>
		<div class="form-field">
			<label for="tag-kebabble-pricing">Options & Pricing</label>
			<input name="ctOrderPricing" id="tag-kebabble-pricing" value="<?php echo $existing; ?>" size="40" type="text" placeholder="food|price,food|price...">
			<p>Comma-separated list of menu items, with a pipe-separated option of a price.</p>
		</div>
		<?php
	}
}
