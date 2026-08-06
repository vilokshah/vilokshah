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
 * Make content print-safe: absolute image URLs, unwrap lazy-load attrs.
 *
 * @param string $html Content HTML.
 * @return string
 */
function manual_docs_prepare_pdf_content( $html ) {
	if ( ! $html ) {
		return '';
	}

	if ( ! class_exists( 'DOMDocument' ) ) {
		return $html;
	}

	$previous = libxml_use_internal_errors( true );
	$dom      = new DOMDocument();
	$wrapped  = '<?xml encoding="utf-8" ?><div id="md-pdf-root">' . $html . '</div>';
	$loaded   = $dom->loadHTML( $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	if ( ! $loaded ) {
		return $html;
	}

	$xpath = new DOMXPath( $dom );
	$imgs  = $xpath->query( '//img' );
	if ( $imgs ) {
		foreach ( $imgs as $img ) {
			$src = $img->getAttribute( 'src' );
			foreach ( array( 'data-src', 'data-lazy-src', 'data-original' ) as $attr ) {
				$lazy = $img->getAttribute( $attr );
				if ( $lazy && ( ! $src || false !== strpos( $src, 'data:image' ) || false !== strpos( $src, 'placeholder' ) ) ) {
					$src = $lazy;
					break;
				}
			}
			if ( $src ) {
				$img->setAttribute( 'src', manual_docs_absolutize_url( $src ) );
			}

			$srcset = $img->getAttribute( 'srcset' );
			if ( ! $srcset ) {
				$srcset = $img->getAttribute( 'data-srcset' );
			}
			if ( $srcset ) {
				$img->setAttribute( 'srcset', manual_docs_absolutize_srcset( $srcset ) );
			}

			$img->removeAttribute( 'loading' );
			$img->removeAttribute( 'data-src' );
			$img->removeAttribute( 'data-lazy-src' );
			$img->removeAttribute( 'data-original' );
			$img->removeAttribute( 'data-srcset' );
			$img->setAttribute( 'loading', 'eager' );
			$img->setAttribute( 'decoding', 'sync' );
		}
	}

	// Absolutize common media / link hrefs that printers resolve poorly.
	foreach ( array( '//a[@href]', '//source[@src]', '//video[@src]', '//audio[@src]' ) as $query ) {
		$nodes = $xpath->query( $query );
		if ( ! $nodes ) {
			continue;
		}
		foreach ( $nodes as $node ) {
			$attr = $node->hasAttribute( 'href' ) ? 'href' : 'src';
			$val  = $node->getAttribute( $attr );
			if ( $val && 0 !== strpos( $val, '#' ) && 0 !== strpos( $val, 'mailto:' ) && 0 !== strpos( $val, 'tel:' ) ) {
				$node->setAttribute( $attr, manual_docs_absolutize_url( $val ) );
			}
		}
	}

	$root = $dom->getElementById( 'md-pdf-root' );
	if ( ! $root ) {
		return $html;
	}

	$out = '';
	foreach ( $root->childNodes as $child ) {
		$out .= $dom->saveHTML( $child );
	}
	return $out ? $out : $html;
}

/**
 * Absolutize a URL against the site home.
 *
 * @param string $url URL.
 * @return string
 */
function manual_docs_absolutize_url( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url || 0 === strpos( $url, 'data:' ) || 0 === strpos( $url, 'blob:' ) ) {
		return $url;
	}
	if ( preg_match( '#^(https?:)?//#i', $url ) ) {
		if ( 0 === strpos( $url, '//' ) ) {
			$scheme = is_ssl() ? 'https:' : 'http:';
			return $scheme . $url;
		}
		return $url;
	}
	return home_url( $url );
}

/**
 * Absolutize srcset attribute values.
 *
 * @param string $srcset Srcset.
 * @return string
 */
