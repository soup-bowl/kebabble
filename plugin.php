<?php
/**
 * Plugin Name: Kebabble
 * Description: Office food management system.
 * Author: soup-bowl
 * Author URI: https://github.com/soup-bowl/kebabble
 * Version: 0.2.1-Alpha
 *
 * @package kebabble
 */

defined( 'ABSPATH' ) || die( 'Operation not permitted.' );

/**
 * Composer Autoloader.
 */
require_once __DIR__ . '/vendor/autoload.php';

( new DI\Container() )->get( 'kebabble\hooks' )->main();
