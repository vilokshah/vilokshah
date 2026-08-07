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
	$items = array();
	$docs  = function_exists( 'manual_docs_get_docs_entry_url' ) ? manual_docs_get_docs_entry_url() : '';
	if ( $docs ) {
		$items[] = array(
			'url'   => $docs,
			'label' => __( 'Docs', 'manual-docs' ),
		);
	}

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
		<svg class="md-docs-tree-collapse__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
			<rect x="3" y="4" width="18" height="16" rx="3" stroke="currentColor" stroke-width="2"/>
			<path d="M9 4v16" stroke="currentColor" stroke-width="2"/>
			<rect x="3.75" y="5" width="4.5" height="14" rx="1.5" fill="currentColor" opacity="0.4"/>
		</svg>
		<span class="screen-reader-text"><?php esc_html_e( 'Hide documentation tree', 'manual-docs' ); ?></span>
	</button>
	<?php
}

/**
 * Small brand mark for the docs tree chrome (site icon / logo / fallback).
 */
function manual_docs_render_sidebar_favicon() {
	$home  = home_url( '/' );
	$brand = manual_docs_brand_name();
	$icon  = function_exists( 'get_site_icon_url' ) ? get_site_icon_url( 64 ) : '';

	if ( ! $icon ) {
		$logo_dark  = absint( manual_docs_get_option( 'logo_dark_id', 0 ) );
		$logo_light = absint( manual_docs_get_option( 'logo_light_id', 0 ) );
		$logo_id    = $logo_dark ? $logo_dark : $logo_light;
		if ( ! $logo_id && has_custom_logo() ) {
			$custom = get_theme_mod( 'custom_logo' );
			$logo_id = $custom ? absint( $custom ) : 0;
		}
		if ( $logo_id ) {
			$icon = wp_get_attachment_image_url( $logo_id, 'thumbnail' );
		}
	}
	?>
	<a class="md-docs-sidebar__brand" href="<?php echo esc_url( $home ); ?>" title="<?php echo esc_attr( $brand ); ?>">
		<?php if ( $icon ) : ?>
			<img class="md-docs-sidebar__favicon" src="<?php echo esc_url( $icon ); ?>" alt="<?php echo esc_attr( $brand ); ?>" width="28" height="28" decoding="async" />
		<?php else : ?>
			<span class="md-docs-sidebar__favicon md-docs-sidebar__favicon--mark" aria-hidden="true"></span>
			<span class="screen-reader-text"><?php echo esc_html( $brand ); ?></span>
		<?php endif; ?>
	</a>
	<?php
}

/**
 * Centered docs search modal (opened from the tree chrome).
 *
 * @param string $placeholder Search placeholder.
 */
function manual_docs_render_docs_search_modal( $placeholder = '' ) {
	static $rendered = false;
	if ( $rendered ) {
		return;
	}
	$rendered = true;
	if ( ! $placeholder ) {
		$placeholder = __( 'Search documentation…', 'manual-docs' );
	}
	?>
	<div class="md-docs-search-modal" data-md-docs-search-modal hidden>
		<div class="md-docs-search-modal__backdrop" data-md-docs-search-close tabindex="-1"></div>
		<div class="md-docs-search-modal__dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Search documentation', 'manual-docs' ); ?>">
			<button type="button" class="md-docs-search-modal__close" data-md-docs-search-close aria-label="<?php esc_attr_e( 'Close search', 'manual-docs' ); ?>">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
			</button>
			<?php
			manual_docs_render_live_search(
				array(
					'class'       => 'md-live-search--modal',
					'placeholder' => $placeholder,
				)
			);
			?>
		</div>
	</div>
	<?php
}

/**
 * Docs left tree: favicon + collapse/search chrome, truncated nav, centered search modal.
 *
 * @param array $args {
 *     @type string $search_placeholder Placeholder for the modal search.
 * }
 */
function manual_docs_render_docs_sidebar( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'search_placeholder' => __( 'Search docs…', 'manual-docs' ),
		)
	);
	?>
	<aside class="md-docs-sidebar" id="md-docs-sidebar" aria-label="<?php esc_attr_e( 'Documentation navigation', 'manual-docs' ); ?>">
		<div class="md-docs-sidebar__chrome">
			<?php manual_docs_render_sidebar_favicon(); ?>
			<div class="md-docs-sidebar__chrome-actions">
				<?php manual_docs_render_tree_collapse_button(); ?>
				<button type="button" class="md-docs-search-trigger" data-md-docs-search-open aria-haspopup="dialog" title="<?php esc_attr_e( 'Search documentation', 'manual-docs' ); ?>">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
					<span class="screen-reader-text"><?php esc_html_e( 'Search documentation', 'manual-docs' ); ?></span>
				</button>
			</div>
		</div>
		<div class="md-docs-sidebar__body">
			<nav class="md-docs-sidebar__nav" data-md-doc-tree>
				<?php manual_docs_render_doc_nav(); ?>
			</nav>
		</div>
	</aside>
	<?php
	manual_docs_render_docs_search_modal( $args['search_placeholder'] );
}

