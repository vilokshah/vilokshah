<?php
/**
 * Manual Docs theme functions and definitions.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MANUAL_DOCS_VERSION', '2.8.1' );

/**
 * Version roots for JS (search filters).
 *
 * @return array<int,array{id:int,name:string,slug:string}>
 */
function manual_docs_localize_versions() {
	if ( ! function_exists( 'manual_docs_get_version_roots' ) ) {
		return array();
	}
	$out = array();
	foreach ( manual_docs_get_version_roots() as $root ) {
		$out[] = array(
			'id'   => (int) $root->ID,
			'name' => get_the_title( $root ),
			'slug' => $root->post_name,
		);
	}
	return $out;
}
define( 'MANUAL_DOCS_DIR', get_template_directory() );
define( 'MANUAL_DOCS_URI', get_template_directory_uri() );

/**
 * Theme setup.
 */
function manual_docs_setup() {
	load_theme_textdomain( 'manual-docs', MANUAL_DOCS_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
		'style',
		'script',
		'navigation-widgets',
	) );
	add_theme_support( 'custom-logo', array(
		'height'      => 80,
		'width'       => 240,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'site-icon' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/editor.css' );

	register_nav_menus( array(
		'primary'   => __( 'Primary Menu', 'manual-docs' ),
		'docs'      => __( 'Documentation Sidebar', 'manual-docs' ),
		'footer'    => __( 'Footer Menu', 'manual-docs' ),
		'community' => __( 'Community Menu', 'manual-docs' ),
	) );

	add_image_size( 'manual-docs-card', 640, 360, true );
}
add_action( 'after_setup_theme', 'manual_docs_setup' );

/**
 * Register widget areas.
 */
function manual_docs_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Documentation Sidebar', 'manual-docs' ),
		'id'            => 'docs-sidebar',
		'description'   => __( 'Appears beside documentation content.', 'manual-docs' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );

	register_sidebar( array(
		'name'          => __( 'Community Sidebar', 'manual-docs' ),
		'id'            => 'community-sidebar',
		'description'   => __( 'Appears on bbPress / community pages.', 'manual-docs' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );

	register_sidebar( array(
		'name'          => __( 'Footer Widgets', 'manual-docs' ),
		'id'            => 'footer-widgets',
		'description'   => __( 'Footer column widgets.', 'manual-docs' ),
		'before_widget' => '<section id="%1$s" class="widget footer-widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
}
add_action( 'widgets_init', 'manual_docs_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function manual_docs_scripts() {
	$fonts_url = function_exists( 'manual_docs_google_fonts_url' ) ? manual_docs_google_fonts_url() : '';
	$main_deps = array();

	if ( $fonts_url ) {
		wp_enqueue_style(
			'manual-docs-fonts',
			$fonts_url,
			array(),
			null
		);
		$main_deps[] = 'manual-docs-fonts';
	}

	wp_enqueue_style(
		'manual-docs-main',
		MANUAL_DOCS_URI . '/assets/css/main.css',
		$main_deps,
		MANUAL_DOCS_VERSION
	);

	wp_enqueue_script(
		'manual-docs-main',
		MANUAL_DOCS_URI . '/assets/js/main.js',
		array(),
		MANUAL_DOCS_VERSION,
		true
	);

	manual_docs_enqueue_search_assets();

	$is_docs_view = is_singular( 'manual_documentation' )
		|| is_post_type_archive( 'manual_documentation' )
		|| ( function_exists( 'manual_docs_category_taxonomy' ) && is_tax( manual_docs_category_taxonomy() ) );

	if ( $is_docs_view ) {
		wp_enqueue_script(
			'manual-docs-toc',
			MANUAL_DOCS_URI . '/assets/js/toc.js',
			array(),
			MANUAL_DOCS_VERSION,
			true
		);
	}

	if ( is_singular( 'manual_documentation' ) ) {
		wp_enqueue_script(
			'manual-docs-ajax-docs',
			MANUAL_DOCS_URI . '/assets/js/ajax-docs.js',
			array( 'manual-docs-toc', 'manual-docs-live-search' ),
			MANUAL_DOCS_VERSION,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'manual_docs_scripts' );

/**
 * Enqueue live-search script + localization (safe to call multiple times).
 */
function manual_docs_enqueue_search_assets() {
	if ( wp_script_is( 'manual-docs-live-search', 'enqueued' ) ) {
		return;
	}

	wp_enqueue_script(
		'manual-docs-live-search',
		MANUAL_DOCS_URI . '/assets/js/live-search.js',
		array(),
		MANUAL_DOCS_VERSION,
		true
	);

	wp_localize_script( 'manual-docs-live-search', 'manualDocs', array(
		'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
		'restUrl'   => esc_url_raw( rest_url( 'manual-docs/v1/' ) ),
		'nonce'     => wp_create_nonce( 'manual_docs_search' ),
		'restNonce' => wp_create_nonce( 'wp_rest' ),
		'homeUrl'   => home_url( '/' ),
		'loginUrl'  => wp_login_url( home_url( '/' ) ),
		'ajaxDocs'  => true,
		'versions'  => function_exists( 'manual_docs_localize_versions' ) ? manual_docs_localize_versions() : array(),
		'i18n'      => array(
			'searchPlaceholder' => __( 'Search documentation…', 'manual-docs' ),
			'noResults'         => __( 'No documents found.', 'manual-docs' ),
			'searching'         => __( 'Searching…', 'manual-docs' ),
			'loginRequired'     => __( 'Please log in to view documentation.', 'manual-docs' ),
			'loadingDoc'        => __( 'Loading document…', 'manual-docs' ),
		),
	) );
}

/**
 * Load theme includes.
 */
$manual_docs_includes = array(
	'theme-options.php',
	'rest-compat.php',
	'security.php',
	'cpt.php',
	'permalinks.php',
	'import-compat.php',
	'access-control.php',
	'versioning.php',
	'live-search.php',
	'ajax-docs.php',
	'pdf-download.php',
	'bbpress.php',
	'customizer.php',
	'helpers.php',
);

foreach ( $manual_docs_includes as $file ) {
	$path = MANUAL_DOCS_DIR . '/inc/' . $file;
	if ( file_exists( $path ) ) {
		require_once $path;
	}
}

/**
 * Body classes.
 *
 * @param array $classes Body classes.
 * @return array
 */
function manual_docs_body_classes( $classes ) {
	$tax = function_exists( 'manual_docs_category_taxonomy' ) ? manual_docs_category_taxonomy() : 'manualdocumentationcategory';
	if ( is_singular( 'manual_documentation' ) || is_post_type_archive( 'manual_documentation' ) || is_tax( $tax ) ) {
		$classes[] = 'manual-docs-layout';
	}

	if ( function_exists( 'is_bbpress' ) && is_bbpress() ) {
		$classes[] = 'manual-docs-community';
	}

	if ( ! is_user_logged_in() ) {
		$classes[] = 'manual-docs-guest';
	}

	return $classes;
}
add_filter( 'body_class', 'manual_docs_body_classes' );

/**
 * Add AJAX data attributes to Documentation Sidebar menu links.
 *
 * @param array    $atts  Link attributes.
 * @param WP_Post  $item  Menu item.
 * @param stdClass $args  Menu args.
 * @return array
 */
function manual_docs_nav_menu_link_attributes( $atts, $item, $args ) {
	if ( empty( $args->theme_location ) || 'docs' !== $args->theme_location ) {
		return $atts;
	}

	if ( ! empty( $item->object ) && 'manual_documentation' === $item->object && ! empty( $item->object_id ) ) {
		$atts['data-md-ajax-doc'] = '1';
		$atts['data-md-doc-id']   = (string) (int) $item->object_id;
	}

	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'manual_docs_nav_menu_link_attributes', 10, 3 );

/**
 * Excerpt length for documentation cards.
 *
 * @param int $length Excerpt length.
 * @return int
 */
function manual_docs_excerpt_length( $length ) {
	$tax = function_exists( 'manual_docs_category_taxonomy' ) ? manual_docs_category_taxonomy() : 'manualdocumentationcategory';
	if ( is_post_type_archive( 'manual_documentation' ) || is_tax( $tax ) ) {
		return 22;
	}
	return $length;
}
add_filter( 'excerpt_length', 'manual_docs_excerpt_length' );