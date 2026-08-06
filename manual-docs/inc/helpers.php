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
 * Curated font families available in Appearance → Manual Docs.
 *
 * @return array<string,array{label:string,stack:string,google:string}>
 */
function manual_docs_font_catalog() {
	return array(
		'sora'            => array(
			'label'  => 'Sora',
			'stack'  => '"Sora", "Segoe UI", sans-serif',
			'google' => 'Sora:wght@500;600;700',
		),
		'dm-sans'         => array(
			'label'  => 'DM Sans',
			'stack'  => '"DM Sans", "Segoe UI", sans-serif',
			'google' => 'DM+Sans:wght@400;500;600;700',
		),
		'outfit'          => array(
			'label'  => 'Outfit',
			'stack'  => '"Outfit", "Segoe UI", sans-serif',
			'google' => 'Outfit:wght@400;500;600;700',
		),
		'space-grotesk'   => array(
			'label'  => 'Space Grotesk',
			'stack'  => '"Space Grotesk", "Segoe UI", sans-serif',
			'google' => 'Space+Grotesk:wght@500;600;700',
		),
		'source-serif-4'  => array(
			'label'  => 'Source Serif 4',
			'stack'  => '"Source Serif 4", Georgia, serif',
			'google' => 'Source+Serif+4:wght@500;600;700',
		),
		'ibm-plex-sans'   => array(
			'label'  => 'IBM Plex Sans',
			'stack'  => '"IBM Plex Sans", "Segoe UI", sans-serif',
			'google' => 'IBM+Plex+Sans:wght@400;500;600;700',
		),
		'source-sans-3'   => array(
			'label'  => 'Source Sans 3',
			'stack'  => '"Source Sans 3", "Segoe UI", sans-serif',
			'google' => 'Source+Sans+3:wght@400;500;600;700',
		),
		'nunito-sans'     => array(
			'label'  => 'Nunito Sans',
			'stack'  => '"Nunito Sans", "Segoe UI", sans-serif',
			'google' => 'Nunito+Sans:wght@400;500;600;700',
		),
		'work-sans'       => array(
			'label'  => 'Work Sans',
			'stack'  => '"Work Sans", "Segoe UI", sans-serif',
			'google' => 'Work+Sans:wght@400;500;600;700',
		),
		'literata'        => array(
			'label'  => 'Literata',
			'stack'  => '"Literata", Georgia, serif',
			'google' => 'Literata:wght@400;500;600;700',
		),
		'system'          => array(
			'label'  => 'System UI',
			'stack'  => 'system-ui, -apple-system, "Segoe UI", sans-serif',
			'google' => '',
		),
	);
}

/**
 * Resolve a font option key to a CSS stack.
 *
 * @param string $key     Option key value.
 * @param string $fallback Catalog key fallback.
 * @return string
 */
function manual_docs_font_stack( $key, $fallback = 'ibm-plex-sans' ) {
	$catalog = manual_docs_font_catalog();
	if ( isset( $catalog[ $key ] ) ) {
		return $catalog[ $key ]['stack'];
	}
	return isset( $catalog[ $fallback ] ) ? $catalog[ $fallback ]['stack'] : '"IBM Plex Sans", "Segoe UI", sans-serif';
}

/**
 * Google Fonts CSS2 URL for the selected display + body fonts.
 *
 * @return string Empty when only system fonts are selected.
 */
function manual_docs_google_fonts_url() {
	$catalog = manual_docs_font_catalog();
	$display = (string) manual_docs_get_option( 'font_display', 'sora' );
	$body    = (string) manual_docs_get_option( 'font_body', 'ibm-plex-sans' );
	$families = array();
	foreach ( array( $display, $body ) as $key ) {
		if ( empty( $catalog[ $key ]['google'] ) ) {
			continue;
		}
		$families[ $catalog[ $key ]['google'] ] = true;
	}
	if ( empty( $families ) ) {
		return '';
	}
	return 'https://fonts.googleapis.com/css2?family=' . implode( '&family=', array_keys( $families ) ) . '&display=swap';
}

/**
 * Render header logo supporting separate dark/light assets.
 */
function manual_docs_render_site_logo() {
	$dark_id  = absint( manual_docs_get_option( 'logo_dark_id', 0 ) );
	$light_id = absint( manual_docs_get_option( 'logo_light_id', 0 ) );
	$home     = home_url( '/' );
	$brand    = manual_docs_brand_name();

	$dark_html  = $dark_id ? wp_get_attachment_image( $dark_id, 'full', false, array( 'class' => 'md-logo md-logo--dark', 'alt' => $brand ) ) : '';
	$light_html = $light_id ? wp_get_attachment_image( $light_id, 'full', false, array( 'class' => 'md-logo md-logo--light', 'alt' => $brand ) ) : '';

	// Fall back: one themed logo covers both modes; else WP custom logo.
	if ( $dark_html && ! $light_html ) {
		$light_html = wp_get_attachment_image( $dark_id, 'full', false, array( 'class' => 'md-logo md-logo--light', 'alt' => $brand ) );
	} elseif ( $light_html && ! $dark_html ) {
		$dark_html = wp_get_attachment_image( $light_id, 'full', false, array( 'class' => 'md-logo md-logo--dark', 'alt' => $brand ) );
	}

	if ( $dark_html || $light_html ) {
		echo '<div class="md-custom-logo md-themed-logo">';
		echo '<a class="md-logo-link" href="' . esc_url( $home ) . '" rel="home">';
		echo $dark_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image
		echo $light_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</a></div>';
		return;
	}

	if ( has_custom_logo() ) {
		echo '<div class="md-custom-logo">';
		the_custom_logo();
		echo '</div>';
		return;
	}

	echo '<a class="md-brand__text" href="' . esc_url( $home ) . '">';
	echo '<span class="md-brand__mark" aria-hidden="true"></span>';
	echo esc_html( $brand );
	echo '</a>';
}

