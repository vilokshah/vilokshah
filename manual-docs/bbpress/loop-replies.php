<?php
/**
 * Replies Loop — card layout
 *
 * @package ManualDocs
 * @subpackage bbPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php do_action( 'bbp_template_before_replies_loop' ); ?>

<ul id="topic-<?php bbp_topic_id(); ?>-replies" class="forums bbp-replies md-reply-list">
	<li class="bbp-body md-reply-list__body">
		<?php
		if ( bbp_thread_replies() ) :
			bbp_list_replies();
		else :
			while ( bbp_replies() ) :
				bbp_the_reply();
				bbp_get_template_part( 'loop', 'single-reply' );
			endwhile;
		endif;
		?>
	</li>

	<li class="bbp-footer md-reply-list__footer">
		<div class="tr">
			<div>
				<span class="td colspan<?php echo ( bbp_show_lead_topic() ) ? '2' : '3'; ?>"><?php bbp_topic_reply_count( 0, true ); ?></span>
			</div>
		</div><!-- .tr -->
	</li>
</ul><!-- #topic-<?php bbp_topic_id(); ?>-replies -->

<?php do_action( 'bbp_template_after_replies_loop' ); ?>
