<?php
/**
 * Content shortcodes: tabs, accordion, callouts.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [md_tabs] ... [md_tab title="Label"]...[/md_tab] ... [/md_tabs]
 *
 * @param array  $atts    Attributes.
 * @param string $content Content.
 * @return string
 */
function manual_docs_shortcode_tabs( $atts, $content = '' ) {
	$inner = do_shortcode( shortcode_unautop( $content ) );
	if ( ! $inner ) {
		return '';
	}
	return '<div class="md-tabs" data-md-tabs>' . $inner . '</div>';
}
add_shortcode( 'md_tabs', 'manual_docs_shortcode_tabs' );

/**
 * Single tab panel.
 *
 * @param array  $atts    Attributes.
 * @param string $content Content.
 * @return string
 */
function manual_docs_shortcode_tab( $atts, $content = '' ) {
	$atts  = shortcode_atts( array( 'title' => __( 'Tab', 'manual-docs' ) ), $atts, 'md_tab' );
	$title = sanitize_text_field( $atts['title'] );
	$id    = 'md-tab-' . sanitize_title( $title ) . '-' . wp_unique_id();
	$body  = do_shortcode( shortcode_unautop( $content ) );

	return '<div class="md-tabs__panel" data-md-tab-panel role="tabpanel" id="' . esc_attr( $id ) . '" data-title="' . esc_attr( $title ) . '">' .
		'<h3 class="md-tabs__fallback-title screen-reader-text">' . esc_html( $title ) . '</h3>' .
		'<div class="md-tabs__panel-body">' . $body . '</div></div>';
}
add_shortcode( 'md_tab', 'manual_docs_shortcode_tab' );

/**
 * [md_accordion] ... [md_item title="..."]...[/md_item] ... [/md_accordion]
 *
 * @param array  $atts    Attributes.
 * @param string $content Content.
 * @return string
 */
function manual_docs_shortcode_accordion( $atts, $content = '' ) {
	$inner = do_shortcode( shortcode_unautop( $content ) );
	if ( ! $inner ) {
		return '';
	}
	return '<div class="md-accordion" data-md-accordion>' . $inner . '</div>';
}
add_shortcode( 'md_accordion', 'manual_docs_shortcode_accordion' );

/**
 * Accordion item.
 *
 * @param array  $atts    Attributes.
 * @param string $content Content.
 * @return string
 */
function manual_docs_shortcode_accordion_item( $atts, $content = '' ) {
	$atts  = shortcode_atts( array( 'title' => __( 'Section', 'manual-docs' ), 'open' => '0' ), $atts, 'md_item' );
	$title = sanitize_text_field( $atts['title'] );
	$open  = ! empty( $atts['open'] ) && '0' !== (string) $atts['open'];
	$id    = 'md-acc-' . sanitize_title( $title ) . '-' . wp_unique_id();
	$body  = do_shortcode( shortcode_unautop( $content ) );

	return '<div class="md-accordion__item' . ( $open ? ' is-open' : '' ) . '" data-md-acc-item>' .
		'<button type="button" class="md-accordion__trigger" data-md-acc-trigger aria-expanded="' . ( $open ? 'true' : 'false' ) . '" aria-controls="' . esc_attr( $id ) . '">' .
		'<span>' . esc_html( $title ) . '</span><span class="md-accordion__icon" aria-hidden="true"></span></button>' .
		'<div class="md-accordion__panel" id="' . esc_attr( $id ) . '"' . ( $open ? '' : ' hidden' ) . '>' .
		'<div class="md-accordion__panel-body">' . $body . '</div></div></div>';
}
add_shortcode( 'md_item', 'manual_docs_shortcode_accordion_item' );

/**
 * Callout shortcodes.
 *
 * @param array  $atts    Attributes.
 * @param string $content Content.
 * @param string $tag     Tag name.
 * @return string
 */
