<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@revive.today>
 * @license MIT
 *
 * Plugin Name: Kebabble
 * Description: Office food management system.
 * Author: soup-bowl
 * Author URI: https://gitlab.com/soup-bowl/kebabble
 * Version: botman-implementation
 * Text Domain: kebabble
 */

defined( 'ABSPATH' ) || die( 'Operation not permitted.' );

$php_version = explode( '.', phpversion() );
$min_version = 7.2;

$mv = explode( '.', (string) $min_version );
if ( (int) $php_version[0] >= $mv[0] && (int) $php_version[1] >= $mv[1] ) {
	/**
	 * Composer Autoloader.
	 */
	require_once __DIR__ . '/vendor/autoload.php';

	( new DI\Container() )->get( Kebabble\Hooks::class )->main();
} else {
	add_action(
		'admin_notices',
		function() use( $min_version ) {
			echo wp_kses(
				"<div class='notice notice-error'>
				<p>" . sprintf( __( '<b>%1$s</b> is not supported on this PHP version. Please use <b>%2$s or higher</b>', 'kebabble' ), 'Kebabble', (string) $min_version ) . "</p>
				</div>",
				[
					'div' => [
						'class' => [],
					],
					'p' => [],
					'b' => [],
				]
			);
		}
	);
}
