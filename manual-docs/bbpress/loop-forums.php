<?php
/**
 * Forums Loop
 *
 * @package ManualDocs
 * @subpackage bbPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php do_action( 'bbp_template_before_forums_loop' ); ?>

<ul id="forums-list-<?php bbp_forum_id(); ?>" class="bbp-forums md-forum-list">
	<li class="bbp-body md-forum-list__body">
		<?php
		while ( bbp_forums() ) :
			bbp_the_forum();
			bbp_get_template_part( 'loop', 'single-forum' );
		endwhile;
		?>
	</li>
</ul>

<?php do_action( 'bbp_template_after_forums_loop' ); ?>
