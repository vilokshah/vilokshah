<footer class="md-footer" role="contentinfo">
	<div class="md-footer__inner">
		<div class="md-footer__brand">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="md-brand__text md-brand__text--footer">
				<span class="md-brand__mark" aria-hidden="true"></span>
				<?php bloginfo( 'name' ); ?>
			</a>
			<p class="md-footer__tagline"><?php bloginfo( 'description' ); ?></p>
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
	<div class="md-footer__bar">
		<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'manual-docs' ); ?></p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>