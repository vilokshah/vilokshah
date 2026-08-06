<?php
/**
 * Custom post type and taxonomies for documentation.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register manual_documentation CPT and taxonomies.
 */
function manual_docs_register_cpt() {
	$labels = array(
		'name'               => __( 'Documentation', 'manual-docs' ),
		'singular_name'      => __( 'Document', 'manual-docs' ),
		'menu_name'          => __( 'Documentation', 'manual-docs' ),
		'name_admin_bar'     => __( 'Document', 'manual-docs' ),
		'add_new'            => __( 'Add New', 'manual-docs' ),
		'add_new_item'       => __( 'Add New Document', 'manual-docs' ),
		'new_item'           => __( 'New Document', 'manual-docs' ),
		'edit_item'          => __( 'Edit Document', 'manual-docs' ),
		'view_item'          => __( 'View Document', 'manual-docs' ),
		'all_items'          => __( 'All Documents', 'manual-docs' ),
		'search_items'       => __( 'Search Documents', 'manual-docs' ),
		'parent_item_colon'  => __( 'Parent Document:', 'manual-docs' ),
		'not_found'          => __( 'No documents found.', 'manual-docs' ),
		'not_found_in_trash' => __( 'No documents found in Trash.', 'manual-docs' ),
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_rest'       => true,
		'query_var'          => true,
		'rewrite'            => array(
			'slug'       => 'docs',
			'with_front' => false,
		),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => true,
		'menu_position'      => 5,
		'menu_icon'          => 'dashicons-book-alt',
		'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'page-attributes', 'revisions', 'custom-fields' ),
		'taxonomies'         => array( 'doc_category', 'doc_version' ),
	);

	register_post_type( 'manual_documentation', $args );

	register_taxonomy(
		'doc_category',
		'manual_documentation',
		array(
			'labels'            => array(
				'name'          => __( 'Doc Categories', 'manual-docs' ),
				'singular_name' => __( 'Doc Category', 'manual-docs' ),
				'search_items'  => __( 'Search Categories', 'manual-docs' ),
				'all_items'     => __( 'All Categories', 'manual-docs' ),
				'parent_item'   => __( 'Parent Category', 'manual-docs' ),
				'edit_item'     => __( 'Edit Category', 'manual-docs' ),
				'update_item'   => __( 'Update Category', 'manual-docs' ),
				'add_new_item'  => __( 'Add New Category', 'manual-docs' ),
				'new_item_name' => __( 'New Category Name', 'manual-docs' ),
				'menu_name'     => __( 'Categories', 'manual-docs' ),
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'         => 'docs/category',
				'with_front'   => false,
				'hierarchical' => true,
			),
		)
	);

	register_taxonomy(
		'doc_version',
		'manual_documentation',
		array(
			'labels'            => array(
				'name'          => __( 'Doc Versions', 'manual-docs' ),
				'singular_name' => __( 'Doc Version', 'manual-docs' ),
				'search_items'  => __( 'Search Versions', 'manual-docs' ),
				'all_items'     => __( 'All Versions', 'manual-docs' ),
				'edit_item'     => __( 'Edit Version', 'manual-docs' ),
				'update_item'   => __( 'Update Version', 'manual-docs' ),
				'add_new_item'  => __( 'Add New Version', 'manual-docs' ),
				'new_item_name' => __( 'New Version Name', 'manual-docs' ),
				'menu_name'     => __( 'Versions', 'manual-docs' ),
			),
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => 'docs/version',
				'with_front' => false,
			),
		)
	);
}
add_action( 'init', 'manual_docs_register_cpt' );

/**
 * Flush rewrite rules on theme switch.
 */
function manual_docs_rewrite_flush() {
	manual_docs_register_cpt();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'manual_docs_rewrite_flush' );

/**
 * Document meta box: related version group key & order hints.
 */
function manual_docs_add_meta_boxes() {
	add_meta_box(
		'manual_docs_meta',
		__( 'Document Settings', 'manual-docs' ),
		'manual_docs_render_meta_box',
		'manual_documentation',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'manual_docs_add_meta_boxes' );

/**
 * Render document settings meta box.
 *
 * @param WP_Post $post Post object.
 */
function manual_docs_render_meta_box( $post ) {
	wp_nonce_field( 'manual_docs_save_meta', 'manual_docs_meta_nonce' );

	$version_group = get_post_meta( $post->ID, '_manual_docs_version_group', true );
	$is_featured   = get_post_meta( $post->ID, '_manual_docs_featured', true );
	?>
	<p>
		<label for="manual_docs_version_group"><strong><?php esc_html_e( 'Version Group Key', 'manual-docs' ); ?></strong></label>
		<input type="text" class="widefat" id="manual_docs_version_group" name="manual_docs_version_group" value="<?php echo esc_attr( $version_group ); ?>" placeholder="<?php esc_attr_e( 'e.g. getting-started', 'manual-docs' ); ?>" />
		<span class="description"><?php esc_html_e( 'Same key links documents across versions for the version switcher.', 'manual-docs' ); ?></span>
	</p>
	<p>
		<label>
			<input type="checkbox" name="manual_docs_featured" value="1" <?php checked( $is_featured, '1' ); ?> />
			<?php esc_html_e( 'Featured on documentation home', 'manual-docs' ); ?>
		</label>
	</p>
	<?php
}

/**
 * Save document meta.
 *
 * @param int $post_id Post ID.
 */
function manual_docs_save_meta( $post_id ) {
	if ( ! isset( $_POST['manual_docs_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['manual_docs_meta_nonce'] ) ), 'manual_docs_save_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['manual_docs_version_group'] ) ) {
		update_post_meta( $post_id, '_manual_docs_version_group', sanitize_title( wp_unslash( $_POST['manual_docs_version_group'] ) ) );
	}

	$featured = isset( $_POST['manual_docs_featured'] ) ? '1' : '0';
	update_post_meta( $post_id, '_manual_docs_featured', $featured );
}
add_action( 'save_post_manual_documentation', 'manual_docs_save_meta' );

/**
 * Category access role meta on taxonomy term edit.
 */
function manual_docs_category_add_fields() {
	?>
	<div class="form-field">
		<label for="manual_docs_allowed_roles"><?php esc_html_e( 'Allowed Roles', 'manual-docs' ); ?></label>
		<input type="text" name="manual_docs_allowed_roles" id="manual_docs_allowed_roles" value="" />
		<p><?php esc_html_e( 'Comma-separated role slugs that may view this category (e.g. subscriber,contributor). Leave empty for all logged-in users.', 'manual-docs' ); ?></p>
	</div>
	<?php
}
add_action( 'doc_category_add_form_fields', 'manual_docs_category_add_fields' );

/**
 * Edit category role fields.
 *
 * @param WP_Term $term Term object.
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
add_action( 'doc_category_edit_form_fields', 'manual_docs_category_edit_fields' );

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
add_action( 'created_doc_category', 'manual_docs_save_category_meta' );
add_action( 'edited_doc_category', 'manual_docs_save_category_meta' );