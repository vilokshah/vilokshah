<?php
/**
 * 404 template.
 *
 * @package ManualDocs
 */

get_header();
?>

<main id="main-content" class="md-main">
	<div class="md-container md-container--narrow md-404">
		<p class="md-eyebrow">404</p>
		<h1 class="md-page-title"><?php esc_html_e( 'Page not found', 'manual-docs' ); ?></h1>
		<p><?php esc_html_e( 'The page you are looking for does not exist or may have moved.', 'manual-docs' ); ?></p>
		<p>
			<a class="md-btn md-btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Go home', 'manual-docs' ); ?></a>
			<a class="md-btn md-btn--ghost" href="<?php echo esc_url( function_exists( 'manual_docs_get_docs_entry_url' ) ? manual_docs_get_docs_entry_url() : home_url( '/' ) ); ?>"><?php esc_html_e( 'Browse docs', 'manual-docs' ); ?></a>
		</p>
	</div>
</main>

<?php
get_footer();