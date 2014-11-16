<?php
/**
 * Plugin Name: Pfadfinden Bootstrap
 * Plugin URI: http://lab.hanseaten-bremen.de/pfadfinden-bootstrap/
 * Description: Allows bootstrapping a Pfadfinden theme installation. Also includes a few HTML5 fixes.
 * Version: 0.1
 * Author: Philipp Cordes
 * License: GPL2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'I’m a plugin.' );
}


/**
 * Show an error message.
 * 
 * @see http://www.squarepenguin.com/wordpress/?p=6
 * 
 * @param string  $message
 * @param integer $type
 * @return boolean
 */
function trigger_pfadfinden_bootstrap_error( $message, $type )
{
	if ( isset( $_GET['action'] ) && 'error_scrape' === $_GET['action'] ) {
		echo $message;
		return true;
	}

	return trigger_error( $message, $type );
}


// FIXME: Check for suitable environment
if ( defined( 'PHP_VERSION_ID' ) && PHP_VERSION_ID >= 50300 ) {
	if ( ! class_exists( 'Shy\WordPress\Plugin' ) ) {
		// If the required classes aren’t already used by another Plugin, register the autoloader
		require_once __DIR__ . '/use/shy-wordpress/autoloader.php';
	}

	// Register our autoloader
	require_once __DIR__ . '/src/autoloader.php';

	return new \Pfadfinden\WordPress\Bootstrap\BootstrapPlugin();
}


// Display error message
trigger_pfadfinden_bootstrap_error(
	'You need at least PHP 5.4 to use Pfadfinden Bootstrap.',
	E_USER_ERROR
);
