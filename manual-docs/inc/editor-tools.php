<?php
/**
 * Editor helpers — Content Elements inserter + block editor preference.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prefer the block editor for documentation unless Classic Editor plugin forces otherwise.
 *
 * @param bool   $use       Whether to use block editor.
 * @param string $post_type Post type.
 * @return bool
 */
function manual_docs_prefer_block_editor( $use, $post_type ) {
	if ( 'manual_documentation' !== $post_type ) {
		return $use;
	}
	// Respect Classic Editor plugin “Replace” mode when active.
	if ( class_exists( 'Classic_Editor' ) ) {
		$option = get_option( 'classic-editor-replace' );
		if ( 'replace' === $option ) {
			return false;
		}
	}
	return true;
}
add_filter( 'use_block_editor_for_post_type', 'manual_docs_prefer_block_editor', 20, 2 );

/**
 * Add Content Elements meta box on documentation edit screens.
 */
function manual_docs_editor_tools_metabox() {
	add_meta_box(
		'manual_docs_content_elements',
		__( 'Content Elements', 'manual-docs' ),
		'manual_docs_render_content_elements_metabox',
		'manual_documentation',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'manual_docs_editor_tools_metabox' );

/**
 * Render Content Elements builder UI.
 */
function manual_docs_render_content_elements_metabox() {
	$using_blocks = function_exists( 'use_block_editor_for_post' ) && use_block_editor_for_post( get_the_ID() );
	?>
	<div class="md-editor-tools" id="md-editor-tools" data-editor="<?php echo $using_blocks ? 'block' : 'classic'; ?>">
		<p class="description">
			<?php
			if ( $using_blocks ) {
				esc_html_e( 'Tip: use core Gutenberg blocks for Tables, Images, and Code. Use the builders below for Accordion, Tabs, and Callouts — they insert a Shortcode block.', 'manual-docs' );
			} else {
				esc_html_e( 'Build Accordion, Tabs, Callouts, Code, or a starter Table, then Insert into the editor. For full table editing, re-enable the block editor (recommended).', 'manual-docs' );
			}
			?>
		</p>

		<details class="md-editor-tools__panel" open>
			<summary><?php esc_html_e( 'Callout', 'manual-docs' ); ?></summary>
			<p>
				<label><?php esc_html_e( 'Type', 'manual-docs' ); ?>
					<select id="md-tool-callout-type">
						<option value="md_info"><?php esc_html_e( 'Info', 'manual-docs' ); ?></option>
						<option value="md_tip"><?php esc_html_e( 'Tip', 'manual-docs' ); ?></option>
						<option value="md_note"><?php esc_html_e( 'Note', 'manual-docs' ); ?></option>
						<option value="md_warning"><?php esc_html_e( 'Warning', 'manual-docs' ); ?></option>
					</select>
				</label>
			</p>
			<p><label><?php esc_html_e( 'Title', 'manual-docs' ); ?> <input type="text" class="widefat" id="md-tool-callout-title" value="<?php esc_attr_e( 'Note', 'manual-docs' ); ?>" /></label></p>
			<p><label><?php esc_html_e( 'Body', 'manual-docs' ); ?> <textarea class="widefat" rows="3" id="md-tool-callout-body"><?php esc_html_e( 'Supporting details for the reader.', 'manual-docs' ); ?></textarea></label></p>
			<p><button type="button" class="button button-primary" data-md-insert="callout"><?php esc_html_e( 'Insert callout', 'manual-docs' ); ?></button></p>
		</details>

		<details class="md-editor-tools__panel">
			<summary><?php esc_html_e( 'Accordion', 'manual-docs' ); ?></summary>
			<div id="md-tool-acc-items">
				<div class="md-editor-tools__item" data-md-acc-row>
					<input type="text" class="widefat" placeholder="<?php esc_attr_e( 'Item title', 'manual-docs' ); ?>" value="<?php esc_attr_e( 'How does this work?', 'manual-docs' ); ?>" data-md-acc-title />
					<textarea class="widefat" rows="2" placeholder="<?php esc_attr_e( 'Item body', 'manual-docs' ); ?>" data-md-acc-body><?php esc_html_e( 'Explain the answer here.', 'manual-docs' ); ?></textarea>
				</div>
			</div>
			<p>
				<button type="button" class="button" data-md-acc-add><?php esc_html_e( 'Add item', 'manual-docs' ); ?></button>
				<button type="button" class="button button-primary" data-md-insert="accordion"><?php esc_html_e( 'Insert accordion', 'manual-docs' ); ?></button>
			</p>
		</details>

		<details class="md-editor-tools__panel">
			<summary><?php esc_html_e( 'Tabs', 'manual-docs' ); ?></summary>
			<div id="md-tool-tab-items">
				<div class="md-editor-tools__item" data-md-tab-row>
					<input type="text" class="widefat" placeholder="<?php esc_attr_e( 'Tab label', 'manual-docs' ); ?>" value="<?php esc_attr_e( 'Setup', 'manual-docs' ); ?>" data-md-tab-title />
					<textarea class="widefat" rows="2" placeholder="<?php esc_attr_e( 'Tab body', 'manual-docs' ); ?>" data-md-tab-body><?php esc_html_e( 'Tab content goes here.', 'manual-docs' ); ?></textarea>
				</div>
			</div>
			<p>
				<button type="button" class="button" data-md-tab-add><?php esc_html_e( 'Add tab', 'manual-docs' ); ?></button>
				<button type="button" class="button button-primary" data-md-insert="tabs"><?php esc_html_e( 'Insert tabs', 'manual-docs' ); ?></button>
			</p>
		</details>

		<details class="md-editor-tools__panel">
			<summary><?php esc_html_e( 'Code block', 'manual-docs' ); ?></summary>
			<p class="description"><?php esc_html_e( 'Front end shows line numbers + a Copy button automatically.', 'manual-docs' ); ?></p>
			<p><label><?php esc_html_e( 'Language label', 'manual-docs' ); ?> <input type="text" class="widefat" id="md-tool-code-lang" placeholder="bash / php / json" value="bash" /></label></p>
			<p><label><?php esc_html_e( 'Code', 'manual-docs' ); ?> <textarea class="widefat" rows="5" id="md-tool-code-body" style="font-family:monospace;">curl -X GET "/wp-json/manual-docs/v1/search?q=platform"</textarea></label></p>
			<p><button type="button" class="button button-primary" data-md-insert="code"><?php esc_html_e( 'Insert code block', 'manual-docs' ); ?></button></p>
		</details>

		<details class="md-editor-tools__panel">
			<summary><?php esc_html_e( 'Table starter', 'manual-docs' ); ?></summary>
			<p class="description"><?php esc_html_e( 'Inserts a starter HTML table. Prefer Gutenberg’s Table block when available — it supports adding/removing columns visually.', 'manual-docs' ); ?></p>
			<p>
				<label><?php esc_html_e( 'Columns', 'manual-docs' ); ?> <input type="number" min="2" max="8" value="3" id="md-tool-table-cols" style="width:4.5em;" /></label>
				<label style="margin-left:0.5rem;"><?php esc_html_e( 'Rows', 'manual-docs' ); ?> <input type="number" min="1" max="20" value="3" id="md-tool-table-rows" style="width:4.5em;" /></label>
			</p>
			<p><label><?php esc_html_e( 'Header labels (comma-separated)', 'manual-docs' ); ?> <input type="text" class="widefat" id="md-tool-table-headers" value="Feature, Status, Notes" /></label></p>
			<p><button type="button" class="button button-primary" data-md-insert="table"><?php esc_html_e( 'Insert table', 'manual-docs' ); ?></button></p>
		</details>

		<hr />
		<p class="description">
			<?php
			printf(
				/* translators: %s: Docs Stats URL */
				esc_html__( 'Need a full demo page? Create one under %s.', 'manual-docs' ),
				'<a href="' . esc_url( admin_url( 'themes.php?page=manual-docs-stats' ) ) . '">' . esc_html__( 'Docs Stats → sample document', 'manual-docs' ) . '</a>'
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Admin assets for the Content Elements tools.
 *
 * @param string $hook Hook.
 */
function manual_docs_editor_tools_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'manual_documentation' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_style(
		'manual-docs-editor-tools',
		MANUAL_DOCS_URI . '/assets/css/editor-tools.css',
		array(),
		MANUAL_DOCS_VERSION
	);
	wp_enqueue_script(
		'manual-docs-editor-tools',
		MANUAL_DOCS_URI . '/assets/js/editor-tools.js',
		array( 'jquery', 'editor' ),
		MANUAL_DOCS_VERSION,
		true
	);
	wp_localize_script(
		'manual-docs-editor-tools',
		'manualDocsEditor',
		array(
			'i18n' => array(
				'itemTitle' => __( 'Item title', 'manual-docs' ),
				'itemBody'  => __( 'Item body', 'manual-docs' ),
				'tabLabel'  => __( 'Tab label', 'manual-docs' ),
				'tabBody'   => __( 'Tab content', 'manual-docs' ),
				'col'       => __( 'Column', 'manual-docs' ),
				'cell'      => __( 'Cell', 'manual-docs' ),
				'inserted'  => __( 'Inserted into the editor.', 'manual-docs' ),
				'needEditor'=> __( 'Open the main content editor, then try again.', 'manual-docs' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'manual_docs_editor_tools_assets' );

/**
 * TinyMCE toolbar button for classic editor.
 *
 * @param array $buttons Buttons.
 * @return array
 */
function manual_docs_mce_buttons( $buttons ) {
	array_push( $buttons, 'manual_docs_elements' );
	return $buttons;
}
add_filter( 'mce_buttons', 'manual_docs_mce_buttons' );

/**
 * Register TinyMCE plugin.
 *
 * @param array $plugins Plugins.
 * @return array
 */
function manual_docs_mce_external_plugins( $plugins ) {
	$plugins['manual_docs_elements'] = MANUAL_DOCS_URI . '/assets/js/mce-elements.js?ver=' . MANUAL_DOCS_VERSION;
	return $plugins;
}
add_filter( 'mce_external_plugins', 'manual_docs_mce_external_plugins' );
