<?php
/**
 * Theme footer — Digitate-style multi-column widgets + copyright bar.
 *
 * @package ManualDocs
 */

$footer_text      = manual_docs_get_option( 'footer_text', '' );
$footer_copyright = manual_docs_get_option( 'footer_copyright', '' );
$has_columns      = function_exists( 'manual_docs_has_footer_columns' ) && manual_docs_has_footer_columns();
?>
<footer class="md-footer" role="contentinfo">
	<?php if ( $has_columns ) : ?>
		<div class="md-footer__grid-wrap">
			<div class="md-footer__grid">
				<?php foreach ( array( 'footer-1', 'footer-2', 'footer-3', 'footer-4' ) as $index => $sidebar_id ) : ?>
					<div class="md-footer__col md-footer__col--<?php echo esc_attr( (string) ( $index + 1 ) ); ?>">
						<?php
						if ( is_active_sidebar( $sidebar_id ) ) {
							dynamic_sidebar( $sidebar_id );
						}
						?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php else : ?>
		<div class="md-footer__inner md-footer__inner--legacy">
			<div class="md-footer__brand">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="md-brand__text md-brand__text--footer">
					<span class="md-brand__mark" aria-hidden="true"></span>
					<?php echo esc_html( manual_docs_brand_name() ); ?>
				</a>
				<p class="md-footer__tagline"><?php echo $footer_text ? esc_html( $footer_text ) : esc_html( get_bloginfo( 'description' ) ); ?></p>
			</div>

			<nav class="md-footer__nav" aria-label="<?php esc_attr_e( 'Footer', 'manual-docs' ); ?>">
				<?php
				wp_nav_menu( array(
					'theme_location' => 'footer',
					'container'      => false,
					'menu_class'     => 'md-menu md-menu--footer',
					'fallback_cb'    => false,
					'depth'          => 1,
				) );
				?>
			</nav>

			<?php if ( is_active_sidebar( 'footer-widgets' ) ) : ?>
				<div class="md-footer__widgets">
					<?php dynamic_sidebar( 'footer-widgets' ); ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="md-footer__bar">
		<p>
			<?php
			if ( $footer_copyright ) {
				echo esc_html( $footer_copyright );
			} else {
				printf(
					/* translators: 1: year, 2: site name */
					esc_html__( '© %1$s %2$s. All rights reserved.', 'manual-docs' ),
					esc_html( gmdate( 'Y' ) ),
					esc_html( get_bloginfo( 'name' ) )
				);
			}
			?>
		</p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
