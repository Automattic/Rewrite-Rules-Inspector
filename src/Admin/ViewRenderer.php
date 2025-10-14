<?php

declare(strict_types=1);

namespace Automattic\RewriteRulesInspector\Admin;

/**
 * Renderer class for handling view output and template rendering.
 *
 * @package Automattic\RewriteRulesInspector\Admin
 * @since 1.5.0
 */
final class ViewRenderer {

	/**
	 * Plugin directory path.
	 *
	 * @var string $plugin_dir_path
	 */
	private string $plugin_dir_path;

	/**
	 * Constructor.
	 *
	 * @since 1.5.0
	 * @param string $plugin_dir_path Plugin directory path.
	 */
	public function __construct( string $plugin_dir_path ) {
		$this->plugin_dir_path = $plugin_dir_path;
	}

	/**
	 * Render the main rules view.
	 *
	 * @since 1.5.0
	 * @param array  $rules Array of rewrite rules.
	 * @param array  $permastructs Array of permastructs.
	 * @param object $wp_list_table WordPress list table object.
	 * @param array  $url_test_results Optional URL test results.
	 */
	public function render_rules_view( array $rules, array $permastructs, $wp_list_table, ?array $url_test_results = null ): void {
		// Bump view stats or do something else on page load.
		do_action( 'rri_view_rewrite_rules', $rules );

		?>
		<div class="wrap rri-admin-page">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="rri-section">
				<h2 id="rewrite-rules-section">
					<?php esc_html_e( 'Rewrite Rules', 'rewrite-rules-inspector' ); ?>
					<?php if ( ! empty( $permastructs ) ) : ?>
						<a href="#permastructs-section" class="jump-link"><?php esc_html_e( 'Jump to Permastructs', 'rewrite-rules-inspector' ); ?></a>
					<?php endif; ?>
				</h2>

				<?php $this->render_rules_messages( $rules ); ?>

				<?php if ( $url_test_results ) : ?>
					<?php $this->render_url_test_results( $url_test_results ); ?>
				<?php endif; ?>

				<?php $this->render_rules_description( $wp_list_table ); ?>

				<?php $wp_list_table->display(); ?>
			</div>

			<?php if ( ! empty( $permastructs ) ) : ?>
				<div class="rri-section">
					<?php $this->render_permastructs_table( $permastructs ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render rules-related messages (errors, warnings).
	 *
	 * @since 1.5.0
	 * @param array $rules Array of rewrite rules.
	 */
	private function render_rules_messages( array $rules ): void {
		$missing_count = 0;
		foreach ( $rules as $rule ) {
			if ( 'missing' === $rule['source'] ) {
				++$missing_count;
			}
		}

		if ( empty( $rules ) ) {
			$error_message = apply_filters( 'rri_message_no_rules', __( 'No rewrite rules yet, try flushing.', 'rewrite-rules-inspector' ) );
			echo '<div class="message error"><p>' . wp_kses_post( $error_message ) . '</p></div>';
		} elseif ( $missing_count > 0 ) {
			/* translators: %d: Count of missing rewrite rules */
			$error_message = apply_filters( 'rri_message_missing_rules', sprintf( _n( '%d rewrite rule may be missing, try flushing.', '%d rewrite rules may be missing, try flushing.', $missing_count, 'rewrite-rules-inspector' ), $missing_count ) );
			echo '<div class="message error"><p>' . wp_kses_post( $error_message ) . '</p></div>';
		}
	}

	/**
	 * Render rules description text.
	 *
	 * @since 1.5.0
	 * @param object $wp_list_table WordPress list table object.
	 */
	private function render_rules_description( $wp_list_table ): void {
		if ( ! empty( $_GET['s'] ) ) {
			?>
			<p>
				<?php
				/* translators: %s: Count of rewrite rules */
				printf( wp_kses_post( __( 'A listing of all %1$s rewrite rules for this site that match "<a target="_blank" href="%2$s">%2$s</a>"', 'rewrite-rules-inspector' ) ), count( $wp_list_table->items ), esc_url( $_GET['s'] ) );
				?>
			</p>
			<?php
		} else {
			?>
			<p>
				<?php
				/* translators: %s: Count of rewrite rules */
				printf( esc_html__( 'A listing of all %1$s rewrite rules for this site.', 'rewrite-rules-inspector' ), count( $wp_list_table->items ) );
				?>
			</p>
			<?php
		}
	}

	/**
	 * Render the permastructs table.
	 *
	 * @since 1.5.0
	 * @param array $permastructs Array of permastructs.
	 */
	private function render_permastructs_table( array $permastructs ): void {
		$template_path = $this->plugin_dir_path . 'views/permastructs-table.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}

	/**
	 * Render URL test results.
	 *
	 * @since 1.5.0
	 * @param array $results URL test results.
	 */
	private function render_url_test_results( array $results ): void {
		?>
		<div class="rri-url-test-results">
			<h3><?php esc_html_e( 'URL Test Results', 'rewrite-rules-inspector' ); ?></h3>
			
			<div class="rri-test-summary">
				<p><strong><?php esc_html_e( 'URL Tested:', 'rewrite-rules-inspector' ); ?></strong> <code><?php echo esc_html( $results['url'] ); ?></code></p>
				<p><strong><?php esc_html_e( 'Path Tested:', 'rewrite-rules-inspector' ); ?></strong> <code><?php echo esc_html( $results['path'] ); ?></code></p>
				
				<?php if ( $results['is_404'] ) : ?>
					<div class="notice notice-error">
						<p><strong><?php esc_html_e( 'Result: 404 Error', 'rewrite-rules-inspector' ); ?></strong></p>
						<p><?php 
							printf(
								/* translators: %d: Number of rules tested */
								esc_html__( 'This URL does not match any of the %d rewrite rules and would result in a 404 error.', 'rewrite-rules-inspector' ),
								$results['total_rules_tested']
							);
						?></p>
					</div>
				<?php else : ?>
					<div class="notice notice-success">
						<p><strong><?php esc_html_e( 'Result: Found Matching Rule(s)', 'rewrite-rules-inspector' ); ?></strong></p>
						<p><?php 
							printf(
								/* translators: %d: Number of matches */
								esc_html__( 'Found %d matching rule(s).', 'rewrite-rules-inspector' ),
								count( $results['matches'] )
							);
						?></p>
					</div>

					<?php if ( $results['first_match'] ) : ?>
						<div class="rri-first-match">
							<h4><?php esc_html_e( 'First Match (WordPress will use this):', 'rewrite-rules-inspector' ); ?></h4>
							<table class="widefat">
								<tr>
									<th><?php esc_html_e( 'Rule Pattern', 'rewrite-rules-inspector' ); ?></th>
									<td><code><?php echo esc_html( $results['first_match']['rule'] ); ?></code></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Rewrite Target', 'rewrite-rules-inspector' ); ?></th>
									<td><code><?php echo esc_html( $results['first_match']['rewrite'] ); ?></code></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Source', 'rewrite-rules-inspector' ); ?></th>
									<td><?php echo esc_html( $results['first_match']['source'] ); ?></td>
								</tr>
								<?php if ( ! empty( $results['first_match']['query_vars'] ) ) : ?>
									<tr>
										<th><?php esc_html_e( 'Query Variables', 'rewrite-rules-inspector' ); ?></th>
										<td>
											<?php foreach ( $results['first_match']['query_vars'] as $key => $value ) : ?>
												<code><?php echo esc_html( $key ); ?> = <?php echo esc_html( $value ); ?></code><br>
											<?php endforeach; ?>
										</td>
									</tr>
								<?php endif; ?>
							</table>
						</div>
					<?php endif; ?>

					<?php if ( count( $results['matches'] ) > 1 ) : ?>
						<div class="rri-all-matches">
							<h4><?php esc_html_e( 'All Matching Rules:', 'rewrite-rules-inspector' ); ?></h4>
							<ol>
								<?php foreach ( $results['matches'] as $index => $match ) : ?>
									<li>
										<code><?php echo esc_html( $match['rule'] ); ?></code>
										<span class="rri-match-source">(<?php echo esc_html( $match['source'] ); ?>)</span>
									</li>
								<?php endforeach; ?>
							</ol>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Show a message when you've successfully flushed your rewrite rules.
	 *
	 * @since 1.5.0
	 */
	public function render_flush_success_message(): void {
		echo '<div class="message updated"><p>' . esc_html__( 'Rewrite rules flushed.', 'rewrite-rules-inspector' ) . '</p></div>';
	}
}
