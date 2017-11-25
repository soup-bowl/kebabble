<?php
/*
Plugin Name: Kebabble
Description: Office food management system. Depends on ACF (comes packaged-in).
Version: 0.1.2 Alpha
*/

require_once __DIR__ . '/vendor/autoload.php';
include_once __DIR__ . '/advanced-custom-fields/acf.php';

$hookup = new kebabble\hooks();
$hookup->main();