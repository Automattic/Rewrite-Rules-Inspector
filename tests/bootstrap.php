<?php
/**
 * PHPUnit bootstrap file for Rewrite Rules Inspector plugin tests.
 *
 * @package Automattic\RewriteRulesInspector\Tests
 */

declare( strict_types=1 );

namespace Automattic\RewriteRulesInspector\Tests;

use Yoast\WPTestUtils\WPIntegration;

$vendor_dir = dirname( __DIR__ ) . '/vendor';

require_once $vendor_dir . '/yoast/wp-test-utils/src/WPIntegration/bootstrap-functions.php';

// Check for a `--testsuite WP_Tests` or `--testsuite=WP_Tests` arg when calling phpunit,
// and use it to conditionally load up WordPress.
$argv_local     = $GLOBALS['argv'] ?? [];
$key            = (int) array_search( '--testsuite', $argv_local, true );
$is_integration = false;

// Check for --testsuite WP_Tests (two separate args).
if ( $key && isset( $argv_local[ $key + 1 ] ) && 'WP_Tests' === $argv_local[ $key + 1 ] ) {
	$is_integration = true;
}

// Check for --testsuite=WP_Tests (single arg with equals).
foreach ( $argv_local as $arg ) {
	if ( '--testsuite=WP_Tests' === $arg ) {
		$is_integration = true;
		break;
	}
}

if ( $is_integration ) {
	$_tests_dir = WPIntegration\get_path_to_wp_test_dir();

	if ( empty( $_tests_dir ) ) {
		echo 'ERROR: Could not find WordPress test library directory.' . PHP_EOL;
		echo 'Make sure wp-env is running: npm run wp-env start' . PHP_EOL;
		exit( 1 );
	}

	// Give access to tests_add_filter() function.
	require_once $_tests_dir . '/includes/functions.php';

	/**
	 * Manually load the plugin being tested.
	 */
	\tests_add_filter(
		'muplugins_loaded',
		function (): void {
			require dirname( __DIR__ ) . '/rewrite-rules-inspector.php';
		}
	);

	// Make sure the Composer autoload file has been generated.
	WPIntegration\check_composer_autoload_exists();

	// Start up the WP testing environment.
	require $_tests_dir . '/includes/bootstrap.php';

	/*
	 * Register the custom autoloader to overload the PHPUnit MockObject classes when running on PHP 8.
	 *
	 * This function has to be called _last_, after the WP test bootstrap to make sure it registers
	 * itself in FRONT of the Composer autoload (which also prepends itself to the autoload queue).
	 */
	WPIntegration\register_mockobject_autoloader();

	// Add custom test case.
	require __DIR__ . '/Integration/TestCase.php';
} else {
	// Unit tests: load Brain Monkey bootstrap.
	require_once $vendor_dir . '/yoast/wp-test-utils/src/BrainMonkey/bootstrap.php';

	// Load Composer autoloader.
	require_once $vendor_dir . '/autoload.php';

	// Stub WordPress functions needed to load the plugin.
	if ( ! function_exists( 'plugin_basename' ) ) {
		/**
		 * Stub for plugin_basename to allow loading the plugin.
		 *
		 * @param string $file Plugin file path.
		 * @return string Plugin basename.
		 */
		function plugin_basename( $file ) {
			return basename( dirname( $file ) ) . '/' . basename( $file );
		}
	}

	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', '/tmp/' );
	}

	// Load the base test case.
	require_once __DIR__ . '/Unit/TestCase.php';
}
