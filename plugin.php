<?php
/*
Plugin Name: Kebabble
Description: Office food management system. Depends on ACF (comes packaged-in).
Author: soup-bowl
Author URI: https://github.com/soup-bowl/kebabble
Version: 0.1.6 Alpha
*/

require_once __DIR__ . '/vendor/autoload.php';

$hookup = (new kebabble\hooks())->main();