/**
 * Collapse control for the docs tree sidebar.
 */
function manual_docs_render_tree_collapse_button() {
	?>
	<button type="button" class="md-docs-tree-collapse" data-md-tree-collapse aria-expanded="true" title="<?php esc_attr_e( 'Hide documentation tree', 'manual-docs' ); ?>">
		<span class="md-docs-tree-collapse__icon" aria-hidden="true">‹</span>
		<span class="screen-reader-text"><?php esc_html_e( 'Hide documentation tree', 'manual-docs' ); ?></span>
	</button>
	<?php
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
 * Tree for sidebar: all version roots (so goat/flamingo/hummingbird are visible),
 * each with its children. Falls back to full tree when no roots.
 *
 * @param int|null $post_id Current doc.
 * @return array
 */
function manual_docs_get_version_scoped_tree( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_queried_object_id();
	$roots   = function_exists( 'manual_docs_get_version_roots' ) ? manual_docs_get_version_roots() : array();

	// Show every release root in the left nav so other versions are discoverable.
	if ( count( $roots ) >= 2 ) {
		$tree = array();
		foreach ( $roots as $root ) {
			$tree[] = array(
				'post'     => $root,
				'children' => manual_docs_get_doc_tree( (int) $root->ID ),
				'depth'    => 0,
			);
		}
		return $tree;
	}

	$root = $post_id && function_exists( 'manual_docs_get_version_root_for_doc' )
		? manual_docs_get_version_root_for_doc( $post_id )
		: null;

	if ( ! $root ) {
		$slug = manual_docs_get_option( 'default_version_slug', '' );
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
 * Depth-first reading order of all docs under a version root (pre-order).
 * Children are visited before the next sibling — used by Previous/Next.
 *
 * @param int $root_id Version root post ID.
 * @return int[]
 */
function manual_docs_get_version_reading_order( $root_id ) {
	$root_id = (int) $root_id;
	static $cache = array();
	if ( isset( $cache[ $root_id ] ) ) {
		return $cache[ $root_id ];
	}

	$order = array();
	manual_docs_collect_doc_ids_dfs( $root_id, $order );
	$cache[ $root_id ] = $order;
	return $order;
}

/**
 * Collect descendant IDs in DFS pre-order.
 *
 * @param int   $parent_id Parent.
 * @param int[] $out       Collector.
 */
function manual_docs_collect_doc_ids_dfs( $parent_id, &$out ) {
	$children = get_posts(
		array(
			'post_type'              => 'manual_documentation',
			'post_parent'            => (int) $parent_id,
			'posts_per_page'         => -1,
			'orderby'                => 'menu_order title',
			'order'                  => 'ASC',
			'post_status'            => 'publish',
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	foreach ( $children as $cid ) {
		$cid   = (int) $cid;
		$out[] = $cid;
		manual_docs_collect_doc_ids_dfs( $cid, $out );
	}
}

/**
 * Adjacent docs in version DFS order (first child before next sibling).
 *
 * @param int|null $post_id Post ID.
 * @return array{prev:?WP_Post,next:?WP_Post}
 */
function manual_docs_adjacent_docs( $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	$post    = get_post( $post_id );
	if ( ! $post || 'manual_documentation' !== $post->post_type ) {
		return array(
			'prev' => null,
			'next' => null,
		);
	}

	$root = function_exists( 'manual_docs_get_version_root_for_doc' )
		? manual_docs_get_version_root_for_doc( $post_id )
		: null;

	if ( $root ) {
		$order = manual_docs_get_version_reading_order( (int) $root->ID );
		// Include the version root at the start of the reading path.
		array_unshift( $order, (int) $root->ID );
		$order = array_values( array_unique( array_map( 'intval', $order ) ) );
	} else {
		// Fallback: siblings only when no version root.
		$order = get_posts(
			array(
				'post_type'      => 'manual_documentation',
				'post_parent'    => (int) $post->post_parent,
				'posts_per_page' => -1,
				'orderby'        => 'menu_order title',
				'order'          => 'ASC',
				'post_status'    => 'publish',
				'fields'         => 'ids',
			)
		);
		$order = array_map( 'intval', $order );
	}

	$pos  = array_search( $post_id, $order, true );
	$prev = null;
	$next = null;

	if ( false !== $pos ) {
		if ( isset( $order[ $pos - 1 ] ) ) {
			$prev = get_post( $order[ $pos - 1 ] );
		}
		if ( isset( $order[ $pos + 1 ] ) ) {
			$next = get_post( $order[ $pos + 1 ] );
		}
	}

	return array(
		'prev' => $prev,
		'next' => $next,
	);
}