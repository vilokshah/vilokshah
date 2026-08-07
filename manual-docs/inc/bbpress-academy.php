<?php
/**
 * Academy Support forum — extra topic fields (Course ID, Course name).
 *
 * Security: capability checks, nonce verification, server-side forum
 * membership check (never trust client forum_id alone), sanitize + length
 * limits, escape on output.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Meta keys (underscore-prefixed = private). */
define( 'MANUAL_DOCS_ACADEMY_COURSE_ID_KEY', '_md_academy_course_id' );
define( 'MANUAL_DOCS_ACADEMY_COURSE_NAME_KEY', '_md_academy_course_name' );
define( 'MANUAL_DOCS_ACADEMY_NONCE_ACTION', 'manual_docs_academy_topic_fields' );
define( 'MANUAL_DOCS_ACADEMY_NONCE_NAME', 'manual_docs_academy_nonce' );

/**
 * Configured Academy Support forum slug.
 *
 * @return string
 */
function manual_docs_academy_forum_slug() {
	$slug = manual_docs_get_option( 'academy_forum_slug', 'academy-support' );
	$slug = sanitize_title( (string) $slug );
	return $slug ? $slug : 'academy-support';
}

/**
 * Resolve Academy Support forum post.
 *
 * @return WP_Post|null
 */
function manual_docs_get_academy_forum() {
	if ( ! manual_docs_bbpress_active() ) {
		return null;
	}
	$slug = manual_docs_academy_forum_slug();
	$post = get_page_by_path( $slug, OBJECT, 'forum' );
	return ( $post instanceof WP_Post ) ? $post : null;
}

/**
 * Whether a forum ID is the Academy Support forum.
 *
 * @param int $forum_id Forum ID.
 * @return bool
 */
function manual_docs_is_academy_forum( $forum_id ) {
	$forum_id = absint( $forum_id );
	if ( ! $forum_id ) {
		return false;
	}
	$academy = manual_docs_get_academy_forum();
	return $academy && (int) $academy->ID === $forum_id;
}

/**
 * Whether the current request context is Academy Support (forum view or new topic there).
 *
 * @return bool
 */
