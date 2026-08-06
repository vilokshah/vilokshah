<?php
/**
 * Front page / documentation landing.
 *
 * @package ManualDocs
 */

get_header();

$hero_title = get_theme_mod( 'manual_docs_hero_title', __( 'Documentation', 'manual-docs' ) );
$hero_text  = get_theme_mod( 'manual_docs_hero_text', __( 'Find guides, references, and answers in one place.', 'manual-docs' ) );
$categories = function_exists( 'manual_docs_get_accessible_categories' ) ? manual_docs_get_accessible_categories() : array();
?>

<main id="main-content" class="md-main md-main--home">
	<section class="md-hero">
		<div class="md-hero__atmosphere" aria-hidden="true"></div>
		<div class="md-container md-hero__content">
			<p class="md-hero__brand"><?php bloginfo( 'name' ); ?></p>
			<h1 class="md-hero__title"><?php echo esc_html( $hero_title ); ?></h1>
			<p class="md-hero__text"><?php echo esc_html( $hero_text ); ?></p>
			<div class="md-hero__search">
				<?php manual_docs_render_live_search( array( 'class' => 'md-live-search--hero' ) ); ?>
			</div>
			<div class="md-hero__cta">
				<a class="md-btn md-btn--primary" href="<?php echo esc_url( get_post_type_archive_link( 'manual_documentation' ) ); ?>">
					<?php esc_html_e( 'Browse all docs', 'manual-docs' ); ?>
				</a>
				<?php if ( manual_docs_bbpress_active() && function_exists( 'bbp_get_forums_url' ) ) : ?>
					<a class="md-btn md-btn--ghost" href="<?php echo esc_url( bbp_get_forums_url() ); ?>">
						<?php esc_html_e( 'Join the community', 'manual-docs' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<section class="md-section md-categories">
		<div class="md-container">
			<header class="md-section__header">
				<h2><?php esc_html_e( 'Browse by category', 'manual-docs' ); ?></h2>
				<p><?php esc_html_e( 'Explore documentation organized by topic.', 'manual-docs' ); ?></p>
			</header>

			<?php if ( ! empty( $categories ) ) : ?>
				<ul class="md-category-grid">
					<?php foreach ( $categories as $term ) : ?>
						<li>
							<a class="md-category-tile" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
								<span class="md-category-tile__name"><?php echo esc_html( $term->name ); ?></span>
								<span class="md-category-tile__count">
									<?php
									printf(
										/* translators: %d: document count */
										esc_html( _n( '%d document', '%d documents', (int) $term->count, 'manual-docs' ) ),
										(int) $term->count
									);
									?>
								</span>
								<?php if ( $term->description ) : ?>
									<span class="md-category-tile__desc"><?php echo esc_html( wp_trim_words( $term->description, 16 ) ); ?></span>
								<?php endif; ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="md-empty"><?php esc_html_e( 'Categories will appear here once documentation is published.', 'manual-docs' ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<?php
	$featured = new WP_Query( array(
		'post_type'      => 'manual_documentation',
		'posts_per_page' => 6,
		'meta_key'       => '_manual_docs_featured',
		'meta_value'     => '1',
		'post_status'    => 'publish',
	) );

	if ( ! $featured->have_posts() ) {
		$featured = new WP_Query( array(
			'post_type'      => 'manual_documentation',
			'posts_per_page' => 6,
			'post_status'    => 'publish',
			'orderby'        => 'menu_order date',
			'order'          => 'ASC',
		) );
	}
	?>

	<?php if ( $featured->have_posts() ) : ?>
		<section class="md-section md-featured">
			<div class="md-container">
				<header class="md-section__header">
					<h2><?php esc_html_e( 'Popular guides', 'manual-docs' ); ?></h2>
					<p><?php esc_html_e( 'Start with these frequently referenced documents.', 'manual-docs' ); ?></p>
				</header>
				<ul class="md-doc-grid">
					<?php while ( $featured->have_posts() ) : ?>
						<?php $featured->the_post(); ?>
						<?php if ( ! manual_docs_user_can_view_doc( get_the_ID() ) ) { continue; } ?>
						<li><?php get_template_part( 'template-parts/content', 'doc-card' ); ?></li>
					<?php endwhile; ?>
					<?php wp_reset_postdata(); ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( manual_docs_bbpress_active() ) : ?>
		<section class="md-section md-community-home">
			<div class="md-container md-community-home__panel">
				<div>
					<h2><?php esc_html_e( 'Community forums', 'manual-docs' ); ?></h2>
					<p><?php esc_html_e( 'Discuss product questions, share solutions, and learn with other members.', 'manual-docs' ); ?></p>
				</div>
				<?php if ( function_exists( 'bbp_get_forums_url' ) ) : ?>
					<a class="md-btn md-btn--primary" href="<?php echo esc_url( bbp_get_forums_url() ); ?>"><?php esc_html_e( 'Open forums', 'manual-docs' ); ?></a>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>
</main>

<?php
get_footer();