<?php

namespace Pfadfinden\WordPress\Bootstrap;

use Shy\WordPress\Plugin;
use Shy\WordPress\HookListTrait;



/**
 * 
 */
class BootstrapPlugin extends Plugin
{
	//use HookListTrait;
	private function addHookMethod( $hook, $method, $priority = 10, $arguments = 99 )
	{
		add_action( $hook, array( $this, $method ), $priority, $arguments );
	}


	public function __construct()
	{
		$this->features = array(
			'html5'     => 'Html5Feature',//new Html5Feature(),
			'installer' => 'ThemeInstallerFeature',//new ThemeInstallerFeature(),
		);

		//parent::__construct();

		$this->addHookMethod( 'admin_menu', 'registerAdminMenu' );
		$this->addHookMethod( 'admin_init', 'registerSettings' );
		$this->addHookMethod( 'plugin_action_links', 'filterPluginActions' );
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
	public function filterPluginActions( array $actions, $plugin_file, $plugin_data, $context )
	{
		if ( substr( $plugin_file, -24 ) !== 'pfadfinden-bootstrap.php' ) {
			return $actions;
		}

		return array(
			'settings' => sprintf(
				'<a href="options-general.php?page=pfadfinden-bootstrap">%s</a>',
				esc_html__( 'Settings' )
			),
		) + $actions;
	}

	/**
	 * Register our options page.
	 * 
	 * @return void
	 */
	public function registerAdminMenu()
	{
		add_options_page(
			__( 'Pfadfinden Bootstrap Settings', 'pfadfinden-bootstrap' ),
			__( 'Pfadfinden Bootstrap', 'pfadfinden-bootstrap' ),
			'manage_options',
			$this->getNamespace(),
			array( $this, 'renderOptionsPage' )
		);
	}

	/**
	 * Render our options page.
	 */
	public function renderOptionsPage()
	{
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Pfadfinden Bootstrap Settings', 'pfadfinden-bootstrap' ); ?></h2>
			<form action="options.php" method="post">
				<?php settings_fields( 'pfadfinden-settings-group' ); ?>
				<?php do_settings_sections( $this->getNamespace() ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register our settings.
	 */
	public function registerSettings()
	{
		$section = $this->getNamespace() . '-section-general';

		add_settings_section(
			$section,
			'',
			array( $this, 'renderSectionTeaser' ),
			$this->getNamespace()
		);
		add_settings_field(
			$this->getNamespace() . '-features',
			__( 'Activated Features', 'pfadfinden-bootstrap' ),
			array( $this, 'renderFeatureCheckboxes' ),
			$this->getNamespace(),
			$section
		);
		add_settings_field(
			$this->getNamespace() . '-key',
			__( 'API Key', 'pfadfinden-bootstrap' ),
			array( $this, 'renderKeyField' ),
			$this->getNamespace(),
			$section,
			array(
				'label_for' => $this->getNamespace() . '-key'
			)
		);

		register_setting(
			'pfadfinden-settings-group',
			$this->getNamespace() . '-features',
			array( $this, 'sanitizeOptionFeatures' )
		);
		register_setting(
			'pfadfinden-settings-group',
			$this->getNamespace() . '-key',
			array( $this, 'sanitizeOptionKey' )
		);
	}

	public function renderSectionTeaser()
	{
	}

	public function renderFeatureCheckboxes()
	{
		$active = get_option( $this->getNamespace() . '-features', array() );

		foreach ( $this->features as $key => $feature ) {
			printf(
				'<p><label><input type="checkbox" name="%s[]" value="%s"%s /> %s</label></p>',
				esc_attr( $this->getNamespace() . '-features' ),
				esc_attr( $key ),
				in_array( $key, $active ) ? ' checked="checked"' : '',
				esc_html( $feature )
			);
		}
	}

	/**
	 * Remove all non-existant features slugs from the input array.
	 * 
	 * @param array $input
	 * @return array
	 */
	public function sanitizeOptionFeatures( $input )
	{
		if ( ! is_array( $input ) ) {
			return array();
		}

		return array_intersect( $input, array_keys( $this->features ) );
	}

	public function renderKeyField()
	{
		$key = get_option( $this->getNamespace() . '-key', '' );

		printf(
			'<input type="text" id="%s" class="regular-text" name="%s" value="%s" />',
			esc_attr( $this->getNamespace() . '-key' ),
			esc_attr( $this->getNamespace() . '-key' ),
			esc_attr( $key )
		);
	}

	public function sanitizeOptionKey( $input )
	{
		return $input;
	}

	public function getNamespace()
	{
		return 'pfadfinden-bootstrap';
	}
}
