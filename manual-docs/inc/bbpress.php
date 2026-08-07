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

	wp_enqueue_script(
		'manual-docs-community-ui',
		MANUAL_DOCS_URI . '/assets/js/community-ui.js',
		array(),
		MANUAL_DOCS_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'manual_docs_bbpress_assets', 20 );

/**
 * Resolve a reliable bbPress directory URL (avoids bare /forums|/topics 404s).
 *
 * @param string $which 'forums' or 'topics'.
 * @return string
 */
function manual_docs_bbpress_directory_url( $which = 'forums' ) {
	$which = ( 'topics' === $which ) ? 'topics' : 'forums';

	if ( 'forums' === $which ) {
		if ( function_exists( 'bbp_get_forums_url' ) ) {
			$url = bbp_get_forums_url();
			if ( $url ) {
				return $url;
			}
		}
		if ( function_exists( 'bbp_get_root_url' ) ) {
			$url = bbp_get_root_url();
			if ( $url ) {
				return $url;
			}
		}
		$archive = get_post_type_archive_link( 'forum' );
		return $archive ? $archive : home_url( '/?post_type=forum' );
	}

	if ( function_exists( 'bbp_get_topics_url' ) ) {
		$url = bbp_get_topics_url();
		if ( $url ) {
			return $url;
		}
	}
	$archive = get_post_type_archive_link( 'topic' );
	return $archive ? $archive : home_url( '/?post_type=topic' );
}

/**
 * Community nav fallback links.
 */
function manual_docs_community_links() {
	if ( ! manual_docs_bbpress_active() ) {
		return array();
	}

	$links   = array();
	$links[] = array(
		'label' => __( 'Forums', 'manual-docs' ),
		'url'   => manual_docs_bbpress_directory_url( 'forums' ),
	);
	$links[] = array(
		'label' => __( 'Topics', 'manual-docs' ),
		'url'   => manual_docs_bbpress_directory_url( 'topics' ),
	);

	if ( is_user_logged_in() && function_exists( 'bbp_get_user_profile_url' ) ) {
		$links[] = array(
			'label' => __( 'My Profile', 'manual-docs' ),
			'url'   => bbp_get_user_profile_url( get_current_user_id() ),
		);
	}

	return apply_filters( 'manual_docs_community_links', $links );
}

/**
 * One-time rewrite flush so /forums, /topics, and /users/... resolve after theme update.
 */
function manual_docs_maybe_flush_bbpress_rewrites() {
	if ( ! manual_docs_bbpress_active() ) {
		return;
	}
	$flag = 'manual_docs_bbp_rewrites_' . MANUAL_DOCS_VERSION;
	if ( get_option( $flag ) ) {
		return;
	}
	if ( function_exists( 'manual_docs_hard_flush_rewrites' ) ) {
		manual_docs_hard_flush_rewrites();
	} else {
		flush_rewrite_rules( true );
	}
	update_option( $flag, 1, false );
}
add_action( 'init', 'manual_docs_maybe_flush_bbpress_rewrites', 99 );

/**
 * Resolve bbPress member profile paths when rewrite rules are stale (common Local 404s).
 *
 * Handles /users/{nicename}/, /users/{nicename}/topics/, /replies/, /edit/, etc.
 *
 * @param WP $wp WP object.
 * @return bool
 */
function manual_docs_resolve_bbpress_user_request( $wp ) {
	if ( ! manual_docs_bbpress_active() || ! function_exists( 'bbp_get_user_slug' ) || ! function_exists( 'manual_docs_request_path' ) ) {
		return false;
	}

	// Already resolved by core rewrite rules.
	if ( ! empty( $wp->query_vars['bbp_user'] ) || ( function_exists( 'bbp_get_user_rewrite_id' ) && ! empty( $wp->query_vars[ bbp_get_user_rewrite_id() ] ) ) ) {
		return false;
	}

	$path = manual_docs_request_path();
	if ( '' === $path ) {
		return false;
	}

	$user_slug = trim( (string) bbp_get_user_slug(), '/' );
	if ( '' === $user_slug ) {
		$user_slug = 'users';
	}

	$pattern = '#^' . preg_quote( $user_slug, '#' ) . '/([^/]+)(?:/([^/]+))?(?:/' . preg_quote( function_exists( 'bbp_get_paged_slug' ) ? bbp_get_paged_slug() : 'page', '#' ) . '/([0-9]+))?/?$#';
	if ( ! preg_match( $pattern, $path, $m ) ) {
		return false;
	}

	$nicename = sanitize_title( $m[1] );
	$section  = isset( $m[2] ) ? sanitize_title( $m[2] ) : '';
	$page_num = isset( $m[3] ) ? absint( $m[3] ) : 0;

	if ( ! $nicename || ! get_user_by( 'slug', $nicename ) ) {
		return false;
	}

	$user_qv = function_exists( 'bbp_get_user_rewrite_id' ) ? bbp_get_user_rewrite_id() : 'bbp_user';
	$wp->query_vars[ $user_qv ] = $nicename;
	unset( $wp->query_vars['error'], $wp->query_vars['pagename'], $wp->query_vars['name'], $wp->query_vars['page'] );

	$topics_slug = function_exists( 'bbp_get_topic_archive_slug' ) ? bbp_get_topic_archive_slug() : 'topics';
	$replies_slug = function_exists( 'bbp_get_reply_archive_slug' ) ? bbp_get_reply_archive_slug() : 'replies';
	$edit_slug    = function_exists( 'bbp_get_edit_slug' ) ? bbp_get_edit_slug() : 'edit';

	if ( $section === $topics_slug && function_exists( 'bbp_get_user_topics_rewrite_id' ) ) {
		$wp->query_vars[ bbp_get_user_topics_rewrite_id() ] = '1';
	} elseif ( $section === $replies_slug && function_exists( 'bbp_get_user_replies_rewrite_id' ) ) {
		$wp->query_vars[ bbp_get_user_replies_rewrite_id() ] = '1';
	} elseif ( $section === $edit_slug && function_exists( 'bbp_get_edit_rewrite_id' ) ) {
		$wp->query_vars[ bbp_get_edit_rewrite_id() ] = '1';
	} elseif ( $section && function_exists( 'bbp_get_user_engagements_slug' ) && $section === bbp_get_user_engagements_slug() && function_exists( 'bbp_get_user_engagements_rewrite_id' ) ) {
		$wp->query_vars[ bbp_get_user_engagements_rewrite_id() ] = '1';
	} elseif ( $section && function_exists( 'bbp_get_user_favorites_slug' ) && $section === bbp_get_user_favorites_slug() && function_exists( 'bbp_get_user_favorites_rewrite_id' ) ) {
		$wp->query_vars[ bbp_get_user_favorites_rewrite_id() ] = '1';
	} elseif ( $section && function_exists( 'bbp_get_user_subscriptions_slug' ) && $section === bbp_get_user_subscriptions_slug() && function_exists( 'bbp_get_user_subscriptions_rewrite_id' ) ) {
		$wp->query_vars[ bbp_get_user_subscriptions_rewrite_id() ] = '1';
	} elseif ( $section ) {
		// Unknown profile section — leave as profile root rather than hard 404.
		return true;
	}

	if ( $page_num && function_exists( 'bbp_get_paged_rewrite_id' ) ) {
		$wp->query_vars[ bbp_get_paged_rewrite_id() ] = $page_num;
	}

	return true;
}

