<?php
/**
 * bbPress theme-compat wrapper — modern community layout.
 *
 * bbPress registers post types `forum`, `topic`, and `reply`. This theme does
 * NOT re-register them. Template parts in /bbpress/ override the loop markup
 * into card layouts; do not add archive-forum.php / single-forum.php here or
 * you will bypass theme compatibility.
 *
 * @package ManualDocs
 */

get_header();

$title = function_exists( 'manual_docs_community_page_title' ) ? manual_docs_community_page_title() : __( 'Forums', 'manual-docs' );
$show_toolbar = true;
if ( function_exists( 'bbp_is_single_user' ) && bbp_is_single_user() ) {
	$show_toolbar = false;
}
?>

<main id="main-content" class="md-main md-main--community">
	<?php
	if ( function_exists( 'manual_docs_render_community_hero' ) ) {
		manual_docs_render_community_hero( $title );
	}
	?>

	<div class="md-community-shell">
		<div class="md-community-main">
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

			if ( $show_toolbar && function_exists( 'manual_docs_render_community_toolbar' ) ) {
				manual_docs_render_community_toolbar();
			}
			?>

			<div class="md-bbpress entry-content">
				<?php
				if ( function_exists( 'bbp_is_single_topic' ) && bbp_is_single_topic() && function_exists( 'manual_docs_academy_fields_badge_html' ) ) {
					$badge = manual_docs_academy_fields_badge_html( bbp_get_topic_id() );
					if ( $badge ) {
						echo '<div class="md-academy-badge-wrap">' . $badge . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
				}

				while ( have_posts() ) {
					the_post();
					the_content();
				}
				?>
			</div>
		</div>

		<aside class="md-community-sidebar" aria-label="<?php esc_attr_e( 'Community sidebar', 'manual-docs' ); ?>">
			<?php
			if ( function_exists( 'manual_docs_render_recent_topics_panel' ) ) {
				manual_docs_render_recent_topics_panel();
			}
			if ( is_active_sidebar( 'community-sidebar' ) ) {
				dynamic_sidebar( 'community-sidebar' );
			}
			?>
		</aside>
	</div>
</main>

<?php
get_footer();
