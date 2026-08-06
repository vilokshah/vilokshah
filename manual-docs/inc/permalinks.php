<?php
/**
 * Documentation permalinks for subdirectory + Local / Apache installs.
 *
 * The plain Apache "Not Found" page means the request never reached WordPress.
 * This file:
 * 1. Resolves /documentation/... when WP does get the request (stale rules).
 * 2. Can switch doc URLs to /index.php/... or ?manual_documentation=... (no mod_rewrite).
 * 3. Hard-flushes rules and writes .htaccess when the filesystem allows it.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Permalink mode for documentation links.
 *
 * @return string pretty|index_php|query
 */
function manual_docs_permalink_mode() {
	$mode = 'pretty';
	if ( function_exists( 'manual_docs_get_option' ) ) {
		$saved = manual_docs_get_option( 'permalink_mode', 'pretty' );
		if ( is_string( $saved ) && in_array( $saved, array( 'pretty', 'index_php', 'query' ), true ) ) {
			$mode = $saved;
		}
	}

	// If WP itself uses index permalinks, treat docs the same.
	if ( 'pretty' === $mode && function_exists( 'got_url_rewrite' ) ) {
		global $wp_rewrite;
		if ( $wp_rewrite instanceof WP_Rewrite && $wp_rewrite->using_index_permalinks() ) {
			$mode = 'index_php';
		}
	}

	return apply_filters( 'manual_docs_permalink_mode', $mode );
}

/**
 * Hierarchical path of a documentation post (parent slugs + leaf).
 *
 * @param int|WP_Post $post Post.
 * @return string
 */
function manual_docs_post_hierarchy_path( $post ) {
	$post = get_post( $post );
	if ( ! $post || 'manual_documentation' !== $post->post_type ) {
		return '';
	}

	$chain   = array_reverse( get_post_ancestors( $post ) );
	$chain[] = $post->ID;
	$slugs   = array();
	foreach ( $chain as $id ) {
		$slugs[] = get_post_field( 'post_name', $id );
	}

	return implode( '/', array_filter( $slugs ) );
}

/**
 * Extract the path relative to the WordPress home URL.
 *
 * @return string
 */
function manual_docs_request_path() {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$path = rawurldecode( $path );
	$path = trim( $path, '/' );

	$home_path = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$home_path = trim( $home_path, '/' );

	if ( $home_path && 0 === strpos( $path, $home_path ) ) {
		$path = trim( substr( $path, strlen( $home_path ) ), '/' );
	}

	// PATHINFO: /index.php/documentation/...
	if ( 0 === strpos( $path, 'index.php/' ) ) {
		$path = substr( $path, strlen( 'index.php/' ) );
	} elseif ( 'index.php' === $path ) {
		$path = '';
	}

	return trim( $path, '/' );
}

/**
 * Find a documentation post from a hierarchical path.
 *
 * @param string $doc_path Path after /documentation/ (e.g. goat-2/getting-started/first-login).
 * @return WP_Post|null
 */
function manual_docs_get_post_by_doc_path( $doc_path ) {
	$doc_path = trim( $doc_path, '/' );
	if ( '' === $doc_path ) {
		return null;
	}

	$post = get_page_by_path( $doc_path, OBJECT, 'manual_documentation' );
	if ( $post instanceof WP_Post ) {
		return $post;
	}

	$parts = array_values( array_filter( explode( '/', $doc_path ) ) );
	$leaf  = end( $parts );
	if ( ! $leaf ) {
		return null;
	}

	$found = get_posts(
		array(
			'name'             => $leaf,
			'post_type'        => 'manual_documentation',
			'post_status'      => array( 'publish', 'private' ),
			'posts_per_page'   => 20,
			'suppress_filters' => true,
		)
	);

	if ( empty( $found ) ) {
		return null;
	}

	foreach ( $found as $candidate ) {
		if ( manual_docs_post_hierarchy_path( $candidate ) === $doc_path ) {
			return $candidate;
		}
	}

	return $found[0];
}

/**
 * Apply resolved documentation query vars onto WP.
 *
 * @param WP     $wp       WP object.
 * @param string $doc_path Relative doc path.
 * @param bool   $is_pdf   PDF flag.
 */
