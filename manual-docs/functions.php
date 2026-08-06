<?php
/**
 * Manual Docs theme functions and definitions.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MANUAL_DOCS_VERSION', '1.0.0' );
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
	wp_enqueue_style(
		'manual-docs-fonts',
		'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=Sora:wght@500;600;700&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'manual-docs-main',
		MANUAL_DOCS_URI . '/assets/css/main.css',
		array( 'manual-docs-fonts' ),
		MANUAL_DOCS_VERSION
	);

	wp_enqueue_script(
		'manual-docs-main',
		MANUAL_DOCS_URI . '/assets/js/main.js',
		array(),
		MANUAL_DOCS_VERSION,
		true
	);

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
		'loginUrl'  => wp_login_url( get_permalink() ),
		'i18n'      => array(
			'searchPlaceholder' => __( 'Search documentation…', 'manual-docs' ),
			'noResults'         => __( 'No documents found.', 'manual-docs' ),
			'searching'         => __( 'Searching…', 'manual-docs' ),
			'loginRequired'     => __( 'Please log in to view documentation.', 'manual-docs' ),
		),
	) );

	if ( is_singular( 'manual_documentation' ) ) {
		wp_enqueue_script(
			'manual-docs-toc',
			MANUAL_DOCS_URI . '/assets/js/toc.js',
			array(),
			MANUAL_DOCS_VERSION,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'manual_docs_scripts' );

/**
 * Load theme includes.
 */
$manual_docs_includes = array(
	'security.php',
	'cpt.php',
	'access-control.php',
	'versioning.php',
	'live-search.php',
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
	if ( is_singular( 'manual_documentation' ) || is_post_type_archive( 'manual_documentation' ) || is_tax( 'doc_category' ) || is_tax( 'doc_version' ) ) {
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
 * Excerpt length for documentation cards.
 *
 * @param int $length Excerpt length.
 * @return int
 */
function manual_docs_excerpt_length( $length ) {
	if ( is_post_type_archive( 'manual_documentation' ) || is_tax( 'doc_category' ) ) {
		return 22;
	}
	return $length;
}
add_filter( 'excerpt_length', 'manual_docs_excerpt_length' );