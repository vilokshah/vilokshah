<?php
/**
 * bbPress theme-compat wrapper (forum / topic / reply / user views).
 *
 * bbPress registers post types `forum`, `topic`, and `reply`. This theme does
 * NOT re-register them — install bbPress (and any companion plugins) and your
 * existing community data continues to work. bbPress injects forum markup into
 * `the_content()` inside this wrapper; do not add archive-forum.php /
 * single-forum.php here or you will bypass theme compatibility.
 *
 * @package ManualDocs
 */

get_header();

$is_user = function_exists( 'bbp_is_single_user' ) && bbp_is_single_user();
$title   = __( 'Forums', 'manual-docs' );

if ( function_exists( 'bbp_is_single_forum' ) && bbp_is_single_forum() ) {
	$title = get_the_title();
} elseif ( function_exists( 'bbp_is_single_topic' ) && bbp_is_single_topic() ) {
	$title = get_the_title();
} elseif ( function_exists( 'bbp_is_single_reply' ) && bbp_is_single_reply() ) {
	$title = get_the_title();
} elseif ( function_exists( 'bbp_is_topic_tag' ) && bbp_is_topic_tag() ) {
	$title = __( 'Topic Tag', 'manual-docs' );
} elseif ( $is_user ) {
	$title = __( 'Member Profile', 'manual-docs' );
} elseif ( function_exists( 'bbp_is_search' ) && bbp_is_search() ) {
	$title = __( 'Forum Search', 'manual-docs' );
}
?>

<main id="main-content" class="md-main md-main--community">
	<div class="md-community-shell">
		<div class="md-community-content">
			<header class="md-page-header md-community-header">
				<p class="md-eyebrow"><?php esc_html_e( 'Community', 'manual-docs' ); ?></p>
				<h1 class="md-page-title"><?php echo esc_html( $title ); ?></h1>
				<?php
				if ( has_nav_menu( 'community' ) ) {
					wp_nav_menu(
						array(
							'theme_location'  => 'community',
							'container'       => 'nav',
							'container_class' => 'md-community-menu',
							'menu_class'      => 'md-menu md-menu--inline',
							'depth'           => 1,
						)
					);
				} elseif ( function_exists( 'manual_docs_community_links' ) ) {
					$links = manual_docs_community_links();
					if ( $links ) {
						echo '<nav class="md-community-menu" aria-label="' . esc_attr__( 'Community', 'manual-docs' ) . '"><ul class="md-menu md-menu--inline">';
						foreach ( $links as $link ) {
							printf( '<li><a href="%s">%s</a></li>', esc_url( $link['url'] ), esc_html( $link['label'] ) );
						}
						echo '</ul></nav>';
					}
				}
				?>
			</header>

			<div class="md-bbpress entry-content">
				<?php
				while ( have_posts() ) {
					the_post();
					the_content();
				}
				?>
			</div>
		</div>

		<?php if ( is_active_sidebar( 'community-sidebar' ) ) : ?>
			<aside class="md-community-sidebar" aria-label="<?php esc_attr_e( 'Community sidebar', 'manual-docs' ); ?>">
				<?php dynamic_sidebar( 'community-sidebar' ); ?>
			</aside>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();
