<?php
/**
 * Version diff — summary-first compare across release roots.
 *
 * Gated entirely by Appearance → Manual Docs → “Enable version diff”.
 * When off, this file still loads but all public helpers no-op.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether version diff is enabled.
 *
 * @return bool
 */
function manual_docs_version_diff_enabled() {
	return (bool) manual_docs_get_option( 'enable_version_diff', false );
}

/**
 * Register query var.
 *
 * @param array $vars Vars.
 * @return array
 */
function manual_docs_version_diff_query_vars( $vars ) {
	$vars[] = 'md_compare';
	return $vars;
}
add_filter( 'query_vars', 'manual_docs_version_diff_query_vars' );

/**
 * Current compare target from request (slug or numeric root ID).
 *
 * @return string
 */
function manual_docs_get_compare_request() {
	if ( ! manual_docs_version_diff_enabled() ) {
		return '';
	}
	$val = get_query_var( 'md_compare' );
	if ( '' === $val || null === $val ) {
		$val = isset( $_GET['md_compare'] ) ? wp_unslash( $_GET['md_compare'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	return sanitize_title( (string) $val );
}

/**
 * Resolve compare target to a version root post.
 *
 * @param string $token Slug or ID.
 * @return WP_Post|null
 */
function manual_docs_resolve_compare_root( $token ) {
	$token = trim( (string) $token );
	if ( '' === $token || ! function_exists( 'manual_docs_get_version_roots' ) ) {
		return null;
	}
	foreach ( manual_docs_get_version_roots() as $root ) {
		if ( (string) $root->ID === $token || $root->post_name === $token || sanitize_title( get_the_title( $root ) ) === $token ) {
			return $root;
		}
	}
	if ( ctype_digit( $token ) ) {
		$post = get_post( (int) $token );
		if ( $post && 'manual_documentation' === $post->post_type ) {
			return $post;
		}
	}
	return null;
}

/**
 * Build compare URL for a doc vs a target root.
 *
 * @param int    $post_id   Source doc.
 * @param string $root_slug Target root slug.
 * @return string
 */
function manual_docs_get_compare_url( $post_id, $root_slug ) {
	$url = get_permalink( $post_id );
	return add_query_arg( 'md_compare', sanitize_title( $root_slug ), $url );
}

/**
 * Plain-text normalize for comparisons.
 *
 * @param string $html HTML.
 * @return string
 */
function manual_docs_diff_plain_text( $html ) {
	$html = (string) $html;
	$html = preg_replace( '#<(script|style)[^>]*>.*?</\1>#is', '', $html );
	$text = wp_strip_all_tags( $html, true );
	$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = preg_replace( "/[ \t]+/", ' ', $text );
	$text = preg_replace( "/\n{3,}/", "\n\n", $text );
	return trim( (string) $text );
}

/**
 * Split rendered HTML into heading sections.
 *
 * @param string $html HTML.
 * @return array<int,array{id:string,title:string,level:int,html:string,text:string}>
 */
function manual_docs_diff_split_sections( $html ) {
	$html = (string) $html;
	if ( '' === trim( $html ) ) {
		return array(
			array(
				'id'    => 'body',
				'title' => __( 'Document body', 'manual-docs' ),
				'level' => 2,
				'html'  => '',
				'text'  => '',
			),
		);
	}

	$parts = preg_split( '/(?=<h([23])\b[^>]*>)/i', $html, -1, PREG_SPLIT_NO_EMPTY );
	if ( ! is_array( $parts ) || empty( $parts ) ) {
		$text = manual_docs_diff_plain_text( $html );
		return array(
			array(
				'id'    => 'body',
				'title' => __( 'Document body', 'manual-docs' ),
				'level' => 2,
				'html'  => $html,
				'text'  => $text,
			),
		);
	}

	$sections = array();
	$intro    = array_shift( $parts );
	// Content before first h2/h3.
	if ( is_string( $intro ) && '' !== trim( wp_strip_all_tags( $intro ) ) && ! preg_match( '/^<h[23]\b/i', $intro ) ) {
		$sections[] = array(
			'id'    => 'introduction',
			'title' => __( 'Introduction', 'manual-docs' ),
			'level' => 2,
			'html'  => $intro,
			'text'  => manual_docs_diff_plain_text( $intro ),
		);
	} elseif ( is_string( $intro ) && preg_match( '/^<h[23]\b/i', $intro ) ) {
		array_unshift( $parts, $intro );
	}

	foreach ( $parts as $index => $chunk ) {
		$title = __( 'Section', 'manual-docs' ) . ' ' . ( $index + 1 );
		$level = 2;
		$id    = 'section-' . ( $index + 1 );
		if ( preg_match( '/<h([23])\b([^>]*)>(.*?)<\/h\1>/is', $chunk, $m ) ) {
			$level = (int) $m[1];
			$title = trim( wp_strip_all_tags( $m[3] ) );
			if ( preg_match( '/\bid=["\']([^"\']+)["\']/', $m[2], $idm ) ) {
				$id = $idm[1];
			} else {
				$id = sanitize_title( $title );
			}
		}
		$sections[] = array(
			'id'    => $id ? $id : ( 'section-' . ( $index + 1 ) ),
			'title' => $title ? $title : __( 'Untitled section', 'manual-docs' ),
			'level' => $level,
			'html'  => $chunk,
			'text'  => manual_docs_diff_plain_text( $chunk ),
		);
	}

	return $sections;
}

/**
 * Render post content for diffing (respects shortcodes).
 *
 * @param WP_Post $post Post.
 * @return string
 */
function manual_docs_diff_get_rendered_content( WP_Post $post ) {
	$GLOBALS['post'] = $post;
	setup_postdata( $post );
	$content = apply_filters( 'the_content', $post->post_content );
	wp_reset_postdata();
	return (string) $content;
}

/**
 * Tokenize text for word-level diff (keeps whitespace tokens).
 *
 * @param string $text Text.
 * @return string[]
 */
function manual_docs_diff_tokens( $text ) {
	$text = (string) $text;
	if ( '' === $text ) {
		return array();
	}
	$parts = preg_split( '/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
	return is_array( $parts ) ? $parts : array( $text );
}

/**
 * Build LCS-based opcodes for two token arrays.
 *
 * @param string[] $a From tokens.
 * @param string[] $b To tokens.
 * @return array<int,array{0:string,1:int,2:int,3:int,4:int}> tag, i1, i2, j1, j2
 */
function manual_docs_diff_opcodes( $a, $b ) {
	$n = count( $a );
	$m = count( $b );

	if ( 0 === $n && 0 === $m ) {
		return array();
	}
	if ( $n * $m > 220000 ) {
		$ops = array();
		if ( $n ) {
			$ops[] = array( 'delete', 0, $n, 0, 0 );
		}
		if ( $m ) {
			$ops[] = array( 'insert', $n, $n, 0, $m );
		}
		return $ops;
	}

	$dp = array_fill( 0, $n + 1, array_fill( 0, $m + 1, 0 ) );
	for ( $i = $n - 1; $i >= 0; $i-- ) {
		for ( $j = $m - 1; $j >= 0; $j-- ) {
			if ( $a[ $i ] === $b[ $j ] ) {
				$dp[ $i ][ $j ] = $dp[ $i + 1 ][ $j + 1 ] + 1;
			} else {
				$dp[ $i ][ $j ] = max( $dp[ $i + 1 ][ $j ], $dp[ $i ][ $j + 1 ] );
			}
		}
	}

	$raw = array();
	$i   = 0;
	$j   = 0;
	while ( $i < $n && $j < $m ) {
		if ( $a[ $i ] === $b[ $j ] ) {
			$raw[] = array( 'equal', $i, $i + 1, $j, $j + 1 );
			$i++;
			$j++;
		} elseif ( $dp[ $i + 1 ][ $j ] >= $dp[ $i ][ $j + 1 ] ) {
			$raw[] = array( 'delete', $i, $i + 1, $j, $j );
			$i++;
		} else {
			$raw[] = array( 'insert', $i, $i, $j, $j + 1 );
			$j++;
		}
	}
	while ( $i < $n ) {
		$raw[] = array( 'delete', $i, $i + 1, $j, $j );
		$i++;
	}
	while ( $j < $m ) {
		$raw[] = array( 'insert', $i, $i, $j, $j + 1 );
		$j++;
	}

	// Merge adjacent same-tag ops.
	$merged = array();
	foreach ( $raw as $op ) {
		if ( ! empty( $merged ) ) {
			$idx  = count( $merged ) - 1;
			$last = $merged[ $idx ];
			if ( $last[0] === $op[0] && $last[2] === $op[1] && $last[4] === $op[3] ) {
				$merged[ $idx ][2] = $op[2];
				$merged[ $idx ][4] = $op[4];
				continue;
			}
		}
		$merged[] = $op;
	}

	return $merged;
}

/**
 * Build highlighted HTML pair for from/to text.
 *
 * @param string $from_text Old text.
 * @param string $to_text   New text.
 * @return array{from:string,to:string}
 */
function manual_docs_diff_highlight_pair( $from_text, $to_text ) {
	$from_text = (string) $from_text;
	$to_text   = (string) $to_text;

	if ( '' === $from_text && '' === $to_text ) {
		return array( 'from' => '', 'to' => '' );
	}
	if ( '' === $from_text ) {
		return array(
			'from' => '',
			'to'   => '<mark class="md-diff-hl md-diff-hl--add">' . esc_html( $to_text ) . '</mark>',
		);
	}
	if ( '' === $to_text ) {
		return array(
			'from' => '<mark class="md-diff-hl md-diff-hl--del">' . esc_html( $from_text ) . '</mark>',
			'to'   => '',
		);
	}
	if ( $from_text === $to_text ) {
		$safe = esc_html( $from_text );
		return array( 'from' => $safe, 'to' => $safe );
	}

	$a   = manual_docs_diff_tokens( $from_text );
	$b   = manual_docs_diff_tokens( $to_text );
	$ops = manual_docs_diff_opcodes( $a, $b );

	$from_html = '';
	$to_html   = '';
	foreach ( $ops as $op ) {
		list( $tag, $i1, $i2, $j1, $j2 ) = $op;
		if ( 'equal' === $tag ) {
			$chunk      = esc_html( implode( '', array_slice( $a, $i1, $i2 - $i1 ) ) );
			$from_html .= $chunk;
			$to_html   .= $chunk;
		} elseif ( 'delete' === $tag ) {
			$chunk      = esc_html( implode( '', array_slice( $a, $i1, $i2 - $i1 ) ) );
			if ( '' !== $chunk ) {
				$from_html .= '<mark class="md-diff-hl md-diff-hl--del">' . $chunk . '</mark>';
			}
		} elseif ( 'insert' === $tag ) {
			$chunk    = esc_html( implode( '', array_slice( $b, $j1, $j2 - $j1 ) ) );
			if ( '' !== $chunk ) {
				$to_html .= '<mark class="md-diff-hl md-diff-hl--add">' . $chunk . '</mark>';
			}
		}
	}

	return array(
		'from' => $from_html,
		'to'   => $to_html,
	);
}

/**
 * Compare two docs; return summary + section details.
 *
 * @param WP_Post $from From doc (e.g. Goat).
 * @param WP_Post $to   To doc (e.g. Flamingo).
 * @return array{path:string,from:array,to:array,changes:array,stats:array}
 */
function manual_docs_diff_compare_posts( WP_Post $from, WP_Post $to ) {
	$from_root = manual_docs_get_version_root_for_doc( $from->ID );
	$to_root   = manual_docs_get_version_root_for_doc( $to->ID );
	$path      = $from_root ? implode( '/', manual_docs_get_relative_slug_path( $from->ID, $from_root->ID ) ) : $from->post_name;

	$left  = manual_docs_diff_split_sections( manual_docs_diff_get_rendered_content( $from ) );
	$right = manual_docs_diff_split_sections( manual_docs_diff_get_rendered_content( $to ) );

	$right_by_key = array();
	foreach ( $right as $sec ) {
		$key = strtolower( trim( $sec['title'] ) );
		$right_by_key[ $key ] = $sec;
	}

	$used    = array();
	$changes = array();

	foreach ( $left as $sec ) {
		$key = strtolower( trim( $sec['title'] ) );
		if ( isset( $right_by_key[ $key ] ) ) {
			$peer = $right_by_key[ $key ];
			$used[ $key ] = true;
			if ( $sec['text'] === $peer['text'] ) {
				continue; // unchanged — omit from summary.
			}
			$hl = manual_docs_diff_highlight_pair( $sec['text'], $peer['text'] );
			$changes[] = array(
				'status'      => 'modified',
				'title'       => $sec['title'],
				'id'          => $sec['id'],
				'summary'     => __( 'Wording changed — expand to see highlighted differences.', 'manual-docs' ),
				'from_text'   => $sec['text'],
				'to_text'     => $peer['text'],
				'from_mark'   => $hl['from'],
				'to_mark'     => $hl['to'],
			);
		} else {
			$hl = manual_docs_diff_highlight_pair( $sec['text'], '' );
			$changes[] = array(
				'status'      => 'removed',
				'title'       => $sec['title'],
				'id'          => $sec['id'],
				'summary'     => sprintf(
					/* translators: %s: version name */
					__( 'Removed in the newer release — only in %s.', 'manual-docs' ),
					$from_root ? get_the_title( $from_root ) : __( 'source', 'manual-docs' )
				),
				'from_text'   => $sec['text'],
				'to_text'     => '',
				'from_mark'   => $hl['from'],
				'to_mark'     => '',
			);
		}
	}

	foreach ( $right as $sec ) {
		$key = strtolower( trim( $sec['title'] ) );
		if ( isset( $used[ $key ] ) ) {
			continue;
		}
		$hl = manual_docs_diff_highlight_pair( '', $sec['text'] );
		$changes[] = array(
			'status'      => 'added',
			'title'       => $sec['title'],
			'id'          => $sec['id'],
			'summary'     => sprintf(
				/* translators: %s: version name */
				__( 'New section — only in %s.', 'manual-docs' ),
				$to_root ? get_the_title( $to_root ) : __( 'target', 'manual-docs' )
			),
			'from_text'   => '',
			'to_text'     => $sec['text'],
			'from_mark'   => '',
			'to_mark'     => $hl['to'],
		);
	}

	$stats = array(
		'modified' => 0,
		'added'    => 0,
		'removed'  => 0,
	);
	foreach ( $changes as $change ) {
		if ( isset( $stats[ $change['status'] ] ) ) {
			$stats[ $change['status'] ]++;
		}
	}

	return array(
		'path'    => $path,
		'from'    => array(
			'id'      => (int) $from->ID,
			'title'   => get_the_title( $from ),
			'url'     => get_permalink( $from ),
			'version' => $from_root ? get_the_title( $from_root ) : '',
			'slug'    => $from_root ? $from_root->post_name : '',
		),
		'to'      => array(
			'id'      => (int) $to->ID,
			'title'   => get_the_title( $to ),
			'url'     => get_permalink( $to ),
			'version' => $to_root ? get_the_title( $to_root ) : '',
			'slug'    => $to_root ? $to_root->post_name : '',
		),
		'changes' => $changes,
		'stats'   => $stats,
	);
}

/**
 * Render Compare versions control (no-op when disabled).
 *
 * @param int|null $post_id Post ID.
 */
function manual_docs_render_compare_control( $post_id = null ) {
	if ( ! manual_docs_version_diff_enabled() ) {
		return;
	}

	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	$roots   = manual_docs_get_version_roots();
	$current = manual_docs_get_doc_version( $post_id );
	if ( count( $roots ) < 2 || ! $current ) {
		return;
	}

	$targets = array();
	foreach ( $roots as $root ) {
		if ( (int) $root->ID === (int) $current['id'] ) {
			continue;
		}
		$sibling = manual_docs_find_version_sibling( $post_id, $root->ID );
		$targets[] = array(
			'root'    => $root,
			'sibling' => $sibling,
			'url'     => $sibling ? manual_docs_get_compare_url( $post_id, $root->post_name ) : '',
		);
	}

	if ( empty( $targets ) ) {
		return;
	}
	?>
	<div class="md-compare" data-md-compare>
		<button type="button" class="md-compare__toggle" data-md-compare-toggle aria-expanded="false" aria-haspopup="true">
			<?php esc_html_e( 'Compare versions', 'manual-docs' ); ?>
		</button>
		<div class="md-compare__menu" data-md-compare-menu hidden>
			<?php foreach ( $targets as $target ) : ?>
				<?php if ( $target['sibling'] && $target['url'] ) : ?>
					<a class="md-compare__item" href="<?php echo esc_url( $target['url'] ); ?>">
						<?php
						printf(
							/* translators: %s: version name */
							esc_html__( 'Compare with %s', 'manual-docs' ),
							esc_html( get_the_title( $target['root'] ) )
						);
						?>
					</a>
				<?php else : ?>
					<span class="md-compare__item md-compare__item--disabled">
						<?php
						printf(
							/* translators: %s: version name */
							esc_html__( 'No %s equivalent', 'manual-docs' ),
							esc_html( get_the_title( $target['root'] ) )
						);
						?>
					</span>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

/**
 * Render the summary-first diff view.
 *
 * @param int    $post_id      Current post.
 * @param string $compare_token Target root slug/id.
 */
function manual_docs_render_version_diff_view( $post_id, $compare_token ) {
	if ( ! manual_docs_version_diff_enabled() ) {
		return;
	}

	$post_id = (int) $post_id;
	$from    = get_post( $post_id );
	$root    = manual_docs_resolve_compare_root( $compare_token );

	if ( ! $from || ! $root ) {
		echo '<div class="md-diff"><p class="md-empty">' . esc_html__( 'Unable to resolve the comparison target.', 'manual-docs' ) . '</p></div>';
		return;
	}

	$current_root = manual_docs_get_version_root_for_doc( $post_id );
	if ( $current_root && (int) $current_root->ID === (int) $root->ID ) {
		echo '<div class="md-diff"><p class="md-empty">' . esc_html__( 'Choose a different release to compare.', 'manual-docs' ) . '</p></div>';
		return;
	}

	$to = manual_docs_find_version_sibling( $post_id, $root->ID );
	if ( ! $to ) {
		echo '<div class="md-diff"><p class="md-empty">';
		printf(
			/* translators: %s: version name */
			esc_html__( 'No equivalent document was found under %s for this path.', 'manual-docs' ),
			esc_html( get_the_title( $root ) )
		);
		echo '</p>';
		echo '<p><a class="md-btn md-btn--ghost" href="' . esc_url( get_permalink( $post_id ) ) . '">' . esc_html__( 'Back to document', 'manual-docs' ) . '</a></p></div>';
		return;
	}

	if ( function_exists( 'manual_docs_user_can_view_doc' ) ) {
		if ( ! manual_docs_user_can_view_doc( $from ) || ! manual_docs_user_can_view_doc( $to ) ) {
			echo '<div class="md-diff"><p class="md-empty">' . esc_html__( 'You do not have access to compare these documents.', 'manual-docs' ) . '</p></div>';
			return;
		}
	}

	$diff     = manual_docs_diff_compare_posts( $from, $to );
	$back_url = get_permalink( $from );
	$total    = count( $diff['changes'] );
	?>
	<div class="md-diff" data-md-diff>
		<header class="md-diff__header">
			<p class="md-diff__eyebrow"><?php esc_html_e( 'Version compare', 'manual-docs' ); ?></p>
			<h2 class="md-diff__title">
				<?php
				printf(
					/* translators: 1: from version, 2: to version */
					esc_html__( 'Changes between %1$s → %2$s', 'manual-docs' ),
					esc_html( $diff['from']['version'] ),
					esc_html( $diff['to']['version'] )
				);
				?>
			</h2>
			<p class="md-diff__path">
				<code><?php echo esc_html( $diff['path'] ? $diff['path'] : $diff['from']['title'] ); ?></code>
				<span><?php esc_html_e( 'Same document path matched across releases.', 'manual-docs' ); ?></span>
			</p>
			<div class="md-diff__stats" aria-label="<?php esc_attr_e( 'Change counts', 'manual-docs' ); ?>">
				<span class="md-diff__stat md-diff__stat--modified"><?php echo esc_html( (string) (int) $diff['stats']['modified'] ); ?> <?php esc_html_e( 'modified', 'manual-docs' ); ?></span>
				<span class="md-diff__stat md-diff__stat--added"><?php echo esc_html( (string) (int) $diff['stats']['added'] ); ?> <?php esc_html_e( 'added', 'manual-docs' ); ?></span>
				<span class="md-diff__stat md-diff__stat--removed"><?php echo esc_html( (string) (int) $diff['stats']['removed'] ); ?> <?php esc_html_e( 'removed', 'manual-docs' ); ?></span>
			</div>
			<div class="md-diff__legend" aria-label="<?php esc_attr_e( 'How to read this compare', 'manual-docs' ); ?>">
				<p class="md-diff__legend-title"><?php esc_html_e( 'How to read this', 'manual-docs' ); ?></p>
				<ul class="md-diff__legend-list">
					<li><mark class="md-diff-hl md-diff-hl--del"><?php esc_html_e( 'Pink / red', 'manual-docs' ); ?></mark> — <?php
						printf(
							/* translators: %s: source version name */
							esc_html__( 'text in %s that was removed or replaced', 'manual-docs' ),
							esc_html( $diff['from']['version'] )
						);
					?></li>
					<li><mark class="md-diff-hl md-diff-hl--add"><?php esc_html_e( 'Green', 'manual-docs' ); ?></mark> — <?php
						printf(
							/* translators: %s: target version name */
							esc_html__( 'text new in %s', 'manual-docs' ),
							esc_html( $diff['to']['version'] )
						);
					?></li>
					<li><?php esc_html_e( 'Unhighlighted text is unchanged in both releases.', 'manual-docs' ); ?></li>
					<li><?php esc_html_e( 'Click a row to expand and compare that section side by side.', 'manual-docs' ); ?></li>
				</ul>
			</div>
		</header>

		<?php if ( 0 === $total ) : ?>
			<p class="md-diff__empty"><?php esc_html_e( 'No section-level differences were detected. The documents may be identical or only differ in formatting.', 'manual-docs' ); ?></p>
		<?php else : ?>
			<ul class="md-diff__list">
				<?php foreach ( $diff['changes'] as $i => $change ) : ?>
					<?php
					$status_label = array(
						'modified' => __( 'Modified', 'manual-docs' ),
						'added'    => __( 'Added', 'manual-docs' ),
						'removed'  => __( 'Removed', 'manual-docs' ),
					);
					$label = isset( $status_label[ $change['status'] ] ) ? $status_label[ $change['status'] ] : $change['status'];
					$panel_id = 'md-diff-panel-' . $i;
					$from_mark = isset( $change['from_mark'] ) ? $change['from_mark'] : esc_html( $change['from_text'] );
					$to_mark   = isset( $change['to_mark'] ) ? $change['to_mark'] : esc_html( $change['to_text'] );
					?>
					<li class="md-diff__item md-diff__item--<?php echo esc_attr( $change['status'] ); ?>">
						<button type="button" class="md-diff__summary" data-md-diff-toggle aria-expanded="false" aria-controls="<?php echo esc_attr( $panel_id ); ?>">
							<span class="md-diff__badge"><?php echo esc_html( $label ); ?></span>
							<span class="md-diff__item-title"><?php echo esc_html( $change['title'] ); ?></span>
							<span class="md-diff__item-meta"><?php echo esc_html( $change['summary'] ); ?></span>
							<span class="md-diff__chevron" aria-hidden="true"></span>
						</button>
						<div class="md-diff__detail" id="<?php echo esc_attr( $panel_id ); ?>" hidden>
							<p class="md-diff__hint">
								<?php
								printf(
									/* translators: 1: from version, 2: to version */
									esc_html__( 'Left = %1$s (older wording). Right = %2$s (newer wording). Highlighted words are what changed.', 'manual-docs' ),
									esc_html( $diff['from']['version'] ),
									esc_html( $diff['to']['version'] )
								);
								?>
							</p>
							<div class="md-diff__cols">
								<div class="md-diff__col md-diff__col--from">
									<p class="md-diff__col-label"><?php echo esc_html( $diff['from']['version'] ); ?></p>
									<div class="md-diff__col-body">
										<?php if ( $change['from_text'] ) : ?>
											<div class="md-diff__prose"><?php echo $from_mark; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via esc_html + safe marks ?></div>
										<?php else : ?>
											<p class="md-muted"><?php esc_html_e( 'Not present in this release.', 'manual-docs' ); ?></p>
										<?php endif; ?>
									</div>
								</div>
								<div class="md-diff__col md-diff__col--to">
									<p class="md-diff__col-label"><?php echo esc_html( $diff['to']['version'] ); ?></p>
									<div class="md-diff__col-body">
										<?php if ( $change['to_text'] ) : ?>
											<div class="md-diff__prose"><?php echo $to_mark; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via esc_html + safe marks ?></div>
										<?php else : ?>
											<p class="md-muted"><?php esc_html_e( 'Not present in this release.', 'manual-docs' ); ?></p>
										<?php endif; ?>
									</div>
								</div>
							</div>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<div class="md-diff__actions">
			<a class="md-btn md-btn--primary" href="<?php echo esc_url( $diff['to']['url'] ); ?>"><?php
				printf(
					/* translators: %s: version name */
					esc_html__( 'Open %s page', 'manual-docs' ),
					esc_html( $diff['to']['version'] )
				);
			?></a>
			<a class="md-btn md-btn--ghost" href="<?php echo esc_url( $back_url ); ?>"><?php
				printf(
					/* translators: %s: version name */
					esc_html__( 'Back to %s', 'manual-docs' ),
					esc_html( $diff['from']['version'] )
				);
			?></a>
		</div>
	</div>
	<?php
}

/**
 * Enqueue compare JS only when feature is on and viewing docs.
 */
function manual_docs_version_diff_assets() {
	if ( ! manual_docs_version_diff_enabled() ) {
		return;
	}
	if ( ! is_singular( 'manual_documentation' ) ) {
		return;
	}
	wp_enqueue_script(
		'manual-docs-version-diff',
		MANUAL_DOCS_URI . '/assets/js/version-diff.js',
		array(),
		MANUAL_DOCS_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'manual_docs_version_diff_assets', 25 );
