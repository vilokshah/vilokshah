<?php
/**
 * Main index template.
 *
 * @package ManualDocs
 */

get_header();
?>

<main id="main-content" class="md-main md-main--blog">
	<div class="md-container">
		<header class="md-page-header">
			<h1 class="md-page-title"><?php echo esc_html( get_the_title( get_option( 'page_for_posts' ) ) ?: __( 'Blog', 'manual-docs' ) ); ?></h1>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="md-post-list">
				<?php while ( have_posts() ) : ?>
					<?php the_post(); ?>
					<article <?php post_class( 'md-post-card' ); ?>>
						<h2 class="md-post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<p class="md-post-card__meta"><?php echo esc_html( get_the_date() ); ?></p>
						<div class="md-post-card__excerpt"><?php the_excerpt(); ?></div>
					</article>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'No posts found.', 'manual-docs' ); ?></p>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();