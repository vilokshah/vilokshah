<?php
/**
 * Topics Loop — single topic card
 *
 * @package ManualDocs
 * @subpackage bbPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$topic_id  = bbp_get_topic_id();
$author_id = bbp_get_topic_author_id( $topic_id );
$forum_id  = bbp_get_topic_forum_id( $topic_id );
$sticky    = bbp_is_topic_sticky( $topic_id );
$views     = function_exists( 'manual_docs_get_topic_views' ) ? manual_docs_get_topic_views( $topic_id ) : 0;
$replies   = (int) bbp_get_topic_reply_count( $topic_id );
$excerpt   = wp_trim_words( wp_strip_all_tags( bbp_get_topic_content( $topic_id ) ), 28 );
$last_id   = (int) get_post_meta( $topic_id, '_bbp_last_active_id', true );
if ( ! $last_id ) {
	$last_id = $topic_id;
}
$last_author = (int) get_post_field( 'post_author', $last_id );
$last_name   = $last_author ? get_the_author_meta( 'display_name', $last_author ) : '';
$author_name = bbp_get_topic_author_display_name( $topic_id );
$forum_title = $forum_id ? bbp_get_forum_title( $forum_id ) : '';
$forum_link  = $forum_id ? bbp_get_forum_permalink( $forum_id ) : '';
?>

<article <?php bbp_topic_class( $topic_id, array( 'md-topic-card' ) ); ?> id="topic-<?php echo esc_attr( (string) $topic_id ); ?>">
	<?php if ( $sticky ) : ?>
		<span class="md-topic-card__pin" title="<?php esc_attr_e( 'Pinned', 'manual-docs' ); ?>">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg>
			<span class="screen-reader-text"><?php esc_html_e( 'Pinned', 'manual-docs' ); ?></span>
		</span>
	<?php endif; ?>

	<div class="md-topic-card__avatar" aria-hidden="true">
		<?php echo manual_docs_community_avatar_html( $author_id, 48 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>

	<div class="md-topic-card__body">
		<p class="md-topic-card__meta-top">
			<span class="md-topic-card__asker"><?php echo esc_html( $author_name ); ?></span>
			<?php if ( $forum_title && $forum_link ) : ?>
				<?php esc_html_e( 'asked in', 'manual-docs' ); ?>
				<a class="md-topic-card__forum" href="<?php echo esc_url( $forum_link ); ?>"><?php echo esc_html( $forum_title ); ?></a>
			<?php endif; ?>
		</p>

		<h3 class="md-topic-card__title">
			<a class="bbp-topic-permalink" href="<?php bbp_topic_permalink(); ?>"><?php bbp_topic_title(); ?></a>
		</h3>

		<?php
		if ( function_exists( 'manual_docs_academy_fields_badge_html' ) ) {
			echo manual_docs_academy_fields_badge_html( $topic_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>

		<?php if ( $excerpt ) : ?>
			<p class="md-topic-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
		<?php endif; ?>

		<footer class="md-topic-card__footer">
			<span class="md-topic-card__stat" title="<?php esc_attr_e( 'Views', 'manual-docs' ); ?>">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z" stroke="currentColor" stroke-width="1.75"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.75"/></svg>
				<?php echo esc_html( number_format_i18n( $views ) ); ?>
			</span>
			<span class="md-topic-card__stat" title="<?php esc_attr_e( 'Replies', 'manual-docs' ); ?>">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16v10H8l-4 4V5z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/></svg>
				<?php echo esc_html( number_format_i18n( $replies ) ); ?>
			</span>
			<?php if ( $last_name ) : ?>
				<span class="md-topic-card__last">
					<span class="md-topic-card__last-avatar" aria-hidden="true"><?php echo manual_docs_community_avatar_html( $last_author, 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="md-topic-card__last-name"><?php echo esc_html( $last_name ); ?></span>
				</span>
			<?php endif; ?>
			<span class="md-topic-card__time">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.75"/><path d="M12 8v5l3 2" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
				<?php bbp_topic_freshness_link(); ?>
			</span>
		</footer>
	</div>
</article>
