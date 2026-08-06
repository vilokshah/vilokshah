<?php
/**
 * PDF download for individual documents.
 *
 * Uses the document permalink + ?manual_docs_pdf=1 so hierarchical
 * Manual permalinks work without a brittle rewrite slug.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Query var.
 *
 * @param array $vars Vars.
 * @return array
 */
function manual_docs_pdf_query_var( $vars ) {
	$vars[] = 'manual_docs_pdf';
	return $vars;
}
add_filter( 'query_vars', 'manual_docs_pdf_query_var' );

/**
 * Optional pretty rewrite for PDF under documentation paths only.
 */
function manual_docs_pdf_rewrite() {
	$slug = function_exists( 'manual_docs_cpt_rewrite_slug' ) ? manual_docs_cpt_rewrite_slug() : 'documentation';
	add_rewrite_rule(
		'^' . preg_quote( $slug, '/' ) . '/(.+?)/pdf/?$',
		'index.php?manual_documentation=$matches[1]&manual_docs_pdf=1',
		'top'
	);
	if ( 'docs' !== $slug ) {
		add_rewrite_rule(
			'^docs/(.+?)/pdf/?$',
			'index.php?manual_documentation=$matches[1]&manual_docs_pdf=1',
			'top'
		);
	}
}
add_action( 'init', 'manual_docs_pdf_rewrite', 30 );

/**
 * Detect PDF request from query var or request param.
 *
 * @return bool
 */
function manual_docs_is_pdf_request() {
	if ( (int) get_query_var( 'manual_docs_pdf' ) ) {
		return true;
	}
	return isset( $_GET['manual_docs_pdf'] ) && '1' === (string) wp_unslash( $_GET['manual_docs_pdf'] ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
}

/**
 * Resolve the document for a PDF request.
 *
 * @return WP_Post|null
 */
function manual_docs_get_pdf_post() {
	if ( is_singular( 'manual_documentation' ) ) {
		$post = get_queried_object();
		return ( $post instanceof WP_Post ) ? $post : null;
	}

	$slug = get_query_var( 'manual_documentation' );
	if ( $slug ) {
		// Hierarchical: last path segment is the document slug.
		$parts = array_values( array_filter( explode( '/', trim( (string) $slug, '/' ) ) ) );
		$name  = $parts ? end( $parts ) : $slug;
		$posts = get_posts( array(
			'name'           => $name,
			'post_type'      => 'manual_documentation',
			'post_status'    => array( 'publish', 'private' ),
			'posts_per_page' => 1,
		) );
		return $posts ? $posts[0] : null;
	}

	if ( isset( $_GET['p'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$post = get_post( absint( $_GET['p'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( $post && 'manual_documentation' === $post->post_type ) {
			return $post;
		}
	}

	return null;
}

/**
 * Serve PDF print view.
 */
function manual_docs_serve_pdf_view() {
	if ( ! manual_docs_is_pdf_request() ) {
		return;
	}

	$post = manual_docs_get_pdf_post();
	if ( ! $post ) {
		status_header( 404 );
		wp_die( esc_html__( 'Document not found for PDF export.', 'manual-docs' ), 404 );
	}

	if ( ! manual_docs_user_can_view_doc( $post ) ) {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( manual_docs_get_pdf_url( $post->ID ) ) );
			exit;
		}
		wp_die( esc_html__( 'You do not have permission to download this document.', 'manual-docs' ), 403 );
	}

	nocache_headers();
	header( 'Content-Type: text/html; charset=utf-8' );
	header( 'X-Robots-Tag: noindex' );

	$title   = get_the_title( $post );
	$content = apply_filters( 'the_content', $post->post_content );
	$version = manual_docs_get_doc_version( $post->ID );
	$site    = get_bloginfo( 'name' );
	$date    = get_the_modified_date( '', $post );
	$version_name = is_array( $version ) ? $version['name'] : '';
	?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( $title ); ?> — PDF</title>
	<style>
		:root { color-scheme: light; }
		body { font-family: Georgia, "Times New Roman", serif; color: #111; line-height: 1.55; max-width: 800px; margin: 0 auto; padding: 2rem; }
		h1,h2,h3,h4 { font-family: system-ui, -apple-system, sans-serif; line-height: 1.25; }
		h1 { font-size: 1.75rem; margin-bottom: .35rem; }
		.meta { color: #555; font-size: .875rem; margin-bottom: 1.5rem; border-bottom: 1px solid #ddd; padding-bottom: .75rem; }
		img { max-width: 100%; height: auto; }
		pre, code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .875rem; }
		pre { background: #f5f5f5; padding: 1rem; overflow: auto; }
		a { color: #1d4ed8; }
		.toolbar { position: sticky; top: 0; background: #fff; border-bottom: 1px solid #e5e5e5; padding: .75rem 0; margin: -2rem -2rem 1.5rem; padding-left: 2rem; padding-right: 2rem; display: flex; gap: .75rem; align-items: center; }
		.toolbar button, .toolbar a { font-family: system-ui, sans-serif; font-size: .875rem; padding: .5rem .9rem; border-radius: 6px; border: 1px solid #ccc; background: #1d4ed8; color: #fff; text-decoration: none; cursor: pointer; }
		.toolbar a.secondary { background: #fff; color: #111; }
		@media print {
			.toolbar { display: none !important; }
			body { padding: 0; max-width: none; }
			a { text-decoration: none; color: inherit; }
		}
	</style>
</head>
<body>
	<div class="toolbar">
		<button type="button" onclick="window.print()"><?php esc_html_e( 'Print / Save as PDF', 'manual-docs' ); ?></button>
		<a class="secondary" href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php esc_html_e( 'Back to document', 'manual-docs' ); ?></a>
	</div>
	<article>
		<h1><?php echo esc_html( $title ); ?></h1>
		<div class="meta">
			<?php echo esc_html( $site ); ?>
			<?php if ( $version_name ) : ?>
				· <?php echo esc_html( sprintf( __( 'Version %s', 'manual-docs' ), $version_name ) ); ?>
			<?php endif; ?>
			· <?php echo esc_html( sprintf( __( 'Updated %s', 'manual-docs' ), $date ) ); ?>
		</div>
		<div class="content">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- filtered via the_content ?>
		</div>
	</article>
	<script>
		(function () {
			var params = new URLSearchParams(window.location.search);
			if (params.get('autoprint') === '1') {
				window.addEventListener('load', function () { window.print(); });
			}
		})();
	</script>
</body>
</html>
	<?php
	exit;
}
add_action( 'template_redirect', 'manual_docs_serve_pdf_view', 1 );

/**
 * PDF URL for a document (works with any permalink structure).
 *
 * @param int  $post_id   Post ID.
 * @param bool $autoprint Auto open print dialog.
 * @return string
 */
function manual_docs_get_pdf_url( $post_id, $autoprint = false ) {
	$permalink = get_permalink( $post_id );
	if ( ! $permalink ) {
		return '';
	}

	$url = add_query_arg( 'manual_docs_pdf', '1', $permalink );
	if ( $autoprint ) {
		$url = add_query_arg( 'autoprint', '1', $url );
	}
	return $url;
}

/**
 * Render PDF download button.
 *
 * @param int|null $post_id Post ID.
 */
function manual_docs_render_pdf_button( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$url     = manual_docs_get_pdf_url( $post_id, true );
	if ( ! $url ) {
		return;
	}
	?>
	<a class="md-btn md-btn--ghost md-pdf-download" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">
		<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v12m0 0l4-4m-4 4l-4-4M4 21h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		<?php esc_html_e( 'Download PDF', 'manual-docs' ); ?>
	</a>
	<?php
}