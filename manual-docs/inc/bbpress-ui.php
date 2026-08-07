<?php
/**
 * Modern community forum UI helpers (search, stats, recent topics, topic cards).
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Topic view meta key.
 */
define( 'MANUAL_DOCS_TOPIC_VIEWS_KEY', '_md_topic_views' );

/**
 * Current community page title.
 *
 * @return string
 */
function manual_docs_community_page_title() {
	// Prefer forum context over long topic titles in the hero.
	if ( function_exists( 'bbp_is_single_topic' ) && bbp_is_single_topic() && function_exists( 'bbp_get_topic_forum_id' ) ) {
		$forum_id = (int) bbp_get_topic_forum_id();
		if ( $forum_id && function_exists( 'bbp_get_forum_title' ) ) {
			return bbp_get_forum_title( $forum_id );
		}
	}
	if ( function_exists( 'bbp_is_single_forum' ) && bbp_is_single_forum() ) {
		return get_the_title();
	}
	if ( function_exists( 'bbp_is_single_reply' ) && bbp_is_single_reply() ) {
		return get_the_title();
	}
	if ( function_exists( 'bbp_is_topic_tag' ) && bbp_is_topic_tag() ) {
		return __( 'Topic Tag', 'manual-docs' );
	}
	if ( function_exists( 'bbp_is_single_user' ) && bbp_is_single_user() ) {
		return __( 'Member Profile', 'manual-docs' );
	}
	if ( function_exists( 'bbp_is_search' ) && bbp_is_search() ) {
		return __( 'Forum Search', 'manual-docs' );
	}
	if ( function_exists( 'bbp_is_topic_archive' ) && bbp_is_topic_archive() ) {
		return __( 'Topics', 'manual-docs' );
	}
	if ( function_exists( 'bbp_is_forum_archive' ) && bbp_is_forum_archive() ) {
		return __( 'Forums', 'manual-docs' );
	}
	return __( 'Community', 'manual-docs' );
}

/**
 * Stats for the current community context.
 *
 * @return array{topics:int,replies:int}
 */
function manual_docs_community_stats() {
	$topics  = 0;
	$replies = 0;

	if ( function_exists( 'bbp_is_single_forum' ) && bbp_is_single_forum() && function_exists( 'bbp_get_forum_id' ) ) {
		$forum_id = (int) bbp_get_forum_id();
		if ( function_exists( 'bbp_get_forum_topic_count' ) ) {
			$topics = (int) bbp_get_forum_topic_count( $forum_id, true );
		}
		if ( function_exists( 'bbp_get_forum_reply_count' ) ) {
			$replies = (int) bbp_get_forum_reply_count( $forum_id, true );
		}
	} elseif ( function_exists( 'bbp_get_statistics' ) ) {
		$stats   = bbp_get_statistics();
		$topics  = isset( $stats['topic_count'] ) ? (int) $stats['topic_count'] : 0;
		$replies = isset( $stats['reply_count'] ) ? (int) $stats['reply_count'] : 0;
	}

	return array(
		'topics'  => $topics,
		'replies' => $replies,
	);
}

/**
 * bbPress search action URL.
 *
 * @return string
 */
function manual_docs_community_search_url() {
	if ( function_exists( 'bbp_get_search_url' ) ) {
		return bbp_get_search_url();
	}
	return home_url( '/' );
}

/**
 * Render centered community hero (title + search).
 *
 * @param string $title Page title.
 */
