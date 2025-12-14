<?php
/**
 * Unit tests bootstrap file.
 *
 * @package Automattic\RewriteRulesInspector\Tests\Unit
 */

// Load Composer autoloader.
require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

// Load Brain Monkey.
require_once dirname( __DIR__, 2 ) . '/vendor/yoast/wp-test-utils/src/BrainMonkey/bootstrap.php';

// Stub WordPress functions needed to load the plugin.
if ( ! function_exists( 'plugin_basename' ) ) {
	function plugin_basename( $file ) {
		return basename( dirname( $file ) ) . '/' . basename( $file );
	}
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}
