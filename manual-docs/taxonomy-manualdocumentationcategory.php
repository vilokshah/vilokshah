<?php
/**
 * Documentation category archive (manualdocumentationcategory).
 *
 * @package ManualDocs
 */

get_header();
$term = get_queried_object();
?>

<main id="main-content" class="md-main md-main--docs-archive">
	<div class="md-docs-shell">
		<aside class="md-docs-sidebar" id="md-docs-sidebar" aria-label="<?php esc_attr_e( 'Documentation navigation', 'manual-docs' ); ?>">
			<div class="md-docs-sidebar__toolbar">
				<?php manual_docs_render_tree_collapse_button(); ?>
			</div>
			<div class="md-docs-sidebar__body">
				<div class="md-docs-sidebar__search">
					<?php manual_docs_render_live_search( array( 'class' => 'md-live-search--sidebar' ) ); ?>
				</div>
				<nav class="md-docs-sidebar__nav" data-md-doc-tree>
					<?php manual_docs_render_doc_nav(); ?>
				</nav>
			</div>
		</aside>

		<button type="button" class="md-sidebar-toggle" data-md-sidebar-toggle aria-controls="md-docs-sidebar" aria-expanded="false">
			<?php esc_html_e( 'Docs menu', 'manual-docs' ); ?>
		</button>

		<div class="md-archive">
			<header class="md-page-header">
				<p class="md-eyebrow"><?php esc_html_e( 'Category', 'manual-docs' ); ?></p>
				<h1 class="md-page-title"><?php single_term_title(); ?></h1>
				<?php if ( $term && ! empty( $term->description ) ) : ?>
					<p class="md-page-desc"><?php echo esc_html( $term->description ); ?></p>
				<?php endif; ?>
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
				<p class="md-empty"><?php esc_html_e( 'No documents in this category.', 'manual-docs' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</main>

<?php
get_footer();