function manual_docs_absolutize_srcset( $srcset ) {
	$parts = array_map( 'trim', explode( ',', (string) $srcset ) );
	$out   = array();
	foreach ( $parts as $part ) {
		if ( '' === $part ) {
			continue;
		}
		$bits = preg_split( '/\s+/', $part, 2 );
		$url  = manual_docs_absolutize_url( $bits[0] );
		$out[] = isset( $bits[1] ) ? $url . ' ' . $bits[1] : $url;
	}
	return implode( ', ', $out );
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
			$login = function_exists( 'manual_docs_get_login_url' )
				? manual_docs_get_login_url( manual_docs_get_pdf_url( $post->ID ) )
				: wp_login_url( manual_docs_get_pdf_url( $post->ID ) );
			wp_safe_redirect( $login );
			exit;
		}
		wp_die( esc_html__( 'You do not have permission to download this document.', 'manual-docs' ), 403 );
	}

	nocache_headers();
	header( 'Content-Type: text/html; charset=utf-8' );
	header( 'X-Robots-Tag: noindex' );

	$title        = get_the_title( $post );
	$content      = apply_filters( 'the_content', $post->post_content );
	$content      = manual_docs_prepare_pdf_content( $content );
	$version      = function_exists( 'manual_docs_get_doc_version' ) ? manual_docs_get_doc_version( $post->ID ) : null;
	$site         = get_bloginfo( 'name' );
	$date         = get_the_modified_date( '', $post );
	$version_name = is_array( $version ) ? $version['name'] : '';
	$watermark    = (string) manual_docs_get_option( 'pdf_watermark', 'Digitate Docs' );
	if ( '' === trim( $watermark ) ) {
		$watermark = 'Digitate Docs';
	}
	$header_left  = $site ? $site : 'Digitate Docs';
	$featured     = get_the_post_thumbnail_url( $post, 'full' );
	if ( $featured ) {
		$featured = manual_docs_absolutize_url( $featured );
	}
	$print_date = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
	?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( $title ); ?> — PDF</title>
	<style>
		:root { color-scheme: light; }
		* { box-sizing: border-box; }
		body {
			font-family: Georgia, "Times New Roman", serif;
			color: #111;
			line-height: 1.55;
			max-width: 860px;
			margin: 0 auto;
			padding: 4.5rem 1.5rem 4rem;
			background: #fff;
			position: relative;
		}
		h1,h2,h3,h4 { font-family: system-ui, -apple-system, "Segoe UI", sans-serif; line-height: 1.25; color: #0f172a; }
		h1 { font-size: 1.75rem; margin: 0 0 .35rem; }
		.meta { color: #555; font-size: .875rem; margin-bottom: 1.25rem; border-bottom: 1px solid #ddd; padding-bottom: .75rem; }
		.content img, .featured img, figure img {
			max-width: 100% !important;
			height: auto !important;
			display: block;
			margin: 1rem 0;
			page-break-inside: avoid;
			-webkit-print-color-adjust: exact;
			print-color-adjust: exact;
		}
		figure { margin: 1rem 0; page-break-inside: avoid; }
		figcaption { font-size: .85rem; color: #555; margin-top: .35rem; }
		pre, code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .875rem; }
		pre { background: #f5f5f5; padding: 1rem; overflow: auto; page-break-inside: avoid; }
		table { width: 100%; border-collapse: collapse; margin: 1rem 0; page-break-inside: avoid; }
		th, td { border: 1px solid #ddd; padding: .5rem .65rem; text-align: left; }
		a { color: #1d4ed8; }
		.toolbar {
			position: sticky; top: 0; z-index: 20; background: #fff;
			border-bottom: 1px solid #e5e5e5; margin: -4.5rem -1.5rem 1.5rem;
			padding: .75rem 1.5rem; display: flex; gap: .75rem; align-items: center;
		}
		.toolbar button, .toolbar a {
			font-family: system-ui, sans-serif; font-size: .875rem; padding: .5rem .9rem;
			border-radius: 6px; border: 1px solid #ccc; background: #1d4ed8; color: #fff;
			text-decoration: none; cursor: pointer;
		}
		.toolbar a.secondary { background: #fff; color: #111; }
		.pdf-running-header, .pdf-running-footer, .pdf-watermark { display: none; }

		@media print {
			@page { margin: 18mm 14mm 18mm 14mm; }
			html, body { background: #fff !important; }
			.toolbar { display: none !important; }
			body { padding: 12mm 0 14mm; max-width: none; margin: 0; }
			a { text-decoration: none; color: inherit; }
			.pdf-running-header {
				display: block;
				position: fixed;
				top: 0; left: 0; right: 0;
				height: 10mm;
				font-family: system-ui, sans-serif;
				font-size: 9pt;
				color: #334155;
				border-bottom: 1px solid #cbd5e1;
				padding: 0 0 2mm;
				background: #fff;
			}
			.pdf-running-header__inner {
				display: flex; justify-content: space-between; align-items: baseline; gap: 1rem;
			}
			.pdf-running-header__brand { font-weight: 700; color: #0f172a; }
			.pdf-running-header__doc { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 55%; }
			.pdf-running-footer {
				display: block;
				position: fixed;
				bottom: 0; left: 0; right: 0;
				height: 10mm;
				font-family: system-ui, sans-serif;
				font-size: 8.5pt;
				color: #64748b;
				border-top: 1px solid #cbd5e1;
				padding: 2mm 0 0;
				background: #fff;
			}
			.pdf-running-footer__inner {
				display: flex; justify-content: space-between; gap: 1rem;
			}
			.pdf-watermark {
				display: block;
				position: fixed;
				top: 42%;
				left: 50%;
				transform: translate(-50%, -50%) rotate(-32deg);
				font-family: system-ui, -apple-system, sans-serif;
				font-size: 64pt;
				font-weight: 700;
				letter-spacing: 0.04em;
				color: #0f172a;
				opacity: 0.07;
				white-space: nowrap;
				pointer-events: none;
				z-index: 0;
				-webkit-print-color-adjust: exact;
				print-color-adjust: exact;
			}
			article { position: relative; z-index: 1; }
			.content img, .featured img {
				max-width: 100% !important;
				height: auto !important;
				-webkit-print-color-adjust: exact;
				print-color-adjust: exact;
			}
		}
	</style>
</head>
<body>
	<div class="toolbar">
		<button type="button" id="md-pdf-print"><?php esc_html_e( 'Print / Save as PDF', 'manual-docs' ); ?></button>
		<a class="secondary" href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php esc_html_e( 'Back to document', 'manual-docs' ); ?></a>
	</div>

	<div class="pdf-running-header" aria-hidden="true">
		<div class="pdf-running-header__inner">
			<span class="pdf-running-header__brand"><?php echo esc_html( $watermark ); ?></span>
			<span class="pdf-running-header__doc"><?php echo esc_html( $title ); ?></span>
		</div>
	</div>
	<div class="pdf-running-footer" aria-hidden="true">
		<div class="pdf-running-footer__inner">
			<span><?php echo esc_html( $header_left ); ?><?php echo $version_name ? ' · ' . esc_html( $version_name ) : ''; ?></span>
			<span><?php echo esc_html( sprintf( __( 'Printed %s', 'manual-docs' ), $print_date ) ); ?></span>
		</div>
	</div>
	<div class="pdf-watermark" aria-hidden="true"><?php echo esc_html( $watermark ); ?></div>

	<article>
		<h1><?php echo esc_html( $title ); ?></h1>
		<div class="meta">
			<?php echo esc_html( $site ); ?>
			<?php if ( $version_name ) : ?>
				· <?php echo esc_html( sprintf( __( 'Version %s', 'manual-docs' ), $version_name ) ); ?>
			<?php endif; ?>
			· <?php echo esc_html( sprintf( __( 'Updated %s', 'manual-docs' ), $date ) ); ?>
		</div>
		<?php if ( $featured ) : ?>
			<div class="featured">
				<img src="<?php echo esc_url( $featured ); ?>" alt="<?php echo esc_attr( $title ); ?>" />
			</div>
		<?php endif; ?>
		<div class="content">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- filtered via the_content + PDF prep ?>
		</div>
	</article>
	<script>
		(function () {
			function whenImagesReady(done) {
				var finished = false;
				var finishOnce = function () {
					if (finished) return;
					finished = true;
					done();
				};
				var imgs = Array.prototype.slice.call(document.images || []);
				if (!imgs.length) {
					finishOnce();
					return;
				}
				var left = imgs.length;
				var tick = function () {
					left -= 1;
					if (left <= 0) finishOnce();
				};
				imgs.forEach(function (img) {
					if (img.complete) {
						tick();
						return;
					}
					img.addEventListener('load', tick, { once: true });
					img.addEventListener('error', tick, { once: true });
				});
				setTimeout(finishOnce, 8000);
			}

			function doPrint() {
				whenImagesReady(function () { window.print(); });
			}

			var btn = document.getElementById('md-pdf-print');
			if (btn) btn.addEventListener('click', function (e) { e.preventDefault(); doPrint(); });

			var params = new URLSearchParams(window.location.search);
			if (params.get('autoprint') === '1') {
				window.addEventListener('load', function () { doPrint(); });
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
