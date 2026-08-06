<?php
/**
 * Theme helper functions.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fallback primary menu.
 */
function manual_docs_primary_fallback() {
	$items = array(
		array(
			'url'   => get_post_type_archive_link( 'manual_documentation' ),
			'label' => __( 'Docs', 'manual-docs' ),
		),
	);

	if ( function_exists( 'manual_docs_bbpress_active' ) && manual_docs_bbpress_active() && function_exists( 'bbp_get_forums_url' ) ) {
		$items[] = array(
			'url'   => bbp_get_forums_url(),
			'label' => __( 'Community', 'manual-docs' ),
		);
	}

	echo '<ul class="md-menu">';
	foreach ( $items as $item ) {
		if ( empty( $item['url'] ) ) {
			continue;
		}
		printf( '<li><a href="%s">%s</a></li>', esc_url( $item['url'] ), esc_html( $item['label'] ) );
	}
	echo '</ul>';
}

/**
 * Brand display name.
 *
 * @return string
 */
function manual_docs_brand_name() {
	$name = manual_docs_get_option( 'brand_name', '' );
	return $name ? $name : get_bloginfo( 'name' );
}

/**
 * Get documentation tree under a parent (version-scoped).
 *
 * @param int $parent Parent ID.
 * @param int $depth  Depth.
 * @return array
 */
