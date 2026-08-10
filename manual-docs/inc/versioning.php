<?php
/**
 * Version switching via parent documentation pages.
 *
 * Top-level docs (or configured roots) like goat / flamingo / hummingbird
 * are release versions. Matching docs under each share the same relative
 * path or title within that version tree.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get configured version root posts.
 *
 * Only posts listed in Version root IDs / Version root slugs are treated as
 * releases. Other top-level docs (e.g. standalone parents) are excluded.
 *
 * @return WP_Post[]
 */
function manual_docs_get_version_roots() {
	$options = manual_docs_get_options();
	$ids     = array();

	if ( ! empty( $options['version_root_ids'] ) ) {
		$ids = array_filter( array_map( 'absint', explode( ',', $options['version_root_ids'] ) ) );
	}

	if ( empty( $ids ) && ! empty( $options['version_root_slugs'] ) ) {
		$slugs = array_filter( array_map( 'sanitize_title', array_map( 'trim', explode( ',', $options['version_root_slugs'] ) ) ) );
		foreach ( $slugs as $slug ) {
			$found = manual_docs_find_top_level_doc_by_slug( $slug );
			if ( $found ) {
				$ids[] = (int) $found->ID;
			}
		}
	}

	$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );

	// No configured roots → no version switcher (do not auto-promote every top-level doc).
	if ( empty( $ids ) ) {
		return array();
	}

	$posts = get_posts(
		array(
			'post_type'      => 'manual_documentation',
			'post__in'       => $ids,
			'posts_per_page' => count( $ids ),
			'orderby'        => 'post__in',
			'post_status'    => 'publish',
			'post_parent'    => 0,
		)
	);

	return $posts;
}

/**
 * Default URL to open documentation (first / default version root).
 *
 * @return string
 */
function manual_docs_get_docs_entry_url() {
	$versions     = manual_docs_get_version_roots();
	$default_slug = manual_docs_get_option( 'default_version_slug', '' );

	if ( ! empty( $versions ) ) {
		$start = $versions[0];
		if ( $default_slug ) {
			foreach ( $versions as $v ) {
				if ( $v->post_name === $default_slug || 0 === strpos( $v->post_name, sanitize_title( $default_slug ) . '-' ) ) {
					$start = $v;
					break;
				}
			}
		}
		$url = get_permalink( $start );
		if ( $url ) {
			return $url;
		}
	}

	if ( ! manual_docs_get_option( 'hide_docs_archive', true ) ) {
		$archive = get_post_type_archive_link( 'manual_documentation' );
		if ( $archive ) {
			return $archive;
		}
	}

	return home_url( '/' );
}

/**
 * Find a top-level documentation post by slug (exact, then slug-N import suffixes).
 *
 * @param string $slug Desired slug.
 * @return WP_Post|null
 */
function manual_docs_find_top_level_doc_by_slug( $slug ) {
	$slug = sanitize_title( $slug );
	if ( ! $slug ) {
		return null;
	}

	$exact = get_posts(
		array(
			'name'           => $slug,
			'post_type'      => 'manual_documentation',
			'post_parent'    => 0,
			'posts_per_page' => 1,
			'post_status'    => 'publish',
		)
	);
	if ( $exact ) {
		return $exact[0];
	}

	// WordPress importer may create goat-2 / flamingo-2 when re-importing.
	$candidates = get_posts(
		array(
			'post_type'      => 'manual_documentation',
			'post_parent'    => 0,
			'posts_per_page' => 50,
			'post_status'    => 'publish',
			'orderby'        => 'ID',
			'order'          => 'DESC',
		)
	);

	foreach ( $candidates as $post ) {
		$name = $post->post_name;
		if ( $name === $slug || 0 === strpos( $name, $slug . '-' ) ) {
			return $post;
		}
		if ( strtolower( $post->post_title ) === strtolower( $slug ) ) {
			return $post;
		}
	}

	return null;
}

