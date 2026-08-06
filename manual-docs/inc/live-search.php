<?php
/**
 * Live AJAX / REST search for documentation.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register REST route.
 */
function manual_docs_register_search_routes() {
	register_rest_route(
		'manual-docs/v1',
		'/search',
		array(
			'methods'             => 'GET',
			'callback'            => 'manual_docs_rest_search',
			'permission_callback' => 'manual_docs_search_permission',
			'args'                => array(
				'q' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'version' => array(
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_title',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'manual_docs_register_search_routes' );

/**
 * Search permission callback.
 *
 * @return bool|WP_Error
 */
function manual_docs_search_permission() {
	if ( manual_docs_require_login_for_docs() && ! is_user_logged_in() ) {
		return new WP_Error( 'rest_forbidden', __( 'Login required.', 'manual-docs' ), array( 'status' => 401 ) );
	}
	return true;
}

/**
 * REST search handler.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function manual_docs_rest_search( WP_REST_Request $request ) {
	$q = trim( (string) $request->get_param( 'q' ) );
	if ( strlen( $q ) < 2 ) {
		return rest_ensure_response( array( 'results' => array() ) );
	}

	$args = array(
		'post_type'      => 'manual_documentation',
		'post_status'    => 'publish',
		's'              => $q,
		'posts_per_page' => 24,
		'no_found_rows'  => true,
	);

	$tax        = manual_docs_category_taxonomy();
	$restricted = manual_docs_get_restricted_category_ids_for_user();
	if ( ! empty( $restricted ) && taxonomy_exists( $tax ) ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => $tax,
				'field'    => 'term_id',
				'terms'    => $restricted,
				'operator' => 'NOT IN',
			),
		);
	}

	$query       = new WP_Query( $args );
	$results     = array();
	$version_slug = $request->get_param( 'version' );

	foreach ( $query->posts as $post ) {
		if ( ! manual_docs_user_can_view_doc( $post ) ) {
			continue;
		}

		if ( $version_slug ) {
			$root = manual_docs_get_version_root_for_doc( $post->ID );
			if ( ! $root ) {
				continue;
			}
			$root_slug = $root->post_name;
			// Allow goat-2 style import suffixes.
			if ( $root_slug !== $version_slug && 0 !== strpos( $root_slug, $version_slug . '-' ) ) {
				continue;
			}
		}

		$cats = taxonomy_exists( $tax ) ? get_the_terms( $post->ID, $tax ) : false;
		$cat  = ( ! empty( $cats ) && ! is_wp_error( $cats ) ) ? $cats[0]->name : '';
		$root = function_exists( 'manual_docs_get_version_root_for_doc' ) ? manual_docs_get_version_root_for_doc( $post->ID ) : null;

		$results[] = array(
			'id'       => $post->ID,
			'title'    => get_the_title( $post ),
			'url'      => get_permalink( $post ),
			'excerpt'  => wp_trim_words( wp_strip_all_tags( $post->post_excerpt ? $post->post_excerpt : $post->post_content ), 18 ),
			'category' => $cat,
			'version'  => $root ? get_the_title( $root ) : '',
			'versionSlug' => $root ? $root->post_name : '',
		);

		if ( count( $results ) >= 12 ) {
			break;
		}
	}

	return rest_ensure_response( array( 'results' => $results ) );
}

/**
 * Classic AJAX fallback.
 */
function manual_docs_ajax_search() {
	if ( ! manual_docs_verify_nonce( 'manual_docs_search', 'nonce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid request.', 'manual-docs' ) ), 403 );
	}

	if ( manual_docs_require_login_for_docs() && ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Login required.', 'manual-docs' ) ), 401 );
	}

	$q = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$request = new WP_REST_Request( 'GET', '/manual-docs/v1/search' );
	$request->set_param( 'q', $q );
	if ( ! empty( $_GET['version'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$request->set_param( 'version', sanitize_title( wp_unslash( $_GET['version'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	$response = manual_docs_rest_search( $request );
	wp_send_json_success( $response->get_data() );
}
add_action( 'wp_ajax_manual_docs_search', 'manual_docs_ajax_search' );
add_action( 'wp_ajax_nopriv_manual_docs_search', 'manual_docs_ajax_search' );

/**
 * Render live search form markup.
 *
 * @param array $args Args.
 */
function manual_docs_render_live_search( $args = array() ) {
	$defaults = array(
		'placeholder' => __( 'Search documentation…', 'manual-docs' ),
		'class'       => '',
	);
	$args = wp_parse_args( $args, $defaults );
	?>
	<div class="md-live-search <?php echo esc_attr( $args['class'] ); ?>" role="search">
		<label class="screen-reader-text" for="md-live-search-input"><?php esc_html_e( 'Search documentation', 'manual-docs' ); ?></label>
		<div class="md-live-search__field">
			<svg class="md-live-search__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
			<input
				type="search"
				id="md-live-search-input"
				class="md-live-search__input"
				placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>"
				autocomplete="off"
				autocorrect="off"
				spellcheck="false"
			/>
			<span class="md-live-search__spinner" hidden aria-hidden="true"></span>
		</div>
		<div class="md-live-search__results" id="md-live-search-results" role="listbox" hidden></div>
	</div>
	<?php
}