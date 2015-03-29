<?php

namespace Pfadfinden\WordPress\Tests;

use Pfadfinden\WordPress\ThemeUpdaterSettings;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_MockObject_Builder_InvocationMocker as BuilderInvocationMocker;



class ThemeUpdaterSettingsTest extends \WP_UnitTestCase
{
	/**
	 * @return ThemeUpdaterSettings|MockObject {
	 *    @method BuilderInvocationMocker method(string)
	 * }
	 */
	protected function createMock()
	{
		return $this->getMockBuilder( 'Pfadfinden\WordPress\ThemeUpdaterSettings' )
			->enableProxyingToOriginalMethods()
			->getMock();
	}


	/**
	 * @return array<array {
	 *    @type array<string> $0
	 * }>
	 */
	public function pluginActionProvider()
	{
		return array(
			array( array() ),
			array( array( 'edit' => '<a href="edit-url.php">Edit</a>' ) ),
		);
	}

	/**
	 * @dataProvider pluginActionProvider
	 * @param array<string> $old_actions
	 */
	public function testFilterPluginActions( array $old_actions )
	{
		$settings = $this->createMock();

		$new_actions = $settings->filterPluginActions( $old_actions, 'pfadfinden-theme-updater/pfadfinden-theme-updater.php', array(), 'All' );
		$this->assertCount( 1, array_diff_assoc( $new_actions, $old_actions ), 'Our action gets added.' );
		$this->assertCount( 0, array_diff_assoc( $old_actions, $new_actions ), 'No actions are removed.' );
	}

	public function testRegisterSettings()
	{
		global $wp_settings_fields;

		$settings = $this->createMock();
		$settings->registerSettings();

		$sections = $settings->getSections();
		$this->assertCount( 1, $sections, 'One section is generated.' );

		$fields = $settings->getFieldsForSection( $sections[0] );
		$this->assertEquals( array( 'key', 'keep-settings' ), array_keys( $fields ), 'Known settings are there.' );
	}

	public function testSanitizeOptionDefaults()
	{
		$settings = $this->createMock();

		$this->assertEquals(
			$settings->getDefaults(),
			$settings->sanitizeOptions( array() ),
			'sanitizeOptions() adds in default values if missing.'
		);
	}

	/**
	 * @return array<array {
	 *    @type string $0
	 *    @type string $1
	 *    @type int    $2
	 * }>
	 */
	public function keyProvider()
	{
		return array(
			array( '',            '',            0 ),
			array( 'ABCDEFGHIJ',  'ABCDEFGHIJ',  0 ),
			array( 'abcdefghij',  'abcdefghij',  0 ),
			array( '1234567890',  '1234567890',  0 ),
			array( '123456789',   '123456789',   1 ),
			array( '12345678901', '12345678901', 1 ),
			array( ' 1234567890', '1234567890',  0 ),
		);
	}

	/**
	 * @dataProvider keyProvider
	 * @param string $key
	 * @param string $expected
	 * @param int    $errorCount
	 */
	public function testSanitizeKeyOption( $key, $expected, $errorCount )
	{
		$settings = $this->createMock();

		$sanitized = $settings->sanitizeOptions( array( 'key' => $key ) )['key'];
		$this->assertEquals( $expected, $sanitized, 'Expected sanitized value.' );
		$this->assertCount( $errorCount, $settings->getErrors(), 'Number of generated errors.' );
	}

	public function testDefaults()
	{
		$defaults = $this->createMock()->getDefaults();

		$this->assertEquals(
			array( 'key', 'keep-settings' ),
			array_keys( $defaults ),
			'Defaults for all options.'
		);
	}
}
