<?php
/**
 * WordPress Importer compatibility for versioned documentation trees.
 *
 * Goat / flamingo / hummingbird intentionally share titles. The stock importer
 * skips those as duplicates when title + date match. Allow import when GUID differs.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Do not treat same-titled docs under different version trees as duplicates.
 *
 * @param int   $post_exists Existing post ID (0 if none).
 * @param array $post        Incoming import post data.
 * @return int
 */
function manual_docs_import_existing_post( $post_exists, $post ) {
	if ( empty( $post['post_type'] ) || 'manual_documentation' !== $post['post_type'] ) {
		return $post_exists;
	}

	if ( ! $post_exists ) {
		return 0;
	}

	$incoming_guid = isset( $post['guid'] ) ? (string) $post['guid'] : '';
	$existing      = get_post( (int) $post_exists );

	// Same titles across goat/flamingo/hummingbird are intentional.
	// Only treat as duplicate when GUID matches an existing row.
	if ( $existing && $incoming_guid && $existing->guid !== $incoming_guid ) {
		return 0;
	}

	return $post_exists;
}
add_filter( 'wp_import_existing_post', 'manual_docs_import_existing_post', 10, 2 );

/**
 * Admin notice pointing at split imports when only one version root exists.
 */
function manual_docs_import_hint_notice() {
	if ( ! current_user_can( 'import' ) || ! is_admin() ) {
		return;
	}

	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( 'manual-docs-settings' !== $page ) {
		return;
	}

	if ( ! function_exists( 'manual_docs_get_version_roots' ) ) {
		return;
	}

	$roots = manual_docs_get_version_roots();
	if ( count( $roots ) >= 2 ) {
		return;
	}

	echo '<div class="notice notice-warning"><p>';
	echo esc_html__( 'Manual Docs: Only one release root was found. After updating this theme, re-import flamingo and hummingbird XML files — same titles are allowed across versions.', 'manual-docs' );
	echo '</p></div>';
}
add_action( 'admin_notices', 'manual_docs_import_hint_notice' );
