<?php

/**
 * Try to load a Pfadfinden WordPress class.
 * 
 * @param string $name
 * @return boolean
 */
function pfadfinden_wordpress_tests_autoloader( $name )
{
	if ( substr( $name, 0, 27 ) !== 'Pfadfinden\\WordPress\\Tests\\' ) {
		return false;
	}

	$name = __DIR__ . '/' . str_replace( '\\', DIRECTORY_SEPARATOR, $name ) . '.php';
	return is_file( $name ) && include( $name );
}

spl_autoload_register( 'pfadfinden_wordpress_tests_autoloader' );