function manual_docs_apply_doc_query_vars( $wp, $doc_path, $is_pdf = false ) {
	$wp->query_vars['post_type']            = 'manual_documentation';
	$wp->query_vars['manual_documentation'] = $doc_path;
	$wp->query_vars['name']                 = $doc_path;
	unset( $wp->query_vars['error'] );

	if ( $is_pdf ) {
		$wp->query_vars['manual_docs_pdf'] = '1';
	}
}

/**
 * Resolve documentation path from request URI into query vars.
 *
 * @param WP $wp WP object.
 * @return bool True when handled.
 */
function manual_docs_resolve_docs_request( $wp ) {
	$path = manual_docs_request_path();

	// Also honor explicit query var (query permalink mode / plain links).
	if ( ! empty( $wp->query_vars['manual_documentation'] ) && is_string( $wp->query_vars['manual_documentation'] ) ) {
		$existing = trim( $wp->query_vars['manual_documentation'], '/' );
		if ( $existing && false === strpos( $existing, '/' ) ) {
			// Leaf-only from a rewrite — keep WP default unless we can expand.
			return false;
		}
		if ( $existing && false !== strpos( $existing, '/' ) ) {
			$post = manual_docs_get_post_by_doc_path( $existing );
			if ( $post ) {
				manual_docs_apply_doc_query_vars( $wp, $existing, ! empty( $wp->query_vars['manual_docs_pdf'] ) );
				return true;
			}
		}
		return false;
	}

	if ( '' === $path ) {
		return false;
	}

	$base = function_exists( 'manual_docs_cpt_rewrite_slug' ) ? manual_docs_cpt_rewrite_slug() : 'documentation';
	$base = trim( $base, '/' );

	if ( $path === $base ) {
		$wp->query_vars['post_type'] = 'manual_documentation';
		unset( $wp->query_vars['error'] );
		return true;
	}

	if ( 0 !== strpos( $path, $base . '/' ) ) {
		return false;
	}

	$doc_path = trim( substr( $path, strlen( $base ) + 1 ), '/' );
	$is_pdf   = false;

	if ( preg_match( '#(^|/)pdf/?$#', $doc_path ) ) {
		$is_pdf   = true;
		$doc_path = trim( (string) preg_replace( '#(^|/)pdf/?$#', '', $doc_path ), '/' );
	}

	if ( '' === $doc_path ) {
		$wp->query_vars['post_type'] = 'manual_documentation';
		unset( $wp->query_vars['error'] );
		return true;
	}

	$post = manual_docs_get_post_by_doc_path( $doc_path );
	if ( ! $post ) {
		return false;
	}

	manual_docs_apply_doc_query_vars( $wp, $doc_path, $is_pdf );
	return true;
}

/**
 * Parse documentation URLs after rewrite matching.
 *
 * @param WP $wp WP object.
 */
function manual_docs_parse_request( $wp ) {
	if ( is_admin() || ( function_exists( 'manual_docs_is_rest_like_request' ) && manual_docs_is_rest_like_request() ) ) {
		return;
	}

	manual_docs_resolve_docs_request( $wp );
}
add_action( 'parse_request', 'manual_docs_parse_request', 1 );

/**
 * Early request filter for query-string / pathinfo docs.
 *
 * @param array $query_vars Query vars.
 * @return array
 */
