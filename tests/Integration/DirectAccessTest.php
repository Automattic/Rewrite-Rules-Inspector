<?php
/**
 * Direct file access regression tests.
 *
 * @package Automattic\RewriteRulesInspector\Tests\Integration
 */

namespace Automattic\RewriteRulesInspector\Tests\Integration;

/**
 * Files with top-level code must exit quietly when requested outside WordPress.
 */
class DirectAccessTest extends TestCase {

	/**
	 * Run the file in a fresh PHP process without ABSPATH defined.
	 *
	 * @dataProvider file_provider
	 * @param string $file File path relative to the plugin root.
	 */
	public function test_direct_access_exits_silently( string $file ): void {
		$command = sprintf(
			'%s -d display_errors=1 %s 2>&1',
			escapeshellarg( PHP_BINARY ),
			escapeshellarg( dirname( __DIR__, 2 ) . '/' . $file )
		);
		exec( $command, $output, $exit_code ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- Needs a process without WordPress loaded.

		$this->assertSame( array(), $output );
		$this->assertSame( 0, $exit_code );
	}

	/**
	 * Files that run code outside a class declaration.
	 *
	 * @return array
	 */
	public static function file_provider(): array {
		return array(
			'main plugin file' => array( 'rewrite-rules-inspector.php' ),
			'list table'       => array( 'src/Admin/RewriteRulesTable.php' ),
		);
	}
}
