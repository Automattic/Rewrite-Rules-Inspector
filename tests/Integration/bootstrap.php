<?php
/**
 * Integration tests bootstrap file.
 *
 * @package Automattic\RewriteRulesInspector\Tests\Integration
 */

use Yoast\WPTestUtils\WPIntegration;

// Load Composer autoloader.
require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

// Get access to tests_add_filter() function.
require_once getenv( 'WP_PHPUNIT__DIR' ) . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		require dirname( __DIR__, 2 ) . '/rewrite-rules-inspector.php';
	}
);

// Bootstrap WordPress.
WPIntegration\bootstrap_it();
