<?php
/**
 * Document card partial.
 *
 * @package ManualDocs
 */

$tax  = manual_docs_category_taxonomy();
$cats = taxonomy_exists( $tax ) ? get_the_terms( get_the_ID(), $tax ) : false;
$cat  = ( ! empty( $cats ) && ! is_wp_error( $cats ) ) ? $cats[0] : null;
?>
<article <?php post_class( 'md-doc-card' ); ?>>
	<?php if ( $cat ) : ?>
		<a class="md-doc-card__cat" href="<?php echo esc_url( get_term_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
	<?php endif; ?>
	<h3 class="md-doc-card__title">
		<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
	</h3>
	<div class="md-doc-card__excerpt"><?php the_excerpt(); ?></div>
	<a class="md-doc-card__link" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read guide', 'manual-docs' ); ?> →</a>
</article>