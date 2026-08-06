<?php
/**
 * CPT / taxonomy compatibility with Manual theme data.
 *
 * Uses existing manual_documentation CPT when present.
 * Categories: manualdocumentationcategory (existing Manual taxonomy).
 * Forces show_in_rest so Gutenberg can assign categories + parents.
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
 * CPT permalink base (e.g. documentation).
 *
 * @return string
 */
function manual_docs_cpt_rewrite_slug() {
	$slug = 'documentation';
	if ( function_exists( 'manual_docs_get_option' ) ) {
		$saved = manual_docs_get_option( 'cpt_rewrite_slug', '' );
		if ( is_string( $saved ) && $saved !== '' ) {
			$slug = $saved;
		}
	}
	return sanitize_title( apply_filters( 'manual_docs_cpt_rewrite_slug', $slug ) );
}

/**
 * Force block-editor friendly args + hierarchical rewrites on the documentation CPT.
 *
 * @param array  $args      Args.
 * @param string $post_type Post type.
 * @return array
 */
function manual_docs_force_cpt_rest_args( $args, $post_type ) {
	if ( 'manual_documentation' !== $post_type ) {
		return $args;
	}

	$args['show_in_rest']       = true;
	$args['hierarchical']       = true;
	$args['public']             = true;
	$args['publicly_queryable'] = true;
	$args['show_ui']            = true;
	$args['map_meta_cap']       = isset( $args['map_meta_cap'] ) ? $args['map_meta_cap'] : true;
	$args['query_var']          = true;

	$supports = isset( $args['supports'] ) && is_array( $args['supports'] ) ? $args['supports'] : array( 'title', 'editor' );
	foreach ( array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'page-attributes', 'revisions', 'custom-fields' ) as $feature ) {
		if ( ! in_array( $feature, $supports, true ) ) {
			$supports[] = $feature;
		}
	}
	$args['supports'] = $supports;

	if ( empty( $args['rest_base'] ) ) {
		$args['rest_base'] = 'manual_documentation';
	}

	// Critical: hierarchical rewrite so /documentation/flamingo/ and nested URLs resolve.
	$slug = manual_docs_cpt_rewrite_slug();
	if ( empty( $args['rewrite'] ) || false === $args['rewrite'] ) {
		$args['rewrite'] = array(
			'slug'         => $slug,
			'with_front'   => false,
			'hierarchical' => true,
		);
	} elseif ( is_array( $args['rewrite'] ) ) {
		$args['rewrite']['hierarchical'] = true;
		$args['rewrite']['with_front']   = false;
		if ( empty( $args['rewrite']['slug'] ) ) {
			$args['rewrite']['slug'] = $slug;
		}
	}

	if ( ! isset( $args['has_archive'] ) || false === $args['has_archive'] ) {
		$args['has_archive'] = $slug;
	}

	return $args;
}
add_filter( 'register_post_type_args', 'manual_docs_force_cpt_rest_args', 20, 2 );

/**
 * Force Gutenberg category panel for Manual taxonomy.
 *
 * @param array  $args     Args.
 * @param string $taxonomy Taxonomy.
 * @return array
 */
function manual_docs_force_taxonomy_rest_args( $args, $taxonomy ) {
	if ( 'manualdocumentationcategory' !== $taxonomy && $taxonomy !== manual_docs_category_taxonomy() ) {
		return $args;
	}

	$args['show_in_rest']      = true;
	$args['hierarchical']      = true;
	$args['public']            = true;
	$args['show_ui']           = true;
	$args['show_admin_column'] = true;
	$args['show_in_nav_menus'] = true;
	$args['rest_base']         = ! empty( $args['rest_base'] ) ? $args['rest_base'] : 'manualdocumentationcategory';

	return $args;
}
add_filter( 'register_taxonomy_args', 'manual_docs_force_taxonomy_rest_args', 20, 2 );

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
				'rest_base'          => 'manual_documentation',
				'query_var'          => true,
				'rewrite'            => array(
					'slug'         => manual_docs_cpt_rewrite_slug(),
					'with_front'   => false,
					'hierarchical' => true,
				),
				'capability_type'    => 'post',
				'map_meta_cap'       => true,
				'has_archive'        => manual_docs_cpt_rewrite_slug(),
				'hierarchical'       => true,
				'menu_position'      => 5,
				'menu_icon'          => 'dashicons-book-alt',
				'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'page-attributes', 'revisions', 'custom-fields' ),
			)
		);
	} else {
		// Ensure hierarchical parent UI + REST even if CPT was registered earlier.
		add_post_type_support( 'manual_documentation', array( 'page-attributes', 'editor', 'title', 'excerpt', 'thumbnail', 'revisions', 'custom-fields', 'author' ) );
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
				'rest_base'         => 'manualdocumentationcategory',
				'rewrite'           => array(
					'slug'         => 'documentation-category',
					'with_front'   => false,
					'hierarchical' => true,
				),
			)
		);
	} else {
		register_taxonomy_for_object_type( $tax, 'manual_documentation' );
	}
}
add_action( 'init', 'manual_docs_register_cpt', 25 );

