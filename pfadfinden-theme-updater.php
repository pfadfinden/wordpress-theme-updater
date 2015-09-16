<?php
/*
Plugin Name: Pfadfinden Theme Updater
Plugin URI: http://lab.hanseaten-bremen.de/themes/
Description: Adds the Pfadfinden theme repository to your choice of themes. Requires an API key.
Version: 0.2
Author: Philipp Cordes
Text Domain: pfadfinden-theme-updater
Domain Path: /languages/
License: GPLv2 or later
*/

if ( ! defined( 'ABSPATH' ) ) {
	die( 'I’m a plugin.' );
}


/**
 * Load localized strings for the plugin.
 * 
 * @see http://geertdedeckere.be/article/loading-wordpress-language-files-the-right-way
 */
function pfadfinden_theme_updater_load_textdomain()
{
	remove_action( 'init', __FUNCTION__ );

	$domain = 'pfadfinden-theme-updater';
	// Filter known from load_plugin_textdomain().
	$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	load_textdomain( $domain, WP_LANG_DIR . "/pfadfinden-theme-updater/$domain-$locale.mo" );
	load_plugin_textdomain( $domain, false, basename( __DIR__ ) . '/languages/' );
}
add_action( 'init', 'pfadfinden_theme_updater_load_textdomain' );


if ( ! function_exists( 'trigger_pfadfinden_plugin_error' ) ) {
	/**
	 * Show an error message.
	 * 
	 * @see http://www.squarepenguin.com/wordpress/?p=6 Inspiration
	 * 
	 * @param string $message
	 * @param int    $type    optional
	 * @return bool
	 */
	function trigger_pfadfinden_plugin_error( $message, $type = 0 )
	{
		if ( isset( $_GET['action'] ) && 'error_scrape' === $_GET['action'] ) {
			echo $message;
			return true;
		}

		if ( ! $type ) {
			$type = E_USER_WARNING;
		}

		return trigger_error( $message, $type );
	}
}


// Check for suitable environment
if ( defined( 'PHP_VERSION_ID' ) && PHP_VERSION_ID >= 50400 ) {
	// If we’re the first user of the library, use the bundled one
	if ( ! class_exists( 'Shy\WordPress\Plugin' ) ) {
		pfadfinden_theme_updater_load_textdomain();
		if ( ! include_once __DIR__ . '/use/shy-wordpress/src/autoloader.php' ) {
			trigger_pfadfinden_plugin_error(
				__( 'Couldn’t load required library “shy-wordpress”. Reinstalling the plugin may solve this problem.', 'pfadfinden-theme-updater' ),
				E_USER_ERROR
			);
			return;
		}
	}

	// Register our autoloader
	if ( ! include_once __DIR__ . '/src/autoloader.php' ) {
		pfadfinden_theme_updater_load_textdomain();
		trigger_pfadfinden_plugin_error(
			__( 'The plugin is incomplete. Reinstalling it may solve this problem.', 'pfadfinden-theme-updater' ),
			E_USER_ERROR
		);
		return;
	}

	// PHP < 5.3 issues a parse error if we instance the class here
	return require_once __DIR__ . '/startup.php';
}


// Display error message
pfadfinden_theme_updater_load_textdomain();
trigger_pfadfinden_plugin_error(
	sprintf(
		__( 'You need at least PHP 5.4 to use Pfadfinden Theme Updater. Your are using %s.', 'pfadfinden-theme-updater' ),
		PHP_VERSION
	),
	E_USER_ERROR
);

if ( false ) {
	// Dummy calls for translation to include metadata in translation files
	__( 'Pfadfinden Theme Updater', 'pfadfinden-theme-updater' );
	__( 'Adds the Pfadfinden theme repository to your choice of themes. Requires an API key.', 'pfadfinden-theme-updater' );
}
