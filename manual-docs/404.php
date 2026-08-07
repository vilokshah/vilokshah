<?php
/**
 * 404 template — centered recovery layout with search.
 *
 * @package ManualDocs
 */

get_header();

$docs_url = function_exists( 'manual_docs_get_docs_entry_url' ) ? manual_docs_get_docs_entry_url() : home_url( '/' );
$home_url = home_url( '/' );
?>

<main id="main-content" class="md-main md-main--404">
	<section class="md-404">
		<div class="md-404__atmosphere" aria-hidden="true"></div>
		<div class="md-container md-404__inner">
			<p class="md-404__code" aria-hidden="true">404</p>
			<h1 class="md-404__title"><?php esc_html_e( 'Page not found', 'manual-docs' ); ?></h1>
			<p class="md-404__text">
				<?php esc_html_e( 'The page you are looking for does not exist, was renamed, or may have moved to another release.', 'manual-docs' ); ?>
			</p>

			<div class="md-404__search">
				<?php
				if ( function_exists( 'manual_docs_render_live_search' ) ) {
					manual_docs_render_live_search(
						array(
							'class'       => 'md-live-search--hero md-live-search--404',
							'placeholder' => __( 'Search documentation…', 'manual-docs' ),
						)
					);
				}
				?>
			</div>

			<div class="md-404__actions">
				<a class="md-btn md-btn--primary" href="<?php echo esc_url( $home_url ); ?>"><?php esc_html_e( 'Go home', 'manual-docs' ); ?></a>
				<a class="md-btn md-btn--ghost" href="<?php echo esc_url( $docs_url ); ?>"><?php esc_html_e( 'Browse docs', 'manual-docs' ); ?></a>
			</div>

			<ul class="md-404__hints">
				<li><?php esc_html_e( 'Check the release version switcher if a topic moved.', 'manual-docs' ); ?></li>
				<li><?php esc_html_e( 'Try a broader search term above.', 'manual-docs' ); ?></li>
				<li><?php esc_html_e( 'Return home and open documentation from the header.', 'manual-docs' ); ?></li>
			</ul>
		</div>
	</section>
</main>

<?php
get_footer();