function manual_docs_shortcode_callout( $atts, $content = '', $tag = 'md_note' ) {
	$map = array(
		'md_note'    => array( 'Note', 'note' ),
		'md_tip'     => array( 'Tip', 'tip' ),
		'md_warning' => array( 'Warning', 'warning' ),
		'md_info'    => array( 'Info', 'info' ),
	);
	$meta  = isset( $map[ $tag ] ) ? $map[ $tag ] : array( 'Note', 'note' );
	$atts  = shortcode_atts( array( 'title' => $meta[0] ), $atts, $tag );
	$title = sanitize_text_field( $atts['title'] );
	$body  = do_shortcode( shortcode_unautop( $content ) );

	return '<aside class="md-callout md-callout--' . esc_attr( $meta[1] ) . '" role="note">' .
		'<p class="md-callout__title">' . esc_html( $title ) . '</p>' .
		'<div class="md-callout__body">' . $body . '</div></aside>';
}
add_shortcode( 'md_note', 'manual_docs_shortcode_callout' );
add_shortcode( 'md_tip', 'manual_docs_shortcode_callout' );
add_shortcode( 'md_warning', 'manual_docs_shortcode_callout' );
add_shortcode( 'md_info', 'manual_docs_shortcode_callout' );

/**
 * Kitchen-sink HTML/sample body for element demos.
 *
 * @return string
 */
