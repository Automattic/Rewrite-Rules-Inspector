<?php
/**
 * Unit tests for the RewriteRules class.
 *
 * @package Automattic\RewriteRulesInspector\Tests\Unit\Core
 */

namespace Automattic\RewriteRulesInspector\Tests\Unit\Core;

use Automattic\RewriteRulesInspector\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

/**
 * Test case for RewriteRules class.
 */
class RewriteRulesTest extends TestCase {

	/**
	 * Test that the class can be instantiated.
	 */
	public function test_class_exists(): void {
		$this->assertTrue( class_exists( \Automattic\RewriteRulesInspector\Core\RewriteRules::class ) );
	}
}
