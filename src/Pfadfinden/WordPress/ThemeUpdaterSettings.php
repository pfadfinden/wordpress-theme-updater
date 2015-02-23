<?php

namespace Pfadfinden\WordPress;

use Shy\WordPress\SettingsPage;



/**
 * The code managing the plugin settings.
 */
class ThemeUpdaterSettings extends SettingsPage
{
	public function __construct()
	{
		parent::__construct( 'pfadfinden-theme-updater' );

		add_filter( 'plugin_action_links', 'filterPluginActions', 10, 4 );
	}


	/**
	 * Add our settings entry to the plugin actions.
	 * 
	 * @param array<string> $actions
	 * @param string        $plugin_file
	 * @param array         $plugin_data
	 * @param string        $context
	 * @return array<string>
	 */
	public function filterPluginActions( array $actions, $plugin_file, array $plugin_data, $context )
	{
		if ( substr( $plugin_file, -28 ) !== 'pfadfinden-theme-updater.php' ) {
			return $actions;
		}

		return array(
			'settings' => sprintf(
				'<a href="options-general.php?page=%s">%s</a>',
				esc_attr( $this->slug ),
				esc_html__( 'Settings' )
			),
		) + $actions;
	}


	protected function getPageTitle()
	{
		return __( 'Pfadfinden Theme Updater Settings', 'pfadfinden-theme-updater' );
	}

	protected function getMenuTitle()
	{
		return __( 'Pfadfinden Theme Updater', 'pfadfinden-theme-updater' );
	}


	public function registerSettings()
	{
		$this->addSection( '', 'plugin' );

		$this->addTextField(
			'key',
			__( 'API Key', 'pfadfinden-theme-updater' )
		);

		$this->addCheckboxField(
			'keep-settings',
			__( 'Keep Settings', 'pfadfinden-theme-updater' ),
			__( 'Don’t delete settings when uninstalling the plugin.', 'pfadfinden-theme-updater' )
		);

		parent::registerSettings();
	}

	public function sanitizeOptions( array $options )
	{
		if ( ! isset( $options['key'] ) ) {
			$options['key'] = $this->getDefaults()['keep-settings'];
		} else {
			$key = preg_replace( '[^A-Za-z0-9]+', '', $options['key'] );
			$keylen = strlen( $key );
			if ( 0 !== $keylen && 10 !== $keylen ) {
				$this->addError( 'key', __( 'The API key consists of 10 characters. ', 'pfadfinden-theme-updater' ) );
			}
			$options['key'] = $key;
		}

		if ( ! isset( $options['keep-settings'] ) ) {
			$options['keep-settings'] = $this->getDefaults()['keep-settings'];
		}

		return $options;
	}

	public function getDefaults()
	{
		return array(
			'key'           => '',
			'keep-settings' => false,
		);
	}
}
