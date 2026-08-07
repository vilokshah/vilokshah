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
		// Dark theme palette (legacy keys kept for backward compatibility).
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
		'footer_cta_color'     => '#e11d48',
		// Light theme palette (independent).
		'light_primary_color'    => '#0f172a',
		'light_accent_color'     => '#2563eb',
		'light_header_bg'        => '#ffffff',
		'light_sidebar_bg'       => '#f8fafc',
		'light_content_bg'       => '#ffffff',
		'light_page_bg'          => '#eef2f7',
		'light_text_color'       => '#334155',
		'light_link_color'       => '#1d4ed8',
		'light_pdf_color'        => '#dc2626',
		'light_active_bar_color' => '#e11d48',
		'light_footer_cta_color' => '#e11d48',
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
		'permalink_mode'       => 'pretty',
		'hide_docs_archive'    => 1,
		'show_toc'             => 1,
		'show_pdf'             => 1,
		'show_updated'         => 1,
		'show_edit_link'       => 1,
		'enable_version_diff'  => 1,
		'tree_expand_active'   => 1,
		'tree_scope'           => 'active_version',
		'tree_lazy'            => 1,
		'header_tagline'       => '',
		'login_message'        => __( 'Please log in to view documentation.', 'manual-docs' ),
		'login_page_path'      => '/login/',
		'pdf_watermark'        => 'Digitate Docs',
		'footer_text'          => '',
		'footer_copyright'     => '',
		'logo_dark_id'         => 0,
		'logo_light_id'        => 0,
		'font_display'         => 'sora',
		'font_body'            => 'ibm-plex-sans',
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
	wp_enqueue_media();
	wp_add_inline_script(
		'wp-color-picker',
		'jQuery(function($){
			$(".md-color-field").wpColorPicker();
			function bindLogoPicker(btnSel, inputSel, previewSel) {
				$(document).on("click", btnSel, function(e){
					e.preventDefault();
					var frame = wp.media({ title: "Select logo", button: { text: "Use logo" }, multiple: false });
					frame.on("select", function(){
						var att = frame.state().get("selection").first().toJSON();
						$(inputSel).val(att.id);
						var url = (att.sizes && att.sizes.medium) ? att.sizes.medium.url : att.url;
						$(previewSel).html("<img src=\""+url+"\" alt=\"\" style=\"max-height:48px;width:auto;\" />");
					});
					frame.open();
				});
				$(document).on("click", btnSel + "-clear", function(e){
					e.preventDefault();
					$(inputSel).val("0");
					$(previewSel).empty();
				});
			}
			bindLogoPicker(".md-logo-pick-dark", "#logo_dark_id", "#md-logo-dark-preview");
			bindLogoPicker(".md-logo-pick-light", "#logo_light_id", "#md-logo-light-preview");
		});'
	);
}
add_action( 'admin_enqueue_scripts', 'manual_docs_options_assets' );

/**
 * Save options.
 */
