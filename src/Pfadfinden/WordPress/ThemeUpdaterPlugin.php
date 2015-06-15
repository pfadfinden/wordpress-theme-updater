<?php

namespace Pfadfinden\WordPress;

use Shy\WordPress\Plugin;



/**
 * A plugin that hooks the Pfadfinden theme repository into the Theme Updater.
 * 
 * It knows about the way that WordPress handles and stores theme information.
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

		if ( ! $this->settings['key'] ) {
			// Bail out if there is no key.
			return;
		}

		$this->repository = new ThemeRepository( $this->settings );


		$this->addHookMethod( 'themes_api',        'filterApiCall' );
		$this->addHookMethod( 'themes_api_result', 'filterApiResult' );

		$this->addHookMethod( 'themes_update_check_locales', 'filterThemeUpdateLocales' );
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
			return $this->repository->queryThemeInformation( $args->slug, $args->fields, $args->locale );
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

		// FIXME: Workaround to be removed on 2015-10-23
		if ( is_array( $args ) && isset( $args['body']['request'] ) ) {
			// See https://core.trac.wordpress.org/ticket/29079, fixed in 4.2
			$args = unserialize( $args['body']['request'] ); // Unpack original args
		}

		if ( self::ACTION_QUERY_THEMES !== $action || ! isset( $args->browse ) || 'featured' !== $args->browse ) {
			return $result;
		}

		if ( ! $result || ! is_object( $result ) ) {
			// Construct empty result
			// FIXME: Maybe unneccessary
			$result = (object) [
				'info'   => [
					'page'    => 1,
					'pages'   => 0,
					'results' => false,
				],
				'themes' => [],
			];
		}

		$themes = $this->repository->queryFeaturedThemes( $args->fields, $args->locale );
		if ( ! is_wp_error( $themes ) ) {
			$this->spliceThemes( $result, $themes );
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
	 * Filter locales queried for a theme update.
	 * 
	 * Just in time to wait for the theme updates HTTP request…
	 * 
	 * @param array $locales
	 * @return array
	 */
	public function filterThemeUpdateLocales( $locales )
	{
		$this->addHookMethod( 'http_response', 'filterThemeUpdateResponse' );

		return $locales;
	}

	/**
	 * @param string $url
	 * @return bool
	 */
	protected function isThemeUpdateUrl( $url )
	{
		return (bool) preg_match( '@^https?://api.wordpress.org/themes/update-check/1.1/$@', $url );
	}

	/**
	 * Add our updates to the list.
	 * 
	 * @param array  $response
	 * @param array  $args     Original args to request
	 * @param string $url
	 */
	public function filterThemeUpdateResponse( array $response, array $args, $url )
	{
		if ( ! $this->isThemeUpdateUrl( $url ) ) {
			return;
		}

		$this->removeHookMethod( 'http_response', __FUNCTION__ );

		$themes = $this->repository->queryUpdates( [
			// Eliminate worst offenders
			'author'         => false,
			'description'    => false,
			'preview_url'    => false,
			'screenshot_url' => false,
		] );
		if ( is_wp_error( $themes ) ) {
			// Silently fail.
			return $response;
		}

		$updates = json_decode( wp_remote_retrieve_body( $response ), true );

		foreach ( $themes as $theme ) {
			$installed_theme = wp_get_theme( $theme->slug );
			if ( ! $installed_theme->exists() || version_compare( $theme->version, $installed_theme->version, '<=' ) ) {
				continue;
			}

			// Because that’s why: Rename all the fields.
			$updates['themes'][ $theme->slug ] = [
				'theme'       => $theme->slug,
				'new_version' => $theme->version,
				'url'         => $theme->homepage,
				'package'     => $theme->download_link,
			];
		}

		$response['body'] = json_encode( $updates );

		return $response;
	}
}
