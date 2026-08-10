<?php
/**
 * Product scope: documentation categories as products within a version.
 *
 * Model:
 * - Version roots (Goat / Flamingo / Hummingbird) hold all products.
 * - Product = manualdocumentationcategory term (AIOps, Platform, …).
 * - Sidebar tree = product branch under the active version (not the whole version).
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether product-scoped trees are enabled.
 *
 * @return bool
 */
function manual_docs_product_tree_enabled() {
	return (bool) manual_docs_get_option( 'tree_product_scope', true );
}

/**
 * Category slugs that are not products (e.g. release meta on version roots).
 *
 * @return string[]
 */
function manual_docs_non_product_category_slugs() {
	$raw = (string) manual_docs_get_option( 'product_exclude_slugs', 'releases' );
	$slugs = array_filter( array_map( 'sanitize_title', array_map( 'trim', explode( ',', $raw ) ) ) );
	/**
	 * Filter non-product category slugs.
	 *
	 * @param string[] $slugs Slugs to ignore when resolving a product.
	 */
	return array_values( apply_filters( 'manual_docs_non_product_category_slugs', $slugs ) );
}

/**
 * Whether a term is a product category.
 *
 * @param WP_Term|null $term Term.
 * @return bool
 */
function manual_docs_is_product_category( $term ) {
	if ( ! $term || is_wp_error( $term ) || empty( $term->slug ) ) {
		return false;
	}
	return ! in_array( $term->slug, manual_docs_non_product_category_slugs(), true );
}

/**
 * Product categories assigned to a document (excludes non-product slugs).
 *
 * @param int $post_id Post ID.
 * @return WP_Term[]
 */
function manual_docs_get_doc_product_terms( $post_id ) {
	$post_id = (int) $post_id;
	$tax     = function_exists( 'manual_docs_category_taxonomy' ) ? manual_docs_category_taxonomy() : '';
	if ( ! $post_id || ! $tax || ! taxonomy_exists( $tax ) ) {
		return array();
	}

	$terms = get_the_terms( $post_id, $tax );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return array();
	}

	$out = array();
	foreach ( $terms as $term ) {
		if ( manual_docs_is_product_category( $term ) ) {
			if ( function_exists( 'manual_docs_user_can_view_category' ) && ! manual_docs_user_can_view_category( $term->term_id ) ) {
				continue;
			}
			$out[] = $term;
		}
	}

	return $out;
}

/**
 * Primary product term for a document.
 *
 * @param int $post_id Post ID.
 * @return WP_Term|null
 */
function manual_docs_get_primary_product_term( $post_id ) {
	$terms = manual_docs_get_doc_product_terms( $post_id );
	return ! empty( $terms ) ? $terms[0] : null;
}

/**
 * Resolve product term from request context (singular doc, taxonomy archive, query arg).
 *
 * @param int|null $post_id Optional doc ID.
 * @return WP_Term|null
 */
