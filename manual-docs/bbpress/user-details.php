<?php
/**
 * User profile navigation — clean community profile menu.
 *
 * Omits Favorites / Subscriptions / Engagements (removed from this theme).
 *
 * @package ManualDocs
 * @subpackage bbPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'bbp_template_before_user_details' );
?>

<div id="bbp-single-user-details" class="md-profile-aside">
	<div id="bbp-user-avatar" class="md-profile-aside__avatar">
		<span class="vcard">
			<a class="url fn n" href="<?php bbp_user_profile_url(); ?>" title="<?php bbp_displayed_user_field( 'display_name' ); ?>" rel="me">
				<?php echo get_avatar( bbp_get_displayed_user_field( 'user_email', 'raw' ), 120 ); ?>
			</a>
		</span>
		<p class="md-profile-aside__name"><?php bbp_displayed_user_field( 'display_name' ); ?></p>
		<?php if ( function_exists( 'bbp_get_user_display_role' ) ) : ?>
			<p class="md-profile-aside__role"><?php echo esc_html( bbp_get_user_display_role() ); ?></p>
		<?php endif; ?>
	</div>

	<?php do_action( 'bbp_template_before_user_details_menu_items' ); ?>

	<nav id="bbp-user-navigation" class="md-profile-nav" aria-label="<?php esc_attr_e( 'Profile menu', 'manual-docs' ); ?>">
		<ul>
			<li class="<?php echo bbp_is_single_user_profile() ? 'current' : ''; ?>">
				<a href="<?php bbp_user_profile_url(); ?>"><?php esc_html_e( 'Profile', 'manual-docs' ); ?></a>
			</li>
			<li class="<?php echo bbp_is_single_user_topics() ? 'current' : ''; ?>">
				<a href="<?php bbp_user_topics_created_url(); ?>"><?php esc_html_e( 'Topics', 'manual-docs' ); ?></a>
			</li>
			<li class="<?php echo bbp_is_single_user_replies() ? 'current' : ''; ?>">
				<a href="<?php bbp_user_replies_created_url(); ?>"><?php esc_html_e( 'Replies', 'manual-docs' ); ?></a>
			</li>
			<?php if ( bbp_is_user_home() || current_user_can( 'edit_user', bbp_get_displayed_user_id() ) ) : ?>
				<li class="<?php echo bbp_is_single_user_edit() ? 'current' : ''; ?>">
					<a href="<?php bbp_user_profile_edit_url(); ?>"><?php esc_html_e( 'Edit profile', 'manual-docs' ); ?></a>
				</li>
			<?php endif; ?>
		</ul>
	</nav>

	<?php do_action( 'bbp_template_after_user_details_menu_items' ); ?>
</div>

<?php
do_action( 'bbp_template_after_user_details' );
