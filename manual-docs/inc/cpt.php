<?php
/**
 * CPT / taxonomy compatibility with Manual theme data.
 *
 * Uses existing manual_documentation CPT when present.
 * Categories: manualdocumentationcategory (existing Manual taxonomy).
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Category taxonomy slug used by Manual / this theme.
 *
 * @return string
 */
function manual_docs_category_taxonomy() {
	return apply_filters( 'manual_docs_category_taxonomy', 'manualdocumentationcategory' );
}

/**
 * Register CPT/tax only when missing (safe theme swap).
 */
function manual_docs_register_cpt() {
	if ( ! post_type_exists( 'manual_documentation' ) ) {
		$labels = array(
			'name'               => __( 'Documentation', 'manual-docs' ),
			'singular_name'      => __( 'Document', 'manual-docs' ),
			'menu_name'          => __( 'Documentation', 'manual-docs' ),
			'add_new_item'       => __( 'Add New Documentation', 'manual-docs' ),
			'edit_item'          => __( 'Edit Documentation', 'manual-docs' ),
			'view_item'          => __( 'View Documentation', 'manual-docs' ),
			'all_items'          => __( 'All Documentation', 'manual-docs' ),
			'search_items'       => __( 'Search Documentation', 'manual-docs' ),
			'parent_item_colon'  => __( 'Parent Documentation:', 'manual-docs' ),
			'not_found'          => __( 'No documentation found.', 'manual-docs' ),
			'not_found_in_trash' => __( 'No documentation found in Trash.', 'manual-docs' ),
		);

		register_post_type(
			'manual_documentation',
			array(
				'labels'             => $labels,
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_rest'       => true,
				'query_var'          => true,
				'rewrite'            => array(
					'slug'       => 'documentation',
					'with_front' => false,
				),
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => true,
				'menu_position'      => 5,
				'menu_icon'          => 'dashicons-book-alt',
				'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'page-attributes', 'revisions', 'custom-fields' ),
			)
		);
	}

	$tax = manual_docs_category_taxonomy();
	if ( ! taxonomy_exists( $tax ) ) {
		register_taxonomy(
			$tax,
			'manual_documentation',
			array(
				'labels'            => array(
					'name'          => __( 'Documentation Categories', 'manual-docs' ),
					'singular_name' => __( 'Documentation Category', 'manual-docs' ),
					'search_items'  => __( 'Search Categories', 'manual-docs' ),
					'all_items'     => __( 'All Categories', 'manual-docs' ),
					'parent_item'   => __( 'Parent Category', 'manual-docs' ),
					'edit_item'     => __( 'Edit Category', 'manual-docs' ),
					'update_item'   => __( 'Update Category', 'manual-docs' ),
					'add_new_item'  => __( 'Add New Category', 'manual-docs' ),
					'new_item_name' => __( 'New Category Name', 'manual-docs' ),
					'menu_name'     => __( 'Documentation Categories', 'manual-docs' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'         => 'documentation-category',
					'with_front'   => false,
					'hierarchical' => true,
				),
			)
		);
	} else {
		// Ensure CPT is linked to existing Manual taxonomy.
		register_taxonomy_for_object_type( $tax, 'manual_documentation' );
	}
}
add_action( 'init', 'manual_docs_register_cpt', 20 );

/**
 * Flush rewrites on theme switch.
 */
function manual_docs_rewrite_flush() {
	manual_docs_register_cpt();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'manual_docs_rewrite_flush' );

/**
 * Category access role fields (add).
 */
function manual_docs_category_add_fields() {
	?>
	<div class="form-field">
		<label for="manual_docs_allowed_roles"><?php esc_html_e( 'Allowed Roles', 'manual-docs' ); ?></label>
		<input type="text" name="manual_docs_allowed_roles" id="manual_docs_allowed_roles" value="" />
		<p><?php esc_html_e( 'Comma-separated role slugs (e.g. subscriber,contributor). Empty = all logged-in users.', 'manual-docs' ); ?></p>
	</div>
	<?php
}

/**
 * Category access role fields (edit).
 *
 * @param WP_Term $term Term.
 */
function manual_docs_category_edit_fields( $term ) {
	$roles = get_term_meta( $term->term_id, 'manual_docs_allowed_roles', true );
	?>
	<tr class="form-field">
		<th scope="row"><label for="manual_docs_allowed_roles"><?php esc_html_e( 'Allowed Roles', 'manual-docs' ); ?></label></th>
		<td>
			<input type="text" name="manual_docs_allowed_roles" id="manual_docs_allowed_roles" value="<?php echo esc_attr( $roles ); ?>" class="regular-text" />
			<p class="description"><?php esc_html_e( 'Comma-separated role slugs. Empty = all logged-in users.', 'manual-docs' ); ?></p>
		</td>
	</tr>
	<?php
}

/**
 * Save category role meta.
 *
 * @param int $term_id Term ID.
 */
function manual_docs_save_category_meta( $term_id ) {
	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	if ( isset( $_POST['manual_docs_allowed_roles'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$raw   = sanitize_text_field( wp_unslash( $_POST['manual_docs_allowed_roles'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$roles = array_filter( array_map( 'sanitize_key', array_map( 'trim', explode( ',', $raw ) ) ) );
		update_term_meta( $term_id, 'manual_docs_allowed_roles', implode( ',', $roles ) );
	}
}

/**
 * Hook taxonomy term fields after init (taxonomy may already exist).
 */
function manual_docs_bind_category_meta_hooks() {
	$tax = manual_docs_category_taxonomy();
	add_action( "{$tax}_add_form_fields", 'manual_docs_category_add_fields' );
	add_action( "{$tax}_edit_form_fields", 'manual_docs_category_edit_fields' );
	add_action( "created_{$tax}", 'manual_docs_save_category_meta' );
	add_action( "edited_{$tax}", 'manual_docs_save_category_meta' );
}
add_action( 'init', 'manual_docs_bind_category_meta_hooks', 30 );