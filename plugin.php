<?php
/*
Plugin Name: Kebabble
Description: Office food management system. Depends on ACF (comes packaged-in).
Author: soup-bowl
Author URI: https://github.com/soup-bowl/kebabble
Version: 0.1.2 Alpha
*/

require_once __DIR__ . '/vendor/autoload.php';
if (file_exists(__DIR__ . '/advanced-custom-fields')) {
	include_once __DIR__ . '/advanced-custom-fields/acf.php';
} else {
	include_once __DIR__ . '/../advanced-custom-fields/acf.php';
}

$hookup = new kebabble\hooks();
$hookup->main();