/**
 * Late pass: attach taxonomy + REST visibility if another plugin registered first.
 */
function manual_docs_late_bind_taxonomy() {
	$tax = manual_docs_category_taxonomy();
	if ( taxonomy_exists( $tax ) ) {
		register_taxonomy_for_object_type( $tax, 'manual_documentation' );
	}

	global $wp_taxonomies;
	if ( isset( $wp_taxonomies[ $tax ] ) ) {
		$wp_taxonomies[ $tax ]->show_in_rest = true;
		$wp_taxonomies[ $tax ]->hierarchical = true;
		if ( empty( $wp_taxonomies[ $tax ]->rest_base ) ) {
			$wp_taxonomies[ $tax ]->rest_base = $tax;
		}
	}

	global $wp_post_types;
	if ( isset( $wp_post_types['manual_documentation'] ) ) {
		$wp_post_types['manual_documentation']->show_in_rest = true;
		$wp_post_types['manual_documentation']->hierarchical = true;
	}
}
add_action( 'init', 'manual_docs_late_bind_taxonomy', 99 );

/**
 * Extra rewrite fallbacks for documentation/{slug}/ paths.
 */
function manual_docs_register_doc_rewrites() {
	$slug = manual_docs_cpt_rewrite_slug();
	if ( ! $slug ) {
		return;
	}

	// Top-level doc: /documentation/flamingo/
	add_rewrite_rule(
		'^' . preg_quote( $slug, '/' ) . '/([^/]+)/?$',
		'index.php?manual_documentation=$matches[1]',
		'top'
	);

	// Nested docs: /documentation/flamingo/getting-started/installation/
	add_rewrite_rule(
		'^' . preg_quote( $slug, '/' ) . '/(.+?)/?$',
		'index.php?manual_documentation=$matches[1]',
		'top'
	);
}
add_action( 'init', 'manual_docs_register_doc_rewrites', 30 );

/**
 * Flush rewrite rules on theme switch only.
 */
function manual_docs_rewrite_flush() {
	manual_docs_register_cpt();
	manual_docs_register_doc_rewrites();
	flush_rewrite_rules();
	update_option( 'manual_docs_permalinks_flushed_2_3', 1 );
}
add_action( 'after_switch_theme', 'manual_docs_rewrite_flush' );

/**
 * One-time permalink flush after 2.3 rewrite fix.
 */
function manual_docs_maybe_flush_permalinks_once() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( get_option( 'manual_docs_permalinks_flushed_2_3' ) ) {
		return;
	}
	manual_docs_register_cpt();
	manual_docs_register_doc_rewrites();
	flush_rewrite_rules( false );
	update_option( 'manual_docs_permalinks_flushed_2_3', 1 );
}
add_action( 'admin_init', 'manual_docs_maybe_flush_permalinks_once', 1 );

/**
 * Handle manual "Flush permalinks" from theme settings.
 */
function manual_docs_handle_flush_permalinks() {
	if ( ! isset( $_POST['manual_docs_flush_permalinks'] ) ) {
		return;
	}
	if ( ! isset( $_POST['manual_docs_options_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['manual_docs_options_nonce'] ) ), 'manual_docs_save_options' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	manual_docs_register_cpt();
	manual_docs_register_doc_rewrites();
	flush_rewrite_rules();
	update_option( 'manual_docs_permalinks_flushed_2_3', 1 );
	add_settings_error( 'manual_docs_options', 'manual_docs_flushed', __( 'Permalinks flushed. Try your documentation URL again.', 'manual-docs' ), 'updated' );
}
add_action( 'admin_init', 'manual_docs_handle_flush_permalinks', 0 );

/**
 * Admin notice when pretty permalinks are disabled (causes Apache 404 on /documentation/...).
 */
function manual_docs_permalink_structure_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$structure = get_option( 'permalink_structure' );
	if ( ! empty( $structure ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'Manual Docs: Pretty permalinks are disabled. Documentation URLs like /documentation/flamingo/ will 404.', 'manual-docs' );
	echo ' <a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">' . esc_html__( 'Enable pretty permalinks', 'manual-docs' ) . '</a>';
	echo '</p></div>';
}
add_action( 'admin_notices', 'manual_docs_permalink_structure_notice' );

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