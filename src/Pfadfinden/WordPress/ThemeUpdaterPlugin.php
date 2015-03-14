<?php

namespace Pfadfinden\WordPress;

use Shy\WordPress\Plugin;



/**
 * A plugin that hooks the Pfadfinden theme repository into the Theme Updater.
 * 
 * @author Philipp Cordes <philipp.cordes@pfadfinden.de>
 */
class ThemeUpdaterPlugin extends Plugin
{
	const ACTION_QUERY_THEMES      = 'query_themes';
	const ACTION_FEATURE_LIST      = 'feature_list';
	const ACTION_THEME_INFORMATION = 'theme_information';


	/**
	 * @var ThemeUpdaterSettings
	 */
	protected $settings;

	/**
	 * @var ThemeRepository
	 */
	protected $repository;


	public function __construct()
	{
		$this->settings   = new ThemeUpdaterSettings();
		$this->repository = new ThemeRepository( $this->settings );


//		$this->addHookMethod( 'themes_api_args',   'filterApiArgs' );
		$this->addHookMethod( 'themes_api',        'filterApiCall' );
		$this->addHookMethod( 'themes_api_result', 'filterApiResult' );

//		$this->addHookMethod( 'theme_install_actions', 'filterInstallActions' );

		$this->addHookMethod( 'wp_update_themes', 'injectUpdates', 20 );
	}


	/**
	 * Filter arguments passed a Theme API call.
	 * 
	 * Currently unused.
	 * 
	 * @param object $args
	 * @param string $action 'theme_information', 'feature_list', 'query_themes'
	 * @return object
	 */
	public function filterApiArgs( $args, $action )
	{
		return $args;
	}

	/**
	 * Replace a Theme API call.
	 * 
	 * Actually, only the call for theme information in special cases.
	 * 
	 * @param \WP_Error|object|false $result
	 * @param string $action 'theme_information', 'feature_list' or 'query_themes'
	 * @param object $args
	 * @return \WP_Error|object|array|false
	 */
	public function filterApiCall( $result, $action, $args )
	{
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( self::ACTION_THEME_INFORMATION === $action
			&& $this->repository->isKnownTheme( $args->slug )
		) {
			// Only handle our theme information calls
			return $this->repository->queryThemeInformation( $args );
		}

		return $result;
	}

	/**
	 * Filter a Theme API result.
	 * 
	 * Inject our themes at appropriate places.
	 * 
	 * @param object|\WP_Error $result
	 * @param string $action 'theme_information', 'feature_list', 'query_themes'
	 * @param object|array $args An array after using built-in API, object otherwise.
	 * @return object
	 */
	public function filterApiResult( $result, $action, $args )
	{
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( is_array( $args ) ) {
			// Workaround for https://core.trac.wordpress.org/ticket/29079
			$args = unserialize( $args['body']['request'] ); // Unpack original args
		}

		if ( self::ACTION_QUERY_THEMES === $action ) {
			if ( ! $result || ! is_object( $result ) ) {
				// Construct empty result
				// FIXME: Maybe unneccessary
				$result = (object) array(
					'info'   => array(
						'page'    => 1,
						'pages'   => 0,
						'results' => false,
					),
					'themes' => array(),
				);
			}

			$this->spliceThemes( $result, $this->queryThemes( $args ) );
		}

		return $result;
	}

	/**
	 * Splice additional themes into an existing Theme API result.
	 *
	 * Put them in front.
	 *
	 * @param object $result {
	 *    @type object $info {
	 *       @type integer|false  $results have browser count if false
	 *       @type integer|string $page
	 *       @type integer        $pages may be 0
	 *    }
	 *    @type array  $themes
	 * }
	 * @param array $themes
	 * @return void
	 */
	public function spliceThemes( $result, array $themes )
	{
		$add = function ( $number, $increment ) {
			return is_integer( $number ) ? $number + $increment : $number;
		};

		if ( is_array( $result->info ) ) {
			$result->info['results'] = $add( $result->info['results'], count( $themes ) );
		} elseif ( is_object( $result->info ) ) {
			// Seemed to be an object once…
			$result->info->results   = $add( $result->info->results,   count( $themes ) );
		}

		array_splice( $result->themes, 0, 0, $themes );
	}


	/**
	 * @param array<string> $actions Array of HTML tags, primarily &lt;a&gt;
	 * @param object $theme
	 * @return array<string>
	 */
	public function filterInstallActions( array $actions, $theme )
	{
		if ( ! $this->repository->isKnownTheme( $theme->slug ) ) {
			return $actions;
		}

		return $actions;
	}
}
