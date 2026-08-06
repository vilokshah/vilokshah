<?php
/**
 * Theme Customizer settings.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register customizer settings.
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function manual_docs_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'manual_docs_options',
		array(
			'title'    => __( 'Manual Docs Options', 'manual-docs' ),
			'priority' => 30,
		)
	);

	$wp_customize->add_setting(
		'manual_docs_require_login',
		array(
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
		)
	);
	$wp_customize->add_control(
		'manual_docs_require_login',
		array(
			'label'       => __( 'Require login to view documentation', 'manual-docs' ),
			'description' => __( 'Guests are redirected to the WordPress login page before accessing docs.', 'manual-docs' ),
			'section'     => 'manual_docs_options',
			'type'        => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'manual_docs_hero_title',
		array(
			'default'           => __( 'Documentation', 'manual-docs' ),
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'manual_docs_hero_title',
		array(
			'label'   => __( 'Docs home title', 'manual-docs' ),
			'section' => 'manual_docs_options',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'manual_docs_hero_text',
		array(
			'default'           => __( 'Find guides, references, and answers in one place.', 'manual-docs' ),
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'manual_docs_hero_text',
		array(
			'label'   => __( 'Docs home subtitle', 'manual-docs' ),
			'section' => 'manual_docs_options',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'manual_docs_show_community_cta',
		array(
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
		)
	);
	$wp_customize->add_control(
		'manual_docs_show_community_cta',
		array(
			'label'   => __( 'Show community / forums CTA on documents', 'manual-docs' ),
			'section' => 'manual_docs_options',
			'type'    => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'manual_docs_accent_color',
		array(
			'default'           => '#0f766e',
			'sanitize_callback' => 'sanitize_hex_color',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'manual_docs_accent_color',
			array(
				'label'   => __( 'Accent color', 'manual-docs' ),
				'section' => 'manual_docs_options',
			)
		)
	);
}
add_action( 'customize_register', 'manual_docs_customize_register' );

/**
 * Output custom accent CSS variables.
 */
function manual_docs_customizer_css() {
	$accent = get_theme_mod( 'manual_docs_accent_color', '#0f766e' );
	?>
	<style id="manual-docs-customizer-css">
		:root {
			--md-accent: <?php echo esc_html( $accent ); ?>;
			--md-accent-soft: color-mix(in srgb, <?php echo esc_html( $accent ); ?> 14%, white);
		}
	</style>
	<?php
}
add_action( 'wp_head', 'manual_docs_customizer_css', 20 );