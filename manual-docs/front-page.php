<?php
/**
 * Front page — documentation portal.
 *
 * @package ManualDocs
 */

get_header();

$brand      = manual_docs_brand_name();
$hero_title = manual_docs_get_option( 'hero_title', __( 'Documentation', 'manual-docs' ) );
$hero_text  = manual_docs_get_option( 'hero_text', __( 'Search guides, explore products, and find answers fast.', 'manual-docs' ) );
$eyebrow    = manual_docs_get_option( 'hero_eyebrow', '' );
$categories = manual_docs_get_accessible_categories();
$versions   = manual_docs_get_version_roots();
?>

<main id="main-content" class="md-main md-main--home">
	<section class="md-hero">
		<div class="md-hero__atmosphere" aria-hidden="true"></div>
		<div class="md-container md-hero__content">
			<?php if ( $eyebrow ) : ?>
				<p class="md-hero__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>
			<p class="md-hero__brand"><?php echo esc_html( $brand ); ?></p>
			<h1 class="md-hero__title"><?php echo esc_html( $hero_title ); ?></h1>
			<p class="md-hero__text"><?php echo esc_html( $hero_text ); ?></p>
			<div class="md-hero__search">
				<?php manual_docs_render_live_search( array( 'class' => 'md-live-search--hero' ) ); ?>
			</div>
			<div class="md-hero__cta">
				<?php
				$default_slug = manual_docs_get_option( 'default_version_slug', '' );
				$start_url    = get_post_type_archive_link( 'manual_documentation' );
				if ( $versions ) {
					$start = $versions[0];
					foreach ( $versions as $v ) {
						if ( $default_slug && $v->post_name === $default_slug ) {
							$start = $v;
							break;
						}
					}
					$start_url = get_permalink( $start );
				}
				?>
				<a class="md-btn md-btn--primary" href="<?php echo esc_url( $start_url ); ?>">
					<?php esc_html_e( 'Browse documentation', 'manual-docs' ); ?>
				</a>
				<?php if ( manual_docs_bbpress_active() && function_exists( 'bbp_get_forums_url' ) ) : ?>
					<a class="md-btn md-btn--ghost" href="<?php echo esc_url( bbp_get_forums_url() ); ?>">
						<?php esc_html_e( 'Community', 'manual-docs' ); ?>
					</a>
				<?php endif; ?>
			</div>
			<?php if ( count( $versions ) > 1 ) : ?>
				<div class="md-hero-versions" aria-label="<?php esc_attr_e( 'Release versions', 'manual-docs' ); ?>">
					<?php foreach ( $versions as $v ) : ?>
						<a href="<?php echo esc_url( get_permalink( $v ) ); ?>"><?php echo esc_html( get_the_title( $v ) ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( count( $versions ) > 1 ) : ?>
		<section class="md-section">
			<div class="md-container">
				<header class="md-section__header">
					<h2><?php esc_html_e( 'Choose a release', 'manual-docs' ); ?></h2>
					<p><?php esc_html_e( 'Each release keeps the same documentation tree with version-specific content.', 'manual-docs' ); ?></p>
				</header>
				<ul class="md-version-grid">
					<?php foreach ( $versions as $v ) : ?>
						<li>
							<a class="md-version-tile" href="<?php echo esc_url( get_permalink( $v ) ); ?>">
								<span class="md-version-tile__name"><?php echo esc_html( get_the_title( $v ) ); ?></span>
								<span class="md-version-tile__meta"><?php esc_html_e( 'Open this release tree', 'manual-docs' ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<section class="md-section md-categories">
		<div class="md-container">
			<header class="md-section__header">
				<h2><?php esc_html_e( 'Browse by category', 'manual-docs' ); ?></h2>
				<p><?php esc_html_e( 'Explore documentation organized by product and topic.', 'manual-docs' ); ?></p>
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
				<p class="md-empty"><?php esc_html_e( 'Categories from manualdocumentationcategory will appear here.', 'manual-docs' ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( manual_docs_bbpress_active() ) : ?>
		<section class="md-section md-community-home">
			<div class="md-container md-community-home__panel">
				<div>
					<h2><?php esc_html_e( 'Community forums', 'manual-docs' ); ?></h2>
					<p><?php esc_html_e( 'Ask questions, share solutions, and learn with other members.', 'manual-docs' ); ?></p>
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