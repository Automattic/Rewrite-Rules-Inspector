<?php
/**
 * Rewrite Rules Inspector class
 *
 * @package automattic\rewrite-rules-inspector
 * @since 1.3.0
 */

/**
 * Main class for the plugin.
 *
 * @since 1.0.0
 */
class Rewrite_Rules_Inspector {

	/**
	 * Which admin menu parent the Rewrite Rules Inspector page will appear under. Default is Tools.
	 *
	 * @var string $parent_slug
	 */
	public $parent_slug = 'tools.php';

	/**
	 * Rewrite Rules Inspector page slug.
	 *
	 * @var string $page_slug
	 */
	public $page_slug = 'rewrite-rules-inspector';

	/**
	 * Capability needed to view the Rewrite Rules Inspector page.
	 *
	 * @var string $view_cap
	 */
	public $view_cap = 'manage_options';

	/**
	 * Whether or not users can flush the rewrite rules from this tool.
	 *
	 * @var bool $flushing_enabled
	 */
	public $flushing_enabled = true;

	/**
	 * Sources of rules.
	 *
	 * @var array $sources
	 */
	public $sources = array();

	/**
	 * Run the integration.
	 *
	 * @since 1.3.0
	 */
	public function run() {
		// This plugin only runs in the admin, but we need it initialized on init.
		add_action( 'init', array( $this, 'action_init' ) );
	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 */
	public function action_init() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );

		// Allow the view to be placed elsewhere than tools.php.
		$this->parent_slug = apply_filters( 'rri_parent_slug', $this->parent_slug );

		// Whether or not users can flush the rewrite rules from this tool.
		$this->flushing_enabled = apply_filters( 'rri_flushing_enabled', $this->flushing_enabled );