function manual_docs_save_options() {
	if ( ! isset( $_POST['manual_docs_options_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['manual_docs_options_nonce'] ) ), 'manual_docs_save_options' ) ) {
		return;
	}
	// Dedicated buttons handle their own actions — don't overwrite options mid-fix.
	if ( isset( $_POST['manual_docs_flush_permalinks'] ) || isset( $_POST['manual_docs_fix_local_404'] ) || isset( $_POST['manual_docs_restore_pretty'] ) ) {
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
	$color_keys = array(
		'primary_color',
		'accent_color',
		'header_bg',
		'sidebar_bg',
		'content_bg',
		'page_bg',
		'text_color',
		'link_color',
		'pdf_color',
		'active_bar_color',
		'footer_cta_color',
		'light_primary_color',
		'light_accent_color',
		'light_header_bg',
		'light_sidebar_bg',
		'light_content_bg',
		'light_page_bg',
		'light_text_color',
		'light_link_color',
		'light_pdf_color',
		'light_active_bar_color',
		'light_footer_cta_color',
	);
	$text_keys  = array( 'brand_name', 'hero_title', 'hero_text', 'hero_eyebrow', 'version_label', 'version_root_slugs', 'version_root_ids', 'default_version_slug', 'cpt_rewrite_slug', 'permalink_mode', 'header_tagline', 'login_message', 'login_page_path', 'pdf_watermark', 'footer_text', 'footer_copyright', 'font_display', 'font_body', 'tree_scope' );
	$bool_keys  = array( 'require_login', 'show_community_cta', 'show_toc', 'show_pdf', 'show_updated', 'show_edit_link', 'enable_version_diff', 'tree_expand_active', 'tree_lazy', 'hide_docs_archive' );
	$int_keys   = array( 'logo_dark_id', 'logo_light_id' );

	foreach ( $color_keys as $key ) {
		$val = isset( $incoming[ $key ] ) ? sanitize_hex_color( $incoming[ $key ] ) : '';
		$clean[ $key ] = $val ? $val : $defaults[ $key ];
	}
	foreach ( $text_keys as $key ) {
		$clean[ $key ] = isset( $incoming[ $key ] ) ? sanitize_text_field( $incoming[ $key ] ) : $defaults[ $key ];
	}
	foreach ( $int_keys as $key ) {
		$clean[ $key ] = isset( $incoming[ $key ] ) ? absint( $incoming[ $key ] ) : 0;
	}
	if ( ! empty( $clean['cpt_rewrite_slug'] ) ) {
		$clean['cpt_rewrite_slug'] = sanitize_title( $clean['cpt_rewrite_slug'] );
	} else {
		$clean['cpt_rewrite_slug'] = 'documentation';
	}
	$mode = isset( $clean['permalink_mode'] ) ? $clean['permalink_mode'] : 'pretty';
	$clean['permalink_mode'] = in_array( $mode, array( 'pretty', 'index_php', 'query' ), true ) ? $mode : 'pretty';

	$font_catalog = function_exists( 'manual_docs_font_catalog' ) ? array_keys( manual_docs_font_catalog() ) : array();
	if ( ! in_array( $clean['font_display'], $font_catalog, true ) ) {
		$clean['font_display'] = $defaults['font_display'];
	}
	if ( ! in_array( $clean['font_body'], $font_catalog, true ) ) {
		$clean['font_body'] = $defaults['font_body'];
	}
	$clean['tree_scope'] = in_array( $clean['tree_scope'], array( 'active_version', 'all_versions' ), true )
		? $clean['tree_scope']
		: 'active_version';

	foreach ( $bool_keys as $key ) {
		$clean[ $key ] = ! empty( $incoming[ $key ] ) ? 1 : 0;
	}

	$old = get_option( 'manual_docs_options', array() );
	update_option( 'manual_docs_options', $clean );

	// Align WP permalink structure with index.php mode (fixes Apache 404 on Local).
	if ( 'index_php' === $clean['permalink_mode'] ) {
		$structure = (string) get_option( 'permalink_structure' );
		if ( false === strpos( $structure, 'index.php' ) ) {
			update_option( 'permalink_structure', '/index.php/%postname%/' );
		}
	}

	// Flush when rewrite slug, permalink mode, or archive visibility changes.
	$old_slug = is_array( $old ) && ! empty( $old['cpt_rewrite_slug'] ) ? $old['cpt_rewrite_slug'] : 'documentation';
	$old_mode = is_array( $old ) && ! empty( $old['permalink_mode'] ) ? $old['permalink_mode'] : 'pretty';
	$old_hide = is_array( $old ) ? ! empty( $old['hide_docs_archive'] ) : true;
	$new_hide = ! empty( $clean['hide_docs_archive'] );
	if ( $old_slug !== $clean['cpt_rewrite_slug'] || $old_mode !== $clean['permalink_mode'] || $old_hide !== $new_hide ) {
		delete_option( 'manual_docs_permalinks_flushed_2_5' );
		if ( function_exists( 'manual_docs_hard_flush_rewrites' ) ) {
			manual_docs_hard_flush_rewrites();
		} else {
			flush_rewrite_rules( true );
		}
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
		<div class="notice notice-info inline" style="margin:12px 0 18px;">
			<p>
				<strong><?php esc_html_e( 'Authoring tip:', 'manual-docs' ); ?></strong>
				<?php esc_html_e( 'Prefer Gutenberg for tables, images, and code. Use the Content Elements sidebar on each document for accordion, tabs, and callouts. Front-end code blocks get line numbers + Copy automatically. If Gutenberg failed before, update this theme, flush Permalinks, and disable conflicting Manual plugins — or keep Classic Editor; the Content Elements panel still works.', 'manual-docs' ); ?>
			</p>
		</div>
		<form method="post">
			<?php wp_nonce_field( 'manual_docs_save_options', 'manual_docs_options_nonce' ); ?>

			<h2 class="title"><?php esc_html_e( 'Branding', 'manual-docs' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th><?php esc_html_e( 'Dark / light logos', 'manual-docs' ); ?></th>
					<td>
						<?php
						$logo_dark_id  = isset( $o['logo_dark_id'] ) ? absint( $o['logo_dark_id'] ) : 0;
						$logo_light_id = isset( $o['logo_light_id'] ) ? absint( $o['logo_light_id'] ) : 0;
						?>
						<div style="display:flex;flex-wrap:wrap;gap:1.5rem;">
							<div>
								<p><strong><?php esc_html_e( 'Dark mode logo', 'manual-docs' ); ?></strong></p>
								<input type="hidden" id="logo_dark_id" name="manual_docs_options[logo_dark_id]" value="<?php echo esc_attr( (string) $logo_dark_id ); ?>" />
								<div id="md-logo-dark-preview" style="min-height:48px;margin-bottom:8px;">
									<?php
									if ( $logo_dark_id ) {
										echo wp_get_attachment_image( $logo_dark_id, 'medium', false, array( 'style' => 'max-height:48px;width:auto;' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									}
									?>
								</div>
								<button type="button" class="button md-logo-pick-dark"><?php esc_html_e( 'Select dark logo', 'manual-docs' ); ?></button>
								<button type="button" class="button-link md-logo-pick-dark-clear"><?php esc_html_e( 'Clear', 'manual-docs' ); ?></button>
							</div>
							<div>
								<p><strong><?php esc_html_e( 'Light mode logo', 'manual-docs' ); ?></strong></p>
								<input type="hidden" id="logo_light_id" name="manual_docs_options[logo_light_id]" value="<?php echo esc_attr( (string) $logo_light_id ); ?>" />
								<div id="md-logo-light-preview" style="min-height:48px;margin-bottom:8px;">
									<?php
									if ( $logo_light_id ) {
										echo wp_get_attachment_image( $logo_light_id, 'medium', false, array( 'style' => 'max-height:48px;width:auto;' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									}
									?>
								</div>
								<button type="button" class="button md-logo-pick-light"><?php esc_html_e( 'Select light logo', 'manual-docs' ); ?></button>
								<button type="button" class="button-link md-logo-pick-light-clear"><?php esc_html_e( 'Clear', 'manual-docs' ); ?></button>
							</div>
						</div>
						<p class="description" style="margin-top:10px;">
							<?php esc_html_e( 'Optional. When set, these swap with the light/dark header toggle. If empty, the Site Identity custom logo is used for both modes.', 'manual-docs' ); ?>
						</p>
						<p>
							<a class="button" href="<?php echo esc_url( admin_url( 'customize.php?autofocus[control]=custom_logo' ) ); ?>">
								<?php esc_html_e( 'Fallback logo (Site Identity)', 'manual-docs' ); ?>
							</a>
							<a class="button" href="<?php echo esc_url( admin_url( 'customize.php?autofocus[control]=site_icon' ) ); ?>">
								<?php esc_html_e( 'Upload favicon (Site Icon)', 'manual-docs' ); ?>
							</a>
						</p>
					</td>
				</tr>
				<tr>
					<th><label for="brand_name"><?php esc_html_e( 'Brand name', 'manual-docs' ); ?></label></th>
					<td><input class="regular-text" type="text" id="brand_name" name="manual_docs_options[brand_name]" value="<?php echo esc_attr( $o['brand_name'] ); ?>" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" /></td>
				</tr>
				<?php
				$font_catalog = function_exists( 'manual_docs_font_catalog' ) ? manual_docs_font_catalog() : array();
				$font_display = isset( $o['font_display'] ) ? $o['font_display'] : 'sora';
				$font_body    = isset( $o['font_body'] ) ? $o['font_body'] : 'ibm-plex-sans';
				?>
				<tr>
					<th><label for="font_display"><?php esc_html_e( 'Heading font', 'manual-docs' ); ?></label></th>
					<td>
						<select id="font_display" name="manual_docs_options[font_display]">
							<?php foreach ( $font_catalog as $key => $font ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $font_display, $key ); ?>><?php echo esc_html( $font['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="font_body"><?php esc_html_e( 'Body font', 'manual-docs' ); ?></label></th>
					<td>
						<select id="font_body" name="manual_docs_options[font_body]">
							<?php foreach ( $font_catalog as $key => $font ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $font_body, $key ); ?>><?php echo esc_html( $font['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Google Fonts load automatically for the selected families (System UI loads nothing).', 'manual-docs' ); ?></p>
					</td>
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
					<th><label for="footer_text"><?php esc_html_e( 'Footer tagline (legacy layout)', 'manual-docs' ); ?></label></th>
					<td><input class="large-text" type="text" id="footer_text" name="manual_docs_options[footer_text]" value="<?php echo esc_attr( $o['footer_text'] ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="footer_copyright"><?php esc_html_e( 'Copyright bar text', 'manual-docs' ); ?></label></th>
					<td>
						<input class="large-text" type="text" id="footer_copyright" name="manual_docs_options[footer_copyright]" value="<?php echo esc_attr( isset( $o['footer_copyright'] ) ? $o['footer_copyright'] : '' ); ?>" placeholder="<?php echo esc_attr( sprintf( __( '© %s Your Company. All rights reserved.', 'manual-docs' ), gmdate( 'Y' ) ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Leave empty to use “© {year} {site name}. All rights reserved.”', 'manual-docs' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Footer columns', 'manual-docs' ); ?></th>
					<td>
						<p>
							<a class="button button-primary" href="<?php echo esc_url( admin_url( 'widgets.php' ) ); ?>"><?php esc_html_e( 'Manage footer widgets', 'manual-docs' ); ?></a>
							<a class="button" href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>"><?php esc_html_e( 'Manage menus', 'manual-docs' ); ?></a>
						</p>
						<p class="description">
							<?php esc_html_e( 'Use Appearance → Widgets → Footer Column 1–4. Suggested setup: Column 1 = “Manual Docs: Contacts”, Columns 2–3 = Navigation Menu (Company / Support), Column 4 = “Manual Docs: Newsletter”.', 'manual-docs' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<h2 class="title"><?php esc_html_e( 'Dark theme colors', 'manual-docs' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Used when the front-end toggle is on Dark. Existing sites keep these values from earlier versions.', 'manual-docs' ); ?></p>
			<table class="form-table" role="presentation">
				<?php
				$dark_colors = array(
					'primary_color'    => __( 'Primary / headings', 'manual-docs' ),
					'accent_color'     => __( 'Accent', 'manual-docs' ),
					'link_color'       => __( 'Link color', 'manual-docs' ),
					'active_bar_color' => __( 'Active tree bar', 'manual-docs' ),
					'pdf_color'        => __( 'PDF button', 'manual-docs' ),
					'footer_cta_color' => __( 'Footer Subscribe button', 'manual-docs' ),
					'header_bg'        => __( 'Header background', 'manual-docs' ),
					'sidebar_bg'       => __( 'Sidebar background', 'manual-docs' ),
					'content_bg'       => __( 'Content panel background', 'manual-docs' ),
					'page_bg'          => __( 'Page background', 'manual-docs' ),
					'text_color'       => __( 'Body text', 'manual-docs' ),
				);
				foreach ( $dark_colors as $key => $label ) :
					?>
					<tr>
						<th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td><input class="md-color-field" type="text" id="<?php echo esc_attr( $key ); ?>" name="manual_docs_options[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $o[ $key ] ); ?>" /></td>
					</tr>
				<?php endforeach; ?>
			</table>

			<h2 class="title"><?php esc_html_e( 'Light theme colors', 'manual-docs' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Used when the front-end toggle is on Light. Completely separate from Dark — change one without affecting the other.', 'manual-docs' ); ?></p>
			<table class="form-table" role="presentation">
				<?php
				$light_colors = array(
					'light_primary_color'    => __( 'Primary / headings', 'manual-docs' ),
					'light_accent_color'     => __( 'Accent', 'manual-docs' ),
					'light_link_color'       => __( 'Link color', 'manual-docs' ),
					'light_active_bar_color' => __( 'Active tree bar', 'manual-docs' ),
					'light_pdf_color'        => __( 'PDF button', 'manual-docs' ),
					'light_footer_cta_color' => __( 'Footer Subscribe button', 'manual-docs' ),
					'light_header_bg'        => __( 'Header background', 'manual-docs' ),
					'light_sidebar_bg'       => __( 'Sidebar background', 'manual-docs' ),
					'light_content_bg'       => __( 'Content panel background', 'manual-docs' ),
					'light_page_bg'          => __( 'Page background', 'manual-docs' ),
					'light_text_color'       => __( 'Body text', 'manual-docs' ),
				);
				foreach ( $light_colors as $key => $label ) :
					?>
					<tr>
						<th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td><input class="md-color-field" type="text" id="<?php echo esc_attr( $key ); ?>" name="manual_docs_options[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( isset( $o[ $key ] ) ? $o[ $key ] : '' ); ?>" /></td>
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
						<p class="description"><?php esc_html_e( 'Comma-separated top-level documentation slugs that are release versions only (e.g. goat,flamingo,hummingbird). Other parent pages are excluded from the version switcher.', 'manual-docs' ); ?></p>
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
			<p class="description" style="color:#b32d2e;">
				<?php esc_html_e( 'If the browser shows a plain Apache “Not Found” page (not a WordPress theme 404), the request never reached WordPress. Use “index.php URLs” below — that fixes Local/subdirectory installs without relying on .htaccess.', 'manual-docs' ); ?>
			</p>
			<table class="form-table" role="presentation">
				<tr>
					<th><?php esc_html_e( 'Documentation URL mode', 'manual-docs' ); ?></th>
					<td>
						<?php $mode = isset( $o['permalink_mode'] ) ? $o['permalink_mode'] : 'pretty'; ?>
						<label style="display:block;margin-bottom:6px;">
							<input type="radio" name="manual_docs_options[permalink_mode]" value="pretty" <?php checked( $mode, 'pretty' ); ?> />
							<?php esc_html_e( 'Pretty (/documentation/…/) — needs working Apache/Nginx rewrite', 'manual-docs' ); ?>
						</label>
						<label style="display:block;margin-bottom:6px;">
							<input type="radio" name="manual_docs_options[permalink_mode]" value="index_php" <?php checked( $mode, 'index_php' ); ?> />
							<strong><?php esc_html_e( 'index.php URLs (recommended for Local 404s)', 'manual-docs' ); ?></strong>
							— <code>/digidocs/index.php/documentation/goat-2/…/</code>
						</label>
						<label style="display:block;">
							<input type="radio" name="manual_docs_options[permalink_mode]" value="query" <?php checked( $mode, 'query' ); ?> />
							<?php esc_html_e( 'Query string (always works)', 'manual-docs' ); ?>
							— <code>/digidocs/?manual_documentation=goat-2/…</code>
						</label>
					</td>
				</tr>
			</table>
			<p>
				<a class="button" href="<?php echo esc_url( admin_url( 'options-permalink.php' ) ); ?>"><?php esc_html_e( 'Open Permalinks settings', 'manual-docs' ); ?></a>
				<?php submit_button( __( 'Flush + write .htaccess', 'manual-docs' ), 'secondary', 'manual_docs_flush_permalinks', false ); ?>
				<?php submit_button( __( 'Fix Local 404s now', 'manual-docs' ), 'primary', 'manual_docs_fix_local_404', false ); ?>
				<?php submit_button( __( 'Restore pretty URLs', 'manual-docs' ), 'secondary', 'manual_docs_restore_pretty', false ); ?>
			</p>
			<p class="description">
				<?php esc_html_e( 'Why not /documentation/… directly? That URL only works if Apache/Nginx rewrites unknown paths to index.php. On Local + /digidocs/, missing or ignored .htaccess produces the plain server “Not Found” page before WordPress runs. index.php URLs skip that requirement. After you copy the recommended .htaccess into the digidocs folder, use “Restore pretty URLs”.', 'manual-docs' ); ?>
			</p>
			<?php
			$structure = get_option( 'permalink_structure' );
			$home_path = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
			$htaccess  = ABSPATH . '.htaccess';
			if ( empty( $structure ) ) :
				?>
				<p style="color:#b32d2e;"><strong><?php esc_html_e( 'Pretty permalinks are currently OFF. Choose “Post name” under Settings → Permalinks, then save — or click “Fix Local 404s now”.', 'manual-docs' ); ?></strong></p>
			<?php else : ?>
				<p><?php esc_html_e( 'Current permalink structure:', 'manual-docs' ); ?> <code><?php echo esc_html( $structure ); ?></code></p>
			<?php endif; ?>
			<p>
				<?php esc_html_e( 'Site path:', 'manual-docs' ); ?>
				<code>/<?php echo esc_html( $home_path ? $home_path . '/' : '' ); ?></code>
				·
				<?php esc_html_e( '.htaccess:', 'manual-docs' ); ?>
				<?php echo file_exists( $htaccess ) ? esc_html__( 'found', 'manual-docs' ) : esc_html__( 'missing (create/writable needed)', 'manual-docs' ); ?>
			</p>
			<?php if ( function_exists( 'manual_docs_example_working_url' ) ) : ?>
				<p>
					<?php esc_html_e( 'Try this URL after saving:', 'manual-docs' ); ?>
					<code><?php echo esc_html( manual_docs_example_working_url() ); ?></code>
				</p>
			<?php endif; ?>
			<details style="margin:1em 0;">
				<summary><?php esc_html_e( 'Show recommended .htaccess for subdirectory installs', 'manual-docs' ); ?></summary>
				<pre style="background:#1e1e1e;color:#eee;padding:12px;overflow:auto;"><?php
				echo esc_html( function_exists( 'manual_docs_recommended_htaccess' ) ? manual_docs_recommended_htaccess() : '' );
				?></pre>
				<p class="description"><?php esc_html_e( 'Only needed for Pretty mode. Put this in your WordPress root .htaccess (the digidocs folder). If Local still 404s, use index.php mode instead.', 'manual-docs' ); ?></p>
			</details>

			<h2 class="title"><?php esc_html_e( 'Live search shortcode', 'manual-docs' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Search hits the Manual Docs REST endpoint (/wp-json/manual-docs/v1/search) and can filter by release version. Place it on any page or post:', 'manual-docs' ); ?>
			</p>
			<pre style="background:#1e1e1e;color:#eee;padding:12px;overflow:auto;">[manual_docs_search]
[manual_docs_search placeholder="Search docs…" class="md-live-search--shortcode"]</pre>

			<h2 class="title"><?php esc_html_e( 'Access & UI', 'manual-docs' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th><?php esc_html_e( 'Access', 'manual-docs' ); ?></th>
					<td>
						<label><input type="checkbox" name="manual_docs_options[require_login]" value="1" <?php checked( $o['require_login'], 1 ); ?> /> <?php esc_html_e( 'Require login to view documentation', 'manual-docs' ); ?></label><br />
						<label><input type="checkbox" name="manual_docs_options[hide_docs_archive]" value="1" <?php checked( ! empty( $o['hide_docs_archive'] ), 1 ); ?> /> <?php esc_html_e( 'Hide /docs archive page (redirect visitors to the default release)', 'manual-docs' ); ?></label>
					</td>
				</tr>
				<tr>
					<th><label for="login_message"><?php esc_html_e( 'Login message', 'manual-docs' ); ?></label></th>
					<td><input class="large-text" type="text" id="login_message" name="manual_docs_options[login_message]" value="<?php echo esc_attr( $o['login_message'] ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="login_page_path"><?php esc_html_e( 'Login page path', 'manual-docs' ); ?></label></th>
					<td>
						<input class="regular-text" type="text" id="login_page_path" name="manual_docs_options[login_page_path]" value="<?php echo esc_attr( isset( $o['login_page_path'] ) ? $o['login_page_path'] : '/login/' ); ?>" placeholder="/login/" />
						<p class="description"><?php esc_html_e( 'Guests are redirected here (default /login/). Use your magic-link / custom login page path.', 'manual-docs' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="pdf_watermark"><?php esc_html_e( 'PDF watermark / header', 'manual-docs' ); ?></label></th>
					<td>
						<input class="regular-text" type="text" id="pdf_watermark" name="manual_docs_options[pdf_watermark]" value="<?php echo esc_attr( isset( $o['pdf_watermark'] ) ? $o['pdf_watermark'] : 'Digitate Docs' ); ?>" />
						<p class="description"><?php esc_html_e( 'Shown as the print header brand and diagonal watermark on every PDF page.', 'manual-docs' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Document chrome', 'manual-docs' ); ?></th>
					<td>
						<label><input type="checkbox" name="manual_docs_options[show_toc]" value="1" <?php checked( $o['show_toc'], 1 ); ?> /> <?php esc_html_e( 'Show On this page TOC', 'manual-docs' ); ?></label><br />
						<label><input type="checkbox" name="manual_docs_options[show_pdf]" value="1" <?php checked( $o['show_pdf'], 1 ); ?> /> <?php esc_html_e( 'Show PDF download', 'manual-docs' ); ?></label><br />
						<label><input type="checkbox" name="manual_docs_options[show_updated]" value="1" <?php checked( $o['show_updated'], 1 ); ?> /> <?php esc_html_e( 'Show last updated date', 'manual-docs' ); ?></label><br />
						<label><input type="checkbox" name="manual_docs_options[show_edit_link]" value="1" <?php checked( $o['show_edit_link'], 1 ); ?> /> <?php esc_html_e( 'Show edit link (for editors)', 'manual-docs' ); ?></label><br />
						<label><input type="checkbox" name="manual_docs_options[tree_expand_active]" value="1" <?php checked( $o['tree_expand_active'], 1 ); ?> /> <?php esc_html_e( 'Auto-expand active tree branch', 'manual-docs' ); ?></label><br />
						<label><input type="checkbox" name="manual_docs_options[tree_lazy]" value="1" <?php checked( ! empty( $o['tree_lazy'] ), 1 ); ?> /> <?php esc_html_e( 'Lazy-load tree children (recommended for large libraries)', 'manual-docs' ); ?></label><br />
						<label><input type="checkbox" name="manual_docs_options[show_community_cta]" value="1" <?php checked( $o['show_community_cta'], 1 ); ?> /> <?php esc_html_e( 'Show community CTA', 'manual-docs' ); ?></label><br />
						<label><input type="checkbox" name="manual_docs_options[enable_version_diff]" value="1" <?php checked( ! empty( $o['enable_version_diff'] ), 1 ); ?> /> <?php esc_html_e( 'Enable version diff (Compare across releases)', 'manual-docs' ); ?></label>
						<p class="description" style="margin-top:6px;"><?php esc_html_e( 'When enabled, docs show a “Compare versions” control. Turn off to hide the feature completely with no other impact.', 'manual-docs' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Tree scope', 'manual-docs' ); ?></th>
					<td>
						<?php $tree_scope = isset( $o['tree_scope'] ) ? $o['tree_scope'] : 'active_version'; ?>
						<label style="display:block;margin-bottom:6px;">
							<input type="radio" name="manual_docs_options[tree_scope]" value="active_version" <?php checked( $tree_scope, 'active_version' ); ?> />
							<strong><?php esc_html_e( 'Active release only (recommended for 1,000–20,000+ docs)', 'manual-docs' ); ?></strong>
							— <?php esc_html_e( 'left tree shows the current version; switch versions with the release dropdown.', 'manual-docs' ); ?>
						</label>
						<label style="display:block;">
							<input type="radio" name="manual_docs_options[tree_scope]" value="all_versions" <?php checked( $tree_scope, 'all_versions' ); ?> />
							<?php esc_html_e( 'All version roots in the left tree (fine for smaller libraries)', 'manual-docs' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Settings', 'manual-docs' ) ); ?>
		</form>
	</div>
	<?php
}

/**
 * Print CSS variables from options (after main.css so values win).
 */
function manual_docs_options_css() {
	// Prefer inline on main stylesheet so load order cannot override colors.
	if ( wp_style_is( 'manual-docs-main', 'enqueued' ) || wp_style_is( 'manual-docs-main', 'done' ) ) {
		return;
	}
	echo '<style id="manual-docs-options-css">' . manual_docs_get_options_css_text() . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_head', 'manual_docs_options_css', 100 );

/**
 * Build CSS custom properties + component hooks for one theme mode.
 *
 * @param string $mode   'dark' or 'light'.
 * @param array  $colors Escaped hex colors: primary, accent, link, pdf, bar, header, sidebar, content, page, text, cta.
 * @return string
 */
function manual_docs_palette_css_block( $mode, $colors ) {
	$mode     = ( 'light' === $mode ) ? 'light' : 'dark';
	$primary  = $colors['primary'];
	$accent   = $colors['accent'];
	$link     = $colors['link'];
	$pdf      = $colors['pdf'];
	$bar      = $colors['bar'];
	$header   = $colors['header'];
	$sidebar  = $colors['sidebar'];
	$content  = $colors['content'];
	$page     = $colors['page'];
	$text     = $colors['text'];
	$cta      = $colors['cta'];
	$line_mix = ( 'light' === $mode ) ? '82%' : '70%';
	$muted_mix = ( 'light' === $mode ) ? '55%' : '72%';

	return "
html[data-md-theme=\"{$mode}\"] {
	--md-primary: {$primary};
	--md-accent: {$accent};
	--md-accent-soft: color-mix(in srgb, {$accent} 16%, transparent);
	--md-link: {$link};
	--md-pdf: {$pdf};
	--md-active-bar: {$bar};
	--md-footer-cta: {$cta};
	--md-header-bg: {$header};
	--md-sidebar-bg: {$sidebar};
	--md-content-bg: {$content};
	--md-page-bg: {$page};
	--md-text: {$text};
	--md-ink: {$primary};
	--md-surface: {$content};
	--md-muted: color-mix(in srgb, {$text} {$muted_mix}, #64748b);
	--md-line: color-mix(in srgb, {$content} {$line_mix}, #94a3b8);
}
html[data-md-theme=\"{$mode}\"] body { background: var(--md-page-bg); color: var(--md-text); }
html[data-md-theme=\"{$mode}\"] h1,
html[data-md-theme=\"{$mode}\"] h2,
html[data-md-theme=\"{$mode}\"] h3,
html[data-md-theme=\"{$mode}\"] h4,
html[data-md-theme=\"{$mode}\"] h5,
html[data-md-theme=\"{$mode}\"] h6,
html[data-md-theme=\"{$mode}\"] .md-doc-title,
html[data-md-theme=\"{$mode}\"] .md-page-title,
html[data-md-theme=\"{$mode}\"] .md-brand__text,
html[data-md-theme=\"{$mode}\"] .md-hero__title { color: var(--md-ink); }
html[data-md-theme=\"{$mode}\"] .md-header { background: var(--md-header-bg); border-bottom-color: var(--md-line); }
html[data-md-theme=\"{$mode}\"] .md-docs-sidebar { background: var(--md-sidebar-bg); border-right-color: var(--md-line); }
html[data-md-theme=\"{$mode}\"] .md-doc-article,
html[data-md-theme=\"{$mode}\"] .md-archive,
html[data-md-theme=\"{$mode}\"] .md-docs-shell { background: var(--md-content-bg); }
html[data-md-theme=\"{$mode}\"] .md-doc-content,
html[data-md-theme=\"{$mode}\"] .md-doc-toc__card,
html[data-md-theme=\"{$mode}\"] .md-version-switcher,
html[data-md-theme=\"{$mode}\"] .md-doc-pager__link,
html[data-md-theme=\"{$mode}\"] .md-docs-search-modal__dialog,
html[data-md-theme=\"{$mode}\"] .md-live-search__results { background: var(--md-surface); color: var(--md-text); border-color: var(--md-line); }
html[data-md-theme=\"{$mode}\"] .md-doc-content h1,
html[data-md-theme=\"{$mode}\"] .md-doc-content h2,
html[data-md-theme=\"{$mode}\"] .md-doc-content h3,
html[data-md-theme=\"{$mode}\"] .md-doc-content h4 { color: var(--md-ink); }
html[data-md-theme=\"{$mode}\"] .md-doc-content p,
html[data-md-theme=\"{$mode}\"] .md-doc-content li,
html[data-md-theme=\"{$mode}\"] .md-doc-content td,
html[data-md-theme=\"{$mode}\"] .md-doc-nav a,
html[data-md-theme=\"{$mode}\"] .md-docs-tree a,
html[data-md-theme=\"{$mode}\"] .md-menu a,
html[data-md-theme=\"{$mode}\"] .md-header a,
html[data-md-theme=\"{$mode}\"] .md-meta-item { color: var(--md-text); }
html[data-md-theme=\"{$mode}\"] .md-doc-content a:not(.md-btn),
html[data-md-theme=\"{$mode}\"] .md-doc-toc__list a.is-active,
html[data-md-theme=\"{$mode}\"] .md-doc-toc__list a:hover { color: var(--md-link); }
html[data-md-theme=\"{$mode}\"] .md-doc-content a.md-btn--primary,
html[data-md-theme=\"{$mode}\"] .md-doc-content a.md-btn--primary:hover,
html[data-md-theme=\"{$mode}\"] .md-doc-content a.md-btn--primary:focus {
	color: #fff;
	background: var(--md-accent);
}
html[data-md-theme=\"{$mode}\"] .md-doc-content a.md-btn--ghost,
html[data-md-theme=\"{$mode}\"] .md-doc-content a.md-btn--ghost:hover,
html[data-md-theme=\"{$mode}\"] .md-doc-content a.md-btn--ghost:focus {
	color: var(--md-ink);
}
html[data-md-theme=\"{$mode}\"] .md-doc-toc__list a,
html[data-md-theme=\"{$mode}\"] .md-breadcrumb,
html[data-md-theme=\"{$mode}\"] .md-breadcrumb a { color: var(--md-muted); }
html[data-md-theme=\"{$mode}\"] .md-version-select { color: var(--md-ink); }
html[data-md-theme=\"{$mode}\"] .md-badge { background: var(--md-accent-soft); color: var(--md-accent); }
html[data-md-theme=\"{$mode}\"] .md-doc-nav__item.is-active > a {
	border-left-color: var(--md-active-bar);
	color: var(--md-link);
	background: var(--md-accent-soft);
}
html[data-md-theme=\"{$mode}\"] .md-meta-pdf,
html[data-md-theme=\"{$mode}\"] .md-meta-pdf:hover { color: var(--md-pdf); }
html[data-md-theme=\"{$mode}\"] .md-footer-newsletter__submit { background: var(--md-footer-cta); }
html[data-md-theme=\"{$mode}\"] .md-footer { border-top-color: var(--md-line); }
";
}

/**
 * Build options CSS text.
 *
 * @return string
 */
function manual_docs_get_options_css_text() {
	$o            = manual_docs_get_options();
	$font_display = function_exists( 'manual_docs_font_stack' ) ? manual_docs_font_stack( isset( $o['font_display'] ) ? $o['font_display'] : 'sora', 'sora' ) : '"Sora", "Segoe UI", sans-serif';
	$font_body    = function_exists( 'manual_docs_font_stack' ) ? manual_docs_font_stack( isset( $o['font_body'] ) ? $o['font_body'] : 'ibm-plex-sans', 'ibm-plex-sans' ) : '"IBM Plex Sans", "Segoe UI", sans-serif';
	$font_display = preg_replace( '/[^a-zA-Z0-9\s,\-"\']/', '', $font_display );
	$font_body    = preg_replace( '/[^a-zA-Z0-9\s,\-"\']/', '', $font_body );

	$dark = array(
		'primary' => esc_html( $o['primary_color'] ),
		'accent'  => esc_html( $o['accent_color'] ),
		'link'    => esc_html( $o['link_color'] ),
		'pdf'     => esc_html( $o['pdf_color'] ),
		'bar'     => esc_html( $o['active_bar_color'] ),
		'header'  => esc_html( $o['header_bg'] ),
		'sidebar' => esc_html( $o['sidebar_bg'] ),
		'content' => esc_html( $o['content_bg'] ),
		'page'    => esc_html( $o['page_bg'] ),
		'text'    => esc_html( $o['text_color'] ),
		'cta'     => esc_html( ! empty( $o['footer_cta_color'] ) ? $o['footer_cta_color'] : '#e11d48' ),
	);

	$light = array(
		'primary' => esc_html( $o['light_primary_color'] ),
		'accent'  => esc_html( $o['light_accent_color'] ),
		'link'    => esc_html( $o['light_link_color'] ),
		'pdf'     => esc_html( $o['light_pdf_color'] ),
		'bar'     => esc_html( $o['light_active_bar_color'] ),
		'header'  => esc_html( $o['light_header_bg'] ),
		'sidebar' => esc_html( $o['light_sidebar_bg'] ),
		'content' => esc_html( $o['light_content_bg'] ),
		'page'    => esc_html( $o['light_page_bg'] ),
		'text'    => esc_html( $o['light_text_color'] ),
		'cta'     => esc_html( ! empty( $o['light_footer_cta_color'] ) ? $o['light_footer_cta_color'] : '#e11d48' ),
	);

	$css  = "
:root {
	--md-font-display: {$font_display};
	--md-font-body: {$font_body};
}
html[data-md-theme=\"dark\"],
html[data-md-theme=\"light\"] {
	--md-font-display: {$font_display};
	--md-font-body: {$font_body};
}
body { font-family: var(--md-font-body); }
h1, h2, h3, h4, h5, h6,
.md-doc-title,
.md-brand__text,
.md-hero__title { font-family: var(--md-font-display); }
";
	$css .= manual_docs_palette_css_block( 'dark', $dark );
	$css .= manual_docs_palette_css_block( 'light', $light );

	return $css;
}

/**
 * Attach options CSS after main.css (fixes colors not applying).
 */
function manual_docs_enqueue_options_css() {
	if ( ! wp_style_is( 'manual-docs-main', 'enqueued' ) ) {
		return;
	}
	wp_add_inline_style( 'manual-docs-main', manual_docs_get_options_css_text() );
}
add_action( 'wp_enqueue_scripts', 'manual_docs_enqueue_options_css', 30 );