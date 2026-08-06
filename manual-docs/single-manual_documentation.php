<?php
/**
 * Single manual_documentation template.
 *
 * @package ManualDocs
 */

get_header();
?>

<main id="main-content" class="md-main md-main--docs">
	<div class="md-docs-shell" data-md-ajax-shell>
		<aside class="md-docs-sidebar" id="md-docs-sidebar" aria-label="<?php esc_attr_e( 'Documentation navigation', 'manual-docs' ); ?>">
			<div class="md-docs-sidebar__search">
				<?php manual_docs_render_live_search( array( 'class' => 'md-live-search--sidebar' ) ); ?>
			</div>
			<nav class="md-docs-sidebar__nav" data-md-doc-tree>
				<?php
				if ( has_nav_menu( 'docs' ) ) {
					wp_nav_menu( array(
						'theme_location' => 'docs',
						'container'      => false,
						'menu_class'     => 'md-doc-nav',
						'depth'          => 3,
					) );
				} else {
					manual_docs_render_doc_nav();
				}
				?>
			</nav>
			<?php if ( is_active_sidebar( 'docs-sidebar' ) ) : ?>
				<div class="md-docs-sidebar__widgets">
					<?php dynamic_sidebar( 'docs-sidebar' ); ?>
				</div>
			<?php endif; ?>
		</aside>

		<button type="button" class="md-sidebar-toggle" data-md-sidebar-toggle aria-controls="md-docs-sidebar" aria-expanded="false">
			<?php esc_html_e( 'Docs menu', 'manual-docs' ); ?>
		</button>

		<?php while ( have_posts() ) : ?>
			<?php the_post(); ?>
			<article <?php post_class( 'md-doc-article' ); ?> id="md-doc-article" data-md-doc-id="<?php the_ID(); ?>" aria-live="polite" tabindex="-1">
				<div class="md-ajax-progress" data-md-ajax-progress hidden>
					<span class="md-ajax-progress__bar"></span>
				</div>

				<header class="md-doc-header">
					<div data-md-breadcrumbs>
						<?php manual_docs_breadcrumbs(); ?>
					</div>
					<div class="md-doc-toolbar">
						<div data-md-version-slot>
							<?php manual_docs_render_version_switcher(); ?>
						</div>
						<a class="md-btn md-btn--ghost md-pdf-download" data-md-pdf-btn href="<?php echo esc_url( manual_docs_get_pdf_url( get_the_ID(), true ) ); ?>" target="_blank" rel="noopener">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v12m0 0l4-4m-4 4l-4-4M4 21h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
							<?php esc_html_e( 'Download PDF', 'manual-docs' ); ?>
						</a>
					</div>
					<h1 class="md-doc-title" data-md-doc-title><?php the_title(); ?></h1>
					<p class="md-doc-meta" data-md-doc-meta>
						<?php
						$version = manual_docs_get_doc_version();
						if ( $version ) {
							echo '<span class="md-badge" data-md-version-badge>' . esc_html( sprintf( __( 'v%s', 'manual-docs' ), $version->name ) ) . '</span>';
						} else {
							echo '<span class="md-badge" data-md-version-badge hidden></span>';
						}
						?>
						<span data-md-modified><?php echo esc_html( sprintf( __( 'Updated %s', 'manual-docs' ), get_the_modified_date() ) ); ?></span>
					</p>
				</header>

				<div class="md-doc-layout">
					<div class="md-doc-content entry-content" id="md-doc-content" data-md-doc-content>
						<?php the_content(); ?>
					</div>
					<aside class="md-doc-toc" data-md-toc aria-label="<?php esc_attr_e( 'On this page', 'manual-docs' ); ?>">
						<p class="md-doc-toc__title"><?php esc_html_e( 'On this page', 'manual-docs' ); ?></p>
						<nav id="md-toc-list" class="md-doc-toc__list" data-md-toc-list></nav>
					</aside>
				</div>

				<?php $adjacent = manual_docs_adjacent_docs(); ?>
				<nav class="md-doc-pager" data-md-pager aria-label="<?php esc_attr_e( 'Document navigation', 'manual-docs' ); ?>">
					<?php echo manual_docs_get_pager_html( $adjacent ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper ?>
				</nav>

				<div data-md-community-slot>
					<?php manual_docs_render_community_cta(); ?>
				</div>
			</article>
		<?php endwhile; ?>
	</div>
</main>

<?php
get_footer();