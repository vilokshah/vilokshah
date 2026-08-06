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
 * Fallback primary menu when no menu is assigned.
 */
function manual_docs_primary_fallback() {
	$items = array(
		array(
			'url'   => get_post_type_archive_link( 'manual_documentation' ),
			'label' => __( 'Documentation', 'manual-docs' ),
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
 * Get hierarchical documentation tree for sidebar.
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

	$restricted = manual_docs_get_restricted_category_ids_for_user();
	if ( ! empty( $restricted ) ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'doc_category',
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
 * Render documentation sidebar navigation.
 *
 * @param array $tree Tree nodes.
 * @param int   $current Current post ID.
 */
function manual_docs_render_doc_nav( $tree = null, $current = 0 ) {
	if ( null === $tree ) {
		$tree = manual_docs_get_doc_tree();
	}
	if ( ! $current ) {
		$current = get_queried_object_id();
	}
	if ( empty( $tree ) ) {
		echo '<p class="md-nav-empty">' . esc_html__( 'No documents yet.', 'manual-docs' ) . '</p>';
		return;
	}
	echo '<ul class="md-doc-nav">';
	foreach ( $tree as $node ) {
		$post      = $node['post'];
		$is_active = ( (int) $post->ID === (int) $current );
		$has_kids  = ! empty( $node['children'] );
		printf(
			'<li class="%s"><a href="%s"%s>%s</a>',
			esc_attr( 'md-doc-nav__item' . ( $is_active ? ' is-active' : '' ) . ( $has_kids ? ' has-children' : '' ) ),
			esc_url( get_permalink( $post ) ),
			$is_active ? ' aria-current="page"' : '',
			esc_html( get_the_title( $post ) )
		);
		if ( $has_kids ) {
			manual_docs_render_doc_nav( $node['children'], $current );
		}
		echo '</li>';
	}
	echo '</ul>';
}

/**
 * Get categories for home grid (accessible only).
 *
 * @return WP_Term[]
 */
function manual_docs_get_accessible_categories() {
	$terms = get_terms( array(
		'taxonomy'   => 'doc_category',
		'hide_empty' => true,
		'parent'     => 0,
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

	$terms = get_the_terms( $post_id, 'doc_category' );
	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
		$term    = $terms[0];
		$parents = array_reverse( get_ancestors( $term->term_id, 'doc_category' ) );
		foreach ( $parents as $parent_id ) {
			$parent = get_term( $parent_id, 'doc_category' );
			if ( $parent && ! is_wp_error( $parent ) ) {
				$items[] = array(
					'label' => $parent->name,
					'url'   => get_term_link( $parent ),
				);
			}
		}
		$items[] = array(
			'label' => $term->name,
			'url'   => get_term_link( $term ),
		);
	}

	$ancestors = array_reverse( get_post_ancestors( $post_id ) );
	foreach ( $ancestors as $ancestor_id ) {
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
 * Prev / next sibling documents.
 *
 * @param int|null $post_id Post ID.
 * @return array{prev:?WP_Post,next:?WP_Post}
 */
function manual_docs_adjacent_docs( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$post    = get_post( $post_id );
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