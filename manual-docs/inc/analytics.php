<?php
/**
 * Documentation analytics — views + search popularity.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Track a document view (rate-limited per session cookie).
 *
 * @param int $post_id Post ID.
 */
function manual_docs_track_doc_view( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 || 'manual_documentation' !== get_post_type( $post_id ) ) {
		return;
	}
	if ( is_preview() || is_admin() ) {
		return;
	}

	$cookie = 'md_viewed_' . $post_id;
	if ( ! empty( $_COOKIE[ $cookie ] ) ) {
		return;
	}

	$count = (int) get_post_meta( $post_id, '_manual_docs_views', true );
	update_post_meta( $post_id, '_manual_docs_views', $count + 1 );

	// Soft cookie so refreshes don't inflate counts (6 hours).
	if ( ! headers_sent() ) {
		setcookie( $cookie, '1', time() + 6 * HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	}
}

/**
 * Hook views on single docs (including AJAX-loaded pages via template).
 */
function manual_docs_maybe_track_current_doc() {
	if ( ! is_singular( 'manual_documentation' ) ) {
		return;
	}
	manual_docs_track_doc_view( get_queried_object_id() );
}
add_action( 'template_redirect', 'manual_docs_maybe_track_current_doc', 20 );

/**
 * Track a search query string.
 *
 * @param string $query Search query.
 */
function manual_docs_track_search_query( $query ) {
	$query = strtolower( trim( wp_strip_all_tags( (string) $query ) ) );
	if ( strlen( $query ) < 2 || strlen( $query ) > 80 ) {
		return;
	}

	$stats = get_option( 'manual_docs_search_stats', array() );
	if ( ! is_array( $stats ) ) {
		$stats = array();
	}

	$key = md5( $query );
	if ( ! isset( $stats[ $key ] ) || ! is_array( $stats[ $key ] ) ) {
		$stats[ $key ] = array(
			'q'     => $query,
			'count' => 0,
			'last'  => 0,
		);
	}
	$stats[ $key ]['q']     = $query;
	$stats[ $key ]['count'] = (int) $stats[ $key ]['count'] + 1;
	$stats[ $key ]['last']  = time();

	// Keep the option bounded.
	if ( count( $stats ) > 400 ) {
		uasort(
			$stats,
			static function ( $a, $b ) {
				return (int) $b['count'] <=> (int) $a['count'];
			}
		);
		$stats = array_slice( $stats, 0, 300, true );
	}

	update_option( 'manual_docs_search_stats', $stats, false );
}

/**
 * Top viewed documents.
 *
 * @param int $limit Limit.
 * @return array<int,array{id:int,title:string,url:string,views:int}>
 */
function manual_docs_get_top_viewed_docs( $limit = 20 ) {
	$limit = max( 1, min( 50, (int) $limit ) );
	$query = new WP_Query(
		array(
			'post_type'              => 'manual_documentation',
			'post_status'            => 'publish',
			'posts_per_page'         => $limit,
			'meta_key'               => '_manual_docs_views',
			'orderby'                => 'meta_value_num',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		)
	);

	$out = array();
	foreach ( $query->posts as $post ) {
		$out[] = array(
			'id'    => (int) $post->ID,
			'title' => get_the_title( $post ),
			'url'   => get_permalink( $post ),
			'views' => (int) get_post_meta( $post->ID, '_manual_docs_views', true ),
		);
	}
	return $out;
}

/**
 * Top search queries.
 *
 * @param int $limit Limit.
 * @return array<int,array{q:string,count:int,last:int}>
 */
function manual_docs_get_top_searches( $limit = 20 ) {
	$limit = max( 1, min( 50, (int) $limit ) );
	$stats = get_option( 'manual_docs_search_stats', array() );
	if ( ! is_array( $stats ) || empty( $stats ) ) {
		return array();
	}
	$rows = array_values( $stats );
	usort(
		$rows,
		static function ( $a, $b ) {
			$c = (int) ( $b['count'] ?? 0 ) <=> (int) ( $a['count'] ?? 0 );
			if ( 0 !== $c ) {
				return $c;
			}
			return (int) ( $b['last'] ?? 0 ) <=> (int) ( $a['last'] ?? 0 );
		}
	);
	return array_slice( $rows, 0, $limit );
}

/**
 * Register stats admin page.
 */
function manual_docs_stats_menu() {
	add_theme_page(
		__( 'Docs Stats', 'manual-docs' ),
		__( 'Docs Stats', 'manual-docs' ),
		'edit_theme_options',
		'manual-docs-stats',
		'manual_docs_render_stats_page'
	);
}
add_action( 'admin_menu', 'manual_docs_stats_menu' );

/**
 * Handle reset / sample install actions.
 */
