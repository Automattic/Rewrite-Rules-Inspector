<?php
/**
 * Source attribution and generation regression tests.
 *
 * @package Automattic\RewriteRulesInspector\Tests\Integration
 */

namespace Automattic\RewriteRulesInspector\Tests\Integration;

use Automattic\RewriteRulesInspector\Core\RewriteRules;

/**
 * Preserve inspector accuracy without generating each source twice.
 */
class RewriteRulesGenerationTest extends TestCase {

	/**
	 * Generate each source once and retain stored and missing rules.
	 *
	 * @dataProvider permalink_provider
	 * @param string $structure Permalink structure.
	 */
	public function test_single_generation_preserves_attribution( string $structure ): void {
		global $wp_rewrite;
		$this->set_permalink_structure( $structure );
		$stored = $wp_rewrite->rewrite_rules();
		update_option( 'rewrite_rules', $stored );
		$calls  = 0;
		$filter = static function ( $rules ) use ( &$calls ) {
			++$calls;
			$rules['inspector-filtered/?$'] = 'index.php?name=filtered';
			return $rules;
		};
		add_filter( 'post_rewrite_rules', $filter );
		try {
			$rules = ( new RewriteRules() )->get_rules();
			$this->assertSame( 1, $calls );
			$this->assertSame( 'missing', $rules['inspector-filtered/?$']['source'] );
			$this->assertSame( 'index.php?name=filtered', $rules['inspector-filtered/?$']['rewrite'] );
			foreach ( $stored as $rule => $rewrite ) {
				$this->assertSame( $rewrite, $rules[ $rule ]['rewrite'] );
			}

			// A second inspection must see current filters rather than cached results.
			update_option( 'rewrite_rules', $wp_rewrite->rewrite_rules() );
			$calls = 0;
			$rules = ( new RewriteRules() )->get_rules();
			$this->assertSame( 1, $calls );
			$this->assertSame( 'post', $rules['inspector-filtered/?$']['source'] );
		} finally {
			remove_filter( 'post_rewrite_rules', $filter );
		}
	}

	/**
	 * Test representative permalink structures.
	 *
	 * @return array
	 */
	public static function permalink_provider(): array {
		return array(
			array( '/%postname%/' ),
			array( '/%year%/%monthnum%/%day%/%postname%/' ),
			array( '/archives/%post_id%' ),
		);
	}

	/**
	 * Plain permalinks remain supported.
	 */
	public function test_plain_permalinks(): void {
		$this->set_permalink_structure( '' );
		update_option( 'rewrite_rules', array() );
		$this->assertSame( array(), ( new RewriteRules() )->get_rules() );
	}
	/**
	 * Custom permastruct filters contribute to source attribution.
	 */
	public function test_custom_permastruct_source(): void {
		global $wp_rewrite;
		$this->set_permalink_structure( '/%postname%/' );
		register_taxonomy( 'rri_test_topic', 'post', array( 'rewrite' => array( 'slug' => 'topics' ) ) );
		try {
			update_option( 'rewrite_rules', $wp_rewrite->rewrite_rules() );
			$rules = ( new RewriteRules() )->get_rules();
			$this->assertSame( 'rri_test_topic', $rules['topics/([^/]+)/?$']['source'] );
		} finally {
			unregister_taxonomy( 'rri_test_topic' );
		}
	}

	/**
	 * Temporary observers are removed even when generation fails.
	 */
	public function test_observers_are_removed_after_exception(): void {
		global $wp_filter;
		$this->set_permalink_structure( '/%postname%/' );
		$before          = isset( $wp_filter['post_rewrite_rules'] ) ? $wp_filter['post_rewrite_rules']->callbacks : array();
		$fail_generation = static function () {
			throw new \RuntimeException( 'generation failed' );
		};
		add_filter( 'rewrite_rules_array', $fail_generation );
		try {
			try {
				( new RewriteRules() )->get_rules();
				$this->fail( 'Expected generation to fail.' );
			} catch ( \RuntimeException $exception ) {
				$this->assertSame( 'generation failed', $exception->getMessage() );
			}
			$after = isset( $wp_filter['post_rewrite_rules'] ) ? $wp_filter['post_rewrite_rules']->callbacks : array();
			$this->assertSame( $before, $after );
		} finally {
			remove_filter( 'rewrite_rules_array', $fail_generation );
		}
	}
}
