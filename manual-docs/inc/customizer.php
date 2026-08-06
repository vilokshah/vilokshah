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
			'description' => __( 'Logo and Site Icon (favicon) are under Site Identity. Full theme settings: Appearance → Manual Docs.', 'manual-docs' ),
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
			'label'       => __( 'Theme settings', 'manual-docs' ),
			'description' => __( 'Use Site Identity for logo + favicon. Use Appearance → Manual Docs for colors, versions, and the [manual_docs_search] shortcode help.', 'manual-docs' ),
			'section'     => 'manual_docs_options',
			'type'        => 'hidden',
		)
	);

	// Keep Site Identity panel easy to find.
	if ( $wp_customize->get_section( 'title_tagline' ) ) {
		$wp_customize->get_section( 'title_tagline' )->title = __( 'Site Identity (logo & favicon)', 'manual-docs' );
	}
}
add_action( 'customize_register', 'manual_docs_customize_register' );