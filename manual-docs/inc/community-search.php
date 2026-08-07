<?php
/**
 * Live search for bbPress forums, topics, and replies only.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register community search REST route.
 */
function manual_docs_register_community_search_route() {
	register_rest_route(
		'manual-docs/v1',
		'/community-search',
		array(
			'methods'             => 'GET',
			'callback'            => 'manual_docs_rest_community_search',
			'permission_callback' => '__return_true',
			'args'                => array(
				'q' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'manual_docs_register_community_search_route' );

/**
 * Community search handler — forum / topic / reply only.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function manual_docs_rest_community_search( WP_REST_Request $request ) {
	if ( ! function_exists( 'manual_docs_bbpress_active' ) || ! manual_docs_bbpress_active() ) {
		return rest_ensure_response( array( 'results' => array() ) );
	}

	$q = trim( (string) $request->get_param( 'q' ) );
	if ( strlen( $q ) < 2 ) {
		return rest_ensure_response( array( 'results' => array() ) );
	}

	$types = array( 'topic', 'reply', 'forum' );
	$query = new WP_Query(
		array(
			'post_type'              => $types,
			'post_status'            => 'publish',
			's'                      => $q,
			'posts_per_page'         => 12,
			'orderby'                => 'relevance',
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	$results = array();
	foreach ( $query->posts as $post ) {
		$type_label = 'topic' === $post->post_type
			? __( 'Topic', 'manual-docs' )
			: ( 'reply' === $post->post_type ? __( 'Reply', 'manual-docs' ) : __( 'Forum', 'manual-docs' ) );

		$title = get_the_title( $post );
		if ( 'reply' === $post->post_type && function_exists( 'bbp_get_reply_topic_title' ) ) {
			$topic_title = bbp_get_reply_topic_title( $post->ID );
			if ( $topic_title ) {
				$title = sprintf(
					/* translators: %s: parent topic title */
					__( 'Reply in: %s', 'manual-docs' ),
					$topic_title
				);
			}
		}

		$url = get_permalink( $post );
		if ( 'reply' === $post->post_type && function_exists( 'bbp_get_reply_url' ) ) {
			$url = bbp_get_reply_url( $post->ID );
		}

		$excerpt = wp_trim_words( wp_strip_all_tags( $post->post_content ), 18 );

		$results[] = array(
			'id'      => (int) $post->ID,
			'title'   => $title,
			'url'     => $url,
			'type'    => $post->post_type,
			'label'   => $type_label,
			'excerpt' => $excerpt,
		);
	}

	return rest_ensure_response( array( 'results' => $results ) );
}

/**
 * Enqueue community live-search assets on bbPress pages.
 */
function manual_docs_community_search_assets() {
	if ( ! function_exists( 'manual_docs_bbpress_active' ) || ! manual_docs_bbpress_active() ) {
		return;
	}
	if ( ! function_exists( 'is_bbpress' ) || ! is_bbpress() ) {
		return;
	}

	wp_enqueue_script(
		'manual-docs-community-search',
		MANUAL_DOCS_URI . '/assets/js/community-search.js',
		array(),
		MANUAL_DOCS_VERSION,
		true
	);

	wp_localize_script(
		'manual-docs-community-search',
		'manualDocsCommunity',
		array(
			'restUrl' => esc_url_raw( rest_url( 'manual-docs/v1/community-search' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'i18n'    => array(
				'noResults' => __( 'No matching forums, topics, or replies.', 'manual-docs' ),
				'searching' => __( 'Searching…', 'manual-docs' ),
				'hint'      => __( 'Search forums, topics & replies', 'manual-docs' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'manual_docs_community_search_assets', 30 );
