<?php
/**
 * Forums Loop — single forum card
 *
 * @package ManualDocs
 * @subpackage bbPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$forum_id = bbp_get_forum_id();
$topics   = (int) bbp_get_forum_topic_count( $forum_id, true );
$replies  = (int) bbp_get_forum_reply_count( $forum_id, true );
$desc     = bbp_get_forum_content( $forum_id );
$is_acad  = function_exists( 'manual_docs_is_academy_forum' ) && manual_docs_is_academy_forum( $forum_id );
?>

<article <?php bbp_forum_class( $forum_id, array( 'md-forum-card' ) ); ?>>
	<div class="md-forum-card__body">
		<h3 class="md-forum-card__title">
			<a class="bbp-forum-title" href="<?php bbp_forum_permalink(); ?>"><?php bbp_forum_title(); ?></a>
			<?php if ( $is_acad ) : ?>
				<span class="md-forum-card__badge"><?php esc_html_e( 'Academy', 'manual-docs' ); ?></span>
			<?php endif; ?>
		</h3>
		<?php if ( $desc ) : ?>
			<div class="md-forum-card__desc"><?php echo wp_kses_post( wpautop( wp_trim_words( wp_strip_all_tags( $desc ), 36 ) ) ); ?></div>
		<?php endif; ?>
		<footer class="md-forum-card__footer">
			<span><?php echo esc_html( number_format_i18n( $topics ) ); ?> <?php esc_html_e( 'Topics', 'manual-docs' ); ?></span>
			<span><?php echo esc_html( number_format_i18n( $replies ) ); ?> <?php esc_html_e( 'Replies', 'manual-docs' ); ?></span>
			<span class="md-forum-card__fresh"><?php bbp_forum_freshness_link(); ?></span>
		</footer>
	</div>
</article>
