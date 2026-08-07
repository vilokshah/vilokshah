<?php
/**
 * Single manual_documentation — digitate-style docs chrome.
 *
 * Left: search + tree. Main: title, meta, version, content. Right: TOC.
 * When ?md_compare= is set and version diff is enabled, shows summary-first compare.
 *
 * @package ManualDocs
 */

get_header();

$show_toc     = (bool) manual_docs_get_option( 'show_toc', true );
$show_pdf     = (bool) manual_docs_get_option( 'show_pdf', true );
$show_updated = (bool) manual_docs_get_option( 'show_updated', true );
$show_edit    = (bool) manual_docs_get_option( 'show_edit_link', true );
$version      = manual_docs_get_doc_version();
$compare_to   = function_exists( 'manual_docs_get_compare_request' ) ? manual_docs_get_compare_request() : '';
$is_compare   = $compare_to && function_exists( 'manual_docs_version_diff_enabled' ) && manual_docs_version_diff_enabled();
?>

<main id="main-content" class="md-main md-main--docs<?php echo $is_compare ? ' md-main--compare' : ''; ?>">
	<div class="md-docs-shell" data-md-ajax-shell>
		<?php manual_docs_render_docs_sidebar( array( 'search_placeholder' => __( 'Search docs…', 'manual-docs' ) ) ); ?>

		<button type="button" class="md-sidebar-toggle" data-md-sidebar-toggle aria-controls="md-docs-sidebar" aria-expanded="false">
			<?php esc_html_e( 'Docs menu', 'manual-docs' ); ?>
		</button>

		<?php while ( have_posts() ) : ?>
			<?php the_post(); ?>
			<article <?php post_class( 'md-doc-article' ); ?> id="md-doc-article" data-md-doc-id="<?php the_ID(); ?>" aria-live="polite" tabindex="-1">
				<div class="md-ajax-progress" data-md-ajax-progress hidden><span class="md-ajax-progress__bar"></span></div>

				<header class="md-doc-header">
					<div data-md-breadcrumbs class="md-doc-header__crumbs">
						<?php manual_docs_breadcrumbs(); ?>
					</div>

					<div class="md-doc-header__title-row">
						<h1 class="md-doc-title" data-md-doc-title><?php the_title(); ?></h1>
						<div class="md-doc-header__version" data-md-version-slot>
							<?php manual_docs_render_version_switcher(); ?>
						</div>
					</div>

					<?php if ( ! $is_compare ) : ?>
					<div class="md-doc-meta-bar" data-md-doc-meta>
						<?php if ( $show_updated ) : ?>
							<span class="md-meta-item md-meta-updated" data-md-modified>
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18M8 3v4M16 3v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
								<?php echo esc_html( sprintf( __( 'Updated on %s', 'manual-docs' ), get_the_modified_date() ) ); ?>
							</span>
						<?php endif; ?>

						<?php if ( $show_edit && current_user_can( 'edit_post', get_the_ID() ) ) : ?>
							<a class="md-meta-item md-meta-edit" href="<?php echo esc_url( get_edit_post_link() ); ?>">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16v4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
								<?php esc_html_e( 'Edit', 'manual-docs' ); ?>
							</a>
						<?php endif; ?>

						<?php if ( $show_pdf ) : ?>
							<a class="md-meta-item md-meta-pdf" data-md-pdf-btn href="<?php echo esc_url( manual_docs_get_pdf_url( get_the_ID(), true ) ); ?>" target="_blank" rel="noopener" title="<?php esc_attr_e( 'Download PDF', 'manual-docs' ); ?>">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8l-5-5z" stroke="currentColor" stroke-width="2"/><path d="M14 3v5h5M8 13h8M8 17h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
								<span><?php esc_html_e( 'PDF', 'manual-docs' ); ?></span>
							</a>
						<?php endif; ?>

						<span class="md-badge" data-md-version-badge <?php echo $version ? '' : 'hidden'; ?>>
							<?php echo $version ? esc_html( $version['name'] ) : ''; ?>
						</span>
					</div>
					<?php endif; ?>
				</header>

				<?php if ( $is_compare ) : ?>
					<div class="md-doc-layout md-doc-layout--no-toc">
						<div class="md-doc-content" id="md-doc-content" data-md-doc-content>
							<?php manual_docs_render_version_diff_view( get_the_ID(), $compare_to ); ?>
						</div>
					</div>
				<?php else : ?>
				<div class="md-doc-layout <?php echo $show_toc ? '' : 'md-doc-layout--no-toc'; ?>">
					<div class="md-doc-content entry-content" id="md-doc-content" data-md-doc-content>
						<?php the_content(); ?>
					</div>

					<?php if ( $show_toc ) : ?>
						<aside class="md-doc-toc" data-md-toc aria-label="<?php esc_attr_e( 'On this page', 'manual-docs' ); ?>">
							<div class="md-doc-toc__card">
								<p class="md-doc-toc__title">
									<span class="md-doc-toc__title-label"><?php esc_html_e( 'On this page', 'manual-docs' ); ?></span>
									<button type="button" class="md-doc-toc__hide" data-md-toc-toggle aria-expanded="true" aria-controls="md-toc-list" title="<?php esc_attr_e( 'Hide table of contents', 'manual-docs' ); ?>"><?php esc_html_e( 'hide', 'manual-docs' ); ?></button>
								</p>
								<nav id="md-toc-list" class="md-doc-toc__list" data-md-toc-list></nav>
							</div>
						</aside>
					<?php endif; ?>
				</div>

				<?php $adjacent = manual_docs_adjacent_docs(); ?>
				<nav class="md-doc-pager" data-md-pager aria-label="<?php esc_attr_e( 'Document navigation', 'manual-docs' ); ?>">
					<?php echo manual_docs_get_pager_html( $adjacent ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</nav>

				<div data-md-community-slot>
					<?php if ( manual_docs_get_option( 'show_community_cta', true ) ) { manual_docs_render_community_cta(); } ?>
				</div>
				<?php endif; ?>
			</article>
		<?php endwhile; ?>
	</div>
</main>

<?php
get_footer();
