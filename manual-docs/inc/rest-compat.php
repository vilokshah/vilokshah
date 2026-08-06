<?php
/**
 * REST / Gutenberg compatibility.
 *
 * Ensures core post publish JSON stays clean and that
 * manualdocumentationcategory appears in the block editor.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * True for REST / AJAX / cron / CLI requests.
 *
 * @return bool
 */
function manual_docs_is_rest_like_request() {
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return true;
	}
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return true;
	}
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return true;
	}
	if ( wp_is_json_request() ) {
		return true;
	}

	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( false !== strpos( $uri, '/wp-json/' ) || false !== strpos( $uri, 'rest_route=' ) ) {
		return true;
	}

	return false;
}

/**
 * Ensure CPT + taxonomy are fully REST-enabled before routes register.
 */
function manual_docs_ensure_rest_objects() {
	global $wp_post_types, $wp_taxonomies;

	if ( isset( $wp_post_types['manual_documentation'] ) ) {
		$pt = $wp_post_types['manual_documentation'];
		$pt->show_in_rest           = true;
		$pt->hierarchical           = true;
		$pt->public                 = true;
		$pt->show_ui                = true;
		$pt->publicly_queryable     = true;
		if ( empty( $pt->rest_base ) ) {
			$pt->rest_base = 'manual_documentation';
		}
		if ( empty( $pt->rest_controller_class ) ) {
			$pt->rest_controller_class = 'WP_REST_Posts_Controller';
		}
		add_post_type_support( 'manual_documentation', array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'page-attributes', 'revisions', 'custom-fields' ) );
	}

	$tax = function_exists( 'manual_docs_category_taxonomy' ) ? manual_docs_category_taxonomy() : 'manualdocumentationcategory';

	if ( taxonomy_exists( $tax ) ) {
		register_taxonomy_for_object_type( $tax, 'manual_documentation' );
	}

	if ( isset( $wp_taxonomies[ $tax ] ) ) {
		$tx = $wp_taxonomies[ $tax ];
		$tx->show_in_rest      = true;
		$tx->hierarchical      = true;
		$tx->show_ui           = true;
		$tx->show_admin_column = true;
		$tx->public            = true;
		if ( empty( $tx->rest_base ) ) {
			$tx->rest_base = $tax;
		}
		if ( empty( $tx->rest_controller_class ) ) {
			$tx->rest_controller_class = 'WP_REST_Terms_Controller';
		}
		// Ensure object type association is present on the taxonomy object.
		if ( empty( $tx->object_type ) || ! in_array( 'manual_documentation', (array) $tx->object_type, true ) ) {
			$tx->object_type[] = 'manual_documentation';
		}
	}
}
add_action( 'init', 'manual_docs_ensure_rest_objects', 100 );
add_action( 'rest_api_init', 'manual_docs_ensure_rest_objects', 0 );

/**
 * Discard accidental output before REST JSON is printed.
 *
 * @param mixed $result Response data.
 * @return mixed
 */
function manual_docs_rest_clean_output( $result ) {
	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}
	return $result;
}
add_filter( 'rest_pre_echo_response', 'manual_docs_rest_clean_output', 0 );

/**
 * Never let theme access rules alter REST queries.
 *
 * @param WP_Query $query Query.
 */
function manual_docs_skip_access_on_rest( $query ) {
	if ( manual_docs_is_rest_like_request() ) {
		// Remove the theme access filter for this request cycle.
		remove_action( 'pre_get_posts', 'manual_docs_filter_query_access' );
	}
}
add_action( 'pre_get_posts', 'manual_docs_skip_access_on_rest', 0 );

/**
 * Explicitly expose taxonomy in post type REST link / schema.
 *
 * @param array $args CPT args.
 * @param string $post_type Post type.
 * @return array
 */
function manual_docs_cpt_rest_taxonomies_arg( $args, $post_type ) {
	if ( 'manual_documentation' !== $post_type ) {
		return $args;
	}
	$tax = function_exists( 'manual_docs_category_taxonomy' ) ? manual_docs_category_taxonomy() : 'manualdocumentationcategory';
	$taxonomies = isset( $args['taxonomies'] ) && is_array( $args['taxonomies'] ) ? $args['taxonomies'] : array();
	if ( ! in_array( $tax, $taxonomies, true ) ) {
		$taxonomies[] = $tax;
	}
	$args['taxonomies'] = $taxonomies;
	return $args;
}
add_filter( 'register_post_type_args', 'manual_docs_cpt_rest_taxonomies_arg', 30, 2 );