/**
 * Get documentation tree under a parent (full recursion — small libraries).
 *
 * @param int $parent Parent ID.
 * @param int $depth  Depth.
 * @return array
 */
function manual_docs_get_doc_tree( $parent = 0, $depth = 0 ) {
	$posts = manual_docs_get_direct_child_posts( $parent );
	$tree  = array();

	foreach ( $posts as $post ) {
		$tree[] = array(
			'post'         => $post,
			'children'     => manual_docs_get_doc_tree( $post->ID, $depth + 1 ),
			'depth'        => $depth,
			'lazy'         => false,
			'has_children' => false,
		);
	}

	return $tree;
}

/**
 * Direct child documentation posts for a parent (access-filtered).
 *
 * @param int $parent Parent ID.
 * @return WP_Post[]
 */
function manual_docs_get_direct_child_posts( $parent = 0 ) {
	$args = array(
		'post_type'              => 'manual_documentation',
		'post_parent'            => (int) $parent,
		'posts_per_page'         => -1,
		'orderby'                => 'menu_order title',
		'order'                  => 'ASC',
		'post_status'            => 'publish',
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
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
	$out   = array();
	foreach ( $posts as $post ) {
		if ( manual_docs_user_can_view_doc( $post ) ) {
			$out[] = $post;
		}
	}
	return $out;
}

/**
 * Whether a documentation post has published children.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function manual_docs_doc_has_children( $post_id ) {
	global $wpdb;
	$post_id = (int) $post_id;
	$found   = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND post_type = %s AND post_status = 'publish' LIMIT 1",
			$post_id,
			'manual_documentation'
		)
	);
	return ! empty( $found );
}

/**
 * Build one nav node; optionally defer grandchildren (lazy) for large libraries.
 *
 * @param WP_Post $post           Post.
 * @param int     $depth          Depth.
 * @param int[]   $expand_path_ids Ancestor IDs that must have children loaded.
 * @param bool    $lazy_mode      Whether lazy loading is enabled.
 * @return array
 */
function manual_docs_build_nav_node( $post, $depth, $expand_path_ids, $lazy_mode ) {
	$post_id    = (int) $post->ID;
	$has_kids   = manual_docs_doc_has_children( $post_id );
	$must_load  = ! $lazy_mode || in_array( $post_id, $expand_path_ids, true );
	$children   = array();
	$lazy       = false;

	if ( $has_kids ) {
		if ( $must_load ) {
			foreach ( manual_docs_get_direct_child_posts( $post_id ) as $child ) {
				$children[] = manual_docs_build_nav_node( $child, $depth + 1, $expand_path_ids, $lazy_mode );
			}
		} else {
			$lazy = true;
		}
	}

	return array(
		'post'         => $post,
		'children'     => $children,
		'depth'        => $depth,
		'lazy'         => $lazy,
		'has_children' => $has_kids,
	);
}

/**
 * Tree for sidebar: active version by default (scale), optional all roots.
 *
 * @param int|null $post_id Current doc.
 * @return array
 */
function manual_docs_get_version_scoped_tree( $post_id = null ) {
	$post_id   = $post_id ? (int) $post_id : (int) get_queried_object_id();
	$roots     = function_exists( 'manual_docs_get_version_roots' ) ? manual_docs_get_version_roots() : array();
	$scope     = (string) manual_docs_get_option( 'tree_scope', 'active_version' );
	$lazy_mode = (bool) manual_docs_get_option( 'tree_lazy', true );

	$expand_path = array();
	if ( $post_id ) {
		$expand_path = array_map( 'intval', get_post_ancestors( $post_id ) );
		$expand_path[] = $post_id;
	}

	// All version roots visible — only use on smaller libraries.
	if ( 'all_versions' === $scope && count( $roots ) >= 2 ) {
		$tree = array();
		foreach ( $roots as $root ) {
			$root_path = $expand_path;
			$root_path[] = (int) $root->ID;
			$tree[] = manual_docs_build_nav_node( $root, 0, array_values( array_unique( $root_path ) ), $lazy_mode );
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
		if ( ! $lazy_mode ) {
			return manual_docs_get_doc_tree( 0 );
		}
		$top = manual_docs_get_direct_child_posts( 0 );
		$tree = array();
		foreach ( $top as $post ) {
			$tree[] = manual_docs_build_nav_node( $post, 0, $expand_path, $lazy_mode );
		}
		return $tree;
	}

	// Active version: show the root + its descendants (lazy below the open path).
	$expand_path[] = (int) $root->ID;
	$expand_path   = array_values( array_unique( array_map( 'intval', $expand_path ) ) );

	return array( manual_docs_build_nav_node( $root, 0, $expand_path, $lazy_mode ) );
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
		$has_kids  = ! empty( $node['children'] ) || ! empty( $node['lazy'] ) || ! empty( $node['has_children'] );
		$is_open   = $expand && ( $is_active || in_array( (int) $post->ID, array_map( 'intval', $ancestors ), true ) );
		// Keep open when children were eagerly loaded for the active path.
		if ( ! empty( $node['children'] ) && $expand && in_array( (int) $post->ID, array_map( 'intval', $ancestors ), true ) ) {
			$is_open = true;
		}
		if ( $is_active && ! empty( $node['children'] ) ) {
			$is_open = true;
		}
		$classes = 'md-doc-nav__item';
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
			$lazy_attr = ! empty( $node['lazy'] ) ? ' data-md-lazy-parent="' . esc_attr( (string) (int) $post->ID ) . '"' : '';
			echo '<ul class="md-doc-nav__children"' . $lazy_attr . ( $is_open ? '' : ' hidden' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			if ( ! empty( $node['children'] ) ) {
				manual_docs_render_doc_nav_nodes( $node['children'], $current, $ancestors, $expand );
			}
			echo '</ul>';
		}
		echo '</li>';
	}
}

/**
 * Render only the child <li> nodes for a parent (lazy AJAX).
 *
 * @param int $parent_id Parent post ID.
 * @param int $current   Current doc ID.
 */
function manual_docs_render_nav_children_html( $parent_id, $current = 0 ) {
	$lazy_mode   = (bool) manual_docs_get_option( 'tree_lazy', true );
	$expand_path = $current ? array_map( 'intval', get_post_ancestors( $current ) ) : array();
	if ( $current ) {
		$expand_path[] = (int) $current;
	}
	$expand_path[] = (int) $parent_id;
	$expand_path   = array_values( array_unique( $expand_path ) );

	$nodes = array();
	foreach ( manual_docs_get_direct_child_posts( $parent_id ) as $child ) {
		// Children of this parent: do not recurse further when lazy (mark grandchildren lazy).
		$nodes[] = manual_docs_build_nav_node( $child, 0, $expand_path, $lazy_mode );
	}

	$ancestors = $current ? get_post_ancestors( $current ) : array();
	$expand    = (bool) manual_docs_get_option( 'tree_expand_active', true );
	manual_docs_render_doc_nav_nodes( $nodes, $current, $ancestors, $expand );
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
		'url'   => function_exists( 'manual_docs_get_docs_entry_url' ) ? manual_docs_get_docs_entry_url() : home_url( '/' ),
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

	$transient_key = 'manual_docs_read_order_' . $root_id;
	$order         = get_transient( $transient_key );
	if ( ! is_array( $order ) ) {
		$order = array();
		manual_docs_collect_doc_ids_dfs( $root_id, $order );
		set_transient( $transient_key, $order, 12 * HOUR_IN_SECONDS );
	}

	$cache[ $root_id ] = $order;
	return $order;
}

/**
 * Bust cached reading-order / tree helpers when docs change.
 *
 * @param int $post_id Post ID.
 */
function manual_docs_bust_doc_caches( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || 'manual_documentation' !== $post->post_type ) {
		return;
	}
	$root = function_exists( 'manual_docs_get_version_root_for_doc' ) ? manual_docs_get_version_root_for_doc( $post_id ) : null;
	if ( $root ) {
		delete_transient( 'manual_docs_read_order_' . (int) $root->ID );
	}
	if ( function_exists( 'manual_docs_get_version_roots' ) ) {
		foreach ( manual_docs_get_version_roots() as $r ) {
			delete_transient( 'manual_docs_read_order_' . (int) $r->ID );
		}
	}
}
add_action( 'save_post_manual_documentation', 'manual_docs_bust_doc_caches' );
add_action( 'before_delete_post', 'manual_docs_bust_doc_caches' );

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