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
	 */
	public function render_rules_view( array $rules, array $permastructs, $wp_list_table ): void {
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
	 * Show a message when you've successfully flushed your rewrite rules.
	 *
	 * @since 1.5.0
	 */
	public function render_flush_success_message(): void {
		echo '<div class="message updated"><p>' . esc_html__( 'Rewrite rules flushed.', 'rewrite-rules-inspector' ) . '</p></div>';
	}
}
