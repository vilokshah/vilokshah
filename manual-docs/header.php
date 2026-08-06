<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script>
	(function () {
		try {
			var t = localStorage.getItem('manualDocsTheme');
			if (t !== 'light' && t !== 'dark') {
				t = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
			}
			document.documentElement.setAttribute('data-md-theme', t);
		} catch (e) {
			document.documentElement.setAttribute('data-md-theme', 'dark');
		}
	})();
	</script>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#main-content"><?php esc_html_e( 'Skip to content', 'manual-docs' ); ?></a>

<header class="md-header" role="banner">
	<div class="md-header__inner">
		<div class="md-brand">
			<?php manual_docs_render_site_logo(); ?>
		</div>

		<nav class="md-nav-primary" aria-label="<?php esc_attr_e( 'Primary', 'manual-docs' ); ?>">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'md-menu',
				'fallback_cb'    => 'manual_docs_primary_fallback',
				'depth'          => 2,
			) );
			?>
		</nav>

		<div class="md-header__actions">
			<button type="button" class="md-theme-toggle" data-md-theme-toggle aria-pressed="false" title="<?php esc_attr_e( 'Toggle light / dark mode', 'manual-docs' ); ?>">
				<span class="screen-reader-text"><?php esc_html_e( 'Toggle color theme', 'manual-docs' ); ?></span>
				<svg class="md-theme-toggle__sun" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="2"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
				<svg class="md-theme-toggle__moon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 14.5A8.5 8.5 0 1110.5 3a7 7 0 0010.5 11.5z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
			</button>

			<button type="button" class="md-search-toggle" aria-expanded="false" aria-controls="md-header-search" data-md-search-toggle>
				<span class="screen-reader-text"><?php esc_html_e( 'Open search', 'manual-docs' ); ?></span>
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
			</button>

			<?php if ( is_user_logged_in() ) : ?>
				<a class="md-btn md-btn--ghost md-btn--sm" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Log out', 'manual-docs' ); ?></a>
			<?php else : ?>
				<a class="md-btn md-btn--primary md-btn--sm" href="<?php echo esc_url( function_exists( 'manual_docs_get_login_url' ) ? manual_docs_get_login_url( get_permalink() ) : wp_login_url( get_permalink() ) ); ?>"><?php esc_html_e( 'Log in', 'manual-docs' ); ?></a>
			<?php endif; ?>

			<button type="button" class="md-nav-toggle" aria-expanded="false" aria-controls="md-mobile-nav" data-md-nav-toggle>
				<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'manual-docs' ); ?></span>
				<span class="md-nav-toggle__bars" aria-hidden="true"></span>
			</button>
		</div>
	</div>

	<div id="md-header-search" class="md-header__search" hidden>
		<div class="md-header__search-inner">
			<?php manual_docs_render_live_search( array( 'class' => 'md-live-search--header' ) ); ?>
		</div>
	</div>

	<div id="md-mobile-nav" class="md-mobile-nav" hidden>
		<?php
		wp_nav_menu( array(
			'theme_location' => 'primary',
			'container'      => false,
			'menu_class'     => 'md-menu md-menu--mobile',
			'fallback_cb'    => 'manual_docs_primary_fallback',
		) );
		?>
	</div>
</header>