function manual_docs_render_community_hero( $title ) {
	$q = '';
	if ( isset( $_GET['bbp_search'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$q = sanitize_text_field( wp_unslash( $_GET['bbp_search'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	} elseif ( function_exists( 'bbp_get_search_terms' ) ) {
		$q = (string) bbp_get_search_terms();
	}
	?>
	<?php $GLOBALS['md_community_hero_rendered'] = true; ?>
	<header class="md-community-hero">
		<p class="md-community-hero__eyebrow"><?php esc_html_e( 'Community', 'manual-docs' ); ?></p>
		<h1 class="md-community-hero__title"><?php echo esc_html( $title ); ?></h1>
		<form class="md-community-search" role="search" method="get" action="<?php echo esc_url( manual_docs_community_search_url() ); ?>" data-md-community-live-search>
			<label class="screen-reader-text" for="md-community-search-input"><?php esc_html_e( 'Search forums, topics and replies', 'manual-docs' ); ?></label>
			<input
				id="md-community-search-input"
				class="md-community-search__input"
				type="search"
				name="bbp_search"
				value="<?php echo esc_attr( $q ); ?>"
				placeholder="<?php echo esc_attr__( 'Have a question? Search forums, topics & replies…', 'manual-docs' ); ?>"
				autocomplete="off"
				aria-autocomplete="list"
			/>
			<button type="submit" class="md-community-search__submit" aria-label="<?php esc_attr_e( 'Search', 'manual-docs' ); ?>">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
			</button>
		</form>
	</header>
	<?php
}

/**
 * Render stats bar + Create Topic + Favorite/Subscribe.
 */
function manual_docs_render_community_toolbar() {
	$stats = manual_docs_community_stats();
	?>
	<div class="md-community-toolbar">
		<p class="md-community-toolbar__stats">
			<span><strong><?php echo esc_html( number_format_i18n( $stats['topics'] ) ); ?></strong> <?php esc_html_e( 'Topics', 'manual-docs' ); ?></span>
			<span><strong><?php echo esc_html( number_format_i18n( $stats['replies'] ) ); ?></strong> <?php esc_html_e( 'Replies', 'manual-docs' ); ?></span>
		</p>
		<div class="md-community-toolbar__actions">
			<?php manual_docs_render_create_topic_button(); ?>
			<?php manual_docs_render_engagement_buttons(); ?>
		</div>
	</div>
	<?php
}

/**
 * Resolve a forum ID for the Create Topic CTA.
 *
 * @return int
 */
function manual_docs_get_create_topic_forum_id() {
	if ( function_exists( 'bbp_is_single_forum' ) && bbp_is_single_forum() ) {
		return (int) bbp_get_forum_id();
	}
	if ( function_exists( 'bbp_is_single_topic' ) && bbp_is_single_topic() && function_exists( 'bbp_get_topic_forum_id' ) ) {
		$topic_id = (int) bbp_get_topic_id();
		if ( ! $topic_id ) {
			$topic_id = (int) get_queried_object_id();
		}
		return (int) bbp_get_topic_forum_id( $topic_id );
	}
	if ( function_exists( 'bbp_is_single_reply' ) && bbp_is_single_reply() && function_exists( 'bbp_get_reply_forum_id' ) ) {
		return (int) bbp_get_reply_forum_id();
	}

	if ( function_exists( 'manual_docs_get_academy_forum' ) ) {
		$academy = manual_docs_get_academy_forum();
		if ( $academy ) {
			return (int) $academy->ID;
		}
	}

	$q = new WP_Query(
		array(
			'post_type'              => function_exists( 'bbp_get_forum_post_type' ) ? bbp_get_forum_post_type() : 'forum',
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'orderby'                => 'menu_order title',
			'order'                  => 'ASC',
			'post_parent'            => 0,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);
	return ! empty( $q->posts[0] ) ? (int) $q->posts[0] : 0;
}

/**
 * “Create Topic” CTA for forums / topics / replies views.
 */
function manual_docs_render_create_topic_button() {
	$label    = __( 'Create Topic', 'manual-docs' );
	$forum_id = manual_docs_get_create_topic_forum_id();
	$url      = '';

	if ( ! is_user_logged_in() ) {
		$redirect = $forum_id && function_exists( 'bbp_get_forum_permalink' )
			? bbp_get_forum_permalink( $forum_id ) . '#new-post'
			: ( function_exists( 'manual_docs_bbpress_directory_url' ) ? manual_docs_bbpress_directory_url( 'forums' ) : home_url( '/' ) );
		$url   = wp_login_url( $redirect );
		$label = __( 'Log in to create a topic', 'manual-docs' );
	} elseif ( $forum_id && function_exists( 'bbp_get_forum_permalink' ) ) {
		$can_create = true;
		if ( function_exists( 'bbp_current_user_can_access_create_topic_form' ) && function_exists( 'bbp_is_single_forum' ) && bbp_is_single_forum() ) {
			$can_create = (bool) bbp_current_user_can_access_create_topic_form();
		} elseif ( function_exists( 'bbp_current_user_can_publish_topics' ) ) {
			$can_create = (bool) bbp_current_user_can_publish_topics();
		} elseif ( ! current_user_can( 'publish_topics' ) ) {
			$can_create = false;
		}
		if ( $can_create ) {
			$url = bbp_get_forum_permalink( $forum_id ) . '#new-post';
		}
	}

	if ( ! $url ) {
		return;
	}
	?>
	<a class="md-btn md-btn--primary md-btn--create-topic" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
	<?php
}

/**
 * Favorite + Subscribe with bbPress engagement wrappers (AJAX-ready).
 */
function manual_docs_render_engagement_buttons() {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$GLOBALS['md_rendering_subscribe_toolbar'] = true;

	$args = array(
		'before'      => '',
		'after'       => '',
		'subscribe'   => __( 'Subscribe', 'manual-docs' ),
		'unsubscribe' => __( 'Unsubscribe', 'manual-docs' ),
		'favorite'    => __( 'Favorite', 'manual-docs' ),
		'favorited'   => __( 'Unfavorite', 'manual-docs' ),
	);

	if ( function_exists( 'bbp_is_single_forum' ) && bbp_is_single_forum() && function_exists( 'bbp_get_forum_subscription_link' ) ) {
		$object_id = (int) bbp_get_forum_id();
		if ( ! $object_id ) {
			$object_id = (int) get_queried_object_id();
		}
		$args['object_id'] = $object_id;
		$html              = bbp_get_forum_subscription_link( $args );
		if ( $html ) {
			echo '<span class="md-engagement md-engagement--subscribe">' . $html . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	} elseif ( ( function_exists( 'bbp_is_single_topic' ) && bbp_is_single_topic() )
		|| ( function_exists( 'bbp_is_single_reply' ) && bbp_is_single_reply() ) ) {
		$object_id = (int) bbp_get_topic_id();
		if ( ! $object_id ) {
			$object_id = (int) get_queried_object_id();
		}
		$args['object_id'] = $object_id;

		if ( function_exists( 'bbp_get_topic_favorite_link' ) ) {
			$html = bbp_get_topic_favorite_link( $args );
			if ( $html ) {
				echo '<span class="md-engagement md-engagement--favorite">' . $html . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
		if ( function_exists( 'bbp_get_topic_subscription_link' ) ) {
			$html = bbp_get_topic_subscription_link( $args );
			if ( $html ) {
				echo '<span class="md-engagement md-engagement--subscribe">' . $html . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
	}

	$GLOBALS['md_rendering_subscribe_toolbar'] = false;
}

/**
 * Keep Favorite/Subscribe labels clean (no pipe separators) after AJAX refresh.
 *
 * @param array $args Parse args.
 * @return array
 */
function manual_docs_engagement_parse_args( $args ) {
	if ( empty( $GLOBALS['md_rendering_subscribe_toolbar'] ) && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
		return $args;
	}
	$args['before'] = '';
	$args['after']  = '';
	if ( array_key_exists( 'subscribe', $args ) ) {
		$args['subscribe']   = __( 'Subscribe', 'manual-docs' );
		$args['unsubscribe'] = __( 'Unsubscribe', 'manual-docs' );
	}
	if ( array_key_exists( 'favorite', $args ) ) {
		$args['favorite']  = __( 'Favorite', 'manual-docs' );
		$args['favorited'] = __( 'Unfavorite', 'manual-docs' );
	}
	return $args;
}
add_filter( 'bbp_before_get_user_subscribe_link_parse_args', 'manual_docs_engagement_parse_args' );
add_filter( 'bbp_before_get_user_favorites_link_parse_args', 'manual_docs_engagement_parse_args' );
add_filter( 'bbp_before_get_topic_subscribe_link_parse_args', 'manual_docs_engagement_parse_args' );
add_filter( 'bbp_before_get_topic_favorite_link_parse_args', 'manual_docs_engagement_parse_args' );
add_filter( 'bbp_before_get_forum_subscribe_link_parse_args', 'manual_docs_engagement_parse_args' );

/**
 * Hide default bbPress subscribe/favorite in topic chrome (toolbar owns the only set).
 *
 * @param string $html Link HTML.
 * @return string
 */
function manual_docs_suppress_duplicate_topic_actions( $html ) {
	if ( ! empty( $GLOBALS['md_rendering_subscribe_toolbar'] ) ) {
		return $html;
	}
	// AJAX engagement responses must pass through or Favorite/Subscribe appear broken.
	if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( function_exists( 'bbp_is_ajax' ) && bbp_is_ajax() ) ) {
		return $html;
	}
	if ( is_admin() ) {
		return $html;
	}
	if ( function_exists( 'bbp_is_single_user' ) && bbp_is_single_user() ) {
		return $html;
	}
	if ( ( function_exists( 'bbp_is_single_topic' ) && bbp_is_single_topic() )
		|| ( function_exists( 'bbp_is_single_forum' ) && bbp_is_single_forum() )
		|| ( function_exists( 'bbp_is_single_reply' ) && bbp_is_single_reply() ) ) {
		return '';
	}
	return $html;
}
add_filter( 'bbp_get_user_subscribe_link', 'manual_docs_suppress_duplicate_topic_actions', 5 );
add_filter( 'bbp_get_topic_subscribe_link', 'manual_docs_suppress_duplicate_topic_actions', 5 );
add_filter( 'bbp_get_forum_subscribe_link', 'manual_docs_suppress_duplicate_topic_actions', 5 );
add_filter( 'bbp_get_topic_favorite_link', 'manual_docs_suppress_duplicate_topic_actions', 5 );
add_filter( 'bbp_get_user_favorites_link', 'manual_docs_suppress_duplicate_topic_actions', 5 );

/**
 * Ensure bbPress engagements (Favorite/Subscribe AJAX) scripts load on community pages.
 */
function manual_docs_enqueue_bbpress_engagements() {
	if ( ! function_exists( 'is_bbpress' ) || ! is_bbpress() ) {
		return;
	}
	if ( ! function_exists( 'bbp_is_single_forum' ) ) {
		return;
	}
	if ( ! bbp_is_single_forum() && ! bbp_is_single_topic() && ! ( function_exists( 'bbp_is_single_reply' ) && bbp_is_single_reply() ) ) {
		return;
	}

	$src = '';
	if ( function_exists( 'bbp_get_theme_compat_url' ) ) {
		$src = trailingslashit( bbp_get_theme_compat_url() ) . 'js/engagements.js';
	} elseif ( defined( 'BBPRESS_PLUGIN_URL' ) ) {
		$src = BBPRESS_PLUGIN_URL . 'templates/default/js/engagements.js';
	}

	if ( ! wp_script_is( 'bbpress-engagements', 'registered' ) && $src ) {
		$ver = function_exists( 'bbp_get_version' ) ? bbp_get_version() : MANUAL_DOCS_VERSION;
		wp_register_script( 'bbpress-engagements', $src, array( 'jquery' ), $ver, true );
	}

	if ( wp_script_is( 'bbpress-engagements', 'registered' ) || wp_script_is( 'bbpress-engagements', 'enqueued' ) ) {
		wp_enqueue_script( 'bbpress-engagements' );
	}

	if ( function_exists( 'bbp_get_ajax_url' ) && ( wp_script_is( 'bbpress-engagements', 'enqueued' ) || wp_script_is( 'bbpress-engagements', 'registered' ) ) ) {
		wp_localize_script(
			'bbpress-engagements',
			'bbpEngagementJS',
			array(
				'bbp_ajaxurl'        => bbp_get_ajax_url(),
				'generic_ajax_error' => __( 'Something went wrong. Refresh your browser and try again.', 'manual-docs' ),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'manual_docs_enqueue_bbpress_engagements', 40 );

/**
 * Remove the native bbPress search form above forum/topic cards (hero search replaces it).
 *
 * @param string $form Search form HTML.
 * @return string
 */
function manual_docs_remove_inline_bbpress_search( $form ) {
	unset( $form );
	return '';
}
add_filter( 'bbp_get_search_form', 'manual_docs_remove_inline_bbpress_search', 99 );

/**
 * Initials from a display name.
 *
 * @param string $name Name.
 * @return string
 */
function manual_docs_user_initials( $name ) {
	$name  = trim( wp_strip_all_tags( (string) $name ) );
	$parts = preg_split( '/\s+/', $name );
	if ( ! $parts ) {
		return '?';
	}
	$ini = '';
	foreach ( array_slice( $parts, 0, 2 ) as $part ) {
		$ini .= strtoupper( substr( $part, 0, 1 ) );
	}
	return $ini ? $ini : '?';
}

/**
 * Get topic view count.
 *
 * @param int $topic_id Topic ID.
 * @return int
 */
function manual_docs_get_topic_views( $topic_id ) {
	return absint( get_post_meta( absint( $topic_id ), MANUAL_DOCS_TOPIC_VIEWS_KEY, true ) );
}

/**
 * Increment topic views (rate-limited per browser).
 */
function manual_docs_maybe_count_topic_view() {
	if ( ! function_exists( 'bbp_is_single_topic' ) || ! bbp_is_single_topic() ) {
		return;
	}
	if ( is_preview() || is_admin() ) {
		return;
	}
	$topic_id = function_exists( 'bbp_get_topic_id' ) ? (int) bbp_get_topic_id() : 0;
	if ( ! $topic_id ) {
		return;
	}

	$key = 'md_tv_' . $topic_id;
	if ( ! empty( $_COOKIE[ $key ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return;
	}

	$views = manual_docs_get_topic_views( $topic_id ) + 1;
	update_post_meta( $topic_id, MANUAL_DOCS_TOPIC_VIEWS_KEY, $views );

	if ( ! headers_sent() ) {
		setcookie( $key, '1', time() + 6 * HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	}
}
add_action( 'bbp_template_before_single_topic', 'manual_docs_maybe_count_topic_view' );

/**
 * Recent topics for sidebar.
 *
 * @param int $limit Number of topics.
 * @return WP_Post[]
 */
function manual_docs_get_recent_topics( $limit = 8 ) {
	if ( ! post_type_exists( 'topic' ) ) {
		return array();
	}
	$q = new WP_Query(
		array(
			'post_type'              => 'topic',
			'post_status'            => 'publish',
			'posts_per_page'         => absint( $limit ),
			'orderby'                => 'modified',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		)
	);
	return $q->posts;
}

/**
 * Render recent topics sidebar panel.
 */
function manual_docs_render_recent_topics_panel() {
	$topics = manual_docs_get_recent_topics( 8 );
	?>
	<section class="md-recent-topics" aria-label="<?php esc_attr_e( 'Recent topics', 'manual-docs' ); ?>">
		<h2 class="md-recent-topics__title"><?php esc_html_e( 'Recent Topics', 'manual-docs' ); ?></h2>
		<?php if ( empty( $topics ) ) : ?>
			<p class="md-recent-topics__empty"><?php esc_html_e( 'No topics yet.', 'manual-docs' ); ?></p>
		<?php else : ?>
			<ul class="md-recent-topics__list">
				<?php foreach ( $topics as $topic ) : ?>
					<?php
					$label   = get_the_title( $topic );
					$forum_id = function_exists( 'bbp_get_topic_forum_id' ) ? (int) bbp_get_topic_forum_id( $topic->ID ) : 0;
					if ( $forum_id && function_exists( 'manual_docs_is_academy_forum' ) && manual_docs_is_academy_forum( $forum_id ) && function_exists( 'manual_docs_get_academy_fields' ) ) {
						$af = manual_docs_get_academy_fields( $topic->ID );
						$extra = array_filter(
							array(
								$af['course_name'],
								! empty( $af['course_id'] ) ? sprintf(
									/* translators: %s: course id */
									__( 'Course ID: %s', 'manual-docs' ),
									$af['course_id']
								) : '',
								! empty( $af['lab_id'] ) ? sprintf(
									/* translators: %s: lab id */
									__( 'Lab: %s', 'manual-docs' ),
									$af['lab_id']
								) : '',
							)
						);
						if ( $extra ) {
							$label .= ' — ' . implode( ' · ', $extra );
						}
					}
					?>
					<li>
						<a href="<?php echo esc_url( get_permalink( $topic ) ); ?>">
							<svg class="md-recent-topics__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h8l4 4v14H7V3z" stroke="currentColor" stroke-width="1.75"/><path d="M15 3v5h5" stroke="currentColor" stroke-width="1.75"/></svg>
							<span><?php echo esc_html( $label ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
	<?php
}

/**
 * Author avatar markup (image or initials fallback).
 *
 * @param int $user_id User ID.
 * @param int $size    Size.
 * @return string
 */
function manual_docs_community_avatar_html( $user_id, $size = 44 ) {
	$user_id = absint( $user_id );
	$size    = absint( $size );
	$name    = $user_id ? get_the_author_meta( 'display_name', $user_id ) : __( 'Guest', 'manual-docs' );
	$initial = manual_docs_user_initials( $name );

	if ( $user_id && get_option( 'show_avatars' ) ) {
		$img = get_avatar( $user_id, $size, '', $name, array( 'class' => 'md-topic-card__avatar-img' ) );
		if ( $img ) {
			return $img;
		}
	}

	return '<span class="md-topic-card__avatar-fallback" aria-hidden="true">' . esc_html( $initial ) . '</span>';
}

/**
 * Whether a sidebar widget id should be hidden on the community sidebar.
 *
 * @param string $widget_id Widget id (e.g. archives-2, block-5).
 * @return bool
 */
function manual_docs_is_hidden_community_widget( $widget_id ) {
	$widget_id = (string) $widget_id;
	if ( preg_match( '/^(archives|categories)(-|$)/', $widget_id ) ) {
		return true;
	}
	// Block widgets (WP 5.8+).
	if ( 0 === strpos( $widget_id, 'block-' ) ) {
		$number = (int) str_replace( 'block-', '', $widget_id );
		$blocks = get_option( 'widget_block', array() );
		$content = '';
		if ( $number && ! empty( $blocks[ $number ]['content'] ) ) {
			$content = (string) $blocks[ $number ]['content'];
		} elseif ( ! empty( $blocks[ $widget_id ]['content'] ) ) {
			$content = (string) $blocks[ $widget_id ]['content'];
		}
		if ( $content && ( false !== strpos( $content, 'wp:archives' ) || false !== strpos( $content, 'wp:categories' ) || false !== strpos( $content, 'wp-block-archives' ) || false !== strpos( $content, 'wp-block-categories' ) ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Strip Archives / Categories from the community sidebar before render.
 *
 * @param array $sidebars Sidebars widgets map.
 * @return array
 */
function manual_docs_strip_community_sidebar_widgets( $sidebars ) {
	if ( empty( $sidebars['community-sidebar'] ) || ! is_array( $sidebars['community-sidebar'] ) ) {
		return $sidebars;
	}
	$sidebars['community-sidebar'] = array_values(
		array_filter(
			$sidebars['community-sidebar'],
			static function ( $id ) {
				return ! manual_docs_is_hidden_community_widget( $id );
			}
		)
	);
	return $sidebars;
}
add_filter( 'sidebars_widgets', 'manual_docs_strip_community_sidebar_widgets', 100 );

/**
 * Hide Archives / Categories widgets on the community sidebar (legacy callback).
 *
 * @param array|false $instance Widget settings.
 * @param WP_Widget   $widget   Widget instance.
 * @param array       $args     Sidebar args.
 * @return array|false
 */
function manual_docs_filter_community_sidebar_widgets( $instance, $widget, $args ) {
	if ( empty( $args['id'] ) || 'community-sidebar' !== $args['id'] ) {
		return $instance;
	}
	if ( $widget instanceof WP_Widget_Archives || $widget instanceof WP_Widget_Categories ) {
		return false;
	}
	$id_base = isset( $widget->id_base ) ? $widget->id_base : '';
	if ( in_array( $id_base, array( 'archives', 'categories' ), true ) ) {
		return false;
	}
	$widget_id = isset( $widget->id ) ? $widget->id : '';
	if ( $widget_id && manual_docs_is_hidden_community_widget( $widget_id ) ) {
		return false;
	}
	return $instance;
}
add_filter( 'widget_display_callback', 'manual_docs_filter_community_sidebar_widgets', 10, 3 );

/**
 * Hide meaningless “Viewing 0 posts” counts and relabel reply views as replies.
 *
 * @param string $ret Count HTML/text.
 * @return string
 */
function manual_docs_filter_pagination_count( $ret ) {
	$text = wp_strip_all_tags( (string) $ret );
	if ( preg_match( '/\b0\s+(?:posts?|replies|reply)\b/i', $text ) ) {
		return '';
	}
	// Topic/reply threads: bbPress says “posts”; community copy should say “replies”.
	if ( ( function_exists( 'bbp_is_single_topic' ) && bbp_is_single_topic() )
		|| ( function_exists( 'bbp_is_single_reply' ) && bbp_is_single_reply() )
		|| ( function_exists( 'bbp_is_reply_edit' ) && bbp_is_reply_edit() ) ) {
		$ret = preg_replace( '/\bposts\b/i', 'replies', (string) $ret );
		$ret = preg_replace( '/\bpost\b/i', 'reply', $ret );
	}
	return $ret;
}
add_filter( 'bbp_get_topic_pagination_count', 'manual_docs_filter_pagination_count' );
add_filter( 'bbp_get_reply_pagination_count', 'manual_docs_filter_pagination_count' );
add_filter( 'bbp_get_forum_pagination_count', 'manual_docs_filter_pagination_count' );
