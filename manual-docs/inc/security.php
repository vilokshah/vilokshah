<?php
/**
 * Security hardening for Manual Docs.
 *
 * Intentionally minimal — must never corrupt Gutenberg REST JSON.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

/**
 * Disable XML-RPC on the public site only.
 */
function manual_docs_disable_xmlrpc() {
	if ( is_admin() || ( function_exists( 'manual_docs_is_rest_like_request' ) && manual_docs_is_rest_like_request() ) ) {
		return;
	}
	if ( apply_filters( 'manual_docs_disable_xmlrpc', true ) ) {
		add_filter( 'xmlrpc_enabled', '__return_false' );
	}
}
add_action( 'init', 'manual_docs_disable_xmlrpc' );

/**
 * Front-end security headers only (never on REST/admin/login).
 */
function manual_docs_security_headers() {
	if ( headers_sent() || is_admin() ) {
		return;
	}
	if ( function_exists( 'is_login' ) && is_login() ) {
		return;
	}
	if ( function_exists( 'manual_docs_is_rest_like_request' ) && manual_docs_is_rest_like_request() ) {
		return;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}

	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
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
 * Escape and validate redirect URLs used by the theme.
 *
 * @param string $url URL.
 * @return string
 */
function manual_docs_safe_redirect_url( $url ) {
	$url = esc_url_raw( $url );
	return wp_validate_redirect( $url, home_url( '/' ) );
}