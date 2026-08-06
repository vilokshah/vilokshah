<?php
/**
 * bbPress wrapper template.
 *
 * @package ManualDocs
 */

get_header();
?>

<main id="main-content" class="md-main md-main--community">
	<div class="md-community-shell">
		<div class="md-community-content">
			<header class="md-page-header md-community-header">
				<p class="md-eyebrow"><?php esc_html_e( 'Community', 'manual-docs' ); ?></p>
				<h1 class="md-page-title"><?php esc_html_e( 'Forums', 'manual-docs' ); ?></h1>
				<?php
				if ( has_nav_menu( 'community' ) ) {
					wp_nav_menu( array(
						'theme_location' => 'community',
						'container'      => 'nav',
						'container_class'=> 'md-community-menu',
						'menu_class'     => 'md-menu md-menu--inline',
						'depth'          => 1,
					) );
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