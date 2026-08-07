<?php
/**
 * Topics Loop
 *
 * @package ManualDocs
 * @subpackage bbPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php do_action( 'bbp_template_before_topics_loop' ); ?>

<ul id="bbp-forum-<?php bbp_forum_id(); ?>" class="bbp-topics md-topic-list">
	<li class="bbp-body md-topic-list__body">
		<?php
		while ( bbp_topics() ) :
			bbp_the_topic();
			bbp_get_template_part( 'loop', 'single-topic' );
		endwhile;
		?>
	</li>

	<li class="bbp-footer md-topic-list__footer">
		<div class="tr">
			<p>
				<span class="td colspan<?php echo ( bbp_is_user_home() && ( bbp_is_favorites() || bbp_is_subscriptions() ) ) ? '4' : '3'; ?>">
					<?php bbp_topic_pagination_count(); ?>
				</span>
			</p>
		</div><!-- .tr -->
	</li>
</ul><!-- #bbp-forum-<?php bbp_forum_id(); ?> -->

<?php do_action( 'bbp_template_after_topics_loop' ); ?>
