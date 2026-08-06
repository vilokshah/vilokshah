<?php
/**
 * Theme options (admin settings page).
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default theme options.
 *
 * @return array
 */
function manual_docs_default_options() {
	return array(
		'require_login'        => 1,
		'primary_color'        => '#e8eef7',
		'accent_color'         => '#3b82f6',
		'header_bg'            => '#0b1220',
		'sidebar_bg'           => '#111827',
		'content_bg'           => '#0f172a',
		'page_bg'              => '#020617',
		'text_color'           => '#cbd5e1',
		'link_color'           => '#60a5fa',
		'pdf_color'            => '#f87171',
		'active_bar_color'     => '#f43f5e',
		'brand_name'           => '',
		'hero_title'           => __( 'Documentation', 'manual-docs' ),
		'hero_text'            => __( 'Search guides, explore products, and find answers fast.', 'manual-docs' ),
		'hero_eyebrow'         => '',
		'show_community_cta'   => 1,
		'version_label'        => __( 'Release version', 'manual-docs' ),
		'version_root_slugs'   => 'goat,flamingo,hummingbird',
		'version_root_ids'     => '',
		'default_version_slug' => 'goat',
		'cpt_rewrite_slug'     => 'documentation',
		'show_toc'             => 1,
		'show_pdf'             => 1,
		'show_updated'         => 1,
		'show_edit_link'       => 1,
		'tree_expand_active'   => 1,
		'header_tagline'       => '',
		'login_message'        => __( 'Please log in to view documentation.', 'manual-docs' ),
		'footer_text'          => '',
	);
}

/**
 * Get all options merged with defaults.
 *
 * @return array
 */
