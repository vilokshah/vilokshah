<?php
/**
 * Security hardening for Manual Docs.
 *
 * Careful not to break Gutenberg / REST JSON responses.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove WordPress version from head and feeds.
 */
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

/**
 * Disable XML-RPC when not needed.
 */
function manual_docs_disable_xmlrpc() {
	if ( apply_filters( 'manual_docs_disable_xmlrpc', true ) ) {
		add_filter( 'xmlrpc_enabled', '__return_false' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
	}
}
add_action( 'init', 'manual_docs_disable_xmlrpc' );

/**
 * Whether the current request is REST / AJAX / cron (must stay clean JSON).
 *
 * @return bool
 */
function manual_docs_is_api_request() {
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return true;
	}
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return true;
	}
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return true;
	}
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( false !== strpos( $request_uri, '/wp-json/' ) || false !== strpos( $request_uri, 'rest_route=' ) ) {
		return true;
	}
	return false;
}

/**
 * Security headers (front-end HTML only).
 */
function manual_docs_security_headers() {
	if ( headers_sent() || is_admin() || manual_docs_is_api_request() ) {
		return;
	}

	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: geolocation=(), microphone=(), camera=()' );

	if ( is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
	}
}
add_action( 'send_headers', 'manual_docs_security_headers' );

/**
 * Sanitize text helper.
 *
 * @param string $text Raw text.
 * @return string
 */
function manual_docs_sanitize_text( $text ) {
	return sanitize_text_field( wp_strip_all_tags( (string) $text ) );
}

/**
 * Verify AJAX nonce helper.
 *
 * @param string $action Nonce action.
 * @param string $query_arg Query arg name.
 * @return bool
 */
function manual_docs_verify_nonce( $action = 'manual_docs_search', $query_arg = 'nonce' ) {
	$nonce = isset( $_REQUEST[ $query_arg ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $query_arg ] ) ) : '';
	return (bool) wp_verify_nonce( $nonce, $action );
}

/**
 * Restrict only Manual Docs custom REST routes when login is required.
 * Never interfere with core /wp/v2/manual_documentation publish routes.
 *
 * @param mixed           $result  Response.
 * @param WP_REST_Server  $server  Server.
 * @param WP_REST_Request $request Request.
 * @return mixed
 */
function manual_docs_rest_auth_check( $result, $server, $request ) {
	$route = $request->get_route();

	if ( 0 !== strpos( $route, '/manual-docs/' ) ) {
		return $result;
	}

	if ( function_exists( 'manual_docs_require_login_for_docs' ) && manual_docs_require_login_for_docs() && ! is_user_logged_in() ) {
		return new WP_Error(
			'manual_docs_rest_forbidden',
			__( 'Authentication required to access documentation.', 'manual-docs' ),
			array( 'status' => 401 )
		);
	}

	return $result;
}
add_filter( 'rest_pre_dispatch', 'manual_docs_rest_auth_check', 10, 3 );

/**
 * Hide author enumeration via ?author=N redirects for guests.
 */
function manual_docs_block_author_enumeration() {
	if ( is_admin() || is_user_logged_in() || manual_docs_is_api_request() ) {
		return;
	}

	if ( isset( $_GET['author'] ) || ( isset( $_SERVER['REQUEST_URI'] ) && preg_match( '#/author/\d+#', (string) $_SERVER['REQUEST_URI'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'manual_docs_block_author_enumeration' );

/**
 * Escape and validate redirect URLs used by the theme.
 *
 * @param string $url URL.
 * @return string
 */
function manual_docs_safe_redirect_url( $url ) {
	$url = esc_url_raw( $url );
	return wp_validate_redirect( $url, home_url( '/' ) );
}