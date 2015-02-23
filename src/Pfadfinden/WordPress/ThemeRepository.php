<?php

namespace Pfadfinden\WordPress;

use Shy\WordPress\Hook;



class ThemeRepository
{
	const URL = 'http://lab.hanseaten-bremen.de/themes/';

	const ACTION_QUERY_THEMES      = 'query_themes';
	const ACTION_FEATURE_LIST      = 'feature_list';
	const ACTION_THEME_INFORMATION = 'theme_information';


	/**
	 * Slugs of managed themes.
	 * 
	 * @var array<string>
	 */
	private $known_themes = array( 'bdp-reloaded', 'buena' );


	/**
	 * Whether theme information is available.
	 * 
	 * @param string $theme_slug
	 * @return boolean
	 */
	public function isKnownTheme( $theme_slug )
	{
		return in_array( $theme_slug, $this->known_themes, true );
	}


	/**
	 * 
	 * @param string $action One of the action constants
	 * @param array  $params
	 * @return array
	 */
	protected function doApiQuery( $action, array $params = array() )
	{
		$params['action'] = $action;

		$response = wp_remote_get( self::URL . 'api/?key=' . $this->key, array(
			'body' => json_encode( $params ),
		) );

		return json_decode( $response['body'], true );
	}


	/**
	 * Query managed themes to add.
	 * 
	 * Exactly one criterion is present.
	 * 
	 * @param object $args {
	 *    @type integer        $per_page
	 *    @type array<boolean> $fields   to explicitly include or exclude
	 *    @type string         $browse   'featured', 'popular' or 'new' if present
	 *    @type string         $search   search term if present
	 *    @type array<string>  $tag      list of tag slugs if present, such as 'accessibility-ready'
	 * }
	 * @return array<object {
	 *    @type string  $name
	 *    @type string  $slug lowercase, hyphenated
	 *    @type string  $version
	 *    @type string  $author
	 *    @type string  $preview_url
	 *    @type string  $screenshot_url
	 *    @type float   $rating between 0 and 100
	 *    @type integer $num_ratings
	 *    @type integer $downloaded
	 *    @type string  $last_updated Y-m-d
	 *    @type string  $homepage
	 *    @type string  $description
	 *    @type array   $tags
	 * }>
	 */
	protected function queryThemes( $args )
	{
		if ( ! isset( $args->browse ) || 'featured' !== $args->browse ) {
			return array();
		}

		// API defaults
		array('tested' => false, 'downloadlink' => false,);

		// WordPress default fields
		array('description' => true, 'sections' => false, 'tested' => true, 'requires' => true,
		'rating' => true, 'downloaded' => true, 'downloadlink' => true, 'last_updated' => true,
		'homepage' => true, 'tags' => true, 'num_ratings' => true);

		return array(
			(object) array(
				'name'           => 'Pfadfinden reloaded',
				'slug'           => 'bdp-reloaded',
				'version'        => '0.1',
				'author'         => 'corphi',
				'preview_url'    => self::URL . 'bdp-reloaded/preview/',
				'screenshot_url' => self::URL . 'bdp-reloaded/screenshot.png',
				'rating'         => 50.0 ,
				'num_ratings'    => 1,
				'downloaded'     => 0,
				'last_updated'   => '2014-07-14',
				'homepage'       => self::URL . 'bdp-reloaded/',
				'description'    => 'The first incarnation of a planned redesign of pfadfinden.de. Now available for every BdP group. Base design by Philipp Steinmetzger, improved and made into a theme by Philipp Cordes (PC)',
				'tags'           => array(
					
				),
			),
			(object) array(
				'name'           => 'Buena',
				'slug'           => 'buena',
				'version'        => '0.1',
				'author'         => 'corphi',
				'preview_url'    => self::URL . 'buena/preview/',
				'screenshot_url' => self::URL . 'buena/screenshot.png',
				'rating'         => 50.0 ,
				'num_ratings'    => 1,
				'downloaded'     => 0,
				'last_updated'   => '2014-10-18',
				'homepage'       => self::URL . 'buena/',
				'description'    => 'The new look of pfadfinden.de.',
				'tags'           => array(
					
				),
			),
		);
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
	 * Query information about a specific theme.
	 * 
	 * @param string         $slug   theme slug
	 * @param array<boolean> $fields to explicitly include or exclude
	 * @return object {
	 *    @type string  $name
	 *    @type string  $slug
	 *    @type string  $version
	 *    @type string  $author
	 *    @type string  $preview_url
	 *    @type string  $screenshot_url
	 *    @type float   $rating between 0.0 and 100.0
	 *    @type integer $num_ratings
	 *    @type integer $downloaded
	 *    @type string  $last_updated
	 *    @type string  $homepage
	 *    @type array   $sections {
	 *       @type string $description
	 *    }
	 *    @type string  $description empty string when having sections
	 *    @type string  $download_link
	 *    @type array<string> $tags keys are tag slugs, values also lowercase. strange.
	 * }
	 */
	protected function queryThemeInformation( $slug = '', array $fields = array() )
	{
		// Often $args->fields['sections'] === false, $args->fields['tags'] === false.
		/*
		 *   object(stdClass)[59]
		 *     public 'name'           => string 'Magazine Basic'
		 *     public 'slug'           => string 'magazine-basic'
		 *     public 'version'        => string '1.1'
		 *     public 'author'         => string 'tinkerpriest'
		 *     public 'preview_url'    => string 'http://wp-themes.com/?magazine-basic'
		 *     public 'screenshot_url' => string 'http://wp-themes.com/wp-content/themes/magazine-basic/screenshot.png'
		 *     public 'rating'         => float 80
		 *     public 'num_ratings'    => int 1
		 *     public 'homepage'       => string 'http://wordpress.org/themes/magazine-basic'
		 *     public 'description'    => string 'A basic magazine style layout with a fully customizable layout through a backend interface. Designed by <a href="http://bavotasan.com">c.bavota</a> of <a href="http://tinkerpriestmedia.com">Tinker Priest Media</a>.'
		 *     public 'download_link'  => string 'http://wordpress.org/themes/download/magazine-basic.1.1.zip'
		 */
		return (object) array(
			'name'           => 'Pfadfinden reloaded',
			'slug'           => $slug,
			'version'        => '',
			'author'         => '',
			'preview_url'    => '',
			'screenshot_url' => '',
			'rating'         => 50.0,
			'num_ratings'    => 1,
			'downloaded'     => 0,
			'last_updated'   => '2014-07-14',
			'homepage'       => '',
			'sections'       => array( // if not explicitly omitted
				'description' => '',
			),
			'description'    => '', // when having sections: empty string
			'download_link'  => self::URL . 'bdp-reloaded/download/?key=' . $this->key,
			'tags'           => array(
				'tag' => 'tag',
			),
		);

		$response = wp_remote_get( self::URL . 'api/?key=' . $this->key, array(
			'body' => json_encode( array(
				'action' => self::ACTION_THEME_INFORMATION,
				'slug'   => $slug,
				'fields' => $fields,
			) ),
		) );

		$response;
	}

	/**
	 * Query information about updates for installed themes.
	 * 
	 * @return array<string, array<string> {
	 *    @type string $theme
	 *    @type string $version
	 *    @type string $url
	 *    @type string $package
	 * }>
	 */
	protected function queryUpdates()
	{
		return array(
			'bdp-reloaded' => array(
				'theme'       => 'bdp-reloaded',
				'new_version' => '1.0',
				'url'         => self::URL . 'bdp-reloaded/',
				'package'     => self::URL . 'bdp-reloaded/download/?key=' . $this->key,
			),
		);
	}


	/**
	 * @var \Pfadfinden\WordPress\ThemeUpdaterSettings
	 */
	protected $settings;


	public function __construct( ThemeUpdaterSettings $settings )
	{
		$this->settings = $settings;
	}


	/**
	 * Called to check for updates.
	 * 
	 * @return void
	 */
	public function injectUpdates()
	{
		/**
		 * @var object $update {
		 *    @type integer $last_checked timestamp
		 *    @type array   $checked
		 *    @type array   $response indexed by theme slug {
		 *       @type string $url
		 *       @type string $new_version
		 *    }
		 *    @type array   $translations
		 * }
		 */
		$update = get_site_transient( 'update_themes' );

		if ( ! $update ) {
			return;
		}

		if ( ob_start() ) {
			var_dump($update);
			file_put_contents(
				sprintf( '%s/update-%013.4f.txt', WP_CONTENT_DIR, microtime( true ) - strtotime( '2014-07-17' ) ),
				ob_get_clean()
			);
			ob_end();
		}
		return; // FIXME

		$theme_updates = $this->queryUpdates();
		if ( ! $theme_updates ) {
			return;
		}

		foreach ( $theme_updates as $slug => $theme_update ) {
			// FIXME: Evtl. $update->checked[ $slug ] = $current_version setzen.
			$update->response[ $slug ] = $theme_update;
		}
		set_site_transient( 'update_themes', $update );
	}
}
