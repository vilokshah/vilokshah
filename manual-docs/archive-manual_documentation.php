<?php
/**
 * Documentation archive.
 *
 * @package ManualDocs
 */

get_header();
?>

<main id="main-content" class="md-main md-main--docs-archive">
	<div class="md-docs-shell">
		<aside class="md-docs-sidebar" id="md-docs-sidebar" aria-label="<?php esc_attr_e( 'Documentation navigation', 'manual-docs' ); ?>">
			<div class="md-docs-sidebar__search">
				<?php manual_docs_render_live_search( array( 'class' => 'md-live-search--sidebar' ) ); ?>
			</div>
			<nav class="md-docs-sidebar__nav">
				<?php manual_docs_render_doc_nav(); ?>
			</nav>
		</aside>

		<button type="button" class="md-sidebar-toggle" data-md-sidebar-toggle aria-controls="md-docs-sidebar" aria-expanded="false">
			<?php esc_html_e( 'Docs menu', 'manual-docs' ); ?>
		</button>

		<div class="md-archive">
			<header class="md-page-header">
				<h1 class="md-page-title"><?php post_type_archive_title(); ?></h1>
				<p class="md-page-desc"><?php esc_html_e( 'Browse the full documentation library.', 'manual-docs' ); ?></p>
				<?php
				$versions = manual_docs_get_versions();
				if ( ! empty( $versions ) ) :
					?>
					<div class="md-archive-versions">
						<span><?php esc_html_e( 'Filter by version:', 'manual-docs' ); ?></span>
						<?php foreach ( $versions as $version ) : ?>
							<a class="md-badge md-badge--link" href="<?php echo esc_url( get_term_link( $version ) ); ?>"><?php echo esc_html( $version->name ); ?></a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</header>

			<?php if ( have_posts() ) : ?>
				<ul class="md-doc-grid">
					<?php while ( have_posts() ) : ?>
						<?php the_post(); ?>
						<li><?php get_template_part( 'template-parts/content', 'doc-card' ); ?></li>
					<?php endwhile; ?>
				</ul>
				<?php the_posts_pagination( array( 'mid_size' => 2 ) ); ?>
			<?php else : ?>
				<p class="md-empty"><?php esc_html_e( 'No documents found.', 'manual-docs' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</main>

<?php
get_footer();