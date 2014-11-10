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
	private function addHookMhethod( $hook, $method, $priority = 10, $arguments = 99 )
	{
		add_action( $hook, array( $this, $method ), $priority, $arguments );
	}


	public function __construct()
	{
		$this->features = array(
			'html5'     => new Html5Feature(),
			'installer' => new ThemeInstallerFeature(),
		);

		parent::__construct();

		$this->addHookMethod( 'admin_menu', 'registerAdminMenu' );
		$this->addHookMethod( 'admin_init', 'initializeAdministration' );
	}


	/**
	 * Register our options page and menu entry.
	 * 
	 * @return void
	 */
	public function registerAdminMenu()
	{
		add_options_page(
			'Pfadfinden Bootstrap Page Title',
			'Pfadfinden Bootstrap Menu Title',
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
		?>
		<div class="wrap">
			<h2>Pfadfinden Bootstrap</h2>
			<form action="options.php" method="post">
				<?php settings_fields( 'pfadfinden-settings-group' ); ?>
				<?php do_settings_sections( $this->getNamespace() ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public function initializeAdministration()
	{
		register_setting(
			'pfadfinden-settings-group',
			$this->getNamespace() . '-features',
			array( $this, 'sanitizeOptionFeatures' )
		);

		add_settings_section(
			'section-one',
			'Section One',
			array( $this, 'renderSectionTeaser' ),
			$this->getNamespace()
		);
		add_settings_field(
			'pfadfinden-bootstrap-features-id',
			'Features',
			array( $this, 'renderFeatureCheckboxes' ),
			$this->getNamespace(),
			$this->getNamespace() . '-features'
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
				'<label><input type="checkbox" name=""%s value="%s" /> %s</label>',
				in_array( $key, $active ) ? ' checked="checked"' : '',
				esc_attr( $key ),
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

	public function getNamespace()
	{
		return 'pfadfinden-bootstrap';
	}
}
