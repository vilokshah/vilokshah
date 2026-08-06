<?php
/**
 * Sidebar fallback (docs).
 *
 * @package ManualDocs
 */

if ( ! is_active_sidebar( 'docs-sidebar' ) ) {
	return;
}
?>
<aside class="widget-area" role="complementary">
	<?php dynamic_sidebar( 'docs-sidebar' ); ?>
</aside>