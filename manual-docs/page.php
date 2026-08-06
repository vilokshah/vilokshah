<?php
/**
 * Page template.
 *
 * @package ManualDocs
 */

get_header();
?>

<main id="main-content" class="md-main md-main--page">
	<div class="md-container md-container--narrow">
		<?php while ( have_posts() ) : ?>
			<?php the_post(); ?>
			<article <?php post_class( 'md-page' ); ?>>
				<header class="md-page-header">
					<h1 class="md-page-title"><?php the_title(); ?></h1>
				</header>
				<div class="entry-content">
					<?php the_content(); ?>
				</div>
			</article>
		<?php endwhile; ?>
	</div>
</main>

<?php
get_footer();