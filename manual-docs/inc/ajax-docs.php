<?php
/**
 * AJAX / REST document loading for SPA-style docs browsing.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register document REST routes.
 */
function manual_docs_register_doc_routes() {
	register_rest_route(
		'manual-docs/v1',
		'/doc/(?P<id>\d+)',
		array(
			'methods'             => 'GET',
			'callback'            => 'manual_docs_rest_get_doc',
			'permission_callback' => 'manual_docs_search_permission',
			'args'                => array(
				'id'           => array(
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
				'include_tree' => array(
					'required'          => false,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'default'           => 1,
				),
			),
		)
	);

	register_rest_route(
		'manual-docs/v1',
		'/doc-by-slug/(?P<slug>[a-z0-9\-]+)',
		array(
			'methods'             => 'GET',
			'callback'            => 'manual_docs_rest_get_doc_by_slug',
			'permission_callback' => 'manual_docs_search_permission',
			'args'                => array(
				'slug' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_title',
				),
			),
		)
	);

	register_rest_route(
		'manual-docs/v1',
		'/nav-children',
		array(
			'methods'             => 'GET',
			'callback'            => 'manual_docs_rest_nav_children',
			'permission_callback' => 'manual_docs_search_permission',
			'args'                => array(
				'parent'  => array(
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
				'current' => array(
					'required'          => false,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'default'           => 0,
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'manual_docs_register_doc_routes' );

/**
 * REST: lazy tree children HTML for a parent node.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function manual_docs_rest_nav_children( WP_REST_Request $request ) {
	$parent_id = (int) $request->get_param( 'parent' );
	$current   = (int) $request->get_param( 'current' );
	$parent    = get_post( $parent_id );

	if ( ! $parent || 'manual_documentation' !== $parent->post_type || 'publish' !== $parent->post_status ) {
		return new WP_Error( 'manual_docs_not_found', __( 'Parent document not found.', 'manual-docs' ), array( 'status' => 404 ) );
	}

	if ( ! manual_docs_user_can_view_doc( $parent ) ) {
		return new WP_Error(
			'manual_docs_forbidden',
			__( 'You do not have permission to view this document.', 'manual-docs' ),
			array( 'status' => is_user_logged_in() ? 403 : 401 )
		);
	}

	ob_start();
	manual_docs_render_nav_children_html( $parent_id, $current );
	$html = (string) ob_get_clean();

	return rest_ensure_response(
		array(
			'parent' => $parent_id,
			'html'   => $html,
		)
	);
}

/**
 * Resolve document by slug.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function manual_docs_rest_get_doc_by_slug( WP_REST_Request $request ) {
	$posts = get_posts( array(
		'name'           => $request->get_param( 'slug' ),
		'post_type'      => 'manual_documentation',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
	) );

	if ( empty( $posts ) ) {
		return new WP_Error( 'manual_docs_not_found', __( 'Document not found.', 'manual-docs' ), array( 'status' => 404 ) );
	}

	$request->set_param( 'id', $posts[0]->ID );
	return manual_docs_rest_get_doc( $request );
}

/**
 * REST: full document payload for AJAX rendering.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function manual_docs_rest_get_doc( WP_REST_Request $request ) {
	$post_id = (int) $request->get_param( 'id' );
	$post    = get_post( $post_id );

	if ( ! $post || 'manual_documentation' !== $post->post_type || 'publish' !== $post->post_status ) {
		return new WP_Error( 'manual_docs_not_found', __( 'Document not found.', 'manual-docs' ), array( 'status' => 404 ) );
	}

	if ( ! manual_docs_user_can_view_doc( $post ) ) {
		return new WP_Error(
			'manual_docs_forbidden',
			__( 'You do not have permission to view this document.', 'manual-docs' ),
			array( 'status' => is_user_logged_in() ? 403 : 401 )
		);
	}

	$include_tree = true;
	if ( null !== $request->get_param( 'include_tree' ) ) {
		$include_tree = (bool) absint( $request->get_param( 'include_tree' ) );
	}

	return rest_ensure_response( manual_docs_get_doc_payload( $post, array( 'include_tree' => $include_tree ) ) );
}

/**
 * Build document payload for AJAX client.
 *
 * @param WP_Post $post Post.
 * @return array
 */
/**
 * Build JSON payload for AJAX document rendering.
 *
 * @param WP_Post $post Post.
 * @param array   $args {
 *     Optional args.
 *     @type bool $include_tree Whether to rebuild sidebar tree HTML (expensive on large libraries).
 * }
 * @return array
 */
function manual_docs_get_doc_payload( WP_Post $post, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'include_tree' => true,
		)
	);

	$post_id = (int) $post->ID;

	if ( function_exists( 'manual_docs_track_doc_view' ) ) {
		manual_docs_track_doc_view( $post_id );
	}

	// Ensure global $post is set for template tags / filters.
	$GLOBALS['post'] = $post;
	setup_postdata( $post );

	$content = apply_filters( 'the_content', $post->post_content );
	$toc     = manual_docs_extract_toc_from_html( $content );
	$content = manual_docs_inject_heading_ids( $content, $toc );

	$version  = manual_docs_get_doc_version( $post_id );
	$adjacent = manual_docs_adjacent_docs( $post_id );
	$root     = manual_docs_get_version_root_for_doc( $post_id );

	$tree_html = '';
	if ( ! empty( $args['include_tree'] ) ) {
		ob_start();
		manual_docs_render_doc_nav( manual_docs_get_version_scoped_tree( $post_id ), $post_id );
		$tree_html = ob_get_clean();
	}

	// Always include direct children HTML (cheap). Lets the AJAX client expand the
	// active parent without a full tree rebuild or a second nav-children race.
	$has_children  = manual_docs_doc_has_children( $post_id );
	$children_html = '';
	if ( $has_children ) {
		ob_start();
		manual_docs_render_nav_children_html( $post_id, $post_id );
		$children_html = (string) ob_get_clean();
	}

	$product = function_exists( 'manual_docs_get_doc_product_payload' )
		? manual_docs_get_doc_product_payload( $post_id )
		: null;

	$payload = array(
		'id'            => $post_id,
		'title'         => get_the_title( $post ),
		'url'           => get_permalink( $post ),
		'content'       => $content,
		'excerpt'       => wp_trim_words( wp_strip_all_tags( $post->post_excerpt ? $post->post_excerpt : $post->post_content ), 24 ),
		'modified'      => get_the_modified_date( '', $post ),
		'modifiedHuman' => sprintf(
			/* translators: %s: date */
			__( 'Updated on %s', 'manual-docs' ),
			get_the_modified_date( '', $post )
		),
		'version'       => $version,
		'versionBadge'  => $version ? $version['name'] : '',
		'versionRootId' => $root ? (int) $root->ID : 0,
		'productTermId' => $product ? (int) $product['id'] : 0,
		'productSlug'   => $product ? $product['slug'] : '',
		'productName'   => $product ? $product['name'] : '',
		'prevId'        => ! empty( $adjacent['prev'] ) ? (int) $adjacent['prev']->ID : 0,
		'nextId'        => ! empty( $adjacent['next'] ) ? (int) $adjacent['next']->ID : 0,
		'pdfUrl'        => manual_docs_get_pdf_url( $post_id, true ),
		'toc'           => $toc,
		'treeHtml'      => $tree_html,
		'includeTree'   => ! empty( $args['include_tree'] ),
		'hasChildren'   => (bool) $has_children,
		'childrenHtml'  => $children_html,
		'breadcrumbs'   => manual_docs_get_buffered_markup( 'manual_docs_breadcrumbs', array( $post_id ) ),
		'versionHtml'   => manual_docs_get_buffered_markup( 'manual_docs_render_version_switcher', array( $post_id ) ),
		'pagerHtml'     => manual_docs_get_pager_html( $adjacent ),
		'communityHtml' => '',
		'parent'        => (int) $post->post_parent,
		'menuOrder'     => (int) $post->menu_order,
		'editUrl'       => current_user_can( 'edit_post', $post_id ) ? get_edit_post_link( $post_id, 'raw' ) : '',
	);

	wp_reset_postdata();

	return $payload;
}

/**
 * Capture output of a render function.
 *
 * @param callable $callback Callback.
 * @param array    $args     Args.
 * @return string
 */
function manual_docs_get_buffered_markup( $callback, $args = array() ) {
	if ( ! is_callable( $callback ) ) {
		return '';
	}
	ob_start();
	call_user_func_array( $callback, $args );
	return (string) ob_get_clean();
}

/**
 * Build pager HTML for AJAX payload.
 *
 * @param array $adjacent Adjacent docs.
 * @return string
 */
function manual_docs_get_pager_html( $adjacent ) {
	ob_start();
	?>
	<?php if ( ! empty( $adjacent['prev'] ) ) : ?>
		<a class="md-doc-pager__link md-doc-pager__link--prev" href="<?php echo esc_url( get_permalink( $adjacent['prev'] ) ); ?>" data-md-ajax-doc data-md-doc-id="<?php echo esc_attr( (string) $adjacent['prev']->ID ); ?>">
			<span><?php esc_html_e( 'Previous', 'manual-docs' ); ?></span>
			<strong><?php echo esc_html( get_the_title( $adjacent['prev'] ) ); ?></strong>
		</a>
	<?php else : ?>
		<span></span>
	<?php endif; ?>
	<?php if ( ! empty( $adjacent['next'] ) ) : ?>
		<a class="md-doc-pager__link md-doc-pager__link--next" href="<?php echo esc_url( get_permalink( $adjacent['next'] ) ); ?>" data-md-ajax-doc data-md-doc-id="<?php echo esc_attr( (string) $adjacent['next']->ID ); ?>">
			<span><?php esc_html_e( 'Next', 'manual-docs' ); ?></span>
			<strong><?php echo esc_html( get_the_title( $adjacent['next'] ) ); ?></strong>
		</a>
	<?php endif; ?>
	<?php
	return (string) ob_get_clean();
}

/**
 * Extract TOC entries and assign stable IDs from HTML content.
 *
 * @param string $html Content HTML.
 * @return array<int,array{id:string,text:string,level:int}>
 */
function manual_docs_extract_toc_from_html( $html ) {
	$toc = array();
	if ( ! $html ) {
		return $toc;
	}

	if ( ! class_exists( 'DOMDocument' ) ) {
		return $toc;
	}

	$previous = libxml_use_internal_errors( true );
	$dom      = new DOMDocument();
	$wrapped  = '<?xml encoding="utf-8" ?><div id="md-root">' . $html . '</div>';
	$dom->loadHTML( $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	$xpath = new DOMXPath( $dom );
	$nodes = $xpath->query( '//*[self::h2 or self::h3]' );
	if ( ! $nodes ) {
		return $toc;
	}

	$used = array();
	$i    = 0;
	foreach ( $nodes as $node ) {
		$text = trim( preg_replace( '/\s+/', ' ', $node->textContent ) );
		if ( '' === $text ) {
			continue;
		}
		$id = $node->getAttribute( 'id' );
		if ( ! $id ) {
			$base = sanitize_title( $text );
			$id   = $base ? $base : ( 'section-' . $i );
			$n    = 2;
			while ( isset( $used[ $id ] ) ) {
				$id = ( $base ? $base : 'section' ) . '-' . $n;
				$n++;
			}
		}
		$used[ $id ] = true;
		$toc[]       = array(
			'id'    => $id,
			'text'  => $text,
			'level' => ( 'h3' === strtolower( $node->nodeName ) ) ? 3 : 2,
		);
		$i++;
	}

	return $toc;
}

/**
 * Inject heading IDs into content HTML to match TOC.
 *
 * @param string $html Content.
 * @param array  $toc  TOC entries.
 * @return string
 */
function manual_docs_inject_heading_ids( $html, $toc ) {
	if ( ! $html || empty( $toc ) || ! class_exists( 'DOMDocument' ) ) {
		return $html;
	}

	$previous = libxml_use_internal_errors( true );
	$dom      = new DOMDocument();
	$wrapped  = '<?xml encoding="utf-8" ?><div id="md-root">' . $html . '</div>';
	$dom->loadHTML( $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	$root = $dom->getElementById( 'md-root' );
	if ( ! $root ) {
		return $html;
	}

	$xpath = new DOMXPath( $dom );
	$nodes = $xpath->query( './/*[self::h2 or self::h3]', $root );
	if ( ! $nodes ) {
		return $html;
	}

	$index = 0;
	foreach ( $nodes as $node ) {
		if ( ! isset( $toc[ $index ] ) ) {
			break;
		}
		$node->setAttribute( 'id', $toc[ $index ]['id'] );
		$index++;
	}

	$out = '';
	foreach ( $root->childNodes as $child ) {
		$out .= $dom->saveHTML( $child );
	}

	return $out;
}

/**
 * Classic admin-ajax fallback for document fetch.
 */
function manual_docs_ajax_get_doc() {
	if ( ! manual_docs_verify_nonce( 'manual_docs_search', 'nonce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid request.', 'manual-docs' ) ), 403 );
	}

	$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! $id ) {
		wp_send_json_error( array( 'message' => __( 'Missing document id.', 'manual-docs' ) ), 400 );
	}

	$include_tree = true;
	if ( isset( $_GET['include_tree'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$include_tree = (bool) absint( $_GET['include_tree'] ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	$request = new WP_REST_Request( 'GET', '/manual-docs/v1/doc/' . $id );
	$request->set_param( 'id', $id );
	$request->set_param( 'include_tree', $include_tree ? 1 : 0 );
	$response = manual_docs_rest_get_doc( $request );

	if ( is_wp_error( $response ) ) {
		$data   = $response->get_error_data();
		$status = ( is_array( $data ) && isset( $data['status'] ) ) ? (int) $data['status'] : 400;
		wp_send_json_error( array( 'message' => $response->get_error_message() ), $status );
	}

	wp_send_json_success( $response->get_data() );
}
add_action( 'wp_ajax_manual_docs_get_doc', 'manual_docs_ajax_get_doc' );
add_action( 'wp_ajax_nopriv_manual_docs_get_doc', 'manual_docs_ajax_get_doc' );

/**
 * Classic admin-ajax fallback for lazy nav children.
 */
function manual_docs_ajax_nav_children() {
	if ( ! manual_docs_verify_nonce( 'manual_docs_search', 'nonce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid request.', 'manual-docs' ) ), 403 );
	}

	$parent_id = isset( $_GET['parent'] ) ? absint( $_GET['parent'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	$current   = isset( $_GET['current'] ) ? absint( $_GET['current'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

	$request = new WP_REST_Request( 'GET', '/manual-docs/v1/nav-children' );
	$request->set_param( 'parent', $parent_id );
	$request->set_param( 'current', $current );
	$response = manual_docs_rest_nav_children( $request );

	if ( is_wp_error( $response ) ) {
		$data   = $response->get_error_data();
		$status = ( is_array( $data ) && isset( $data['status'] ) ) ? (int) $data['status'] : 400;
		wp_send_json_error( array( 'message' => $response->get_error_message() ), $status );
	}

	wp_send_json_success( $response->get_data() );
}
add_action( 'wp_ajax_manual_docs_nav_children', 'manual_docs_ajax_nav_children' );
add_action( 'wp_ajax_nopriv_manual_docs_nav_children', 'manual_docs_ajax_nav_children' );