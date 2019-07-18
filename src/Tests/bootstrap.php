<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@revive.today>
 * @license MIT
 */

/**
 * Composer.
 */
require_once __DIR__ . '/../../vendor/autoload.php';

// Replicate the WordPress option functionality used by Kebabble to retrieve settings.
function get_option( $option ) {
	return [
		'kbfos_botkey'      => 'xorb-somefakestring',
		'kbfos_botchannel'  => '#example',
		'kbfos_pullthrough' => 1,
		'kbfos_payopts'     => 'Cash, Other'
	];
}

WP_Mock::bootstrap();
