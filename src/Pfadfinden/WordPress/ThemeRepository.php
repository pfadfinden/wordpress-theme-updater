<?php

namespace Pfadfinden\WordPress;



/**
 * A theme repository.
 * 
 * It’s a simple wrapper around a web service mimicking the wordpress.org theme repository.
 * 
 * @author Philipp Cordes <philipp.cordes@pfadfinden.de>
 */
class ThemeRepository
{
	const URL = 'http://lab.hanseaten-bremen.de/themes/api/';


	/**
	 * Slugs of managed themes.
	 * 
	 * FIXME: Move to a transient.
	 * 
	 * @var array<string>
	 */
	private $known_themes = [ 'bdp-reloaded', 'bdp-test', 'buena' ];


	/**
	 * @var ThemeUpdaterSettings
	 */
	protected $settings;


	public function __construct( ThemeUpdaterSettings $settings )
	{
		$this->settings = $settings;
	}


	/**
	 * Whether theme information is available.
	 * 
	 * @param string $theme_slug
	 * @return bool
	 */
	public function isKnownTheme( $theme_slug )
	{
		return in_array( $theme_slug, $this->known_themes, true );
	}


	/**
	 * Wrapper around HTTP calls, always returns an array of theme information.
	 * 
	 * @param string $action One of the supported actions of the repository
	 * @param array  $params Parameters for the action
	 * @param string $locale
	 * @return array<object>|\WP_Error
	 */
	protected function doApiQuery( $action, array $params = [], $locale = '' )
	{
		$url_params = [
			'key'    => $this->settings['key'],
			'action' => $action,
		];
		if ( $params ) {
			if ( function_exists( 'gzcompress' ) ) {
				$url_params['gzparams'] = gzcompress( json_encode( $params ), 9 );
			} else {
				$url_params['params'] = json_encode( $params );
			}
		}
		$url_params = array_map( 'rawurlencode', $url_params );

		$url = add_query_arg( $url_params, self::URL );
		if ( strlen( $url ) > 2000 ) {
			// Lengths beyond 2000 seem unhealthy.
			return new \WP_Error(
				815,
				__( 'Your theme repository query is too long.', 'pfadfinden-theme-updater' )
			);
		}

		$headers = [];
		if ( ! strlen( $locale ) ) {
			$locale = get_locale();
		}
		if ( strlen( $locale ) ) {
			$locale = str_replace( '_', '-', $locale );
			$headers['Accept-Language'] = "$locale, en; q=0.6, *; q=0.1";
		}

		// A GET request allows for caching
		$response = wp_remote_get( $url, [
			'headers' => $headers,
		] );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( isset( $body['type'] ) && 'success' === $body['type'] ) {
			return array_map( function ( array $theme ) {
				return (object) $theme;
			}, $body['themes'] );
		}

		if ( WP_DEBUG ) {
			trigger_error( wp_remote_retrieve_body( $response ), E_USER_ERROR );
		}
		$error = new \WP_Error(
			wp_remote_retrieve_response_code( $response ),
			isset( $body['message'] ) ? $body['message'] : __( 'Unknown theme repository server error, no message attached.', 'pfadfinden-theme-updater' )
		);
		if ( isset( $body['exception'] ) ) {
			$error->add_data( $body['exception'] );
		}

		return $error;
	}


	/**
	 * @param array<bool> $fields to explicitly include or exclude
	 * @param string      $locale
	 * @return array<object {
	 *    @type string $name
	 *    @type string $slug lowercase, hyphenated
	 *    @type string $version
	 *    @type string $author
	 *    @type string $preview_url
	 *    @type string $screenshot_url
	 *    @type float  $rating between 0 and 100
	 *    @type int    $num_ratings
	 *    @type int    $downloaded
	 *    @type string $last_updated Y-m-d
	 *    @type string $homepage
	 *    @type string $description
	 *    @type array  $tags
	 * }>
	 */
	public function queryFeaturedThemes( array $fields = [], $locale = '' )
	{
		return $this->doApiQuery( 'featured', [ 'fields' => $fields ], $locale );
	}

	/**
	 * Query information about a specific theme.
	 * 
	 * @param string|array $slugs  theme slug(s)
	 * @param array<bool>  $fields to explicitly include or exclude
	 * @param string       $locale
	 * @return object {
	 *    @type string $name
	 *    @type string $slug
	 *    @type string $version
	 *    @type string $author
	 *    @type string $preview_url
	 *    @type string $screenshot_url
	 *    @type float  $rating between 0.0 and 100.0
	 *    @type int    $num_ratings
	 *    @type int    $downloaded
	 *    @type string $last_updated
	 *    @type string $homepage
	 *    @type array  $sections {
	 *       @type string $description
	 *    }
	 *    @type string $description empty string when having sections
	 *    @type string $download_link
	 *    @type array<string> $tags keys are tag slugs, values also lowercase. strange.
	 * }
	 */
	public function queryThemeInformation( $slugs, array $fields = [], $locale = '' )
	{
		$themes = $this->doApiQuery( 'information', [
			'slugs'  => (array) $slugs,
			'fields' => $fields,
		], $locale );
		if ( is_wp_error( $themes ) ) {
			return $themes;
		}

		if ( is_string( $slugs ) ) {
			if ( count( $themes ) !== 1 ) {
				return new \WP_Error( __( 'Ambiguous result for single theme information call.', 'pfadfinden-theme-updater' ) );
			}

			return reset( $themes );
		}

		return $themes;
	}

	/**
	 * Query information about updates for installed themes.
	 * 
	 * @param array<bool> $fields to explicitly include or exclude
	 * @param string      $locale
	 * @return array<object>
	 */
	public function queryUpdates( array $fields = [], $locale = '' )
	{
		// FIXME: Only include installed themes
		return $this->queryThemeInformation( $this->known_themes, $fields, $locale );
	}
}