function manual_docs_get_doc_tree( $parent = 0, $depth = 0 ) {
	$args = array(
		'post_type'      => 'manual_documentation',
		'post_parent'    => $parent,
		'posts_per_page' => -1,
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
		'post_status'    => 'publish',
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

	$posts = get_posts( $args );
	$tree  = array();

	foreach ( $posts as $post ) {
		if ( ! manual_docs_user_can_view_doc( $post ) ) {
			continue;
		}
		$tree[] = array(
			'post'     => $post,
			'children' => manual_docs_get_doc_tree( $post->ID, $depth + 1 ),
			'depth'    => $depth,
		);
	}

	return $tree;
}

/**
 * Tree for the current document's version root (children of root, not the root itself as only item).
 *
 * @param int|null $post_id Current doc.
 * @return array
 */
function manual_docs_get_version_scoped_tree( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_queried_object_id();
	$root    = $post_id ? manual_docs_get_version_root_for_doc( $post_id ) : null;

	if ( ! $root ) {
		$roots = manual_docs_get_version_roots();
		$slug  = manual_docs_get_option( 'default_version_slug', '' );
		if ( $slug ) {
			foreach ( $roots as $r ) {
				if ( $r->post_name === $slug ) {
					$root = $r;
					break;
				}
			}
		}
		if ( ! $root && ! empty( $roots ) ) {
			$root = $roots[0];
		}
	}

	if ( ! $root ) {
		return manual_docs_get_doc_tree( 0 );
	}

	// Include root as top node with its children, or just children?
	// Digitate shows sections under the version — show children of root, and if root has content link it.
	return manual_docs_get_doc_tree( (int) $root->ID );
}

/**
 * Render documentation sidebar navigation.
 *
 * @param array $tree    Tree nodes.
 * @param int   $current Current post ID.
 */
function manual_docs_render_doc_nav( $tree = null, $current = 0 ) {
	if ( null === $tree ) {
		$tree = manual_docs_get_version_scoped_tree( $current ? $current : get_queried_object_id() );
	}
	if ( ! $current ) {
		$current = get_queried_object_id();
	}
	if ( empty( $tree ) ) {
		echo '<p class="md-nav-empty">' . esc_html__( 'No documents in this version yet.', 'manual-docs' ) . '</p>';
		return;
	}

	$ancestors = $current ? get_post_ancestors( $current ) : array();
	$expand    = (bool) manual_docs_get_option( 'tree_expand_active', true );

	echo '<ul class="md-doc-nav">';
	manual_docs_render_doc_nav_nodes( $tree, $current, $ancestors, $expand );
	echo '</ul>';
}

/**
 * Render nav nodes recursively.
 *
 * @param array $tree      Nodes.
 * @param int   $current   Current ID.
 * @param array $ancestors Ancestor IDs.
 * @param bool  $expand    Expand active.
 */
function manual_docs_render_doc_nav_nodes( $tree, $current, $ancestors, $expand ) {
	foreach ( $tree as $node ) {
		$post      = $node['post'];
		$is_active = ( (int) $post->ID === (int) $current );
		$has_kids  = ! empty( $node['children'] );
		$is_open   = $expand && ( $is_active || in_array( (int) $post->ID, array_map( 'intval', $ancestors ), true ) );
		$classes   = 'md-doc-nav__item';
		if ( $is_active ) {
			$classes .= ' is-active';
		}
		if ( $has_kids ) {
			$classes .= ' has-children';
		}
		if ( $is_open ) {
			$classes .= ' is-expanded';
		}

		printf( '<li class="%s">', esc_attr( $classes ) );
		if ( $has_kids ) {
			echo '<button type="button" class="md-doc-nav__twist" aria-expanded="' . ( $is_open ? 'true' : 'false' ) . '" data-md-tree-toggle><span class="screen-reader-text">' . esc_html__( 'Toggle section', 'manual-docs' ) . '</span></button>';
		}
		printf(
			'<a href="%s" data-md-ajax-doc data-md-doc-id="%d"%s>%s</a>',
			esc_url( get_permalink( $post ) ),
			(int) $post->ID,
			$is_active ? ' aria-current="page"' : '',
			esc_html( get_the_title( $post ) )
		);
		if ( $has_kids ) {
			echo '<ul class="md-doc-nav__children"' . ( $is_open ? '' : ' hidden' ) . '>';
			manual_docs_render_doc_nav_nodes( $node['children'], $current, $ancestors, $expand );
			echo '</ul>';
		}
		echo '</li>';
	}
}

/**
 * Accessible categories for home.
 *
 * @return WP_Term[]
 */
function manual_docs_get_accessible_categories() {
	$tax = manual_docs_category_taxonomy();
	if ( ! taxonomy_exists( $tax ) ) {
		return array();
	}

	$terms = get_terms( array(
		'taxonomy'   => $tax,
		'hide_empty' => true,
		'parent'     => 0,
		'number'     => 24,
	) );

	if ( is_wp_error( $terms ) ) {
		return array();
	}

	return array_values(
		array_filter(
			$terms,
			static function ( $term ) {
				return manual_docs_user_can_view_category( $term->term_id );
			}
		)
	);
}

/**
 * Breadcrumbs for documentation.
 *
 * @param int|null $post_id Post ID.
 */
function manual_docs_breadcrumbs( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$items   = array();

	$items[] = array(
		'label' => __( 'Docs', 'manual-docs' ),
		'url'   => get_post_type_archive_link( 'manual_documentation' ),
	);

	$root = manual_docs_get_version_root_for_doc( $post_id );
	if ( $root ) {
		$items[] = array(
			'label' => get_the_title( $root ),
			'url'   => get_permalink( $root ),
		);
	}

	$ancestors = array_reverse( get_post_ancestors( $post_id ) );
	foreach ( $ancestors as $ancestor_id ) {
		if ( $root && (int) $ancestor_id === (int) $root->ID ) {
			continue;
		}
		$items[] = array(
			'label' => get_the_title( $ancestor_id ),
			'url'   => get_permalink( $ancestor_id ),
		);
	}

	$items[] = array(
		'label' => get_the_title( $post_id ),
		'url'   => '',
	);
	?>
	<nav class="md-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'manual-docs' ); ?>">
		<ol>
			<?php foreach ( $items as $i => $item ) : ?>
				<li>
					<?php if ( ! empty( $item['url'] ) && $i < count( $items ) - 1 ) : ?>
						<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
					<?php else : ?>
						<span aria-current="page"><?php echo esc_html( $item['label'] ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>
	<?php
}

/**
 * Adjacent docs within same parent.
 *
 * @param int|null $post_id Post ID.
 * @return array{prev:?WP_Post,next:?WP_Post}
 */
function manual_docs_adjacent_docs( $post_id = null ) {
	$post_id  = $post_id ? $post_id : get_the_ID();
	$post     = get_post( $post_id );
	$siblings = get_posts( array(
		'post_type'      => 'manual_documentation',
		'post_parent'    => $post->post_parent,
		'posts_per_page' => -1,
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
		'post_status'    => 'publish',
		'fields'         => 'ids',
	) );

	$prev = null;
	$next = null;
	$pos  = array_search( (int) $post_id, array_map( 'intval', $siblings ), true );

	if ( false !== $pos ) {
		if ( isset( $siblings[ $pos - 1 ] ) ) {
			$prev = get_post( $siblings[ $pos - 1 ] );
		}
		if ( isset( $siblings[ $pos + 1 ] ) ) {
			$next = get_post( $siblings[ $pos + 1 ] );
		}
	}

	return array(
		'prev' => $prev,
		'next' => $next,
	);
}