function manual_docs_resolve_product_term( $post_id = null ) {
	$tax = function_exists( 'manual_docs_category_taxonomy' ) ? manual_docs_category_taxonomy() : '';
	if ( ! $tax || ! taxonomy_exists( $tax ) ) {
		return null;
	}

	// Explicit override: ?md_product=aiops
	if ( ! empty( $_GET['md_product'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$slug = sanitize_title( wp_unslash( $_GET['md_product'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $slug ) {
			$term = get_term_by( 'slug', $slug, $tax );
			if ( $term && ! is_wp_error( $term ) && manual_docs_is_product_category( $term ) ) {
				return $term;
			}
		}
	}

	if ( $post_id ) {
		$term = manual_docs_get_primary_product_term( (int) $post_id );
		if ( $term ) {
			return $term;
		}
	}

	if ( is_tax( $tax ) ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term && manual_docs_is_product_category( $term ) ) {
			return $term;
		}
	}

	return null;
}

/**
 * Product branch root: direct child of the version root on the active path
 * (e.g. Goat → AIOps). Falls back to first version-child that has the product term.
 *
 * @param int         $post_id Doc ID.
 * @param WP_Term|null $product Optional product term.
 * @return WP_Post|null
 */
function manual_docs_get_product_branch_root( $post_id, $product = null ) {
	$post_id = (int) $post_id;
	if ( ! $post_id || ! manual_docs_product_tree_enabled() ) {
		return null;
	}

	$version_root = function_exists( 'manual_docs_get_version_root_for_doc' )
		? manual_docs_get_version_root_for_doc( $post_id )
		: null;
	if ( ! $version_root ) {
		return null;
	}

	// Already on the version root — no product branch.
	if ( (int) $version_root->ID === $post_id ) {
		return null;
	}

	if ( ! $product ) {
		$product = manual_docs_get_primary_product_term( $post_id );
	}
	if ( ! $product ) {
		return null;
	}

	// Prefer the ancestor that sits directly under the version root.
	$chain = array_merge( array( $post_id ), array_map( 'intval', get_post_ancestors( $post_id ) ) );
	foreach ( $chain as $id ) {
		$node = get_post( $id );
		if ( ! $node || 'manual_documentation' !== $node->post_type ) {
			continue;
		}
		if ( (int) $node->post_parent !== (int) $version_root->ID ) {
			continue;
		}
		// Match product term when possible; hierarchy under version still wins.
		$node_terms = manual_docs_get_doc_product_terms( $id );
		$slugs      = wp_list_pluck( $node_terms, 'slug' );
		if ( empty( $slugs ) || in_array( $product->slug, $slugs, true ) ) {
			return $node;
		}
	}

	// Fallback: first direct child of version root tagged with this product.
	return manual_docs_find_product_child_under_version( (int) $version_root->ID, (int) $product->term_id );
}

/**
 * Find a direct child of a version root that has the given product term.
 *
 * @param int $version_root_id Version root ID.
 * @param int $term_id         Product term ID.
 * @return WP_Post|null
 */
function manual_docs_find_product_child_under_version( $version_root_id, $term_id ) {
	$version_root_id = (int) $version_root_id;
	$term_id         = (int) $term_id;
	$tax             = function_exists( 'manual_docs_category_taxonomy' ) ? manual_docs_category_taxonomy() : '';
	if ( ! $version_root_id || ! $term_id || ! $tax || ! taxonomy_exists( $tax ) ) {
		return null;
	}

	$posts = get_posts(
		array(
			'post_type'              => 'manual_documentation',
			'post_parent'            => $version_root_id,
			'posts_per_page'         => 1,
			'orderby'                => 'menu_order title',
			'order'                  => 'ASC',
			'post_status'            => 'publish',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'tax_query'              => array(
				array(
					'taxonomy' => $tax,
					'field'    => 'term_id',
					'terms'    => array( $term_id ),
				),
			),
		)
	);

	if ( empty( $posts ) ) {
		return null;
	}

	$post = $posts[0];
	if ( function_exists( 'manual_docs_user_can_view_doc' ) && ! manual_docs_user_can_view_doc( $post ) ) {
		return null;
	}

	return $post;
}

/**
 * Entry URL for a product category under the default (or given) version.
 *
 * @param WP_Term   $term Product term.
 * @param WP_Post|null $version_root Optional version root.
 * @return string
 */
function manual_docs_get_product_entry_url( $term, $version_root = null ) {
	if ( ! $term || is_wp_error( $term ) ) {
		return '';
	}

	if ( ! $version_root && function_exists( 'manual_docs_get_version_roots' ) ) {
		$roots        = manual_docs_get_version_roots();
		$default_slug = (string) manual_docs_get_option( 'default_version_slug', 'goat' );
		if ( $roots ) {
			$version_root = $roots[0];
			if ( $default_slug ) {
				foreach ( $roots as $root ) {
					if ( $root->post_name === $default_slug || 0 === strpos( $root->post_name, sanitize_title( $default_slug ) . '-' ) ) {
						$version_root = $root;
						break;
					}
				}
			}
		}
	}

	if ( ! $version_root ) {
		$link = get_term_link( $term );
		return is_wp_error( $link ) ? '' : $link;
	}

	$node = manual_docs_find_product_child_under_version( (int) $version_root->ID, (int) $term->term_id );
	if ( $node ) {
		$url = get_permalink( $node );
		return $url ? $url : '';
	}

	// Fall back to version root with product query hint.
	$url = get_permalink( $version_root );
	if ( ! $url ) {
		return '';
	}
	return add_query_arg( 'md_product', $term->slug, $url );
}

/**
 * Compact product payload for AJAX / data attributes.
 *
 * @param int $post_id Post ID.
 * @return array{id:int,slug:string,name:string}|null
 */
function manual_docs_get_doc_product_payload( $post_id ) {
	$term = manual_docs_get_primary_product_term( (int) $post_id );
	if ( ! $term ) {
		return null;
	}
	return array(
		'id'   => (int) $term->term_id,
		'slug' => $term->slug,
		'name' => $term->name,
	);
}

/**
 * Redirect product category archives into the versioned product docs tree.
 */
function manual_docs_redirect_product_category_archive() {
	if ( is_admin() || ! manual_docs_product_tree_enabled() ) {
		return;
	}

	$tax = function_exists( 'manual_docs_category_taxonomy' ) ? manual_docs_category_taxonomy() : '';
	if ( ! $tax || ! is_tax( $tax ) ) {
		return;
	}

	$term = get_queried_object();
	if ( ! ( $term instanceof WP_Term ) || ! manual_docs_is_product_category( $term ) ) {
		return;
	}

	// Allow opt-out: ?md_archive=1 keeps the classic card archive.
	if ( ! empty( $_GET['md_archive'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$url = manual_docs_get_product_entry_url( $term );
	if ( ! $url ) {
		return;
	}

	wp_safe_redirect( $url, 302 );
	exit;
}
add_action( 'template_redirect', 'manual_docs_redirect_product_category_archive', 20 );

/**
 * Prefer a version sibling that shares the same product category.
 *
 * @param WP_Post|null $sibling Existing sibling.
 * @param int          $post_id Current post.
 * @param int          $target_root_id Target version root.
 * @return WP_Post|null
 */
function manual_docs_prefer_product_version_sibling( $sibling, $post_id, $target_root_id ) {
	if ( ! manual_docs_product_tree_enabled() ) {
		return $sibling;
	}

	$product = manual_docs_get_primary_product_term( (int) $post_id );
	if ( ! $product ) {
		return $sibling;
	}

	if ( $sibling ) {
		$sib_terms = manual_docs_get_doc_product_terms( (int) $sibling->ID );
		$slugs     = wp_list_pluck( $sib_terms, 'slug' );
		if ( in_array( $product->slug, $slugs, true ) ) {
			return $sibling;
		}
	}

	$branch = manual_docs_find_product_child_under_version( (int) $target_root_id, (int) $product->term_id );
	return $branch ? $branch : $sibling;
}
