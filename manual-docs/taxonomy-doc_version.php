<?php
/**
 * Doc version taxonomy archive.
 *
 * @package ManualDocs
 */

get_header();
?>

<main id="main-content" class="md-main md-main--docs-archive">
	<div class="md-docs-shell">
		<aside class="md-docs-sidebar" id="md-docs-sidebar">
			<nav class="md-docs-sidebar__nav"><?php manual_docs_render_doc_nav(); ?></nav>
		</aside>
		<button type="button" class="md-sidebar-toggle" data-md-sidebar-toggle aria-controls="md-docs-sidebar" aria-expanded="false">
			<?php esc_html_e( 'Docs menu', 'manual-docs' ); ?>
		</button>
		<div class="md-archive">
			<header class="md-page-header">
				<p class="md-eyebrow"><?php esc_html_e( 'Version', 'manual-docs' ); ?></p>
				<h1 class="md-page-title"><?php single_term_title(); ?></h1>
			</header>
			<?php if ( have_posts() ) : ?>
				<ul class="md-doc-grid">
					<?php while ( have_posts() ) : ?>
						<?php the_post(); ?>
						<li><?php get_template_part( 'template-parts/content', 'doc-card' ); ?></li>
					<?php endwhile; ?>
				</ul>
				<?php the_posts_pagination(); ?>
			<?php else : ?>
				<p class="md-empty"><?php esc_html_e( 'No documents for this version.', 'manual-docs' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</main>

<?php
get_footer();