function manual_docs_kitchen_sink_content() {
	$img = MANUAL_DOCS_URI . '/assets/images/sample-hero.svg';

	return <<<HTML
<!-- wp:heading {"level":2} -->
<h2 id="overview">Overview</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This sample page demonstrates the content elements Manual Docs styles well — headings, lists, tables, media, callouts, tabs, and accordion blocks. Use it as a visual reference when authoring documentation.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2 id="headings-and-copy">Headings and copy</h2>
<!-- /wp:heading -->
<!-- wp:heading {"level":3} -->
<h3 id="subsection">Subsection example</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Body text supports <strong>bold</strong>, <em>emphasis</em>, <a href="#">inline links</a>, and <code>inline code</code>. Keep paragraphs short for scanning.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2 id="lists">Lists</h2>
<!-- /wp:heading -->
<!-- wp:list -->
<ul><li>Unordered item one</li><li>Unordered item two with a longer line of supporting detail</li><li>Nested list:<ul><li>Child A</li><li>Child B</li></ul></li></ul>
<!-- /wp:list -->
<!-- wp:list {"ordered":true} -->
<ol><li>Install the theme</li><li>Import or author documents</li><li>Configure Appearance → Manual Docs</li></ol>
<!-- /wp:list -->

<!-- wp:heading {"level":2} -->
<h2 id="images">Images and figures</h2>
<!-- /wp:heading -->
<!-- wp:image -->
<figure class="wp-block-image size-large"><img src="{$img}" alt="Sample DigiDocs hero illustration"/><figcaption>Sample figure caption under a full-width image.</figcaption></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":2} -->
<h2 id="table">Table</h2>
<!-- /wp:heading -->
<!-- wp:table -->
<figure class="wp-block-table"><table><thead><tr><th>Feature</th><th>Status</th><th>Notes</th></tr></thead><tbody><tr><td>Version switcher</td><td>Ready</td><td>Parent-page roots</td></tr><tr><td>Live search</td><td>Ready</td><td>REST + AJAX</td></tr><tr><td>PDF export</td><td>Ready</td><td>Print stylesheet</td></tr></tbody></table></figure>
<!-- /wp:table -->

<!-- wp:heading {"level":2} -->
<h2 id="code">Code</h2>
<!-- /wp:heading -->
<!-- wp:code -->
<pre class="wp-block-code"><code>curl -X GET "/wp-json/manual-docs/v1/search?q=platform"</code></pre>
<!-- /wp:code -->

<!-- wp:heading {"level":2} -->
<h2 id="callouts">Callouts</h2>
<!-- /wp:heading -->

[md_info title="Info"]Use callouts for supporting context that should stand out from body copy.[/md_info]

[md_tip title="Tip"]Prefer short sentences and one idea per paragraph in operational docs.[/md_tip]

[md_note title="Note"]Callouts are shortcodes: <code>[md_note]</code>, <code>[md_tip]</code>, <code>[md_warning]</code>, <code>[md_info]</code>.[/md_note]

[md_warning title="Warning"]Changing rewrite slugs requires flushing permalinks afterward.[/md_warning]

<!-- wp:heading {"level":2} -->
<h2 id="tabs">Tabs</h2>
<!-- /wp:heading -->

[md_tabs]
[md_tab title="Setup"]
<p>Create version root pages (<code>goat</code>, <code>flamingo</code>, <code>hummingbird</code>) and nest product docs underneath.</p>
[/md_tab]
[md_tab title="Operations"]
<p>Use the release switcher to jump between matching paths across versions.</p>
[/md_tab]
[md_tab title="Troubleshooting"]
<p>If search returns empty, confirm login requirements and category Allowed Roles.</p>
[/md_tab]
[/md_tabs]

<!-- wp:heading {"level":2} -->
<h2 id="accordion">Accordion</h2>
<!-- /wp:heading -->

[md_accordion]
[md_item title="How do version roots work?" open="1"]
<p>Version roots are top-level <code>manual_documentation</code> pages listed in Appearance → Manual Docs. Children inherit that release.</p>
[/md_item]
[md_item title="Can guests download PDFs?"]
<p>If Require login is enabled, guests are redirected to your login path before viewing docs or PDFs.</p>
[/md_item]
[md_item title="Where do forum posts live?"]
<p>Install bbPress. Existing <code>forum</code>, <code>topic</code>, and <code>reply</code> post types continue without re-registration.</p>
[/md_item]
[/md_accordion]

<!-- wp:heading {"level":2} -->
<h2 id="quote">Quote and separator</h2>
<!-- /wp:heading -->
<!-- wp:quote -->
<blockquote class="wp-block-quote"><p>Documentation is a product surface — treat clarity and contrast as first-class features.</p><cite>Manual Docs</cite></blockquote>
<!-- /wp:quote -->
<!-- wp:separator -->
<hr class="wp-block-separator"/>
<!-- /wp:separator -->
<!-- wp:paragraph -->
<p>End of kitchen-sink sample. Duplicate this document or copy sections into real product pages.</p>
<!-- /wp:paragraph -->
HTML;
}

/**
 * Create or update the kitchen-sink sample document.
 *
 * @return int|WP_Error Post ID.
 */
function manual_docs_install_kitchen_sink_sample() {
	$title   = __( 'Content elements sample (kitchen sink)', 'manual-docs' );
	$content = manual_docs_kitchen_sink_content();
	$existing = get_posts(
		array(
			'post_type'      => 'manual_documentation',
			'post_status'    => 'any',
			'name'           => 'content-elements-sample',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	$parent_id = 0;
	if ( function_exists( 'manual_docs_get_version_roots' ) ) {
		$roots = manual_docs_get_version_roots();
		if ( ! empty( $roots[0] ) ) {
			$parent_id = (int) $roots[0]->ID;
		}
	}

	$payload = array(
		'post_title'   => $title,
		'post_name'    => 'content-elements-sample',
		'post_content' => $content,
		'post_status'  => 'publish',
		'post_type'    => 'manual_documentation',
		'post_parent'  => $parent_id,
	);

	if ( ! empty( $existing[0] ) ) {
		$payload['ID'] = (int) $existing[0];
		$result        = wp_update_post( $payload, true );
	} else {
		$result = wp_insert_post( $payload, true );
	}

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	update_post_meta( (int) $result, '_manual_docs_is_sample', 1 );
	return (int) $result;
}
