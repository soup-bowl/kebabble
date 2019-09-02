<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@soupbowl.io>
 * @license MIT
 */

/**
 * Composer.
 */
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Replicate the WordPress option functionality used by Kebabble to retrieve settings.
 *
 * @param string|null $option Dummy input variable. Return will always be the same.
 * @return string[]
 */
function get_option( $option = null ) {
	return [
		'kbfos_botkey'     => 'xorb-somefakestring',
		'kbfos_botchannel' => '#example',
		'kbfos_payopts'    => 'Cash, Other',
	];
}

WP_Mock::bootstrap();
