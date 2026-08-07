<?php
/**
 * User profile body — production-ready member summary.
 *
 * @package ManualDocs
 * @subpackage bbPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'bbp_template_before_user_profile' );

$nicename    = bbp_get_displayed_user_field( 'user_nicename' );
$display     = bbp_get_displayed_user_field( 'display_name' );
$description = bbp_get_displayed_user_field( 'description' );
$website     = bbp_get_displayed_user_field( 'user_url' );
$registered  = bbp_get_displayed_user_field( 'user_registered' );
$role        = function_exists( 'bbp_get_user_display_role' ) ? bbp_get_user_display_role() : '';
$topics      = function_exists( 'bbp_get_user_topic_count' ) ? bbp_get_user_topic_count() : 0;
$replies     = function_exists( 'bbp_get_user_reply_count' ) ? bbp_get_user_reply_count() : 0;
$last        = function_exists( 'bbp_get_user_last_posted' ) ? bbp_get_user_last_posted() : 0;
?>

<section id="bbp-user-profile" class="bbp-user-profile md-profile">
	<header class="md-profile__header">
		<h2 class="md-profile__title"><?php echo esc_html( $display ? $display : $nicename ); ?></h2>
		<p class="md-profile__handle">@<?php echo esc_html( $nicename ); ?></p>
	</header>

	<?php if ( $description ) : ?>
		<div class="md-profile__bio"><?php echo bbp_rel_nofollow( $description ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	<?php endif; ?>

	<div class="md-profile__stats" role="list">
		<div class="md-profile__stat" role="listitem">
			<span class="md-profile__stat-label"><?php esc_html_e( 'Topics', 'manual-docs' ); ?></span>
			<span class="md-profile__stat-value"><?php echo esc_html( number_format_i18n( (int) $topics ) ); ?></span>
		</div>
		<div class="md-profile__stat" role="listitem">
			<span class="md-profile__stat-label"><?php esc_html_e( 'Replies', 'manual-docs' ); ?></span>
			<span class="md-profile__stat-value"><?php echo esc_html( number_format_i18n( (int) $replies ) ); ?></span>
		</div>
		<?php if ( $role ) : ?>
			<div class="md-profile__stat" role="listitem">
				<span class="md-profile__stat-label"><?php esc_html_e( 'Role', 'manual-docs' ); ?></span>
				<span class="md-profile__stat-value"><?php echo esc_html( $role ); ?></span>
			</div>
		<?php endif; ?>
	</div>

	<dl class="md-profile__meta">
		<?php if ( $registered ) : ?>
			<div class="md-profile__meta-row">
				<dt><?php esc_html_e( 'Joined', 'manual-docs' ); ?></dt>
				<dd><?php echo esc_html( bbp_get_time_since( $registered ) ); ?></dd>
			</div>
		<?php endif; ?>
		<?php if ( $last ) : ?>
			<div class="md-profile__meta-row">
				<dt><?php esc_html_e( 'Last activity', 'manual-docs' ); ?></dt>
				<dd><?php echo esc_html( bbp_get_time_since( $last, false, true ) ); ?></dd>
			</div>
		<?php endif; ?>
		<?php if ( $website ) : ?>
			<div class="md-profile__meta-row">
				<dt><?php esc_html_e( 'Website', 'manual-docs' ); ?></dt>
				<dd><?php echo bbp_rel_nofollow( bbp_make_clickable( $website ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></dd>
			</div>
		<?php endif; ?>
	</dl>
</section>

<?php
do_action( 'bbp_template_after_user_profile' );
