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

	if ( ! isset( $args['has_archive'] ) || false === $args['has_archive'] || true === $args['has_archive'] || is_string( $args['has_archive'] ) ) {
		$hide_archive = function_exists( 'manual_docs_get_option' ) ? (bool) manual_docs_get_option( 'hide_docs_archive', true ) : true;
		$args['has_archive'] = $hide_archive ? false : $slug;
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
	if ( function_exists( 'manual_docs_hard_flush_rewrites' ) ) {
		manual_docs_hard_flush_rewrites();
		return;
	}
	manual_docs_register_cpt();
	manual_docs_register_doc_rewrites();
	flush_rewrite_rules( true );
	update_option( 'manual_docs_permalinks_flushed_2_5', 1 );
}
add_action( 'after_switch_theme', 'manual_docs_rewrite_flush' );

/**
 * One-time hard permalink flush after 2.5 fix.
 */
function manual_docs_maybe_flush_permalinks_once() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( get_option( 'manual_docs_permalinks_flushed_2_5' ) ) {
		return;
	}
	if ( function_exists( 'manual_docs_hard_flush_rewrites' ) ) {
		manual_docs_hard_flush_rewrites();
	} else {
		manual_docs_register_cpt();
		manual_docs_register_doc_rewrites();
		flush_rewrite_rules( true );
		update_option( 'manual_docs_permalinks_flushed_2_5', 1 );
	}
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

	if ( ! get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}

	$result = function_exists( 'manual_docs_hard_flush_rewrites' ) ? manual_docs_hard_flush_rewrites() : null;
	$msg    = __( 'Permalinks hard-flushed.', 'manual-docs' );
	if ( is_array( $result ) ) {
		if ( true === $result['htaccess'] ) {
			$msg .= ' ' . __( '.htaccess updated.', 'manual-docs' );
		} elseif ( is_wp_error( $result['htaccess'] ) ) {
			$msg .= ' ' . $result['htaccess']->get_error_message();
			$msg .= ' ' . __( 'Use “Fix Local 404s now” (index.php URLs) instead.', 'manual-docs' );
		}
	}

	add_settings_error( 'manual_docs_options', 'manual_docs_flushed', $msg, 'updated' );
}
add_action( 'admin_init', 'manual_docs_handle_flush_permalinks', 0 );

/**
 * One-click Local 404 fix: switch to index.php permalinks + hard flush.
 */
