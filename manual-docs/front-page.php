<?php
/**
 * Front page template.
 *
 * Respects Settings → Reading “A static page”. The DigiDocs portal is available
 * as the “Docs Portal” page template when you want that layout.
 *
 * @package ManualDocs
 */

// Static front page: render the assigned page (Elementor / Gutenberg / etc.).
if ( 'page' === get_option( 'show_on_front' ) ) {
	$front_id = (int) get_option( 'page_on_front' );
	if ( $front_id ) {
		$template = get_page_template_slug( $front_id );
		// If they assigned Docs Portal to the front page, load that template.
		if ( $template && false !== strpos( $template, 'docs-portal.php' ) ) {
			locate_template( array( 'page-templates/docs-portal.php' ), true, false );
			return;
		}
	}

	locate_template( array( 'page.php' ), true, false );
	return;
}

// “Your latest posts” as homepage → DigiDocs portal.
locate_template( array( 'page-templates/docs-portal.php' ), true, false );
