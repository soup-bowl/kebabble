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
		$existing_pricing = ( ! empty( $term ) ) ? get_term_meta( $term->term_id, 'kebabble_ordpri_org', true ) : '';
		$existing_place   = ( ! empty( $term ) ) ? get_term_meta( $term->term_id, 'kebabble_place_type', true ) : '';
		$place_choices    = explode( ',', ( ! empty( get_option( 'kbfos_settings' )['kbfos_place_type'] ) ) ? get_option( 'kbfos_settings' )['kbfos_place_type'] : 'Kebab, Pizza, Regular' );
		?>
		<div class="form-field">
			<input type="hidden" name="kebabbleNonce" value="<?php echo esc_attr( wp_create_nonce( 'kebabble_nonce' ) ); ?>">
			<label for="tag-kebabble-pricing"><?php esc_html_e( 'Options & Pricing', 'kebabble' ); ?></label>
			<input name="ctOrderPricing" id="tag-kebabble-pricing" value="<?php echo esc_attr( $existing_pricing ); ?>" size="40" type="text" placeholder="food|price,food|price...">
			<p><?php esc_html_e( 'Comma-separated list of menu items, with a pipe-separated option of a price', 'kebabble' ); ?>.</p>
		</div>
		<div class="form-field">
			<label for="tag-kebabble-place-type"><?php esc_html_e( 'Place Type', 'kebabble' ); ?></label>
			<select name="ctPlaceType" id="tag-kebabble-place-type">
				<?php foreach ( $place_choices as $place_choice ) : ?>
					<option <?php echo ( $existing_place === trim( $place_choice ) ) ? 'selected' : ''; ?> id='<?php echo esc_attr( trim( $place_choice ) ); ?>'><?php echo esc_attr( trim( $place_choice ) ); ?></option>
				<?php endforeach; ?>
			</select>
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
		$ext_payopts  = ( ! empty( $term ) ) ? get_term_meta( $term->term_id, '_kebabble_collector_payment_methods_org', true ) : '';
		$ext_charge   = ( ! empty( $term ) ) ? get_term_meta( $term->term_id, 'kebabble_collector_tax', true ) : 0;
		$ext_slackusr = ( ! empty( $term ) ) ? get_term_meta( $term->term_id, 'keabble_collector_slackcode', true ) : '';
		?>
		<div class="form-field">
			<input type="hidden" name="kebabbleNonce" value="<?php echo esc_attr( wp_create_nonce( 'kebabble_nonce' ) ); ?>">
			<label for="kebabble-collector-payopts"><?php esc_attr_e( 'Accepted Payment Methods', 'kebabble' ); ?></label>
			<input name="kebabble-collector-payopts" value="<?php echo esc_attr( $ext_payopts ); ?>" size="40" type="text" placeholder="Cash, Card...">
			<p><?php esc_attr_e( 'Comma-separated list of payment methods', 'kebabble' ); ?>.</p>
		</div>
		<div class="form-field">
			<label for="kebabble-collector-charge"><?php esc_attr_e( 'Tax (non-decimal)', 'kebabble' ); ?></label>
			<input name="kebabble-collector-charge" value="<?php echo (int) $ext_charge; ?>" type="number">
		</div>
		<div class="form-field">
			<label for="kebabble-collector-slackcode"><?php esc_attr_e( 'Slack User Code', 'kebabble' ); ?></label>
			<input name="kebabble-collector-slackcode" value="<?php echo esc_attr( $ext_slackusr ); ?>" size="40" type="text">
			<p><?php esc_attr_e( 'Start an order, and the code will be revealed. Copy the content between <@ and >', 'kebabble' ); ?>.</p>
		</div>
		<?php
	}
}
