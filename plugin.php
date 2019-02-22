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
 * Author URI: https://github.com/soup-bowl/kebabble
 * Version: 0.3.2-Alpha
 */

defined( 'ABSPATH' ) || die( 'Operation not permitted.' );

/**
 * Composer Autoloader.
 */
require_once __DIR__ . '/vendor/autoload.php';

( new DI\Container() )->get( 'Kebabble\Hooks' )->main();