/**
 * Hook user-profile path resolver early.
 *
 * @param WP $wp WP object.
 */
function manual_docs_parse_bbpress_user_request( $wp ) {
	if ( is_admin() ) {
		return;
	}
	manual_docs_resolve_bbpress_user_request( $wp );
}
add_action( 'parse_request', 'manual_docs_parse_bbpress_user_request', 2 );

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
			'slug'         => 'academy-support',
			'title'        => __( 'Academy Support', 'manual-docs' ),
			'content'      => __( 'Course labs, access, and Academy learning questions. Topics require Course ID and course name.', 'manual-docs' ),
			'topics'       => array(
				__( 'User Activation', 'manual-docs' ),
				__( 'Request for Lab Access Extension', 'manual-docs' ),
				__( 'Course enrollment not showing', 'manual-docs' ),
				__( 'Lab environment reset help', 'manual-docs' ),
				__( 'Certificate download issue', 'manual-docs' ),
				__( 'Where to find Course ID', 'manual-docs' ),
			),
			'reply_counts' => array( 2, 2, 1, 1, 1, 0 ),
			'academy'      => true,
			'course_ids'   => array( '77534', '77534', '88102', '88102', '90211', '10001' ),
			'course_names' => array(
				'ignio AIOps Intermediate E2',
				'ignio AIOps Intermediate E2',
				'Platform Fundamentals Lab',
				'Platform Fundamentals Lab',
				'Automation Practitioner',
				'Academy Orientation',
			),
			'lab_ids'      => array( 'LAB-2201', 'LAB-2201', 'LAB-1104', 'LAB-1104', 'LAB-3309', '' ),
			'issue_types'  => array( 'user-activation', 'lab-access', 'enrollment', 'environment', 'certificate', 'other' ),
			'urgencies'    => array( 'normal', 'high', 'normal', 'normal', 'high', 'normal' ),
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
			if ( ! empty( $forum_def['academy'] ) ) {
				$cid   = isset( $forum_def['course_ids'][ $ti ] ) ? $forum_def['course_ids'][ $ti ] : '';
				$cname = isset( $forum_def['course_names'][ $ti ] ) ? $forum_def['course_names'][ $ti ] : '';
				$lab   = isset( $forum_def['lab_ids'][ $ti ] ) ? $forum_def['lab_ids'][ $ti ] : '';
				$issue = isset( $forum_def['issue_types'][ $ti ] ) ? $forum_def['issue_types'][ $ti ] : '';
				$urg   = isset( $forum_def['urgencies'][ $ti ] ) ? $forum_def['urgencies'][ $ti ] : 'normal';
				if ( $cid && defined( 'MANUAL_DOCS_ACADEMY_COURSE_ID_KEY' ) ) {
					update_post_meta( (int) $topic_id, MANUAL_DOCS_ACADEMY_COURSE_ID_KEY, sanitize_text_field( $cid ) );
				}
				if ( $cname && defined( 'MANUAL_DOCS_ACADEMY_COURSE_NAME_KEY' ) ) {
					update_post_meta( (int) $topic_id, MANUAL_DOCS_ACADEMY_COURSE_NAME_KEY, sanitize_text_field( $cname ) );
				}
				if ( $lab && defined( 'MANUAL_DOCS_ACADEMY_LAB_ID_KEY' ) ) {
					update_post_meta( (int) $topic_id, MANUAL_DOCS_ACADEMY_LAB_ID_KEY, sanitize_text_field( $lab ) );
				}
				if ( $issue && defined( 'MANUAL_DOCS_ACADEMY_ISSUE_TYPE_KEY' ) ) {
					update_post_meta( (int) $topic_id, MANUAL_DOCS_ACADEMY_ISSUE_TYPE_KEY, sanitize_key( $issue ) );
				}
				if ( defined( 'MANUAL_DOCS_ACADEMY_URGENCY_KEY' ) ) {
					update_post_meta( (int) $topic_id, MANUAL_DOCS_ACADEMY_URGENCY_KEY, sanitize_key( $urg ) );
				}
			}
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