/**
 * Find the version root ancestor for a document.
 *
 * @param int $post_id Post ID.
 * @return WP_Post|null
 */
function manual_docs_get_version_root_for_doc( $post_id ) {
	$post_id = (int) $post_id;
	$roots   = manual_docs_get_version_roots();
	$root_ids = wp_list_pluck( $roots, 'ID' );
	$root_ids = array_map( 'intval', $root_ids );

	if ( in_array( $post_id, $root_ids, true ) ) {
		return get_post( $post_id );
	}

	$ancestors = get_post_ancestors( $post_id );
	foreach ( $ancestors as $ancestor_id ) {
		if ( in_array( (int) $ancestor_id, $root_ids, true ) ) {
			return get_post( $ancestor_id );
		}
	}

	// Fallback: top-most ancestor or self if top-level.
	if ( ! empty( $ancestors ) ) {
		return get_post( end( $ancestors ) );
	}

	$post = get_post( $post_id );
	return ( $post && 0 === (int) $post->post_parent ) ? $post : null;
}

/**
 * Relative slug path from version root to document (excluding root).
 *
 * @param int $post_id Post ID.
 * @param int $root_id Root ID.
 * @return string[]
 */
function manual_docs_get_relative_slug_path( $post_id, $root_id ) {
	$path = array();
	$current = (int) $post_id;
	$root_id = (int) $root_id;
	$guard   = 0;

	while ( $current && $current !== $root_id && $guard < 40 ) {
		$post = get_post( $current );
		if ( ! $post ) {
			break;
		}
		array_unshift( $path, $post->post_name );
		$current = (int) $post->post_parent;
		$guard++;
	}

	return $path;
}

/**
 * Resolve a document under a version root by relative slug path.
 *
 * @param int      $root_id Root ID.
 * @param string[] $path    Relative slugs.
 * @return WP_Post|null
 */
function manual_docs_resolve_path_under_root( $root_id, $path ) {
	$parent = (int) $root_id;
	$post   = get_post( $parent );

	if ( empty( $path ) ) {
		return $post;
	}

	foreach ( $path as $slug ) {
		$found = get_posts( array(
			'name'           => $slug,
			'post_type'      => 'manual_documentation',
			'post_parent'    => $parent,
			'posts_per_page' => 1,
			'post_status'    => 'publish',
		) );
		if ( empty( $found ) ) {
			return null;
		}
		$post   = $found[0];
		$parent = (int) $post->ID;
	}

	return $post;
}

/**
 * Find matching document in another version by path, then title.
 *
 * @param int $post_id          Current post.
 * @param int $target_root_id   Target version root.
 * @return WP_Post|null
 */
function manual_docs_find_version_sibling( $post_id, $target_root_id ) {
	$post_id        = (int) $post_id;
	$target_root_id = (int) $target_root_id;
	$current_root   = manual_docs_get_version_root_for_doc( $post_id );

	if ( ! $current_root ) {
		return null;
	}

	if ( (int) $current_root->ID === $target_root_id ) {
		return get_post( $post_id );
	}

	// 1) Same relative slug path under target root.
	$path = manual_docs_get_relative_slug_path( $post_id, $current_root->ID );
	$hit  = manual_docs_resolve_path_under_root( $target_root_id, $path );
	if ( $hit ) {
		return $hit;
	}

	// 2) Same post_name directly under the mirrored parent path prefix.
	// 3) Same title under target version root.
	$title = get_the_title( $post_id );
	$slug  = get_post_field( 'post_name', $post_id );

	global $wpdb;
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT ID, post_parent, post_name FROM {$wpdb->posts}
			WHERE post_type = 'manual_documentation'
			AND post_status = 'publish'
			AND (post_title = %s OR post_name = %s)
			LIMIT 40",
			$title,
			$slug
		)
	);

	if ( empty( $rows ) ) {
		return null;
	}

	foreach ( $rows as $row ) {
		$cand_root = manual_docs_get_version_root_for_doc( (int) $row->ID );
		if ( $cand_root && (int) $cand_root->ID === $target_root_id ) {
			return get_post( (int) $row->ID );
		}
	}

	return null;
}

