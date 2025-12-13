<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Automattic\RewriteRulesInspector\Tests
 */

// Detect which testsuite is being run based on environment variable or default.
$testsuite = getenv( 'TESTSUITE' );
if ( ! $testsuite ) {
	$testsuite = 'Unit';
}

if ( 'Unit' === $testsuite ) {
	require_once __DIR__ . '/Unit/bootstrap.php';
} else {
	require_once __DIR__ . '/Integration/bootstrap.php';
}