function manual_docs_get_options() {
	$saved = get_option( 'manual_docs_options', array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return array_merge( manual_docs_default_options(), $saved );
}

/**
 * One-time upgrade to darker defaults (v2.1) when still on old light palette.
 */
function manual_docs_upgrade_dark_palette() {
	if ( get_option( 'manual_docs_dark_palette_2_1' ) ) {
		return;
	}
	$saved = get_option( 'manual_docs_options', array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	$old_light = array(
		'header_bg'  => '#ffffff',
		'sidebar_bg' => '#f4f1ea',
		'content_bg' => '#fbf8f2',
		'page_bg'    => '#f7f4ee',
	);
	$defaults = manual_docs_default_options();
	$is_old   = empty( $saved ) || ( isset( $saved['header_bg'] ) && '#ffffff' === strtolower( $saved['header_bg'] ) );
	if ( $is_old ) {
		foreach ( array( 'primary_color', 'accent_color', 'header_bg', 'sidebar_bg', 'content_bg', 'page_bg', 'text_color', 'link_color', 'pdf_color', 'active_bar_color' ) as $key ) {
			$saved[ $key ] = $defaults[ $key ];
		}
		update_option( 'manual_docs_options', $saved );
	}
	update_option( 'manual_docs_dark_palette_2_1', 1 );
}
add_action( 'after_setup_theme', 'manual_docs_upgrade_dark_palette', 20 );

/**
 * Get a single option.
 *
 * @param string $key     Key.
 * @param mixed  $default Default.
 * @return mixed
 */
function manual_docs_get_option( $key, $default = null ) {
	$options = manual_docs_get_options();
	if ( array_key_exists( $key, $options ) ) {
		return $options[ $key ];
	}
	return $default;
}

/**
 * Register admin menu.
 */
function manual_docs_options_menu() {
	add_theme_page(
		__( 'Manual Docs Settings', 'manual-docs' ),
		__( 'Manual Docs', 'manual-docs' ),
		'edit_theme_options',
		'manual-docs-settings',
		'manual_docs_render_options_page'
	);
}
add_action( 'admin_menu', 'manual_docs_options_menu' );

/**
 * Enqueue admin assets for color pickers.
 *
 * @param string $hook Hook.
 */
function manual_docs_options_assets( $hook ) {
	if ( 'appearance_page_manual-docs-settings' !== $hook ) {
		return;
	}
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );
	wp_add_inline_script( 'wp-color-picker', 'jQuery(function($){$(".md-color-field").wpColorPicker();});' );
}
add_action( 'admin_enqueue_scripts', 'manual_docs_options_assets' );

/**
 * Save options.
 */
function manual_docs_save_options() {
	if ( ! isset( $_POST['manual_docs_options_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['manual_docs_options_nonce'] ) ), 'manual_docs_save_options' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$defaults = manual_docs_default_options();
	$incoming = isset( $_POST['manual_docs_options'] ) ? wp_unslash( $_POST['manual_docs_options'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( ! is_array( $incoming ) ) {
		$incoming = array();
	}

	$clean = array();
	$color_keys = array( 'primary_color', 'accent_color', 'header_bg', 'sidebar_bg', 'content_bg', 'page_bg', 'text_color', 'link_color', 'pdf_color', 'active_bar_color' );
	$text_keys  = array( 'brand_name', 'hero_title', 'hero_text', 'hero_eyebrow', 'version_label', 'version_root_slugs', 'version_root_ids', 'default_version_slug', 'cpt_rewrite_slug', 'header_tagline', 'login_message', 'footer_text' );
	$bool_keys  = array( 'require_login', 'show_community_cta', 'show_toc', 'show_pdf', 'show_updated', 'show_edit_link', 'tree_expand_active' );

	foreach ( $color_keys as $key ) {
		$val = isset( $incoming[ $key ] ) ? sanitize_hex_color( $incoming[ $key ] ) : '';
		$clean[ $key ] = $val ? $val : $defaults[ $key ];
	}
	foreach ( $text_keys as $key ) {
		$clean[ $key ] = isset( $incoming[ $key ] ) ? sanitize_text_field( $incoming[ $key ] ) : $defaults[ $key ];
	}
	if ( ! empty( $clean['cpt_rewrite_slug'] ) ) {
		$clean['cpt_rewrite_slug'] = sanitize_title( $clean['cpt_rewrite_slug'] );
	} else {
		$clean['cpt_rewrite_slug'] = 'documentation';
	}
	foreach ( $bool_keys as $key ) {
		$clean[ $key ] = ! empty( $incoming[ $key ] ) ? 1 : 0;
	}

	$old = get_option( 'manual_docs_options', array() );
	update_option( 'manual_docs_options', $clean );

	// Flush when rewrite slug changes.
	$old_slug = is_array( $old ) && ! empty( $old['cpt_rewrite_slug'] ) ? $old['cpt_rewrite_slug'] : 'documentation';
	if ( $old_slug !== $clean['cpt_rewrite_slug'] ) {
		delete_option( 'manual_docs_permalinks_flushed_2_3' );
		flush_rewrite_rules( false );
		update_option( 'manual_docs_permalinks_flushed_2_3', 1 );
	}

	add_settings_error( 'manual_docs_options', 'manual_docs_saved', __( 'Settings saved.', 'manual-docs' ), 'updated' );
}
add_action( 'admin_init', 'manual_docs_save_options' );

/**
 * Render settings page.
 */
function manual_docs_render_options_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$o = manual_docs_get_options();
	settings_errors( 'manual_docs_options' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Manual Docs Settings', 'manual-docs' ); ?></h1>
		<p><?php esc_html_e( 'Configure branding, colors, access, and release-version roots (parent documentation pages such as goat, flamingo, hummingbird).', 'manual-docs' ); ?></p>
		<form method="post">
			<?php wp_nonce_field( 'manual_docs_save_options', 'manual_docs_options_nonce' ); ?>

			<h2 class="title"><?php esc_html_e( 'Branding', 'manual-docs' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th><label for="brand_name"><?php esc_html_e( 'Brand name', 'manual-docs' ); ?></label></th>
					<td><input class="regular-text" type="text" id="brand_name" name="manual_docs_options[brand_name]" value="<?php echo esc_attr( $o['brand_name'] ); ?>" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="header_tagline"><?php esc_html_e( 'Header tagline', 'manual-docs' ); ?></label></th>
					<td><input class="regular-text" type="text" id="header_tagline" name="manual_docs_options[header_tagline]" value="<?php echo esc_attr( $o['header_tagline'] ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="hero_eyebrow"><?php esc_html_e( 'Home eyebrow', 'manual-docs' ); ?></label></th>
					<td><input class="regular-text" type="text" id="hero_eyebrow" name="manual_docs_options[hero_eyebrow]" value="<?php echo esc_attr( $o['hero_eyebrow'] ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="hero_title"><?php esc_html_e( 'Home title', 'manual-docs' ); ?></label></th>
					<td><input class="regular-text" type="text" id="hero_title" name="manual_docs_options[hero_title]" value="<?php echo esc_attr( $o['hero_title'] ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="hero_text"><?php esc_html_e( 'Home subtitle', 'manual-docs' ); ?></label></th>
					<td><input class="large-text" type="text" id="hero_text" name="manual_docs_options[hero_text]" value="<?php echo esc_attr( $o['hero_text'] ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="footer_text"><?php esc_html_e( 'Footer text', 'manual-docs' ); ?></label></th>
					<td><input class="large-text" type="text" id="footer_text" name="manual_docs_options[footer_text]" value="<?php echo esc_attr( $o['footer_text'] ); ?>" /></td>
				</tr>
			</table>

			<h2 class="title"><?php esc_html_e( 'Colors', 'manual-docs' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				$colors = array(
					'primary_color'    => __( 'Primary / header text', 'manual-docs' ),
					'accent_color'     => __( 'Accent', 'manual-docs' ),
					'link_color'       => __( 'Link color', 'manual-docs' ),
					'active_bar_color' => __( 'Active tree bar', 'manual-docs' ),
					'pdf_color'        => __( 'PDF button', 'manual-docs' ),
					'header_bg'        => __( 'Header background', 'manual-docs' ),
					'sidebar_bg'       => __( 'Sidebar background', 'manual-docs' ),
					'content_bg'       => __( 'Content panel background', 'manual-docs' ),
					'page_bg'          => __( 'Page background', 'manual-docs' ),
					'text_color'       => __( 'Body text', 'manual-docs' ),
				);
				foreach ( $colors as $key => $label ) :
					?>
					<tr>
						<th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td><input class="md-color-field" type="text" id="<?php echo esc_attr( $key ); ?>" name="manual_docs_options[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $o[ $key ] ); ?>" /></td>
					</tr>
				<?php endforeach; ?>
			</table>

			<h2 class="title"><?php esc_html_e( 'Release versions (parent pages)', 'manual-docs' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Versions are parent documentation pages (e.g. goat, flamingo, hummingbird). Children under each parent share the same tree; matching is by relative path / title.', 'manual-docs' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th><label for="version_label"><?php esc_html_e( 'Switcher label', 'manual-docs' ); ?></label></th>
					<td><input class="regular-text" type="text" id="version_label" name="manual_docs_options[version_label]" value="<?php echo esc_attr( $o['version_label'] ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="version_root_slugs"><?php esc_html_e( 'Version root slugs', 'manual-docs' ); ?></label></th>
					<td>
						<input class="large-text" type="text" id="version_root_slugs" name="manual_docs_options[version_root_slugs]" value="<?php echo esc_attr( $o['version_root_slugs'] ); ?>" />
						<p class="description"><?php esc_html_e( 'Comma-separated top-level documentation slugs, e.g. goat,flamingo,hummingbird. Leave empty to use all top-level docs.', 'manual-docs' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="version_root_ids"><?php esc_html_e( 'Version root IDs (optional)', 'manual-docs' ); ?></label></th>
					<td>
						<input class="large-text" type="text" id="version_root_ids" name="manual_docs_options[version_root_ids]" value="<?php echo esc_attr( $o['version_root_ids'] ); ?>" />
						<p class="description"><?php esc_html_e( 'Optional comma-separated post IDs. Overrides slugs when set.', 'manual-docs' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="default_version_slug"><?php esc_html_e( 'Default version slug', 'manual-docs' ); ?></label></th>
					<td><input class="regular-text" type="text" id="default_version_slug" name="manual_docs_options[default_version_slug]" value="<?php echo esc_attr( $o['default_version_slug'] ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="cpt_rewrite_slug"><?php esc_html_e( 'Documentation URL base', 'manual-docs' ); ?></label></th>
					<td>
						<input class="regular-text" type="text" id="cpt_rewrite_slug" name="manual_docs_options[cpt_rewrite_slug]" value="<?php echo esc_attr( $o['cpt_rewrite_slug'] ); ?>" />
						<p class="description">
							<?php
							printf(
								/* translators: %s: example URL path */
								esc_html__( 'Permalink base for docs. Example: %s', 'manual-docs' ),
								'<code>/' . esc_html( $o['cpt_rewrite_slug'] ? $o['cpt_rewrite_slug'] : 'documentation' ) . '/flamingo/</code>'
							);
							?>
						</p>
					</td>
				</tr>
			</table>

			<h2 class="title"><?php esc_html_e( 'Permalinks / 404 fix', 'manual-docs' ); ?></h2>
			<p class="description"><?php esc_html_e( 'If /documentation/flamingo/ shows a server “Not Found” page, pretty permalinks need a flush.', 'manual-docs' ); ?></p>
			<p>
				<a class="button" href="<?php echo esc_url( admin_url( 'options-permalink.php' ) ); ?>"><?php esc_html_e( 'Open Permalinks settings', 'manual-docs' ); ?></a>
				<?php submit_button( __( 'Flush documentation permalinks', 'manual-docs' ), 'secondary', 'manual_docs_flush_permalinks', false ); ?>
			</p>
			<?php
			$structure = get_option( 'permalink_structure' );
			if ( empty( $structure ) ) :
				?>
				<p style="color:#b32d2e;"><strong><?php esc_html_e( 'Pretty permalinks are currently OFF. Choose “Post name” under Settings → Permalinks, then save.', 'manual-docs' ); ?></strong></p>
			<?php else : ?>
				<p><?php esc_html_e( 'Current permalink structure:', 'manual-docs' ); ?> <code><?php echo esc_html( $structure ); ?></code></p>
			<?php endif; ?>

			<h2 class="title"><?php esc_html_e( 'Access & UI', 'manual-docs' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th><?php esc_html_e( 'Access', 'manual-docs' ); ?></th>
					<td>
						<label><input type="checkbox" name="manual_docs_options[require_login]" value="1" <?php checked( $o['require_login'], 1 ); ?> /> <?php esc_html_e( 'Require login to view documentation', 'manual-docs' ); ?></label>
					</td>
				</tr>
				<tr>
					<th><label for="login_message"><?php esc_html_e( 'Login message', 'manual-docs' ); ?></label></th>
					<td><input class="large-text" type="text" id="login_message" name="manual_docs_options[login_message]" value="<?php echo esc_attr( $o['login_message'] ); ?>" /></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Document chrome', 'manual-docs' ); ?></th>
					<td>
						<label><input type="checkbox" name="manual_docs_options[show_toc]" value="1" <?php checked( $o['show_toc'], 1 ); ?> /> <?php esc_html_e( 'Show On this page TOC', 'manual-docs' ); ?></label><br />
						<label><input type="checkbox" name="manual_docs_options[show_pdf]" value="1" <?php checked( $o['show_pdf'], 1 ); ?> /> <?php esc_html_e( 'Show PDF download', 'manual-docs' ); ?></label><br />
						<label><input type="checkbox" name="manual_docs_options[show_updated]" value="1" <?php checked( $o['show_updated'], 1 ); ?> /> <?php esc_html_e( 'Show last updated date', 'manual-docs' ); ?></label><br />
						<label><input type="checkbox" name="manual_docs_options[show_edit_link]" value="1" <?php checked( $o['show_edit_link'], 1 ); ?> /> <?php esc_html_e( 'Show edit link (for editors)', 'manual-docs' ); ?></label><br />
						<label><input type="checkbox" name="manual_docs_options[tree_expand_active]" value="1" <?php checked( $o['tree_expand_active'], 1 ); ?> /> <?php esc_html_e( 'Auto-expand active tree branch', 'manual-docs' ); ?></label><br />
						<label><input type="checkbox" name="manual_docs_options[show_community_cta]" value="1" <?php checked( $o['show_community_cta'], 1 ); ?> /> <?php esc_html_e( 'Show community CTA', 'manual-docs' ); ?></label>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Settings', 'manual-docs' ) ); ?>
		</form>
	</div>
	<?php
}

/**
 * Print CSS variables from options.
 */
function manual_docs_options_css() {
	$o = manual_docs_get_options();
	?>
	<style id="manual-docs-options-css">
		:root {
			--md-primary: <?php echo esc_html( $o['primary_color'] ); ?>;
			--md-accent: <?php echo esc_html( $o['accent_color'] ); ?>;
			--md-accent-soft: color-mix(in srgb, <?php echo esc_html( $o['accent_color'] ); ?> 14%, white);
			--md-link: <?php echo esc_html( $o['link_color'] ); ?>;
			--md-pdf: <?php echo esc_html( $o['pdf_color'] ); ?>;
			--md-active-bar: <?php echo esc_html( $o['active_bar_color'] ); ?>;
			--md-header-bg: <?php echo esc_html( $o['header_bg'] ); ?>;
			--md-sidebar-bg: <?php echo esc_html( $o['sidebar_bg'] ); ?>;
			--md-content-bg: <?php echo esc_html( $o['content_bg'] ); ?>;
			--md-page-bg: <?php echo esc_html( $o['page_bg'] ); ?>;
			--md-text: <?php echo esc_html( $o['text_color'] ); ?>;
		}
	</style>
	<?php
}
add_action( 'wp_head', 'manual_docs_options_css', 5 );