<?php
/*
Plugin Name: Kebabble
Description: Office food management system.
Author: soup-bowl
Author URI: https://github.com/soup-bowl/kebabble
Version: 0.1.7 Alpha
*/

require_once __DIR__ . '/vendor/autoload.php';

(new DI\Container())->get('kebabble\hooks')->main();