/**
 * Register a reliable categories meta box for the classic + block fallback area.
 */
function manual_docs_register_category_metabox() {
	$tax = function_exists( 'manual_docs_category_taxonomy' ) ? manual_docs_category_taxonomy() : 'manualdocumentationcategory';
	if ( ! taxonomy_exists( $tax ) ) {
		return;
	}

	// Core taxonomy metabox (appears under document settings area / metabox region).
	add_meta_box(
		'manual_docs_categories_box',
		__( 'Documentation Categories', 'manual-docs' ),
		'manual_docs_render_category_metabox',
		'manual_documentation',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'manual_docs_register_category_metabox' );

/**
 * Category checklist metabox.
 *
 * @param WP_Post $post Post.
 */
function manual_docs_render_category_metabox( $post ) {
	$tax = manual_docs_category_taxonomy();
	$taxonomy = get_taxonomy( $tax );
	if ( ! $taxonomy ) {
		echo '<p>' . esc_html__( 'Category taxonomy is not registered.', 'manual-docs' ) . '</p>';
		return;
	}

	echo '<div class="categorydiv">';
	$args = array(
		'taxonomy'      => $tax,
		'checked_ontop' => false,
	);
	echo '<div id="' . esc_attr( $tax ) . '-all" class="tabs-panel">';
	echo '<ul id="' . esc_attr( $tax ) . 'checklist" class="categorychecklist form-no-clear">';
	wp_terms_checklist( $post->ID, $args );
	echo '</ul></div>';
	echo '<p class="description">' . esc_html__( 'Select one or more documentation categories. Parent document can be set in Document → Parent.', 'manual-docs' ) . '</p>';
	echo '</div>';
}

/**
 * Parent document metabox (always visible even if page-attributes UI is hidden).
 */
function manual_docs_register_parent_metabox() {
	add_meta_box(
		'manual_docs_parent_box',
		__( 'Parent Document', 'manual-docs' ),
		'manual_docs_render_parent_metabox',
		'manual_documentation',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'manual_docs_register_parent_metabox' );

/**
 * Parent selector metabox.
 *
 * @param WP_Post $post Post.
 */
function manual_docs_render_parent_metabox( $post ) {
	wp_nonce_field( 'manual_docs_save_parent', 'manual_docs_parent_nonce' );
	$dropdown_args = array(
		'post_type'         => 'manual_documentation',
		'selected'          => $post->post_parent,
		'name'              => 'parent_id',
		'show_option_none'  => __( '(no parent)', 'manual-docs' ),
		'sort_column'       => 'menu_order, post_title',
		'echo'              => 0,
		'exclude_tree'      => $post->ID,
	);
	$pages = wp_dropdown_pages( $dropdown_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WP helper returns safe HTML.
	if ( empty( $pages ) ) {
		echo '<p>' . esc_html__( 'No other documents available as parent yet.', 'manual-docs' ) . '</p>';
		return;
	}
	echo $pages; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<p class="description">' . esc_html__( 'Use goat / flamingo / hummingbird (or a section under them) as parent for versioned trees.', 'manual-docs' ) . '</p>';
}

/**
 * Save parent from metabox when block editor also sends parent_id.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post.
 */
function manual_docs_save_parent_metabox( $post_id, $post ) {
	if ( 'manual_documentation' !== $post->post_type ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['manual_docs_parent_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['manual_docs_parent_nonce'] ) ), 'manual_docs_save_parent' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['parent_id'] ) ) {
		return;
	}
	$parent = absint( $_POST['parent_id'] );
	if ( $parent === (int) $post_id ) {
		return;
	}
	// Avoid recursion: wp_update_post would retrigger save_post.
	remove_action( 'save_post_manual_documentation', 'manual_docs_save_parent_metabox', 20 );
	wp_update_post(
		array(
			'ID'          => $post_id,
			'post_parent' => $parent,
		)
	);
	add_action( 'save_post_manual_documentation', 'manual_docs_save_parent_metabox', 20, 2 );
}
add_action( 'save_post_manual_documentation', 'manual_docs_save_parent_metabox', 20, 2 );