function manual_docs_filter_request( $query_vars ) {
	if ( is_admin() || ( function_exists( 'manual_docs_is_rest_like_request' ) && manual_docs_is_rest_like_request() ) ) {
		return $query_vars;
	}

	if ( ! empty( $query_vars['manual_documentation'] ) ) {
		$doc_path = trim( (string) $query_vars['manual_documentation'], '/' );
		if ( $doc_path && false !== strpos( $doc_path, '/' ) ) {
			$post = manual_docs_get_post_by_doc_path( $doc_path );
			if ( $post ) {
				$query_vars['post_type']            = 'manual_documentation';
				$query_vars['manual_documentation'] = $doc_path;
				$query_vars['name']                 = $doc_path;
				unset( $query_vars['error'] );
			}
		}
		return $query_vars;
	}

	$path = manual_docs_request_path();
	$base = function_exists( 'manual_docs_cpt_rewrite_slug' ) ? manual_docs_cpt_rewrite_slug() : 'documentation';
	$base = trim( $base, '/' );

	if ( '' === $path || ( $path !== $base && 0 !== strpos( $path, $base . '/' ) ) ) {
		return $query_vars;
	}

	if ( $path === $base ) {
		$query_vars['post_type'] = 'manual_documentation';
		unset( $query_vars['error'] );
		return $query_vars;
	}

	$doc_path = trim( substr( $path, strlen( $base ) + 1 ), '/' );
	$is_pdf   = false;
	if ( preg_match( '#(^|/)pdf/?$#', $doc_path ) ) {
		$is_pdf   = true;
		$doc_path = trim( (string) preg_replace( '#(^|/)pdf/?$#', '', $doc_path ), '/' );
	}

	if ( '' === $doc_path ) {
		$query_vars['post_type'] = 'manual_documentation';
		unset( $query_vars['error'] );
		return $query_vars;
	}

	$post = manual_docs_get_post_by_doc_path( $doc_path );
	if ( ! $post ) {
		return $query_vars;
	}

	$query_vars['post_type']            = 'manual_documentation';
	$query_vars['manual_documentation'] = $doc_path;
	$query_vars['name']                 = $doc_path;
	unset( $query_vars['error'] );
	if ( $is_pdf ) {
		$query_vars['manual_docs_pdf'] = '1';
	}

	return $query_vars;
}
add_filter( 'request', 'manual_docs_filter_request', 1 );

/**
 * Last-resort 404 rescue when rewrite matched nothing useful.
 *
 * @param bool     $preempt  Whether to short-circuit.
 * @param WP_Query $wp_query Query.
 * @return bool
 */
function manual_docs_pre_handle_404( $preempt, $wp_query ) {
	if ( $preempt || is_admin() || ! ( $wp_query instanceof WP_Query ) || ! $wp_query->is_main_query() ) {
		return $preempt;
	}

	$path = manual_docs_request_path();
	$base = function_exists( 'manual_docs_cpt_rewrite_slug' ) ? manual_docs_cpt_rewrite_slug() : 'documentation';
	$base = trim( $base, '/' );

	if ( '' === $path || ( $path !== $base && 0 !== strpos( $path, $base . '/' ) ) ) {
		// Query-mode rescue.
		$qv = get_query_var( 'manual_documentation' );
		if ( ! $qv ) {
			return $preempt;
		}
		$doc_path = trim( (string) $qv, '/' );
	} else {
		$doc_path = ( $path === $base ) ? '' : trim( substr( $path, strlen( $base ) + 1 ), '/' );
	}

	if ( '' === $doc_path ) {
		return $preempt;
	}

	$doc_path = trim( (string) preg_replace( '#(^|/)pdf/?$#', '', $doc_path ), '/' );
	$post     = manual_docs_get_post_by_doc_path( $doc_path );
	if ( ! $post ) {
		return $preempt;
	}

	$wp_query->posts                 = array( $post );
	$wp_query->post                  = $post;
	$wp_query->post_count            = 1;
	$wp_query->found_posts           = 1;
	$wp_query->max_num_pages         = 1;
	$wp_query->queried_object        = $post;
	$wp_query->queried_object_id     = (int) $post->ID;
	$wp_query->is_404                = false;
	$wp_query->is_single             = true;
	$wp_query->is_singular           = true;
	$wp_query->is_page               = false;
	$wp_query->is_archive            = false;
	$wp_query->is_home               = false;

	status_header( 200 );
	return true;
}
add_filter( 'pre_handle_404', 'manual_docs_pre_handle_404', 10, 2 );

/**
 * Build a documentation URL that works without Apache rewrite.
 *
 * @param string  $url  Default URL.
 * @param WP_Post $post Post.
 * @param bool    $leavename Leave name.
 * @param bool    $sample Sample.
 * @return string
 */