function manual_docs_handle_fix_local_404() {
	if ( ! isset( $_POST['manual_docs_fix_local_404'] ) ) {
		return;
	}
	if ( ! isset( $_POST['manual_docs_options_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['manual_docs_options_nonce'] ) ), 'manual_docs_save_options' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$opts = get_option( 'manual_docs_options', array() );
	if ( ! is_array( $opts ) ) {
		$opts = array();
	}
	$opts['permalink_mode'] = 'index_php';
	update_option( 'manual_docs_options', $opts );
	update_option( 'permalink_structure', '/index.php/%postname%/' );

	if ( function_exists( 'manual_docs_hard_flush_rewrites' ) ) {
		manual_docs_hard_flush_rewrites();
	} else {
		flush_rewrite_rules( true );
	}

	$example = function_exists( 'manual_docs_example_working_url' ) ? manual_docs_example_working_url() : home_url( '/index.php/documentation/' );
	add_settings_error(
		'manual_docs_options',
		'manual_docs_local_fixed',
		sprintf(
			/* translators: %s: example URL */
			__( 'Switched to index.php URLs (no Apache rewrite needed). Open: %s', 'manual-docs' ),
			esc_html( $example )
		),
		'updated'
	);
}
add_action( 'admin_init', 'manual_docs_handle_fix_local_404', 0 );

/**
 * Restore pretty documentation URLs after .htaccess is in place.
 */
function manual_docs_handle_restore_pretty() {
	if ( ! isset( $_POST['manual_docs_restore_pretty'] ) ) {
		return;
	}
	if ( ! isset( $_POST['manual_docs_options_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['manual_docs_options_nonce'] ) ), 'manual_docs_save_options' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$opts = get_option( 'manual_docs_options', array() );
	if ( ! is_array( $opts ) ) {
		$opts = array();
	}
	$opts['permalink_mode'] = 'pretty';
	update_option( 'manual_docs_options', $opts );
	update_option( 'permalink_structure', '/%postname%/' );

	$result = function_exists( 'manual_docs_hard_flush_rewrites' ) ? manual_docs_hard_flush_rewrites() : null;
	$msg    = __( 'Restored pretty URLs (/%postname%/) and flushed rewrites.', 'manual-docs' );
	if ( is_array( $result ) && is_wp_error( $result['htaccess'] ) ) {
		$msg .= ' ' . $result['htaccess']->get_error_message();
		$msg .= ' ' . __( 'Copy sample-data/htaccess-digidocs.txt into your digidocs/.htaccess manually, then try again. If Local still shows Apache Not Found, stay on index.php mode.', 'manual-docs' );
	} elseif ( is_array( $result ) && true === $result['htaccess'] ) {
		$msg .= ' ' . __( '.htaccess updated. Test a /documentation/… URL without index.php.', 'manual-docs' );
	}

	add_settings_error( 'manual_docs_options', 'manual_docs_pretty_restored', $msg, 'updated' );
}
add_action( 'admin_init', 'manual_docs_handle_restore_pretty', 0 );

/**
 * Admin notice when pretty permalinks are disabled.
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
	$roles     = manual_docs_get_editable_roles_list();
	$selected  = array();
	?>
	<div class="form-field">
		<span><?php esc_html_e( 'Allowed Roles', 'manual-docs' ); ?></span>
		<input type="hidden" name="manual_docs_allowed_roles_present" value="1" />
		<fieldset style="border:0;margin:0.5em 0 0;padding:0;">
			<?php foreach ( $roles as $slug => $label ) : ?>
				<label style="display:block;margin:0.25em 0;">
					<input type="checkbox" name="manual_docs_allowed_roles[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $selected, true ) ); ?> />
					<?php echo esc_html( $label ); ?>
					<code style="opacity:0.65;"><?php echo esc_html( $slug ); ?></code>
				</label>
			<?php endforeach; ?>
		</fieldset>
		<p><?php esc_html_e( 'Leave all unchecked to allow every logged-in user. Checked roles restrict this category to those roles only.', 'manual-docs' ); ?></p>
	</div>
	<?php
}

/**
 * Category access role fields (edit).
 *
 * @param WP_Term $term Term.
 */
function manual_docs_category_edit_fields( $term ) {
	$roles_raw = get_term_meta( $term->term_id, 'manual_docs_allowed_roles', true );
	$selected  = array_filter( array_map( 'sanitize_key', array_map( 'trim', explode( ',', (string) $roles_raw ) ) ) );
	$roles     = manual_docs_get_editable_roles_list();
	?>
	<tr class="form-field">
		<th scope="row"><?php esc_html_e( 'Allowed Roles', 'manual-docs' ); ?></th>
		<td>
			<input type="hidden" name="manual_docs_allowed_roles_present" value="1" />
			<fieldset style="border:0;margin:0;padding:0;">
				<?php foreach ( $roles as $slug => $label ) : ?>
					<label style="display:block;margin:0.35em 0;">
						<input type="checkbox" name="manual_docs_allowed_roles[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $selected, true ) ); ?> />
						<?php echo esc_html( $label ); ?>
						<code style="opacity:0.65;"><?php echo esc_html( $slug ); ?></code>
					</label>
				<?php endforeach; ?>
			</fieldset>
			<p class="description"><?php esc_html_e( 'Leave all unchecked to allow every logged-in user. Checked roles restrict this category to those roles only.', 'manual-docs' ); ?></p>
		</td>
	</tr>
	<?php
}

/**
 * Editable role slug => label map for category access UI.
 *
 * @return array<string,string>
 */
function manual_docs_get_editable_roles_list() {
	$out = array();
	if ( ! function_exists( 'wp_roles' ) ) {
		return $out;
	}
	foreach ( wp_roles()->roles as $slug => $role ) {
		$out[ sanitize_key( $slug ) ] = isset( $role['name'] ) ? translate_user_role( $role['name'] ) : $slug;
	}
	/**
	 * Filter roles shown in category Allowed Roles checkboxes.
	 *
	 * @param array<string,string> $out Role slug => label.
	 */
	return apply_filters( 'manual_docs_editable_roles_list', $out );
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
	if ( empty( $_POST['manual_docs_allowed_roles_present'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}

	$roles = array();
	if ( isset( $_POST['manual_docs_allowed_roles'] ) && is_array( $_POST['manual_docs_allowed_roles'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$raw   = wp_unslash( $_POST['manual_docs_allowed_roles'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput,WordPress.Security.NonceVerification
		$roles = array_values( array_unique( array_filter( array_map( 'sanitize_key', $raw ) ) ) );
		$valid = array_keys( manual_docs_get_editable_roles_list() );
		$roles = array_values( array_intersect( $roles, $valid ) );
	}

	update_term_meta( $term_id, 'manual_docs_allowed_roles', implode( ',', $roles ) );
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