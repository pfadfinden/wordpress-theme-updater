<?php
/**
 * PHPUnit bootstrap file
 * 
 * Variant of the one from github.com/tierra/wordpress-plugins-tests
 */

require_once __DIR__ . '/../use/shy-wordpress/src/autoloader.php';
require_once __DIR__ . '/../src/autoloader.php';
require_once __DIR__ . '/autoloader.php';



$GLOBALS['wp_test_plugins'] = array(
	'active_plugins' => array( 'pfadfinden-theme-updater/pfadfinden-theme-updater.php' ),
);



echo 'Setting up WordPress...' . PHP_EOL;

if ( ! isset( $argv )
	|| ( ! in_array( '-v', $argv ) && ! in_array( '--verbose', $argv ) )
) {
	ob_start();
}

require_once ( getenv( 'WP_DEVELOP_DIR' ) ?: '../../../..' )
	. '/tests/phpunit/includes/bootstrap.php';

if ( ob_get_level() ) {
	ob_end_clean();
}
