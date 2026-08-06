<?php
/**
 * bbPress community integration.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether bbPress is active.
 *
 * @return bool
 */
function manual_docs_bbpress_active() {
	return class_exists( 'bbPress' );
}

/**
 * Enqueue bbPress-friendly styles when on forum pages.
 */
function manual_docs_bbpress_assets() {
	if ( ! manual_docs_bbpress_active() || ! function_exists( 'is_bbpress' ) || ! is_bbpress() ) {
		return;
	}

	wp_enqueue_style(
		'manual-docs-bbpress',
		MANUAL_DOCS_URI . '/assets/css/bbpress.css',
		array( 'manual-docs-main' ),
		MANUAL_DOCS_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'manual_docs_bbpress_assets', 20 );

/**
 * Community nav fallback links.
 */
function manual_docs_community_links() {
	if ( ! manual_docs_bbpress_active() ) {
		return array();
	}

	$links = array();

	if ( function_exists( 'bbp_get_forums_url' ) ) {
		$links[] = array(
			'label' => __( 'Forums', 'manual-docs' ),
			'url'   => bbp_get_forums_url(),
		);
	}

	if ( function_exists( 'bbp_get_topics_url' ) ) {
		$links[] = array(
			'label' => __( 'Topics', 'manual-docs' ),
			'url'   => bbp_get_topics_url(),
		);
	}

	if ( is_user_logged_in() && function_exists( 'bbp_get_user_profile_url' ) ) {
		$links[] = array(
			'label' => __( 'My Profile', 'manual-docs' ),
			'url'   => bbp_get_user_profile_url( get_current_user_id() ),
		);
	}

	return apply_filters( 'manual_docs_community_links', $links );
}

/**
 * Soften bbPress breadcrumbs into theme style via CSS class wrapper.
 *
 * @param string $content Content.
 * @return string
 */
function manual_docs_bbpress_wrapper_class( $content ) {
	return $content;
}

/**
 * Add community CTA on documentation pages.
 */
function manual_docs_render_community_cta() {
	if ( ! manual_docs_bbpress_active() || ! get_theme_mod( 'manual_docs_show_community_cta', true ) ) {
		return;
	}

	$forum_url = function_exists( 'bbp_get_forums_url' ) ? bbp_get_forums_url() : home_url( '/forums/' );
	?>
	<aside class="md-community-cta">
		<div class="md-community-cta__inner">
			<h3><?php esc_html_e( 'Need help from the community?', 'manual-docs' ); ?></h3>
			<p><?php esc_html_e( 'Ask questions, share tips, and connect with other members in the forums.', 'manual-docs' ); ?></p>
			<a class="md-btn md-btn--primary" href="<?php echo esc_url( $forum_url ); ?>"><?php esc_html_e( 'Visit Forums', 'manual-docs' ); ?></a>
		</div>
	</aside>
	<?php
}