<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

$_core_dir = getenv( 'WP_CORE_DIR' );
if ( ! $_core_dir ) {
	$_core_dir = '/tmp/wordpress';
}

// @todo: bring in object cache

// Move code into place
copy( dirname( __FILE__ ) . '/../../src/advanced-cache.php', $_core_dir . '/wp-content/object-cache.php' );

require $_tests_dir . '/includes/bootstrap.php';