function manual_docs_is_academy_context() {
	if ( ! manual_docs_bbpress_active() ) {
		return false;
	}

	$forum_id = 0;
	if ( function_exists( 'bbp_get_forum_id' ) ) {
		$forum_id = (int) bbp_get_forum_id();
	}
	if ( ! $forum_id && function_exists( 'bbp_get_topic_forum_id' ) && function_exists( 'bbp_get_topic_id' ) ) {
		$topic_id = (int) bbp_get_topic_id();
		if ( $topic_id ) {
			$forum_id = (int) bbp_get_topic_forum_id( $topic_id );
		}
	}
	// New topic form often posts forum_id.
	if ( ! $forum_id && isset( $_REQUEST['bbp_forum_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$forum_id = absint( wp_unslash( $_REQUEST['bbp_forum_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	return manual_docs_is_academy_forum( $forum_id );
}

/**
 * Sanitize Course ID (alphanumeric + limited punctuation, max 32).
 *
 * @param mixed $value Raw.
 * @return string
 */
function manual_docs_sanitize_academy_course_id( $value ) {
	$value = sanitize_text_field( (string) $value );
	$value = preg_replace( '/[^A-Za-z0-9\-_.]/', '', $value );
	return substr( (string) $value, 0, 32 );
}

/**
 * Sanitize course / environment name (max 120).
 *
 * @param mixed $value Raw.
 * @return string
 */
function manual_docs_sanitize_academy_course_name( $value ) {
	$value = sanitize_text_field( (string) $value );
	return substr( $value, 0, 120 );
}

/**
 * Get academy fields for a topic.
 *
 * @param int $topic_id Topic ID.
 * @return array{course_id:string,course_name:string}
 */
function manual_docs_get_academy_fields( $topic_id ) {
	$topic_id = absint( $topic_id );
	return array(
		'course_id'   => (string) get_post_meta( $topic_id, MANUAL_DOCS_ACADEMY_COURSE_ID_KEY, true ),
		'course_name' => (string) get_post_meta( $topic_id, MANUAL_DOCS_ACADEMY_COURSE_NAME_KEY, true ),
	);
}

/**
 * Whether the current user may edit academy fields on a topic.
 *
 * @param int $topic_id Topic ID (0 for new).
 * @return bool
 */
function manual_docs_user_can_edit_academy_fields( $topic_id = 0 ) {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	$topic_id = absint( $topic_id );
	if ( $topic_id ) {
		if ( current_user_can( 'edit_others_topics' ) || current_user_can( 'moderate' ) ) {
			return true;
		}
		$author = (int) get_post_field( 'post_author', $topic_id );
		return $author && get_current_user_id() === $author && current_user_can( 'edit_topics' );
	}
	return current_user_can( 'publish_topics' ) || current_user_can( 'edit_topics' );
}

/**
 * Render academy fields on the front-end topic form.
 */
function manual_docs_render_academy_topic_form_fields() {
	if ( ! manual_docs_is_academy_context() ) {
		return;
	}

	$topic_id = function_exists( 'bbp_get_topic_id' ) ? (int) bbp_get_topic_id() : 0;
	if ( ! manual_docs_user_can_edit_academy_fields( $topic_id ) ) {
		return;
	}

	$fields = $topic_id ? manual_docs_get_academy_fields( $topic_id ) : array(
		'course_id'   => '',
		'course_name' => '',
	);

	// Prefill from POST on validation error (still sanitized).
	if ( isset( $_POST[ MANUAL_DOCS_ACADEMY_NONCE_NAME ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['md_academy_course_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$fields['course_id'] = manual_docs_sanitize_academy_course_id( wp_unslash( $_POST['md_academy_course_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}
		if ( isset( $_POST['md_academy_course_name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$fields['course_name'] = manual_docs_sanitize_academy_course_name( wp_unslash( $_POST['md_academy_course_name'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}
	}
	?>
	<div class="md-academy-fields" data-md-academy-fields>
		<?php wp_nonce_field( MANUAL_DOCS_ACADEMY_NONCE_ACTION, MANUAL_DOCS_ACADEMY_NONCE_NAME ); ?>
		<p class="md-academy-fields__intro">
			<?php esc_html_e( 'Academy Support details (required for course-related questions).', 'manual-docs' ); ?>
		</p>
		<p>
			<label for="md_academy_course_id">
				<?php esc_html_e( 'Course ID', 'manual-docs' ); ?>
				<span class="md-academy-fields__req" aria-hidden="true">*</span>
			</label>
			<input
				type="text"
				id="md_academy_course_id"
				name="md_academy_course_id"
				value="<?php echo esc_attr( $fields['course_id'] ); ?>"
				maxlength="32"
				pattern="[A-Za-z0-9\-_.]+"
				autocomplete="off"
				required
			/>
			<span class="description"><?php esc_html_e( 'Letters, numbers, hyphens, underscores, or dots only.', 'manual-docs' ); ?></span>
		</p>
		<p>
			<label for="md_academy_course_name">
				<?php esc_html_e( 'Course / environment name', 'manual-docs' ); ?>
				<span class="md-academy-fields__req" aria-hidden="true">*</span>
			</label>
			<input
				type="text"
				id="md_academy_course_name"
				name="md_academy_course_name"
				value="<?php echo esc_attr( $fields['course_name'] ); ?>"
				maxlength="120"
				autocomplete="off"
				required
			/>
		</p>
	</div>
	<?php
}
add_action( 'bbp_theme_before_topic_form_content', 'manual_docs_render_academy_topic_form_fields' );

/**
 * Validate academy fields before topic create.
 *
 * @param int $forum_id Forum ID from bbPress.
 */
function manual_docs_validate_academy_topic_fields_new( $forum_id = 0 ) {
	manual_docs_validate_academy_topic_fields( absint( $forum_id ) );
}
add_action( 'bbp_new_topic_pre_extras', 'manual_docs_validate_academy_topic_fields_new' );

/**
 * Validate academy fields before topic edit.
 *
 * @param int $topic_id Topic ID from bbPress.
 */
function manual_docs_validate_academy_topic_fields_edit( $topic_id = 0 ) {
	$topic_id = absint( $topic_id );
	$forum_id = ( $topic_id && function_exists( 'bbp_get_topic_forum_id' ) ) ? (int) bbp_get_topic_forum_id( $topic_id ) : 0;
	manual_docs_validate_academy_topic_fields( $forum_id );
}
add_action( 'bbp_edit_topic_pre_extras', 'manual_docs_validate_academy_topic_fields_edit' );

/**
 * Core academy field validation (nonce, caps, sanitize).
 *
 * @param int $forum_id Forum ID.
 */
function manual_docs_validate_academy_topic_fields( $forum_id = 0 ) {
	$forum_id = absint( $forum_id );
	if ( ! $forum_id && function_exists( 'bbp_get_forum_id' ) ) {
		$forum_id = (int) bbp_get_forum_id();
	}
	if ( ! manual_docs_is_academy_forum( $forum_id ) ) {
		return;
	}

	$topic_id = function_exists( 'bbp_get_topic_id' ) ? (int) bbp_get_topic_id() : 0;
	if ( ! manual_docs_user_can_edit_academy_fields( $topic_id ) ) {
		bbp_add_error( 'md_academy_cap', __( 'You are not allowed to set Academy Support fields.', 'manual-docs' ) );
		return;
	}

	if ( empty( $_POST[ MANUAL_DOCS_ACADEMY_NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ MANUAL_DOCS_ACADEMY_NONCE_NAME ] ) ), MANUAL_DOCS_ACADEMY_NONCE_ACTION ) ) {
		bbp_add_error( 'md_academy_nonce', __( 'Security check failed for Academy fields. Please reload and try again.', 'manual-docs' ) );
		return;
	}

	$course_id   = isset( $_POST['md_academy_course_id'] ) ? manual_docs_sanitize_academy_course_id( wp_unslash( $_POST['md_academy_course_id'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$course_name = isset( $_POST['md_academy_course_name'] ) ? manual_docs_sanitize_academy_course_name( wp_unslash( $_POST['md_academy_course_name'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	if ( '' === $course_id ) {
		bbp_add_error( 'md_academy_course_id', __( 'Course ID is required for Academy Support topics.', 'manual-docs' ) );
	}
	if ( '' === $course_name ) {
		bbp_add_error( 'md_academy_course_name', __( 'Course / environment name is required for Academy Support topics.', 'manual-docs' ) );
	}
}

/**
 * Persist academy fields after topic save.
 *
 * @param int $topic_id Topic ID.
 */
function manual_docs_save_academy_topic_fields( $topic_id ) {
	$topic_id = absint( $topic_id );
	if ( ! $topic_id || ! function_exists( 'bbp_get_topic_forum_id' ) ) {
		return;
	}

	$forum_id = (int) bbp_get_topic_forum_id( $topic_id );
	if ( ! manual_docs_is_academy_forum( $forum_id ) ) {
		// Strip leftover meta if topic was moved out of Academy.
		delete_post_meta( $topic_id, MANUAL_DOCS_ACADEMY_COURSE_ID_KEY );
		delete_post_meta( $topic_id, MANUAL_DOCS_ACADEMY_COURSE_NAME_KEY );
		return;
	}

	if ( ! manual_docs_user_can_edit_academy_fields( $topic_id ) ) {
		return;
	}

	if ( empty( $_POST[ MANUAL_DOCS_ACADEMY_NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ MANUAL_DOCS_ACADEMY_NONCE_NAME ] ) ), MANUAL_DOCS_ACADEMY_NONCE_ACTION ) ) {
		return;
	}

	$course_id   = isset( $_POST['md_academy_course_id'] ) ? manual_docs_sanitize_academy_course_id( wp_unslash( $_POST['md_academy_course_id'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$course_name = isset( $_POST['md_academy_course_name'] ) ? manual_docs_sanitize_academy_course_name( wp_unslash( $_POST['md_academy_course_name'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	if ( '' === $course_id || '' === $course_name ) {
		return;
	}

	update_post_meta( $topic_id, MANUAL_DOCS_ACADEMY_COURSE_ID_KEY, $course_id );
	update_post_meta( $topic_id, MANUAL_DOCS_ACADEMY_COURSE_NAME_KEY, $course_name );
}
add_action( 'bbp_new_topic', 'manual_docs_save_academy_topic_fields', 10, 1 );
add_action( 'bbp_edit_topic', 'manual_docs_save_academy_topic_fields', 10, 1 );

/**
 * Admin metabox for academy fields.
 */
function manual_docs_academy_metabox_register() {
	if ( ! manual_docs_bbpress_active() ) {
		return;
	}
	add_meta_box(
		'manual_docs_academy_fields',
		__( 'Academy Support fields', 'manual-docs' ),
		'manual_docs_academy_metabox_render',
		'topic',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'manual_docs_academy_metabox_register' );

/**
 * Render admin metabox.
 *
 * @param WP_Post $post Topic.
 */
function manual_docs_academy_metabox_render( $post ) {
	$forum_id = function_exists( 'bbp_get_topic_forum_id' ) ? (int) bbp_get_topic_forum_id( $post->ID ) : 0;
	$is_acad  = manual_docs_is_academy_forum( $forum_id );
	$fields   = manual_docs_get_academy_fields( $post->ID );

	wp_nonce_field( MANUAL_DOCS_ACADEMY_NONCE_ACTION, MANUAL_DOCS_ACADEMY_NONCE_NAME );

	if ( ! $is_acad ) {
		echo '<p class="description">' . esc_html__( 'These fields apply only when the topic belongs to the Academy Support forum.', 'manual-docs' ) . '</p>';
	}
	?>
	<p>
		<label for="md_academy_course_id_admin"><strong><?php esc_html_e( 'Course ID', 'manual-docs' ); ?></strong></label><br />
		<input type="text" class="widefat" id="md_academy_course_id_admin" name="md_academy_course_id" value="<?php echo esc_attr( $fields['course_id'] ); ?>" maxlength="32" autocomplete="off" <?php disabled( ! $is_acad ); ?> />
	</p>
	<p>
		<label for="md_academy_course_name_admin"><strong><?php esc_html_e( 'Course / environment', 'manual-docs' ); ?></strong></label><br />
		<input type="text" class="widefat" id="md_academy_course_name_admin" name="md_academy_course_name" value="<?php echo esc_attr( $fields['course_name'] ); ?>" maxlength="120" autocomplete="off" <?php disabled( ! $is_acad ); ?> />
	</p>
	<?php
}

/**
 * Save academy fields from admin.
 *
 * @param int $post_id Post ID.
 */
function manual_docs_academy_metabox_save( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( 'topic' !== get_post_type( $post_id ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( empty( $_POST[ MANUAL_DOCS_ACADEMY_NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ MANUAL_DOCS_ACADEMY_NONCE_NAME ] ) ), MANUAL_DOCS_ACADEMY_NONCE_ACTION ) ) {
		return;
	}

	$forum_id = function_exists( 'bbp_get_topic_forum_id' ) ? (int) bbp_get_topic_forum_id( $post_id ) : 0;
	if ( ! manual_docs_is_academy_forum( $forum_id ) ) {
		return;
	}
	if ( ! manual_docs_user_can_edit_academy_fields( $post_id ) ) {
		return;
	}

	$course_id   = isset( $_POST['md_academy_course_id'] ) ? manual_docs_sanitize_academy_course_id( wp_unslash( $_POST['md_academy_course_id'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$course_name = isset( $_POST['md_academy_course_name'] ) ? manual_docs_sanitize_academy_course_name( wp_unslash( $_POST['md_academy_course_name'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	if ( '' !== $course_id ) {
		update_post_meta( $post_id, MANUAL_DOCS_ACADEMY_COURSE_ID_KEY, $course_id );
	} else {
		delete_post_meta( $post_id, MANUAL_DOCS_ACADEMY_COURSE_ID_KEY );
	}
	if ( '' !== $course_name ) {
		update_post_meta( $post_id, MANUAL_DOCS_ACADEMY_COURSE_NAME_KEY, $course_name );
	} else {
		delete_post_meta( $post_id, MANUAL_DOCS_ACADEMY_COURSE_NAME_KEY );
	}
}
add_action( 'save_post_topic', 'manual_docs_academy_metabox_save' );

/**
 * Markup for academy badges on topic cards / single topic.
 *
 * @param int $topic_id Topic ID.
 * @return string HTML (escaped).
 */
function manual_docs_academy_fields_badge_html( $topic_id ) {
	$topic_id = absint( $topic_id );
	if ( ! $topic_id || ! function_exists( 'bbp_get_topic_forum_id' ) ) {
		return '';
	}
	if ( ! manual_docs_is_academy_forum( (int) bbp_get_topic_forum_id( $topic_id ) ) ) {
		return '';
	}

	$fields = manual_docs_get_academy_fields( $topic_id );
	if ( '' === $fields['course_id'] && '' === $fields['course_name'] ) {
		return '';
	}

	$parts = array();
	if ( '' !== $fields['course_name'] ) {
		$parts[] = esc_html( $fields['course_name'] );
	}
	if ( '' !== $fields['course_id'] ) {
		$parts[] = sprintf(
			/* translators: %s: course id */
			esc_html__( 'Course ID: %s', 'manual-docs' ),
			esc_html( $fields['course_id'] )
		);
	}

	return '<p class="md-academy-badge">' . implode( ' · ', $parts ) . '</p>';
}

/**
 * Append academy badge under topic title in loops (fallback if template not used).
 *
 * @param int $topic_id Topic ID.
 */
function manual_docs_academy_after_topic_title( $topic_id = 0 ) {
	$topic_id = $topic_id ? absint( $topic_id ) : ( function_exists( 'bbp_get_topic_id' ) ? (int) bbp_get_topic_id() : 0 );
	$html     = manual_docs_academy_fields_badge_html( $topic_id );
	if ( $html ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_html.
	}
}
