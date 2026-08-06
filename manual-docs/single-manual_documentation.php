<?php
/**
 * Single manual_documentation template.
 *
 * @package ManualDocs
 */

get_header();
?>

<main id="main-content" class="md-main md-main--docs">
	<div class="md-docs-shell">
		<aside class="md-docs-sidebar" id="md-docs-sidebar" aria-label="<?php esc_attr_e( 'Documentation navigation', 'manual-docs' ); ?>">
			<div class="md-docs-sidebar__search">
				<?php manual_docs_render_live_search( array( 'class' => 'md-live-search--sidebar' ) ); ?>
			</div>
			<nav class="md-docs-sidebar__nav">
				<?php
				if ( has_nav_menu( 'docs' ) ) {
					wp_nav_menu( array(
						'theme_location' => 'docs',
						'container'      => false,
						'menu_class'     => 'md-doc-nav',
						'depth'          => 3,
					) );
				} else {
					manual_docs_render_doc_nav();
				}
				?>
			</nav>
			<?php if ( is_active_sidebar( 'docs-sidebar' ) ) : ?>
				<div class="md-docs-sidebar__widgets">
					<?php dynamic_sidebar( 'docs-sidebar' ); ?>
				</div>
			<?php endif; ?>
		</aside>

		<button type="button" class="md-sidebar-toggle" data-md-sidebar-toggle aria-controls="md-docs-sidebar" aria-expanded="false">
			<?php esc_html_e( 'Docs menu', 'manual-docs' ); ?>
		</button>

		<?php while ( have_posts() ) : ?>
			<?php the_post(); ?>
			<article <?php post_class( 'md-doc-article' ); ?>>
				<header class="md-doc-header">
					<?php manual_docs_breadcrumbs(); ?>
					<div class="md-doc-toolbar">
						<?php manual_docs_render_version_switcher(); ?>
						<?php manual_docs_render_pdf_button(); ?>
					</div>
					<h1 class="md-doc-title"><?php the_title(); ?></h1>
					<p class="md-doc-meta">
						<?php
						$version = manual_docs_get_doc_version();
						if ( $version ) {
							echo '<span class="md-badge">' . esc_html( sprintf( __( 'v%s', 'manual-docs' ), $version->name ) ) . '</span>';
						}
						?>
						<span><?php echo esc_html( sprintf( __( 'Updated %s', 'manual-docs' ), get_the_modified_date() ) ); ?></span>
					</p>
				</header>

				<div class="md-doc-layout">
					<div class="md-doc-content entry-content" id="md-doc-content">
						<?php the_content(); ?>
					</div>
					<aside class="md-doc-toc" aria-label="<?php esc_attr_e( 'On this page', 'manual-docs' ); ?>">
						<p class="md-doc-toc__title"><?php esc_html_e( 'On this page', 'manual-docs' ); ?></p>
						<nav id="md-toc-list" class="md-doc-toc__list"></nav>
					</aside>
				</div>

				<?php
				$adjacent = manual_docs_adjacent_docs();
				?>
				<nav class="md-doc-pager" aria-label="<?php esc_attr_e( 'Document navigation', 'manual-docs' ); ?>">
					<?php if ( $adjacent['prev'] ) : ?>
						<a class="md-doc-pager__link md-doc-pager__link--prev" href="<?php echo esc_url( get_permalink( $adjacent['prev'] ) ); ?>">
							<span><?php esc_html_e( 'Previous', 'manual-docs' ); ?></span>
							<strong><?php echo esc_html( get_the_title( $adjacent['prev'] ) ); ?></strong>
						</a>
					<?php else : ?>
						<span></span>
					<?php endif; ?>
					<?php if ( $adjacent['next'] ) : ?>
						<a class="md-doc-pager__link md-doc-pager__link--next" href="<?php echo esc_url( get_permalink( $adjacent['next'] ) ); ?>">
							<span><?php esc_html_e( 'Next', 'manual-docs' ); ?></span>
							<strong><?php echo esc_html( get_the_title( $adjacent['next'] ) ); ?></strong>
						</a>
					<?php endif; ?>
				</nav>

				<?php manual_docs_render_community_cta(); ?>
			</article>
		<?php endwhile; ?>
	</div>
</main>

<?php
get_footer();