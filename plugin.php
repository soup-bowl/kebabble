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
 * Version: 0.4.0-Alpha
 * Text Domain: kebabble
 */

defined( 'ABSPATH' ) || die( 'Operation not permitted.' );

$php_version = explode( '.', phpversion() );
if ( (int) $php_version[0] >= 7 && (int) $php_version[1] >= 2 ) {
	/**
	 * Composer Autoloader.
	 */
	require_once __DIR__ . '/vendor/autoload.php';

	( new DI\Container() )->get( Kebabble\Hooks::class )->main();
} else {
	add_action(
		'admin_notices',
		function() {
			?>
			<div class="notice notice-error">
				<p><b>Kebabble</b> is not supported on this PHP version. Please use <b>7.2 or higher</b>.</p>
			</div>
			<?php
		}
	);
}
