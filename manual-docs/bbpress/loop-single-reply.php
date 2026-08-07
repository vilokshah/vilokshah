<?php
/**
 * Replies Loop — single reply card
 *
 * @package ManualDocs
 * @subpackage bbPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$reply_id  = bbp_get_reply_id();
$author_id = bbp_get_reply_author_id( $reply_id );
?>

<article <?php bbp_reply_class( $reply_id, array( 'md-reply-card' ) ); ?> id="post-<?php echo esc_attr( (string) $reply_id ); ?>">
	<header class="md-reply-card__header">
		<div class="md-reply-card__author">
			<span class="md-reply-card__avatar" aria-hidden="true">
				<?php echo function_exists( 'manual_docs_community_avatar_html' ) ? manual_docs_community_avatar_html( $author_id, 44 ) : get_avatar( $author_id, 44 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</span>
			<div class="md-reply-card__who">
				<span class="md-reply-card__name"><?php bbp_reply_author_link( array( 'type' => 'name' ) ); ?></span>
				<span class="md-reply-card__role"><?php bbp_reply_author_role(); ?></span>
			</div>
		</div>
		<div class="md-reply-card__meta">
			<a href="<?php bbp_reply_url(); ?>" class="md-reply-card__time"><?php bbp_reply_post_date(); ?></a>
			<span class="md-reply-card__admin"><?php bbp_reply_admin_links(); ?></span>
		</div>
	</header>
	<div class="md-reply-card__content">
		<?php do_action( 'bbp_theme_before_reply_content' ); ?>
		<?php bbp_reply_content(); ?>
		<?php do_action( 'bbp_theme_after_reply_content' ); ?>
	</div>
</article>
