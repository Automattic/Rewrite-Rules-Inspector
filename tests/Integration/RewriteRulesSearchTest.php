<?php
/**
 * Search URL parsing regression tests.
 *
 * @package Automattic\RewriteRulesInspector\Tests\Integration
 */

namespace Automattic\RewriteRulesInspector\Tests\Integration;

use Automattic\RewriteRulesInspector\Core\RewriteRules;
use Automattic\RewriteRulesInspector\Plugin;
use RuntimeException;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Tests construct search and download requests.

/**
 * Preserve filtering and authorization when URL parsing fails.
 */
class RewriteRulesSearchTest extends TestCase {

	/**
	 * Invalid URLs behave like URLs without a path; valid paths still filter.
	 *
	 * @dataProvider search_provider
	 * @param string $input Search input.
	 * @param bool   $matches_article Whether path filtering should select the article.
	 * @param string $home Site home URL.
	 */
	public function test_search_input( string $input, bool $matches_article, string $home ): void {
		$this->set_permalink_structure( '' );
		$home_url = static function () use ( $home ) {
			return $home;
		};
		add_filter( 'home_url', $home_url );
		$previous_get = $_GET;
		$_GET         = array( 's' => $input );
		$fixture      = static function () {
			return array(
				'articles/?$' => array(
					'rewrite' => 'index.php?name=articles',
					'source'  => 'post',
				),
				'other/?$'    => array(
					'rewrite' => 'index.php?pagename=other',
					'source'  => 'page',
				),
			);
		};
		add_filter( 'rri_rewrite_rules', $fixture );
		try {
			$rules = ( new RewriteRules() )->get_rules();
			$this->assertSame( $matches_article ? array( 'articles/?$' ) : array( 'articles/?$', 'other/?$' ), array_keys( $rules ) );
			$this->assertSame( 'index.php?name=articles', $rules['articles/?$']['rewrite'] );
			if ( ! $matches_article ) {
				$_GET['source'] = 'page';
				$this->assertSame( array( 'other/?$' ), array_keys( ( new RewriteRules() )->get_rules() ) );
			}
		} finally {
			$_GET = $previous_get;
			remove_filter( 'rri_rewrite_rules', $fixture );
			remove_filter( 'home_url', $home_url );
		}
	}

	/**
	 * Malformed URLs and ordinary inputs, including subdirectory installs.
	 *
	 * @return array
	 */
	public static function search_provider(): array {
		return array(
			'incomplete HTTP URL'     => array( 'http://', false, 'http://example.org' ),
			'incomplete HTTPS URL'    => array( 'https://', false, 'http://example.org' ),
			'invalid port'            => array( 'http://example.org:99999/', false, 'http://example.org' ),
			'missing host'            => array( 'http://:', false, 'http://example.org' ),
			'host without path'       => array( 'http://example.org', false, 'http://example.org' ),
			'relative path'           => array( 'articles/', true, 'http://example.org' ),
			'absolute URL'            => array( 'http://example.org/articles/?q=1', true, 'http://example.org' ),
			'subdirectory path'       => array( '/blog/articles/', true, 'http://example.org/blog' ),
			'subdirectory URL'        => array( 'http://example.org/blog/articles/', true, 'http://example.org/blog' ),
			'subdirectory parse fail' => array( 'http://', false, 'http://example.org/blog' ),
		);
	}

	/**
	 * A malformed search must reach export denial without a PHP type error.
	 */
	public function test_anonymous_download_with_malformed_search_is_denied(): void {
		wp_set_current_user( 0 );
		$previous_get     = $_GET;
		$previous_request = $_REQUEST;
		$_GET             = array( 's' => 'http://' );
		$_REQUEST         = array();
		$die_handler      = static function () {
			return static function () {
				throw new RuntimeException( 'download denied' );
			};
		};
		add_filter( 'wp_die_handler', $die_handler );
		try {
			$this->expectException( RuntimeException::class );
			$this->expectExceptionMessage( 'download denied' );
			( new Plugin() )->download_rules();
		} finally {
			$_GET     = $previous_get;
			$_REQUEST = $previous_request;
			remove_filter( 'wp_die_handler', $die_handler );
		}
	}
}
