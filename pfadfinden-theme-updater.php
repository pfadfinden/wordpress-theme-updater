<?php
/**
 * Plugin Name: Pfadfinden Theme Updater
 * Plugin URI: http://lab.hanseaten-bremen.de/themes/
 * Description: Adds the Pfadfinden theme repository to your choice of themes. Requires an API key.
 * Version: 0.1
 * Author: Philipp Cordes
 * Text Domain: pfadfinden-theme-updater
 * Domain Path: /languages/
 * License: GPL2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'I’m a plugin.' );
}


// Load localized strings.
load_plugin_textdomain( 'pfadfinden-theme-updater', false, basename( __DIR__ ) . '/languages' );


if ( ! function_exists( 'trigger_pfadfinden_plugin_error' ) ) {
	/**
	 * Show an error message.
	 * 
	 * @see http://www.squarepenguin.com/wordpress/?p=6 Inspiration
	 * 
	 * @param string  $message
	 * @param integer $type
	 * @return boolean
	 */
	function trigger_pfadfinden_plugin_error( $message, $type )
	{
		if ( isset( $_GET['action'] ) && 'error_scrape' === $_GET['action'] ) {
			echo $message;
			return true;
		}

		return trigger_error( $message, $type );
	}
}


// Check for suitable environment
if ( defined( 'PHP_VERSION_ID' ) && PHP_VERSION_ID >= 50400 ) {
	if ( ! class_exists( 'Shy\WordPress\Plugin' ) ) {
		// If the required classes aren’t already used by another Plugin, register the autoloader
		require_once __DIR__ . '/use/shy-wordpress/src/autoloader.php';
	}

	// Register our autoloader
	require_once __DIR__ . '/src/autoloader.php';

	return new \Pfadfinden\WordPress\ThemeUpdaterPlugin();
}


// Display error message
trigger_pfadfinden_plugin_error(
	__( 'You need at least PHP 5.4 to use Pfadfinden Theme Updater.', 'pfadfinden-theme-updater' ),
	E_USER_ERROR
);
