<?php
/**
 * Integration tests for the plugin.
 *
 * @package Automattic\RewriteRulesInspector\Tests\Integration
 */

namespace Automattic\RewriteRulesInspector\Tests\Integration;

/**
 * Test case for plugin integration.
 */
class PluginTest extends TestCase {

	/**
	 * Test that the plugin constant is defined.
	 */
	public function test_plugin_constant_defined(): void {
		$this->assertTrue( defined( 'REWRITE_RULES_INSPECTOR_VERSION' ) );
	}

	/**
	 * Test that the plugin file path constant is defined.
	 */
	public function test_plugin_file_path_constant_defined(): void {
		$this->assertTrue( defined( 'REWRITE_RULES_INSPECTOR_FILE_PATH' ) );
	}

	/**
	 * Test that the global plugin instance exists.
	 */
	public function test_global_plugin_instance_exists(): void {
		global $rewrite_rules_inspector;
		$this->assertInstanceOf( \Automattic\RewriteRulesInspector\Plugin::class, $rewrite_rules_inspector );
	}

	/**
	 * Test that the admin menu is registered.
	 */
	public function test_admin_menu_registered(): void {
		$this->assertGreaterThan( 0, has_action( 'admin_menu' ) );
	}
}
