<?php
/**
 * Lightweight Customizer mirrors (Appearance → Customize).
 * Full controls live under Appearance → Manual Docs.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register a shortcut note in Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function manual_docs_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'manual_docs_options',
		array(
			'title'       => __( 'Manual Docs', 'manual-docs' ),
			'description' => __( 'Most theme settings are under Appearance → Manual Docs (colors, versions, access).', 'manual-docs' ),
			'priority'    => 30,
		)
	);

	$wp_customize->add_setting(
		'manual_docs_customizer_note',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'manual_docs_customizer_note',
		array(
			'label'       => __( 'Open theme settings', 'manual-docs' ),
			'description' => __( 'Go to Appearance → Manual Docs to configure brand colors, release version parent pages (goat / flamingo / hummingbird), login gate, and document chrome.', 'manual-docs' ),
			'section'     => 'manual_docs_options',
			'type'        => 'hidden',
		)
	);
}
add_action( 'customize_register', 'manual_docs_customize_register' );