function manual_docs_stats_admin_actions() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	if ( isset( $_POST['manual_docs_reset_stats'] ) ) {
		check_admin_referer( 'manual_docs_stats_actions' );
		delete_option( 'manual_docs_search_stats' );
		$q = new WP_Query(
			array(
				'post_type'      => 'manual_documentation',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_manual_docs_views',
			)
		);
		foreach ( $q->posts as $id ) {
			delete_post_meta( (int) $id, '_manual_docs_views' );
		}
		add_settings_error( 'manual_docs_stats', 'reset', __( 'Stats cleared.', 'manual-docs' ), 'updated' );
	}

	if ( isset( $_POST['manual_docs_install_sample'] ) && function_exists( 'manual_docs_install_kitchen_sink_sample' ) ) {
		check_admin_referer( 'manual_docs_stats_actions' );
		$result = manual_docs_install_kitchen_sink_sample();
		if ( is_wp_error( $result ) ) {
			add_settings_error( 'manual_docs_stats', 'sample_err', $result->get_error_message(), 'error' );
		} else {
			add_settings_error(
				'manual_docs_stats',
				'sample_ok',
				sprintf(
					/* translators: %s: edit link */
					__( 'Sample document ready. %s', 'manual-docs' ),
					'<a href="' . esc_url( get_edit_post_link( $result, 'raw' ) ) . '">' . esc_html__( 'Edit it', 'manual-docs' ) . '</a>'
					. ' · <a href="' . esc_url( get_permalink( $result ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View it', 'manual-docs' ) . '</a>'
				),
				'updated'
			);
		}
	}

	if ( isset( $_POST['manual_docs_install_bbpress_sample'] ) && function_exists( 'manual_docs_install_bbpress_sample' ) ) {
		check_admin_referer( 'manual_docs_stats_actions' );
		$result = manual_docs_install_bbpress_sample();
		if ( is_wp_error( $result ) ) {
			add_settings_error( 'manual_docs_stats', 'bbp_sample_err', $result->get_error_message(), 'error' );
		} else {
			$url = ! empty( $result['url'] ) ? $result['url'] : admin_url( 'edit.php?post_type=forum' );
			add_settings_error(
				'manual_docs_stats',
				'bbp_sample_ok',
				sprintf(
					/* translators: 1: forums count, 2: topics count, 3: replies count, 4: link */
					__( 'Sample community data ready: %1$d forums, %2$d topics, %3$d replies. %4$s', 'manual-docs' ),
					(int) $result['forums'],
					(int) $result['topics'],
					(int) $result['replies'],
					'<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html__( 'Open forums', 'manual-docs' ) . '</a>'
				),
				'updated'
			);
		}
	}
}
add_action( 'admin_init', 'manual_docs_stats_admin_actions' );

/**
 * Render stats admin page.
 */
function manual_docs_render_stats_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$views    = manual_docs_get_top_viewed_docs( 25 );
	$searches = manual_docs_get_top_searches( 25 );
	settings_errors( 'manual_docs_stats' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Docs Stats', 'manual-docs' ); ?></h1>
		<p><?php esc_html_e( 'Most visited documents and frequent search queries from the live search. View counts are rate-limited per browser (about 6 hours).', 'manual-docs' ); ?></p>

		<form method="post" style="margin:1rem 0 1.5rem;">
			<?php wp_nonce_field( 'manual_docs_stats_actions' ); ?>
			<?php submit_button( __( 'Create sample elements document', 'manual-docs' ), 'secondary', 'manual_docs_install_sample', false ); ?>
			<?php if ( function_exists( 'manual_docs_bbpress_active' ) && manual_docs_bbpress_active() ) : ?>
				<?php submit_button( __( 'Create sample forums (5 / 30 / 40)', 'manual-docs' ), 'secondary', 'manual_docs_install_bbpress_sample', false ); ?>
			<?php endif; ?>
			<?php submit_button( __( 'Reset all stats', 'manual-docs' ), 'delete', 'manual_docs_reset_stats', false ); ?>
		</form>

		<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1.5rem;">
			<div>
				<h2><?php esc_html_e( 'Most visited documents', 'manual-docs' ); ?></h2>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Document', 'manual-docs' ); ?></th>
							<th style="width:90px;"><?php esc_html_e( 'Views', 'manual-docs' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $views ) ) : ?>
							<tr><td colspan="2"><?php esc_html_e( 'No views recorded yet. Browse a few docs on the front end.', 'manual-docs' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $views as $row ) : ?>
								<tr>
									<td>
										<a href="<?php echo esc_url( $row['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $row['title'] ); ?></a>
										<div class="row-actions">
											<a href="<?php echo esc_url( get_edit_post_link( $row['id'] ) ); ?>"><?php esc_html_e( 'Edit', 'manual-docs' ); ?></a>
										</div>
									</td>
									<td><strong><?php echo esc_html( (string) $row['views'] ); ?></strong></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<div>
				<h2><?php esc_html_e( 'Frequent searches', 'manual-docs' ); ?></h2>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Query', 'manual-docs' ); ?></th>
							<th style="width:90px;"><?php esc_html_e( 'Count', 'manual-docs' ); ?></th>
							<th style="width:140px;"><?php esc_html_e( 'Last seen', 'manual-docs' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $searches ) ) : ?>
							<tr><td colspan="3"><?php esc_html_e( 'No searches recorded yet. Use the live search on the site.', 'manual-docs' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $searches as $row ) : ?>
								<tr>
									<td><code><?php echo esc_html( $row['q'] ); ?></code></td>
									<td><strong><?php echo esc_html( (string) (int) $row['count'] ); ?></strong></td>
									<td><?php echo esc_html( ! empty( $row['last'] ) ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $row['last'] ) : '—' ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<?php
}
