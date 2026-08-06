<?php
/**
 * Comments template (kept minimal).
 *
 * @package ManualDocs
 */

if ( post_password_required() ) {
	return;
}
?>

<div id="comments" class="md-comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="md-comments__title">
			<?php
			printf(
				/* translators: %s: comment count */
				esc_html( _n( '%s comment', '%s comments', get_comments_number(), 'manual-docs' ) ),
				esc_html( number_format_i18n( get_comments_number() ) )
			);
			?>
		</h2>
		<ol class="md-comment-list">
			<?php
			wp_list_comments( array(
				'style'      => 'ol',
				'short_ping' => true,
			) );
			?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
		<p class="md-empty"><?php esc_html_e( 'Comments are closed.', 'manual-docs' ); ?></p>
	<?php endif; ?>

	<?php comment_form(); ?>
</div>