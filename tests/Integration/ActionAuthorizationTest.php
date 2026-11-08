<?php
/**
 * Regression coverage for privileged actions.
 *
 * @package Automattic\RewriteRulesInspector\Tests\Integration
 */

namespace Automattic\RewriteRulesInspector\Tests\Integration;

use Automattic\RewriteRulesInspector\Core\FileExport;
use Automattic\RewriteRulesInspector\Core\RuleFlush;
use Automattic\RewriteRulesInspector\Plugin;
use RuntimeException;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Tests construct requests to exercise nonce verification.

/**
 * Verify real WordPress nonce and capability checks.
 */
class ActionAuthorizationTest extends TestCase {

	/**
	 * Reject requests before output, rule generation, or flushing.
	 *
	 * @dataProvider denied_request_provider
	 * @param string $action Action name.
	 * @param string $role User role.
	 * @param string $nonce Nonce mode.
	 */
	public function test_denied_requests( string $action, string $role, string $nonce ): void {
		wp_set_current_user( '' === $role ? 0 : self::factory()->user->create( array( 'role' => $role ) ) );
		$previous_request = $_REQUEST;
		$_REQUEST         = array();
		if ( 'valid' === $nonce ) {
			$_REQUEST['_wpnonce'] = wp_create_nonce( $action );
		} elseif ( 'invalid' === $nonce ) {
			$_REQUEST['_wpnonce'] = 'invalid';
		}

		$die_handler     = static function () {
			return static function () {
				throw new RuntimeException( 'request denied' );
			};
		};
		$unexpected_work = static function () {
			throw new RuntimeException( 'privileged work ran before authorization' );
		};
		add_filter( 'wp_die_handler', $die_handler );
		add_action( 'generate_rewrite_rules', $unexpected_work );
		add_action( 'rri_flush_rules', $unexpected_work );

		try {
			$this->expectException( RuntimeException::class );
			$this->expectExceptionMessage( 'request denied' );
			if ( 'download-rules' === $action ) {
				( new Plugin() )->download_rules();
			} else {
				( new RuleFlush() )->flush_rules( admin_url() );
			}
		} finally {
			$_REQUEST = $previous_request;
			remove_filter( 'wp_die_handler', $die_handler );
			remove_action( 'generate_rewrite_rules', $unexpected_work );
			remove_action( 'rri_flush_rules', $unexpected_work );
		}
	}

	/**
	 * Denial conditions for each action.
	 *
	 * @return array
	 */
	public static function denied_request_provider(): array {
		$cases = array();
		foreach ( array( 'download-rules', 'flush-rules' ) as $action ) {
			$cases[ $action . ' anonymous' ]     = array( $action, '', 'missing' );
			$cases[ $action . ' missing nonce' ] = array( $action, 'administrator', 'missing' );
			$cases[ $action . ' invalid nonce' ] = array( $action, 'administrator', 'invalid' );
			$cases[ $action . ' subscriber' ]    = array( $action, 'subscriber', 'valid' );
		}
		return $cases;
	}

	/**
	 * An authorized administrator passes the download guard.
	 */
	public function test_administrator_can_authorize_download(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$previous_request     = $_REQUEST;
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'download-rules' );
		try {
			( new FileExport() )->authorize_download();
			$this->assertTrue( current_user_can( 'manage_options' ) );
		} finally {
			$_REQUEST = $previous_request;
		}
	}

	/**
	 * Disabling flushing takes precedence over administrator permission.
	 */
	public function test_disabled_flushing_is_denied(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$previous_request     = $_REQUEST;
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'flush-rules' );
		$die_handler          = static function () {
			return static function () {
				throw new RuntimeException( 'flushing denied' );
			};
		};
		$unexpected_flush     = static function () {
			throw new RuntimeException( 'disabled flushing ran' );
		};
		add_action( 'rri_flush_rules', $unexpected_flush );
		add_filter( 'wp_die_handler', $die_handler );
		try {
			$this->expectException( RuntimeException::class );
			$this->expectExceptionMessage( 'flushing denied' );
			( new RuleFlush( false ) )->flush_rules( admin_url() );
		} finally {
			$_REQUEST = $previous_request;
			remove_filter( 'wp_die_handler', $die_handler );
			remove_action( 'rri_flush_rules', $unexpected_flush );
		}
	}
	/**
	 * Authorized flushing reaches the flush and redirect with the expected URL.
	 */
	public function test_administrator_can_flush(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$previous_request     = $_REQUEST;
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'flush-rules' );
		$flush_count          = did_action( 'rri_flush_rules' );
		$redirect             = '';
		$stop_redirect        = static function ( $location ) use ( &$redirect ) {
			$redirect = $location;
			throw new RuntimeException( 'redirect reached' );
		};
		add_filter( 'wp_redirect', $stop_redirect );
		try {
			try {
				( new RuleFlush() )->flush_rules( admin_url( 'tools.php?page=rewrite-rules-inspector' ) );
				$this->fail( 'Expected the post-flush redirect.' );
			} catch ( RuntimeException $exception ) {
				$this->assertSame( 'redirect reached', $exception->getMessage() );
			}
			$this->assertSame( $flush_count + 1, did_action( 'rri_flush_rules' ) );
			$this->assertSame( admin_url( 'tools.php?page=rewrite-rules-inspector&message=flush-success' ), $redirect );
		} finally {
			$_REQUEST = $previous_request;
			remove_filter( 'wp_redirect', $stop_redirect );
		}
	}
}
