<?php
/**
 * bbPress community integration.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether bbPress is active.
 *
 * @return bool
 */
function manual_docs_bbpress_active() {
	return class_exists( 'bbPress' );
}

/**
 * Enqueue bbPress-friendly styles when on forum pages.
 */
function manual_docs_bbpress_assets() {
	if ( ! manual_docs_bbpress_active() || ! function_exists( 'is_bbpress' ) || ! is_bbpress() ) {
		return;
	}

	wp_enqueue_style(
		'manual-docs-bbpress',
		MANUAL_DOCS_URI . '/assets/css/bbpress.css',
		array( 'manual-docs-main' ),
		MANUAL_DOCS_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'manual_docs_bbpress_assets', 20 );

/**
 * Community nav fallback links.
 */
function manual_docs_community_links() {
	if ( ! manual_docs_bbpress_active() ) {
		return array();
	}

	$links = array();

	if ( function_exists( 'bbp_get_forums_url' ) ) {
		$links[] = array(
			'label' => __( 'Forums', 'manual-docs' ),
			'url'   => bbp_get_forums_url(),
		);
	}

	if ( function_exists( 'bbp_get_topics_url' ) ) {
		$links[] = array(
			'label' => __( 'Topics', 'manual-docs' ),
			'url'   => bbp_get_topics_url(),
		);
	}

	if ( is_user_logged_in() && function_exists( 'bbp_get_user_profile_url' ) ) {
		$links[] = array(
			'label' => __( 'My Profile', 'manual-docs' ),
			'url'   => bbp_get_user_profile_url( get_current_user_id() ),
		);
	}

	return apply_filters( 'manual_docs_community_links', $links );
}

/**
 * Soften bbPress breadcrumbs into theme style via CSS class wrapper.
 *
 * @param string $content Content.
 * @return string
 */
function manual_docs_bbpress_wrapper_class( $content ) {
	return $content;
}

/**
 * Sample forum / topic / reply definitions (5 / 30 / 40).
 *
 * @return array
 */
function manual_docs_bbpress_sample_blueprint() {
	return array(
		array(
			'slug'        => 'general-discussion',
			'title'       => __( 'General Discussion', 'manual-docs' ),
			'content'     => __( 'Announcements, introductions, and community chat.', 'manual-docs' ),
			'topics'      => array(
				__( 'Welcome — introduce yourself', 'manual-docs' ),
				__( 'Where should new questions go?', 'manual-docs' ),
				__( 'Forum guidelines reminder', 'manual-docs' ),
				__( 'Share a recent win', 'manual-docs' ),
				__( 'What are you working on this week?', 'manual-docs' ),
				__( 'Off-topic but useful links', 'manual-docs' ),
			),
			'reply_counts' => array( 3, 2, 2, 1, 1, 0 ),
		),
		array(
			'slug'        => 'getting-started',
			'title'       => __( 'Getting Started', 'manual-docs' ),
			'content'     => __( 'Onboarding questions, first installs, and setup tips.', 'manual-docs' ),
			'topics'      => array(
				__( 'First-time install checklist', 'manual-docs' ),
				__( 'Login / SSO confusion', 'manual-docs' ),
				__( 'Where is the docs portal?', 'manual-docs' ),
				__( 'Permissions for editors vs readers', 'manual-docs' ),
				__( 'Importing sample documentation', 'manual-docs' ),
				__( 'Local vs staging setup', 'manual-docs' ),
			),
			'reply_counts' => array( 3, 2, 2, 1, 1, 0 ),
		),
		array(
			'slug'        => 'platform-features',
			'title'       => __( 'Platform & Features', 'manual-docs' ),
			'content'     => __( 'How the product works day to day.', 'manual-docs' ),
			'topics'      => array(
				__( 'Search not returning expected pages', 'manual-docs' ),
				__( 'Version switcher behaviour', 'manual-docs' ),
				__( 'PDF download tips', 'manual-docs' ),
				__( 'TOC collapse / expand UX', 'manual-docs' ),
				__( 'Dark vs light theme preferences', 'manual-docs' ),
				__( 'Keyboard shortcuts wish list', 'manual-docs' ),
			),
			'reply_counts' => array( 2, 2, 2, 1, 1, 0 ),
		),
		array(
			'slug'        => 'releases-upgrades',
			'title'       => __( 'Releases & Upgrades', 'manual-docs' ),
			'content'     => __( 'Release notes discussion and upgrade help.', 'manual-docs' ),
			'topics'      => array(
				__( 'Goat → Flamingo upgrade notes', 'manual-docs' ),
				__( 'What changed in Hummingbird?', 'manual-docs' ),
				__( 'Breaking changes checklist', 'manual-docs' ),
				__( 'Rollback strategy discussion', 'manual-docs' ),
				__( 'Release cadence feedback', 'manual-docs' ),
				__( 'Deprecation notices — how loud?', 'manual-docs' ),
			),
			'reply_counts' => array( 2, 2, 1, 1, 1, 0 ),
		),
		array(
			'slug'        => 'tips-best-practices',
			'title'       => __( 'Tips & Best Practices', 'manual-docs' ),
			'content'     => __( 'Patterns, checklists, and shared experience.', 'manual-docs' ),
			'topics'      => array(
				__( 'Writing clearer troubleshooting steps', 'manual-docs' ),
				__( 'Screenshot conventions', 'manual-docs' ),
				__( 'How we structure nested docs', 'manual-docs' ),
				__( 'Keeping forums and docs in sync', 'manual-docs' ),
				__( 'Tagging topics effectively', 'manual-docs' ),
				__( 'Onboarding buddies for new members', 'manual-docs' ),
			),
			'reply_counts' => array( 2, 2, 1, 1, 1, 0 ),
		),
	);
}

/**
 * Install sample bbPress forums / topics / replies (5 / 30 / 40).
 *
 * Idempotent: skips if a forum with slug general-discussion already exists
 * and was marked as Manual Docs sample data.
 *
 * @return true|WP_Error|array Counts on success.
 */
function manual_docs_install_bbpress_sample() {
	if ( ! manual_docs_bbpress_active() ) {
		return new WP_Error( 'no_bbpress', __( 'Activate the bbPress plugin first.', 'manual-docs' ) );
	}

	$existing = get_page_by_path( 'general-discussion', OBJECT, 'forum' );
	if ( $existing && get_post_meta( $existing->ID, '_manual_docs_bbpress_sample', true ) ) {
		return new WP_Error(
			'exists',
			sprintf(
				/* translators: %s: forums URL */
				__( 'Sample forums already installed. %s', 'manual-docs' ),
				'<a href="' . esc_url( get_permalink( $existing ) ) . '">' . esc_html__( 'Open General Discussion', 'manual-docs' ) . '</a>'
			)
		);
	}

	$user_id = get_current_user_id() ? get_current_user_id() : 1;
	$created = array(
		'forums'  => 0,
		'topics'  => 0,
		'replies' => 0,
	);

	$reply_snippets = array(
		__( 'Thanks for starting this — I would start with the docs portal search.', 'manual-docs' ),
		__( 'We hit something similar on staging. Clearing permalinks and re-saving Forums settings helped.', 'manual-docs' ),
		__( '+1. Also check whether bbPress is active and that Manual Docs is the current theme.', 'manual-docs' ),
		__( 'Here is a short checklist: confirm version roots, flush rewrite rules, then retest.', 'manual-docs' ),
		__( 'If counts look wrong after import, run Tools → Forums → Repair.', 'manual-docs' ),
	);

	foreach ( manual_docs_bbpress_sample_blueprint() as $fi => $forum_def ) {
		$forum_args = array(
			'post_title'   => $forum_def['title'],
			'post_content' => $forum_def['content'],
			'post_name'    => $forum_def['slug'],
			'post_status'  => 'publish',
			'post_author'  => $user_id,
			'menu_order'   => $fi,
		);

		if ( function_exists( 'bbp_insert_forum' ) ) {
			$forum_id = bbp_insert_forum( $forum_args );
		} else {
			$forum_args['post_type'] = 'forum';
			$forum_id                = wp_insert_post( $forum_args, true );
		}

		if ( is_wp_error( $forum_id ) || ! $forum_id ) {
			return is_wp_error( $forum_id ) ? $forum_id : new WP_Error( 'forum_fail', __( 'Could not create a sample forum.', 'manual-docs' ) );
		}

		update_post_meta( (int) $forum_id, '_manual_docs_bbpress_sample', 1 );
		++$created['forums'];

		foreach ( $forum_def['topics'] as $ti => $topic_title ) {
			$topic_content = '<p>' . esc_html(
				sprintf(
					/* translators: %s: forum title */
					__( 'Sample topic in %s. Use this thread to try the Manual Docs + bbPress look in light and dark themes.', 'manual-docs' ),
					$forum_def['title']
				)
			) . '</p>';

			$topic_args = array(
				'post_parent'  => (int) $forum_id,
				'post_title'   => $topic_title,
				'post_content' => $topic_content,
				'post_status'  => 'publish',
				'post_author'  => $user_id,
				'menu_order'   => $ti,
			);

			if ( function_exists( 'bbp_insert_topic' ) ) {
				$topic_id = bbp_insert_topic(
					$topic_args,
					array(
						'forum_id' => (int) $forum_id,
					)
				);
			} else {
				$topic_args['post_type'] = 'topic';
				$topic_id                = wp_insert_post( $topic_args, true );
				if ( ! is_wp_error( $topic_id ) && $topic_id ) {
					update_post_meta( (int) $topic_id, '_bbp_forum_id', (int) $forum_id );
					update_post_meta( (int) $topic_id, '_bbp_topic_id', (int) $topic_id );
				}
			}

			if ( is_wp_error( $topic_id ) || ! $topic_id ) {
				continue;
			}

			update_post_meta( (int) $topic_id, '_manual_docs_bbpress_sample', 1 );
			++$created['topics'];

			$reply_n = isset( $forum_def['reply_counts'][ $ti ] ) ? (int) $forum_def['reply_counts'][ $ti ] : 0;
			for ( $ri = 0; $ri < $reply_n; $ri++ ) {
				$reply_content = '<p>' . esc_html( $reply_snippets[ $ri % count( $reply_snippets ) ] ) . '</p>';
				$reply_args    = array(
					'post_parent'  => (int) $topic_id,
					'post_title'   => sprintf(
						/* translators: %s: topic title */
						__( 'Reply to: %s', 'manual-docs' ),
						$topic_title
					),
					'post_content' => $reply_content,
					'post_status'  => 'publish',
					'post_author'  => $user_id,
				);

				if ( function_exists( 'bbp_insert_reply' ) ) {
					$reply_id = bbp_insert_reply(
						$reply_args,
						array(
							'forum_id' => (int) $forum_id,
							'topic_id' => (int) $topic_id,
						)
					);
				} else {
					$reply_args['post_type'] = 'reply';
					$reply_id                = wp_insert_post( $reply_args, true );
					if ( ! is_wp_error( $reply_id ) && $reply_id ) {
						update_post_meta( (int) $reply_id, '_bbp_forum_id', (int) $forum_id );
						update_post_meta( (int) $reply_id, '_bbp_topic_id', (int) $topic_id );
					}
				}

				if ( ! is_wp_error( $reply_id ) && $reply_id ) {
					update_post_meta( (int) $reply_id, '_manual_docs_bbpress_sample', 1 );
					++$created['replies'];
				}
			}
		}
	}

	if ( function_exists( 'bbp_get_forums_url' ) ) {
		$created['url'] = bbp_get_forums_url();
	}

	return $created;
}

/**
 * Add community CTA on documentation pages.
 */
function manual_docs_render_community_cta() {
	if ( ! manual_docs_bbpress_active() || ! manual_docs_get_option( 'show_community_cta', true ) ) {
		return;
	}

	$forum_url = function_exists( 'bbp_get_forums_url' ) ? bbp_get_forums_url() : home_url( '/forums/' );
	?>
	<aside class="md-community-cta">
		<div class="md-community-cta__inner">
			<h3><?php esc_html_e( 'Need help from the community?', 'manual-docs' ); ?></h3>
			<p><?php esc_html_e( 'Ask questions, share tips, and connect with other members in the forums.', 'manual-docs' ); ?></p>
			<a class="md-btn md-btn--primary" href="<?php echo esc_url( $forum_url ); ?>"><?php esc_html_e( 'Visit Forums', 'manual-docs' ); ?></a>
		</div>
	</aside>
	<?php
}