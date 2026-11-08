<?php
/**
 * URL parsing regression tests.
 *
 * @package Automattic\RewriteRulesInspector\Tests\Integration
 */

namespace Automattic\RewriteRulesInspector\Tests\Integration;

use Automattic\RewriteRulesInspector\Core\UrlTester;

/**
 * Exercise both URL tester entrypoints with WordPress URL parsing.
 */
class UrlTesterTest extends TestCase {

	/**
	 * Missing paths and failed parses must not emit diagnostics.
	 *
	 * @dataProvider url_provider
	 * @param string $url Input URL.
	 * @param string $path Expected normalized path.
	 */
	public function test_url_paths( string $url, string $path ): void {
		$tester = new UrlTester();
		$this->assertSame( $path, $tester->test_url_with_rules( $url, array() )['path'] );
		$this->assertSame( $path, $tester->test_url( $url )['path'] );
	}

	/**
	 * URL parsing edge cases.
	 *
	 * @return array
	 */
	public static function url_provider(): array {
		return array(
			'host only'     => array( 'https://example.com', '' ),
			'absolute URL'  => array( 'https://example.com/articles/post?x=1', 'articles/post' ),
			'relative path' => array( 'articles/post', 'articles/post' ),
			'query only'    => array( '?x=1', '?x=1' ),
			'invalid URL'   => array( 'http://:', '' ),
		);
	}
}