		// User actions available for the rewrite rules page.
		if ( isset( $_GET['page'], $_GET['action'] ) && $_GET['page'] === $this->page_slug && 'download-rules' === $_GET['action'] ) {
			add_action( 'admin_init', array( $this, 'download_rules' ) );
		} elseif ( isset( $_GET['page'], $_GET['action'] ) && $_GET['page'] === $this->page_slug && 'flush-rules' === $_GET['action'] ) {
			add_action( 'admin_init', array( $this, 'flush_rules' ) );
		} elseif ( isset( $_GET['page'], $_GET['message'] ) && $_GET['page'] === $this->page_slug && 'flush-success' === $_GET['message'] ) {
			add_action( 'admin_notices', array( $this, 'action_admin_notices' ) );
		}
	}

	/**
	 * Add our sub-menu page to the VIP dashboard navigation.
	 *
	 * @since 1.0.0
	 */
	public function action_admin_menu() {
		$hook = add_submenu_page( $this->parent_slug, __( 'Rewrite Rules Inspector', 'rewrite-rules-inspector' ), __( 'Rewrite Rules', 'rewrite-rules-inspector' ), $this->view_cap, $this->page_slug, array( $this, 'view_rules' ) );
		
		// Add screen help.
		add_action( 'load-' . $hook, array( $this, 'add_screen_help' ) );
	}

	/**
	 * Show a message when you've successfully flushed your rewrite rules.
	 *
	 * @since 1.1.0
	 */
	public function action_admin_notices() {
		echo '<div class="message updated"><p>' . esc_html__( 'Rewrite rules flushed.', 'rewrite-rules-inspector' ) . '</p></div>';
	}

	/**
	 * Add screen help tabs to explain rewrite rules and permastructs.
	 *
	 * @since 1.5.0
	 */
	public function add_screen_help() {
		$screen = get_current_screen();
		
		// Overview tab.
		$screen->add_help_tab(
			array(
				'id'      => 'overview',
				'title'   => __( 'Overview', 'rewrite-rules-inspector' ),
				'content' => '<p>' . __( 'The Rewrite Rules Inspector helps you understand and debug your WordPress site\'s URL structure. It shows you all the rewrite rules and permastructs that WordPress uses to handle URLs.', 'rewrite-rules-inspector' ) . '</p>',
			)
		);
		
		// Rewrite Rules tab.
		$screen->add_help_tab(
			array(
				'id'      => 'rewrite-rules',
				'title'   => __( 'Rewrite Rules', 'rewrite-rules-inspector' ),
				'content' => '<p>' . __( '<strong>Rewrite Rules</strong> are the actual URL patterns that WordPress uses to match incoming requests and determine what content to display.', 'rewrite-rules-inspector' ) . '</p>' .
					'<p>' . __( 'Each rule consists of:', 'rewrite-rules-inspector' ) . '</p>' .
					'<ul>' .
					'<li>' . __( '<strong>Rule:</strong> A regular expression pattern that matches URLs (e.g., <code>^category/([^/]+)/?$</code>)', 'rewrite-rules-inspector' ) . '</li>' .
					'<li>' . __( '<strong>Rewrite:</strong> The internal WordPress query that gets executed (e.g., <code>index.php?category_name=$matches[1]</code>)', 'rewrite-rules-inspector' ) . '</li>' .
					'<li>' . __( '<strong>Source:</strong> Where the rule comes from (e.g., category, post, custom permastruct)', 'rewrite-rules-inspector' ) . '</li>' .
					'</ul>' .
					'<p>' . __( 'When someone visits a URL, WordPress checks these rules in order until it finds a match, then executes the corresponding rewrite to determine what content to show.', 'rewrite-rules-inspector' ) . '</p>',
			)
		);
		
		// Permastructs tab.
		$screen->add_help_tab(
			array(
				'id'      => 'permastructs',
				'title'   => __( 'Permastructs', 'rewrite-rules-inspector' ),
				'content' => '<p>' . __( '<strong>Permastructs</strong> are the URL structure templates that define how different types of content should be accessed via URLs.', 'rewrite-rules-inspector' ) . '</p>' .
					'<p>' . __( 'For example:', 'rewrite-rules-inspector' ) . '</p>' .
					'<ul>' .
					'<li>' . __( '<strong>Post Permalink:</strong> <code>/%year%/%monthnum%/%day%/%postname%/</code> - defines how individual posts are accessed', 'rewrite-rules-inspector' ) . '</li>' .
					'<li>' . __( '<strong>Category Archive:</strong> <code>/category/%category%</code> - defines how category pages are accessed', 'rewrite-rules-inspector' ) . '</li>' .
					'<li>' . __( '<strong>Tag Archive:</strong> <code>/tag/%post_tag%</code> - defines how tag pages are accessed', 'rewrite-rules-inspector' ) . '</li>' .
					'</ul>' .
					'<p>' . __( 'WordPress uses these permastructs to generate the actual rewrite rules. The permastructs are like blueprints, while the rewrite rules are the specific patterns that get created from those blueprints.', 'rewrite-rules-inspector' ) . '</p>' .
					'<p>' . __( 'You can customize permastructs through WordPress settings (Settings → Permalinks) or by using WordPress functions like <code>add_permastruct()</code> in your theme or plugin.', 'rewrite-rules-inspector' ) . '</p>',
			)
		);
		
		// Troubleshooting tab.
		$screen->add_help_tab(
			array(
				'id'      => 'troubleshooting',
				'title'   => __( 'Troubleshooting', 'rewrite-rules-inspector' ),
				'content' => '<p>' . __( '<strong>Common Issues:</strong>', 'rewrite-rules-inspector' ) . '</p>' .
					'<ul>' .
					'<li>' . __( '<strong>Missing Rules:</strong> If you see rules marked as "missing", try clicking the "Flush Rules" button to regenerate them.', 'rewrite-rules-inspector' ) . '</li>' .
					'<li>' . __( '<strong>404 Errors:</strong> Check if the URL pattern exists in the rewrite rules. Use the "Match URL" filter to test specific URLs.', 'rewrite-rules-inspector' ) . '</li>' .
					'<li>' . __( '<strong>Custom URLs Not Working:</strong> Verify that your custom permastruct is properly registered and that rewrite rules have been flushed.', 'rewrite-rules-inspector' ) . '</li>' .
					'<li>' . __( '<strong>Plugin Conflicts:</strong> Some plugins may modify rewrite rules. Check the "Source" column to see which rules come from which sources.', 'rewrite-rules-inspector' ) . '</li>' .
					'</ul>' .
					'<p>' . __( '<strong>Tips:</strong>', 'rewrite-rules-inspector' ) . '</p>' .
					'<ul>' .
					'<li>' . __( 'Use the "Rule Source" filter to focus on specific types of rules.', 'rewrite-rules-inspector' ) . '</li>' .
					'<li>' . __( 'Download the rules as a text file for offline analysis.', 'rewrite-rules-inspector' ) . '</li>' .
					'<li>' . __( 'Check the permastructs section to understand the URL structure templates.', 'rewrite-rules-inspector' ) . '</li>' .
					'</ul>',
			)
		);
		
		// Help sidebar.
		$screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'rewrite-rules-inspector' ) . '</strong></p>' .
			'<p><a href="https://wordpress.org/support/article/using-permalinks/" target="_blank">' . __( 'WordPress Permalinks Documentation', 'rewrite-rules-inspector' ) . '</a></p>' .
			'<p><a href="https://developer.wordpress.org/reference/functions/add_rewrite_rule/" target="_blank">' . __( 'WordPress Rewrite API', 'rewrite-rules-inspector' ) . '</a></p>' .
			'<p><a href="https://github.com/Automattic/Rewrite-Rules-Inspector" target="_blank">' . __( 'Plugin on GitHub', 'rewrite-rules-inspector' ) . '</a></p>'
		);
	}

	/**
	 * Get all permastructs that WordPress is aware of.
	 *
	 * @since 1.5.0
	 * @return array Array of permastructs with their names and structures.
	 */
	public function get_permastructs() {
		global $wp_rewrite;

		$permastructs = array();

		// Core permastructs.
		$permastructs['post'] = array(
			'name'        => __( 'Post Permalink', 'rewrite-rules-inspector' ),
			'structure'   => $wp_rewrite->permalink_structure,
			'description' => __( 'The permalink structure for posts', 'rewrite-rules-inspector' ),
		);

		$permastructs['date'] = array(
			'name'        => __( 'Date Archive', 'rewrite-rules-inspector' ),
			'structure'   => $wp_rewrite->get_date_permastruct(),
			'description' => __( 'The permalink structure for date archives', 'rewrite-rules-inspector' ),
		);

		$permastructs['search'] = array(
			'name'        => __( 'Search Results', 'rewrite-rules-inspector' ),
			'structure'   => $wp_rewrite->get_search_permastruct(),
			'description' => __( 'The permalink structure for search results', 'rewrite-rules-inspector' ),
		);

		$permastructs['author'] = array(
			'name'        => __( 'Author Archive', 'rewrite-rules-inspector' ),
			'structure'   => $wp_rewrite->get_author_permastruct(),
			'description' => __( 'The permalink structure for author archives', 'rewrite-rules-inspector' ),
		);

		$permastructs['comments'] = array(
			'name'        => __( 'Comments', 'rewrite-rules-inspector' ),
			'structure'   => $wp_rewrite->root . $wp_rewrite->comments_base,
			'description' => __( 'The permalink structure for comments', 'rewrite-rules-inspector' ),
		);

		$permastructs['root'] = array(
			'name'        => __( 'Root', 'rewrite-rules-inspector' ),
			'structure'   => $wp_rewrite->root . '/',
			'description' => __( 'The root permalink structure', 'rewrite-rules-inspector' ),
		);

		// Extra permastructs including tags, categories, etc.
		foreach ( $wp_rewrite->extra_permastructs as $permastructname => $permastruct ) {
			$structure = '';
			if ( is_array( $permastruct ) ) {
				// Pre 3.4 compat.
				if ( count( $permastruct ) === 2 ) {
					$structure = $permastruct[0];
				} else {
					$structure = $permastruct['struct'] ?? '';
				}
			} else {
				$structure = $permastruct;
			}

			// Generate human-readable names and descriptions.
			$name        = ucwords( str_replace( array( '_', '-' ), ' ', $permastructname ) );
			/* translators: %s: permastruct name */
			$description = sprintf( __( 'The permalink structure for %s', 'rewrite-rules-inspector' ), strtolower( $name ) );

			// Special cases for common permastructs.
			switch ( $permastructname ) {
				case 'category':
					$name        = __( 'Category Archive', 'rewrite-rules-inspector' );
					$description = __( 'The permalink structure for category archives', 'rewrite-rules-inspector' );
					break;
				case 'post_tag':
					$name        = __( 'Tag Archive', 'rewrite-rules-inspector' );
					$description = __( 'The permalink structure for tag archives', 'rewrite-rules-inspector' );
					break;
				case 'post_format':
					$name        = __( 'Post Format Archive', 'rewrite-rules-inspector' );
					$description = __( 'The permalink structure for post format archives', 'rewrite-rules-inspector' );
					break;
				case 'test_custom':
					$name        = __( 'Test Custom (Demo)', 'rewrite-rules-inspector' );
					$description = __( 'A custom permastruct added for testing the permastructs display feature', 'rewrite-rules-inspector' );
					break;
				case 'demo_archive':
					$name        = __( 'Demo Archive (Test)', 'rewrite-rules-inspector' );
					$description = __( 'A demo archive permastruct with date-based structure for testing', 'rewrite-rules-inspector' );
					break;
			}

			$permastructs[ $permastructname ] = array(
				'name'        => $name,
				'structure'   => $structure,
				'description' => $description,
			);
		}

		// Filter out empty structures.
		$permastructs = array_filter(
			$permastructs,
			function ( $permastruct ) {
				return ! empty( $permastruct['structure'] );
			}
		);

		// Allow filtering of permastructs.
		$permastructs = apply_filters( 'rri_permastructs', $permastructs );

		return $permastructs;
	}

	/**
	 * Get the rewrite rules for the current view.
	 *
	 * @since 1.0.0
	 */
	public function get_rules() {
		global $wp_rewrite;

		$rewrite_rules_array = array();
		$rewrite_rules       = get_option( 'rewrite_rules' );
		if ( ! $rewrite_rules ) {
			$rewrite_rules = array();
		}

		// Track down which rewrite rules are associated with which methods by breaking it down.
		$rewrite_rules_by_source             = array();
		$rewrite_rules_by_source['post']     = $wp_rewrite->generate_rewrite_rules( $wp_rewrite->permalink_structure, EP_PERMALINK );
		$rewrite_rules_by_source['date']     = $wp_rewrite->generate_rewrite_rules( $wp_rewrite->get_date_permastruct(), EP_DATE );
		$rewrite_rules_by_source['root']     = $wp_rewrite->generate_rewrite_rules( $wp_rewrite->root . '/', EP_ROOT );
		$rewrite_rules_by_source['comments'] = $wp_rewrite->generate_rewrite_rules( $wp_rewrite->root . $wp_rewrite->comments_base, EP_COMMENTS, true, true, true, false );
		$rewrite_rules_by_source['search']   = $wp_rewrite->generate_rewrite_rules( $wp_rewrite->get_search_permastruct(), EP_SEARCH );
		$rewrite_rules_by_source['author']   = $wp_rewrite->generate_rewrite_rules( $wp_rewrite->get_author_permastruct(), EP_AUTHORS );
		$rewrite_rules_by_source['page']     = $wp_rewrite->page_rewrite_rules();

		// Extra permastructs including tags, categories, etc.
		foreach ( $wp_rewrite->extra_permastructs as $permastructname => $permastruct ) {
			if ( is_array( $permastruct ) ) {
				// Pre 3.4 compat.
				if ( count( $permastruct ) === 2 ) {
					$rewrite_rules_by_source[ $permastructname ] = $wp_rewrite->generate_rewrite_rules( $permastruct[0], $permastruct[1] );
				} else {
					$rewrite_rules_by_source[ $permastructname ] = $wp_rewrite->generate_rewrite_rules( $permastruct['struct'], $permastruct['ep_mask'], $permastruct['paged'], $permastruct['feed'], $permastruct['forcomments'], $permastruct['walk_dirs'], $permastruct['endpoints'] );
				}
			} else {
				$rewrite_rules_by_source[ $permastructname ] = $wp_rewrite->generate_rewrite_rules( $permastruct, EP_NONE );
			}
		}

		// Apply the filters used in core just in case.
		foreach ( $rewrite_rules_by_source as $source => $rules ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- core hook.
			$rewrite_rules_by_source[ $source ] = apply_filters( $source . '_rewrite_rules', $rules );
			if ( 'post_tag' === $source ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core hook.
				$rewrite_rules_by_source[ $source ] = apply_filters( 'tag_rewrite_rules', $rules );
			}
		}

		foreach ( $rewrite_rules as $rule => $rewrite ) {
			$rewrite_rules_array[ $rule ]['rewrite'] = $rewrite;
			foreach ( $rewrite_rules_by_source as $source => $rules ) {
				if ( array_key_exists( $rule, $rules ) ) {
					$rewrite_rules_array[ $rule ]['source'] = $source;
				}
			}

			if ( ! isset( $rewrite_rules_array[ $rule ]['source'] ) ) {
				$rewrite_rules_array[ $rule ]['source'] = apply_filters( 'rewrite_rules_inspector_source', 'other', $rule, $rewrite );
			}
		}

		// Find any rewrite rules that should've been generated but weren't.
		$maybe_missing       = $wp_rewrite->rewrite_rules();
		$rewrite_rules_array = array_reverse( $rewrite_rules_array, true );
		foreach ( $maybe_missing as $rule => $rewrite ) {
			if ( ! array_key_exists( $rule, $rewrite_rules_array ) ) {
				$rewrite_rules_array[ $rule ] = array(
					'rewrite' => $rewrite,
					'source'  => 'missing',
				);
			}
		}

		// Prepend rules so it's obvious.
		$rewrite_rules_array = array_reverse( $rewrite_rules_array, true );

		// Allow static sources of rewrite rules to override, etc.
		$rewrite_rules_array = apply_filters( 'rri_rewrite_rules', $rewrite_rules_array );
		// Set the sources used in our filtering.
		$sources = array( 'all' );
		foreach ( $rewrite_rules_array as $rule => $data ) {
			$sources[] = $data['source'];
		}

		$this->sources = array_unique( $sources );

		$match_path = '';
		
		if ( ! empty( $_GET['s'] ) ) {
			$match_path                = wp_parse_url( esc_url( $_GET['s'] ), PHP_URL_PATH );
			$wordpress_subdir_for_site = wp_parse_url( home_url(), PHP_URL_PATH );
			if ( ! empty( $wordpress_subdir_for_site ) ) {
				$match_path = str_replace( $wordpress_subdir_for_site, '', $match_path );
			}

			$match_path = ltrim( $match_path, '/' );
		}

		$should_filter_by_source = ! empty( $_GET['source'] ) && 'all' !== $_GET['source'] && in_array( $_GET['source'], $this->sources, true );

		// Filter based on match or source if necessary.
		foreach ( $rewrite_rules_array as $rule => $data ) {
			// If we're searching rules based on URL and there's no match, don't return it.
			if ( $match_path !== '' && $match_path !== '0' && ! preg_match( sprintf('#^%s#', $rule), $match_path ) ) {
				unset( $rewrite_rules_array[ $rule ] );
			} elseif ( $should_filter_by_source && $data['source'] !== $_GET['source'] ) {
				unset( $rewrite_rules_array[ $rule ] );
			}
		}

		// Return our array of rewrite rules to be used.
		return $rewrite_rules_array;
	}

	/**
	 * View the rewrite rules for the site.
	 *
	 * @since 1.0.0
	 */
	public function view_rules() {
		$rules = $this->get_rules();

		// Bump view stats or do something else on page load.
		do_action( 'rri_view_rewrite_rules', $rules );

		$wp_list_table = new Rewrite_Rules_Inspector_List_Table( $rules );
		$wp_list_table->prepare_items();

		?>
		<style>
			#the-list tr.type-sunrise,
			#the-list tr.type-custom {
				background-color: #eec7f0;
			}
			#the-list tr.type-sunrise td,
			#the-list tr.type-custom td {
				border-top-color: #f4e6f5;
				border-bottom-color: #efbbf2;
			}
			#the-list tr.source-missing {
				background-color: #f7a8a9;
			}
			#the-list tr.type-missing td {
				border-top-color: #fecfd0;
				border-bottom-color: #f99b9d;
			}
			#permastructs-section {
				margin-top: 30px;
			}
			.permastruct-structure {
				font-family: monospace;
				background: #f6f7f7;
				padding: 2px 6px;
				border-radius: 3px;
				font-size: 13px;
			}
		</style>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php
			// Get permastructs for jump link.
			$permastructs = $this->get_permastructs();
			?>

			<h2 id="rewrite-rules-section"><?php esc_html_e( 'Rewrite Rules', 'rewrite-rules-inspector' ); ?></h2>
			
			<?php if ( ! empty( $permastructs ) ) : ?>
				<p>
					<a href="#permastructs-section"><?php esc_html_e( 'Jump to Permastructs', 'rewrite-rules-inspector' ); ?></a>
				</p>
			<?php endif; ?>

			<?php
			$missing_count = 0;
			foreach ( $rules as $rule ) {
				if ( 'missing' === $rule['source'] ) {
					++$missing_count;
				}
			}

			if ( empty( $rules ) ) {
				$error_message = apply_filters( 'rri_message_no_rules', __( 'No rewrite rules yet, try flushing.', 'rewrite-rules-inspector' ) );
				echo '<div class="message error"><p>' . wp_kses_post( $error_message ) . '</p></div>';
			} elseif ( in_array( 'missing', $this->sources, true ) ) {
				/* translators: %d: Count of missing rewrite rules */
				$error_message = apply_filters( 'rri_message_missing_rules', sprintf( _n( '%d rewrite rule may be missing, try flushing.', '%d rewrite rules may be missing, try flushing.', $missing_count, 'rewrite-rules-inspector' ), $missing_count ) );
				echo '<div class="message error"><p>' . wp_kses_post( $error_message ) . '</p></div>';
			}
			?>

			<?php if ( ! empty( $_GET['s'] ) ) : ?>
				<p>
					<?php
					/* translators: %s: Count of rewrite rules */
					printf( wp_kses_post( __( 'A listing of all %1$s rewrite rules for this site that match "<a target="_blank" href="%2$s">%2$s</a>"', 'rewrite-rules-inspector' ) ), count( $wp_list_table->items ), esc_url( $_GET['s'] ) );
					?>
				</p>
			<?php else : ?>
				<p>
					<?php
					/* translators: %s: Count of rewrite rules */
					printf( esc_html__( 'A listing of all %1$s rewrite rules for this site.', 'rewrite-rules-inspector' ), count( $wp_list_table->items ) );
					?>
				</p>
			<?php endif; ?>

			<?php $wp_list_table->display(); ?>

			<?php if ( ! empty( $permastructs ) ) : ?>
				<h2 id="permastructs-section"><?php esc_html_e( 'Permastructs', 'rewrite-rules-inspector' ); ?></h2>
				
				<p>
					<?php
					/* translators: %d: Count of permastructs */
					printf( esc_html__( 'A listing of all %d permastructs that WordPress is aware of.', 'rewrite-rules-inspector' ), count( $permastructs ) );
					?>
					<a href="#rewrite-rules-section"><?php esc_html_e( 'Jump to Rewrite Rules', 'rewrite-rules-inspector' ); ?></a>
				</p>

				<?php
				// Create a simple list table for permastructs.
				$permastructs_table = new WP_List_Table(
					array(
						'singular' => 'Permastruct',
						'plural'   => 'Permastructs',
					)
				);

				// Set up the columns.
				$permastructs_table->_column_headers = array(
					array(
						'name'        => __( 'Name', 'rewrite-rules-inspector' ),
						'structure'   => __( 'Structure', 'rewrite-rules-inspector' ),
						'description' => __( 'Description', 'rewrite-rules-inspector' ),
					),
					array(),
					array(),
				);

				// Set the items.
				$permastructs_table->items = $permastructs;

				// Display the table.
				?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col" class="manage-column column-name column-primary">
								<?php esc_html_e( 'Name', 'rewrite-rules-inspector' ); ?>
							</th>
							<th scope="col" class="manage-column column-structure">
								<?php esc_html_e( 'Structure', 'rewrite-rules-inspector' ); ?>
							</th>
							<th scope="col" class="manage-column column-description">
								<?php esc_html_e( 'Description', 'rewrite-rules-inspector' ); ?>
							</th>
						</tr>
					</thead>
					<tbody id="the-list">
						<?php foreach ( $permastructs as $permastruct ) : ?>
							<tr>
								<td class="name column-name column-primary">
									<strong><?php echo esc_html( $permastruct['name'] ); ?></strong>
								</td>
								<td class="structure column-structure">
									<code class="permastruct-structure"><?php echo esc_html( $permastruct['structure'] ); ?></code>
								</td>
								<td class="description column-description">
									<?php echo esc_html( $permastruct['description'] ); ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
					<tfoot>
						<tr>
							<th scope="col" class="manage-column column-name column-primary">
								<?php esc_html_e( 'Name', 'rewrite-rules-inspector' ); ?>
							</th>
							<th scope="col" class="manage-column column-structure">
								<?php esc_html_e( 'Structure', 'rewrite-rules-inspector' ); ?>
							</th>
							<th scope="col" class="manage-column column-description">
								<?php esc_html_e( 'Description', 'rewrite-rules-inspector' ); ?>
							</th>
						</tr>
					</tfoot>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Process a user's request to download a set of the rewrite rules.
	 *
	 * Prompts a download of the current set of rules as a text file by
	 * setting the header. Respects current filter rules.
	 *
	 * @since 1.0.0
	 */
	public function download_rules() {
		// Check nonce and permissions.
		check_admin_referer( 'download-rules' );
		if ( ! current_user_can( $this->view_cap ) ) {
			wp_die( esc_html__( 'You do not have permissions to perform this action.', 'rewrite-rules-inspector' ) );
		}

		// Get the rewrite rules and prompt the user to download them.
		// File is saved as YYYYMMDD.themename.rewriterules.txt.
		$theme_name = sanitize_key( get_option( 'stylesheet' ) );
		$filename   = gmdate( 'Ymd' ) . '.' . $theme_name . '.rewriterules.txt';
		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$rewrite_rules   = $this->get_rules();
		$rules_to_export = array();
		foreach ( $rewrite_rules as $rule => $data ) {
			$rules_to_export[ $rule ] = $data['rewrite'];
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.PHP.DevelopmentFunctions.error_log_var_export
		echo var_export( $rules_to_export, true );
		exit;
	}

	/**
	 * Allow a user to flush rewrite rules for their site.
	 *
	 * @since 1.0.0
	 */
	public function flush_rules() {
		global $plugin_page;

		// Check nonce and permissions.
		check_admin_referer( 'flush-rules' );
		if ( ! $this->flushing_enabled || ! current_user_can( $this->view_cap ) ) {
			wp_die( esc_html__( 'You do not have permissions to perform this action.', 'rewrite-rules-inspector' ) );
		}

		wp_cache_delete( 'rewrite_rules', 'options' );
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules
		flush_rewrite_rules( false );
		do_action( 'rri_flush_rules' );

		// Woo hoo!
		$args = array(
			'message' => 'flush-success',
		);
		wp_safe_redirect( add_query_arg( $args, menu_page_url( $plugin_page, false ) ) );
		exit;
	}
}