/**
 * Get all descendant post IDs under a parent (BFS, capped).
 *
 * @param int $parent_id Parent.
 * @param int $limit     Safety cap.
 * @return int[]
 */
function manual_docs_get_descendant_ids( $parent_id, $limit = 5000 ) {
	$all   = array();
	$queue = array( (int) $parent_id );
	$seen  = array();

	while ( $queue && count( $all ) < $limit ) {
		$pid = array_shift( $queue );
		if ( isset( $seen[ $pid ] ) ) {
			continue;
		}
		$seen[ $pid ] = true;

		$children = get_posts( array(
			'post_type'      => 'manual_documentation',
			'post_parent'    => $pid,
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'post_status'    => 'publish',
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
		) );

		foreach ( $children as $cid ) {
			$cid = (int) $cid;
			$all[] = $cid;
			$queue[] = $cid;
		}
	}

	return $all;
}

/**
 * Current version label for a document.
 *
 * @param int|null $post_id Post ID.
 * @return array{id:int,name:string,slug:string}|null
 */
function manual_docs_get_doc_version( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$root    = manual_docs_get_version_root_for_doc( $post_id );
	if ( ! $root ) {
		return null;
	}
	return array(
		'id'   => (int) $root->ID,
		'name' => get_the_title( $root ),
		'slug' => $root->post_name,
	);
}

/**
 * Compatibility shim — old code expected WP_Term; return object-like array via stdClass.
 *
 * @param int|null $post_id Post ID.
 * @return object|null
 */
function manual_docs_get_doc_version_object( $post_id = null ) {
	$data = manual_docs_get_doc_version( $post_id );
	if ( ! $data ) {
		return null;
	}
	return (object) $data;
}

/**
 * Render release version switcher (parent-page based).
 *
 * @param int|null $post_id Post ID.
 */
function manual_docs_render_version_switcher( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$roots   = manual_docs_get_version_roots();
	if ( count( $roots ) < 2 ) {
		return;
	}

	$current = manual_docs_get_doc_version( $post_id );
	$current_id = $current ? (int) $current['id'] : 0;
	$label = manual_docs_get_option( 'version_label', __( 'Release version', 'manual-docs' ) );
	?>
	<div class="md-version-tools">
		<div class="md-version-switcher" data-current="<?php echo esc_attr( $current ? $current['slug'] : '' ); ?>">
			<label for="md-version-select" class="md-version-label"><?php echo esc_html( $label ); ?></label>
			<select id="md-version-select" class="md-version-select" data-post-id="<?php echo esc_attr( (string) $post_id ); ?>">
				<?php foreach ( $roots as $root ) : ?>
					<?php
					$sibling = manual_docs_find_version_sibling( $post_id, $root->ID );
					if ( function_exists( 'manual_docs_prefer_product_version_sibling' ) ) {
						$sibling = manual_docs_prefer_product_version_sibling( $sibling, $post_id, (int) $root->ID );
					}
					// Always allow switching — fall back to the version root when no twin page.
					$target  = $sibling ? $sibling : $root;
					$url     = get_permalink( $target );
					$sib_id  = (int) $target->ID;
					?>
					<option
						value="<?php echo esc_url( $url ); ?>"
						data-md-doc-id="<?php echo esc_attr( (string) $sib_id ); ?>"
						data-version-root="<?php echo esc_attr( (string) $root->ID ); ?>"
						<?php selected( $current_id, (int) $root->ID ); ?>
					>
						<?php echo esc_html( get_the_title( $root ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
		if ( function_exists( 'manual_docs_render_compare_control' ) ) {
			manual_docs_render_compare_control( $post_id );
		}
		?>
	</div>
	<?php
}