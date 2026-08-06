<?php
/**
 * Search results.
 *
 * @package ManualDocs
 */

get_header();
?>

<main id="main-content" class="md-main">
	<div class="md-container">
		<header class="md-page-header">
			<h1 class="md-page-title">
				<?php
				printf(
					/* translators: %s: search query */
					esc_html__( 'Search results for “%s”', 'manual-docs' ),
					esc_html( get_search_query() )
				);
				?>
			</h1>
			<?php manual_docs_render_live_search(); ?>
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
			<p class="md-empty"><?php esc_html_e( 'No results found. Try a different keyword.', 'manual-docs' ); ?></p>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();