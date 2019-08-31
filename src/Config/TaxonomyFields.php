<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@soupbowl.io>
 * @license MIT
 */

namespace Kebabble\Config;

use WP_Term;

/**
 * Display formatting on the kebabble taxonomies.
 */
class TaxonomyFields {
	/**
	 * Registers WordPress hooks.
	 *
	 * @return void
	 */
	public function hook_taxonomy_fields() {
		add_action( 'kebabble_company_add_form_fields', [ &$this, 'company_options_empty' ] );
		add_action( 'kebabble_company_edit_form_fields', [ &$this, 'company_options' ] );
		add_action( 'kebabble_collector_add_form_fields', [ &$this, 'collector_options_empty' ] );
		add_action( 'kebabble_collector_edit_form_fields', [ &$this, 'collector_options' ] );
	}

	/**
	 * Runs company_options without arguments, for hook purposes.
	 *
	 * @return void Prints return on the page.
	 */
	public function company_options_empty() {
		$this->company_options();
	}

	/**
	 * Runs collector_options without arguments, for hook purposes.
	 *
	 * @return void Prints return on the page.
	 */
	public function collector_options_empty() {
		$this->collector_options();
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
		<input type="hidden" name="kebabbleNonce" value="<?php echo esc_attr( wp_create_nonce( 'kebabble_nonce' ) ); ?>">
			<label for="tag-kebabble-pricing">Options & Pricing</label>
			<input name="ctOrderPricing" id="tag-kebabble-pricing" value="<?php echo esc_attr( $existing ); ?>" size="40" type="text" placeholder="food|price,food|price...">
			<p>Comma-separated list of menu items, with a pipe-separated option of a price.</p>
		</div>
		<?php
	}

	/**
	 * Shows the menu specification input field.
	 *
	 * @param WP_Term|null $term ID of the term being edited, if already existing.
	 * @return void Prints return on the page.
	 */
	public function collector_options( ?WP_Term $term = null ):void {
		$existing = ( ! empty( $term ) ) ? get_term_meta( $term->term_id, '_kebabble_collector_payment_methods_org', true ) : '';
		?>
		<div class="form-field">
		<input type="hidden" name="kebabbleNonce" value="<?php echo esc_attr( wp_create_nonce( 'kebabble_nonce' ) ); ?>">
			<label for="kebabble-collector-payopts">Accepted Payment Methods</label>
			<input name="kebabble-collector-payopts" value="<?php echo esc_attr( $existing ); ?>" size="40" type="text" placeholder="Cash, Card...">
			<p>Comma-separated list of payment methods.</p>
		</div>
		<?php
	}
}
