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
	 * Only a leading home directory is removed for sites in a subdirectory.
	 *
	 * @dataProvider subdirectory_provider
	 * @param string $url Input URL.
	 * @param string $path Expected normalized path.
	 */
	public function test_subdirectory_paths( string $url, string $path ): void {
		$home_url = static function () {
			return 'http://example.org/blog';
		};
		add_filter( 'home_url', $home_url );
		try {
			$this->assertSame( $path, ( new UrlTester() )->get_path( $url ) );
		} finally {
			remove_filter( 'home_url', $home_url );
		}
	}

	/**
	 * Paths in, around and outside a /blog home directory.
	 *
	 * @return array
	 */
	public static function subdirectory_provider(): array {
		return array(
			'home directory'          => array( '/blog', '' ),
			'path inside home'        => array( '/blog/articles/', 'articles/' ),
			'URL inside home'         => array( 'http://example.org/blog/articles/', 'articles/' ),
			'path starting like home' => array( '/blog/blogroll/', 'blogroll/' ),
			'home name later in path' => array( '/blog/news/blog-post/', 'news/blog-post/' ),
			'path outside home'       => array( '/blogroll/', 'blogroll/' ),
			'different case'          => array( '/Blog/articles/', 'articles/' ),
			'relative path'           => array( 'articles/', 'articles/' ),
		);
	}

	/**
	 * URL parsing edge cases.
	 *
	 * @return array
	 */
	public static function url_provider(): array {
		return array(
			'host only'                => array( 'https://example.com', '' ),
			'absolute URL'             => array( 'https://example.com/articles/post?x=1', 'articles/post' ),
			'protocol-relative URL'    => array( '//example.com/articles/post', 'articles/post' ),
			'relative path'            => array( 'articles/post', 'articles/post' ),
			'relative path with query' => array( 'articles/post?x=1#top', 'articles/post' ),
			'query only'               => array( '?x=1', '' ),
			'invalid URL'              => array( 'http://:', '' ),
		);
	}
}