function manual_docs_filter_post_type_link( $url, $post, $leavename = false, $sample = false ) {
	unset( $leavename, $sample );

	if ( ! ( $post instanceof WP_Post ) || 'manual_documentation' !== $post->post_type ) {
		return $url;
	}

	$mode = manual_docs_permalink_mode();
	if ( 'pretty' === $mode ) {
		return $url;
	}

	$path = manual_docs_post_hierarchy_path( $post );
	$base = function_exists( 'manual_docs_cpt_rewrite_slug' ) ? manual_docs_cpt_rewrite_slug() : 'documentation';
	$base = trim( $base, '/' );

	if ( ! $path ) {
		return $url;
	}

	if ( 'query' === $mode ) {
		return home_url( '/?manual_documentation=' . rawurlencode( $path ) );
	}

	// index_php — works on Local/Apache when AllowOverride is off.
	return home_url( '/index.php/' . $base . '/' . $path . '/' );
}
add_filter( 'post_type_link', 'manual_docs_filter_post_type_link', 20, 4 );

/**
 * Recommended WordPress .htaccess block for this install.
 *
 * @return string
 */
function manual_docs_recommended_htaccess() {
	$home_path = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	$base      = $home_path ? '/' . $home_path . '/' : '/';

	return "# BEGIN WordPress\n" .
		"<IfModule mod_rewrite.c>\n" .
		"RewriteEngine On\n" .
		"RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]\n" .
		'RewriteBase ' . $base . "\n" .
		"RewriteRule ^index\\.php$ - [L]\n" .
		"RewriteCond %{REQUEST_FILENAME} !-f\n" .
		"RewriteCond %{REQUEST_FILENAME} !-d\n" .
		'RewriteRule . ' . $base . "index.php [L]\n" .
		"</IfModule>\n" .
		"# END WordPress\n";
}

/**
 * Write / update ABSPATH .htaccess WordPress rules.
 *
 * @return bool|WP_Error
 */
function manual_docs_write_htaccess() {
	$htaccess = ABSPATH . '.htaccess';
	$rules    = manual_docs_recommended_htaccess();

	if ( ! function_exists( 'insert_with_markers' ) ) {
		require_once ABSPATH . 'wp-admin/includes/misc.php';
	}

	if ( file_exists( $htaccess ) && ! is_writable( $htaccess ) ) {
		return new WP_Error( 'htaccess_not_writable', __( '.htaccess exists but is not writable.', 'manual-docs' ) );
	}
	if ( ! file_exists( $htaccess ) && ! is_writable( ABSPATH ) ) {
		return new WP_Error( 'abspath_not_writable', __( 'WordPress root is not writable; cannot create .htaccess.', 'manual-docs' ) );
	}

	$ok = insert_with_markers( $htaccess, 'WordPress', explode( "\n", trim( $rules ) ) );
	return $ok ? true : new WP_Error( 'htaccess_write_failed', __( 'Failed to write .htaccess.', 'manual-docs' ) );
}

/**
 * Hard flush rewrite rules and regenerate .htaccess when possible.
 *
 * @return array{flushed:bool,htaccess:bool|WP_Error}
 */
function manual_docs_hard_flush_rewrites() {
	if ( function_exists( 'manual_docs_register_cpt' ) ) {
		manual_docs_register_cpt();
	}
	if ( function_exists( 'manual_docs_register_doc_rewrites' ) ) {
		manual_docs_register_doc_rewrites();
	}

	flush_rewrite_rules( true );
	update_option( 'manual_docs_permalinks_flushed_2_3', 1 );
	update_option( 'manual_docs_permalinks_flushed_2_4', 1 );
	update_option( 'manual_docs_permalinks_flushed_2_5', 1 );

	$ht = manual_docs_write_htaccess();

	return array(
		'flushed'  => true,
		'htaccess' => $ht,
	);
}

/**
 * Example working URL for a top-level or nested doc (for admin UI).
 *
 * @return string
 */
function manual_docs_example_working_url() {
	$mode = manual_docs_permalink_mode();
	$base = function_exists( 'manual_docs_cpt_rewrite_slug' ) ? manual_docs_cpt_rewrite_slug() : 'documentation';
	$path = $base . '/goat-2/getting-started/first-login';

	if ( 'query' === $mode ) {
		return home_url( '/?manual_documentation=' . rawurlencode( 'goat-2/getting-started/first-login' ) );
	}
	if ( 'index_php' === $mode ) {
		return home_url( '/index.php/' . $path . '/' );
	}
	return home_url( '/' . $path . '/' );
}
