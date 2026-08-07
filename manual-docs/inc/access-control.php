<?php
/**
 * Role-based access and login redirects for documentation.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether guests must log in to view docs.
 *
 * @return bool
 */
function manual_docs_require_login_for_docs() {
	$option = (bool) manual_docs_get_option( 'require_login', true );
	return (bool) apply_filters( 'manual_docs_require_login_for_docs', $option );
}

/**
 * Front-end login URL (defaults to /login for magic-link / custom login pages).
 *
 * @param string $redirect_to Optional redirect after login.
 * @return string
 */
function manual_docs_get_login_url( $redirect_to = '' ) {
	$path = (string) manual_docs_get_option( 'login_page_path', '/login/' );
	$path = trim( $path );
	if ( '' === $path ) {
		$path = '/login/';
	}
	if ( '#' !== $path[0] && false === strpos( $path, '://' ) ) {
		$path = '/' . ltrim( $path, '/' );
		$url  = home_url( $path );
	} else {
		$url = $path;
	}

	/**
	 * Filter the docs login URL.
	 *
	 * @param string $url         Login URL.
	 * @param string $redirect_to Redirect target.
	 */
	$url = apply_filters( 'manual_docs_login_url', $url, $redirect_to );

	if ( $redirect_to ) {
		$url = add_query_arg( 'redirect_to', manual_docs_safe_redirect_url( $redirect_to ), $url );
	}

	return $url;
}

/**
 * Check if current user may view a document.
 *
 * @param int|WP_Post|null $post Post.
 * @return bool
 */
function manual_docs_user_can_view_doc( $post = null ) {
	$post = get_post( $post );
	if ( ! $post || 'manual_documentation' !== $post->post_type ) {
		return true;
	}

	if ( current_user_can( 'edit_post', $post->ID ) || current_user_can( 'manage_options' ) ) {
		return true;
	}

	if ( manual_docs_require_login_for_docs() && ! is_user_logged_in() ) {
		return false;
	}

	$tax   = manual_docs_category_taxonomy();
	$terms = taxonomy_exists( $tax ) ? get_the_terms( $post->ID, $tax ) : false;
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return is_user_logged_in() || ! manual_docs_require_login_for_docs();
	}

	foreach ( $terms as $term ) {
		if ( ! manual_docs_user_can_view_category( $term->term_id ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Check category role access.
 *
 * @param int $term_id Term ID.
 * @return bool
 */
function manual_docs_user_can_view_category( $term_id ) {
	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}

	if ( manual_docs_require_login_for_docs() && ! is_user_logged_in() ) {
		return false;
	}

	$roles_raw = get_term_meta( $term_id, 'manual_docs_allowed_roles', true );
	if ( empty( $roles_raw ) ) {
		return is_user_logged_in() || ! manual_docs_require_login_for_docs();
	}

	if ( ! is_user_logged_in() ) {
		return false;
	}

	$user  = wp_get_current_user();
	$roles = array_filter( array_map( 'sanitize_key', array_map( 'trim', explode( ',', $roles_raw ) ) ) );

	foreach ( $roles as $role ) {
		if ( in_array( $role, (array) $user->roles, true ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Redirect guests / unauthorized users away from docs.
 */
function manual_docs_enforce_access() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	if ( function_exists( 'manual_docs_is_rest_like_request' ) && manual_docs_is_rest_like_request() ) {
		return;
	}

	$tax     = manual_docs_category_taxonomy();
	$is_docs = is_singular( 'manual_documentation' )
		|| is_post_type_archive( 'manual_documentation' )
		|| is_tax( $tax );

	if ( ! $is_docs ) {
		return;
	}

	// Hide the CPT archive (/docs/) — send people to the default release instead.
	if ( is_post_type_archive( 'manual_documentation' ) && manual_docs_get_option( 'hide_docs_archive', true ) ) {
		$target = function_exists( 'manual_docs_get_docs_entry_url' ) ? manual_docs_get_docs_entry_url() : home_url( '/' );
		wp_safe_redirect( $target, 301 );
		exit;
	}

	if ( manual_docs_require_login_for_docs() && ! is_user_logged_in() ) {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$redirect    = manual_docs_safe_redirect_url( home_url( $request_uri ) );
		wp_safe_redirect( manual_docs_get_login_url( $redirect ) );
		exit;
	}

	if ( is_singular( 'manual_documentation' ) && ! manual_docs_user_can_view_doc( get_queried_object_id() ) ) {
		wp_die(
			esc_html__( 'You do not have permission to view this document.', 'manual-docs' ),
			esc_html__( 'Access Denied', 'manual-docs' ),
			array( 'response' => 403 )
		);
	}

	if ( is_tax( $tax ) ) {
		$term = get_queried_object();
		if ( $term && ! manual_docs_user_can_view_category( $term->term_id ) ) {
			wp_die(
				esc_html__( 'You do not have permission to view this category.', 'manual-docs' ),
				esc_html__( 'Access Denied', 'manual-docs' ),
				array( 'response' => 403 )
			);
		}
	}
}
add_action( 'template_redirect', 'manual_docs_enforce_access', 5 );

/**
 * Filter documentation queries so unauthorized posts are excluded.
 *
 * @param WP_Query $query Query.
 */
function manual_docs_filter_query_access( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( function_exists( 'manual_docs_is_rest_like_request' ) && manual_docs_is_rest_like_request() ) {
		return;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}

	$tax     = manual_docs_category_taxonomy();
	$is_docs = ( 'manual_documentation' === $query->get( 'post_type' ) )
		|| $query->is_post_type_archive( 'manual_documentation' )
		|| $query->is_tax( $tax );

	if ( ! $is_docs || current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! is_user_logged_in() && manual_docs_require_login_for_docs() ) {
		$query->set( 'post__in', array( 0 ) );
		return;
	}

	$restricted = manual_docs_get_restricted_category_ids_for_user();
	if ( empty( $restricted ) || ! taxonomy_exists( $tax ) ) {
		return;
	}

	$tax_query   = (array) $query->get( 'tax_query' );
	$tax_query[] = array(
		'taxonomy' => $tax,
		'field'    => 'term_id',
		'terms'    => $restricted,
		'operator' => 'NOT IN',
	);
	$query->set( 'tax_query', $tax_query );
}
add_action( 'pre_get_posts', 'manual_docs_filter_query_access' );

/**
 * Category IDs the current user cannot access.
 *
 * @return int[]
 */
function manual_docs_get_restricted_category_ids_for_user() {
	$tax = manual_docs_category_taxonomy();
	if ( ! taxonomy_exists( $tax ) ) {
		return array();
	}

	$terms = get_terms( array(
		'taxonomy'   => $tax,
		'hide_empty' => false,
		'fields'     => 'ids',
	) );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}

	$restricted = array();
	foreach ( $terms as $term_id ) {
		if ( ! manual_docs_user_can_view_category( (int) $term_id ) ) {
			$restricted[] = (int) $term_id;
		}
	}

	return $restricted;
}