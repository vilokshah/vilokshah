<?php
/**
 * Document version switching.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get version terms ordered (newest / custom order via term meta).
 *
 * @return WP_Term[]
 */
function manual_docs_get_versions() {
	$terms = get_terms( array(
		'taxonomy'   => 'doc_version',
		'hide_empty' => false,
	) );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}

	usort(
		$terms,
		static function ( $a, $b ) {
			$oa = (int) get_term_meta( $a->term_id, 'manual_docs_version_order', true );
			$ob = (int) get_term_meta( $b->term_id, 'manual_docs_version_order', true );
			if ( $oa === $ob ) {
				return strnatcasecmp( $b->name, $a->name );
			}
			return $oa <=> $ob;
		}
	);

	return $terms;
}

/**
 * Current document's version term.
 *
 * @param int|null $post_id Post ID.
 * @return WP_Term|null
 */
function manual_docs_get_doc_version( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$terms   = get_the_terms( $post_id, 'doc_version' );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return null;
	}
	return $terms[0];
}

/**
 * Find sibling document in another version via version group key.
 *
 * @param int    $post_id     Current post.
 * @param string $version_slug Target version slug.
 * @return WP_Post|null
 */
function manual_docs_find_version_sibling( $post_id, $version_slug ) {
	$group = get_post_meta( $post_id, '_manual_docs_version_group', true );
	if ( empty( $group ) ) {
		$group = get_post_field( 'post_name', $post_id );
	}

	$query = new WP_Query( array(
		'post_type'      => 'manual_documentation',
		'posts_per_page' => 1,
		'post_status'    => 'publish',
		'post__not_in'   => array( (int) $post_id ),
		'meta_key'       => '_manual_docs_version_group',
		'meta_value'     => $group,
		'tax_query'      => array(
			array(
				'taxonomy' => 'doc_version',
				'field'    => 'slug',
				'terms'    => $version_slug,
			),
		),
	) );

	if ( $query->have_posts() ) {
		return $query->posts[0];
	}

	// Fallback: match by slug within version.
	$slug  = get_post_field( 'post_name', $post_id );
	$query = new WP_Query( array(
		'post_type'      => 'manual_documentation',
		'name'           => $slug,
		'posts_per_page' => 1,
		'post_status'    => 'publish',
		'post__not_in'   => array( (int) $post_id ),
		'tax_query'      => array(
			array(
				'taxonomy' => 'doc_version',
				'field'    => 'slug',
				'terms'    => $version_slug,
			),
		),
	) );

	return $query->have_posts() ? $query->posts[0] : null;
}

/**
 * Render version switcher markup.
 *
 * @param int|null $post_id Post ID.
 */
function manual_docs_render_version_switcher( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$versions = manual_docs_get_versions();
	if ( empty( $versions ) ) {
		return;
	}

	$current = manual_docs_get_doc_version( $post_id );
	$current_slug = $current ? $current->slug : '';
	?>
	<div class="md-version-switcher" data-current="<?php echo esc_attr( $current_slug ); ?>">
		<label for="md-version-select" class="screen-reader-text"><?php esc_html_e( 'Document version', 'manual-docs' ); ?></label>
		<span class="md-version-label"><?php esc_html_e( 'Version', 'manual-docs' ); ?></span>
		<select id="md-version-select" class="md-version-select" data-post-id="<?php echo esc_attr( (string) $post_id ); ?>">
			<?php foreach ( $versions as $version ) : ?>
				<?php
				$sibling = manual_docs_find_version_sibling( $post_id, $version->slug );
				$url     = $sibling ? get_permalink( $sibling ) : get_term_link( $version );
				if ( is_wp_error( $url ) ) {
					continue;
				}
				$disabled = ( ! $sibling && $current_slug !== $version->slug );
				?>
				<option
					value="<?php echo esc_url( $url ); ?>"
					<?php selected( $current_slug, $version->slug ); ?>
					<?php disabled( $disabled ); ?>
				>
					<?php echo esc_html( $version->name ); ?>
					<?php if ( $disabled ) : ?>
						<?php echo esc_html( ' — ' . __( 'unavailable', 'manual-docs' ) ); ?>
					<?php endif; ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>
	<?php
}

/**
 * Version order field on add form.
 */
function manual_docs_version_add_fields() {
	?>
	<div class="form-field">
		<label for="manual_docs_version_order"><?php esc_html_e( 'Sort Order', 'manual-docs' ); ?></label>
		<input type="number" name="manual_docs_version_order" id="manual_docs_version_order" value="0" />
		<p><?php esc_html_e( 'Lower numbers appear first in the version switcher.', 'manual-docs' ); ?></p>
	</div>
	<?php
}
add_action( 'doc_version_add_form_fields', 'manual_docs_version_add_fields' );

/**
 * Version order on edit form.
 *
 * @param WP_Term $term Term.
 */
function manual_docs_version_edit_fields( $term ) {
	$order = (int) get_term_meta( $term->term_id, 'manual_docs_version_order', true );
	?>
	<tr class="form-field">
		<th scope="row"><label for="manual_docs_version_order"><?php esc_html_e( 'Sort Order', 'manual-docs' ); ?></label></th>
		<td>
			<input type="number" name="manual_docs_version_order" id="manual_docs_version_order" value="<?php echo esc_attr( (string) $order ); ?>" />
		</td>
	</tr>
	<?php
}
add_action( 'doc_version_edit_form_fields', 'manual_docs_version_edit_fields' );

/**
 * Save version order.
 *
 * @param int $term_id Term ID.
 */
function manual_docs_save_version_meta( $term_id ) {
	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	if ( isset( $_POST['manual_docs_version_order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		update_term_meta( $term_id, 'manual_docs_version_order', (int) $_POST['manual_docs_version_order'] ); // phpcs:ignore WordPress.Security.NonceVerification
	}
}
add_action( 'created_doc_version', 'manual_docs_save_version_meta' );
add_action( 'edited_doc_version', 'manual_docs_save_version_meta' );