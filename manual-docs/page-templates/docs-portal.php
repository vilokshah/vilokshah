<?php
/**
 * Template Name: Docs Portal
 * Description: DigiDocs homepage — brand hero with live search.
 *
 * @package ManualDocs
 */

get_header();

$brand      = manual_docs_brand_name();
$hero_title = manual_docs_get_option( 'hero_title', __( 'Documentation', 'manual-docs' ) );
$hero_text  = manual_docs_get_option( 'hero_text', __( 'Search guides, explore products, and find answers fast.', 'manual-docs' ) );
$eyebrow    = manual_docs_get_option( 'hero_eyebrow', '' );
?>

<main id="main-content" class="md-main md-main--home">
	<section class="md-hero md-hero--search-only">
		<div class="md-hero__atmosphere" aria-hidden="true"></div>
		<div class="md-hero__grid" aria-hidden="true"></div>
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
			<p class="md-hero__hint"><?php esc_html_e( 'Start typing to search across all releases.', 'manual-docs' ); ?></p>
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
