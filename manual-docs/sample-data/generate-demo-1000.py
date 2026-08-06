#!/usr/bin/env python3
"""Generate a large WXR import (~1002 docs) for Manual Docs version-switch testing."""

from pathlib import Path
from xml.sax.saxutils import escape

OUT = Path(__file__).with_name('manual-docs-demo-1000.xml')

VERSIONS = [
	('goat', 'Goat'),
	('flamingo', 'Flamingo'),
	('hummingbird', 'Hummingbird'),
]

# 1 root + 9 chapters + 36 sections + 288 topics = 334 per version × 3 = 1002
CHAPTERS = [
	('getting-started', 'Getting Started', 'getting-started'),
	('platform', 'Platform', 'platform'),
	('adapters', 'Adapters', 'adapter'),
	('aiops', 'AIOps', 'aiops'),
	('observability', 'Observability', 'platform'),
	('security', 'Security', 'platform'),
	('automation', 'Automation', 'platform'),
	('integrations', 'Integrations', 'adapter'),
	('releases', 'Release Notes', 'releases'),
]

SECTIONS = [
	('overview', 'Overview'),
	('setup', 'Setup'),
	('operations', 'Operations'),
	('troubleshooting', 'Troubleshooting'),
]

TOPICS = [
	('introduction', 'Introduction'),
	('prerequisites', 'Prerequisites'),
	('configuration', 'Configuration'),
	('workflows', 'Workflows'),
	('best-practices', 'Best Practices'),
	('reference', 'Reference'),
	('faq', 'FAQ'),
	('examples', 'Examples'),
]

CATEGORIES = [
	(19406, 'getting-started', 'Getting Started'),
	(42619, 'platform', 'Platform'),
	(92798, 'releases', 'Releases'),
	(97623, 'adapter', 'Adapter'),
	(85870, 'aiops', 'AIOps'),
]

CAT_NAME = {c[1]: c[2] for c in CATEGORIES}
DATE = '2026-08-06 14:30:00'


def content_html(title, version_slug, version_label, path, depth):
	return f'''<!-- wp:heading -->
<h2 class="wp-block-heading">Overview</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This is large-demo content for <strong>{escape(title)}</strong> under the <strong>{escape(version_label)}</strong> release (<code>{escape(version_slug)}</code>). Path: <code>{escape(path)}</code>. Depth: {depth}.</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Key points</h2>
<!-- /wp:heading -->
<!-- wp:list -->
<ul><!-- wp:list-item --><li>Same title and relative path across goat / flamingo / hummingbird</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Use Release version switcher to jump between matching pages</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Body text differs per release so you can confirm the switch landed on {escape(version_slug)}</li><!-- /wp:list-item --></ul>
<!-- /wp:list -->
<!-- wp:heading -->
<h3 class="wp-block-heading">Release-specific notes ({escape(version_slug)})</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Unique marker for version switch testing: <strong>MD-DEMO-{escape(version_slug.upper())}-{escape(path.replace('/', '-'))}</strong>.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Additional filler for {escape(version_label)}: review configuration, validate health checks, and confirm sibling documents in the left tree remain aligned after switching versions.</p>
<!-- /wp:paragraph -->'''


def emit_item(lines, post_id, title, slug, parent_id, menu_order, cat_slug, depth, rel_path, v_slug, v_label):
	body = content_html(title, v_slug, v_label, rel_path, depth)
	cat_name = CAT_NAME.get(cat_slug, 'Platform')
	lines.append(
		f'''	<item>
		<title><![CDATA[{title}]]></title>
		<link>https://example.com/documentation/{rel_path}/</link>
		<pubDate>Thu, 06 Aug 2026 14:00:00 +0000</pubDate>
		<dc:creator><![CDATA[admin]]></dc:creator>
		<guid isPermaLink="false">https://example.com/?post_type=manual_documentation&amp;p={post_id}</guid>
		<description></description>
		<content:encoded><![CDATA[{body}]]></content:encoded>
		<excerpt:encoded><![CDATA[Demo page for {title} ({v_slug}).]]></excerpt:encoded>
		<wp:post_id>{post_id}</wp:post_id>
		<wp:post_date><![CDATA[{DATE}]]></wp:post_date>
		<wp:post_date_gmt><![CDATA[{DATE}]]></wp:post_date_gmt>
		<wp:post_modified><![CDATA[{DATE}]]></wp:post_modified>
		<wp:post_modified_gmt><![CDATA[{DATE}]]></wp:post_modified_gmt>
		<wp:comment_status><![CDATA[closed]]></wp:comment_status>
		<wp:ping_status><![CDATA[closed]]></wp:ping_status>
		<wp:post_name><![CDATA[{slug}]]></wp:post_name>
		<wp:status><![CDATA[publish]]></wp:status>
		<wp:post_parent>{parent_id}</wp:post_parent>
		<wp:menu_order>{menu_order}</wp:menu_order>
		<wp:post_type><![CDATA[manual_documentation]]></wp:post_type>
		<wp:post_password><![CDATA[]]></wp:post_password>
		<wp:is_sticky>0</wp:is_sticky>
		<category domain="manualdocumentationcategory" nicename="{cat_slug}"><![CDATA[{cat_name}]]></category>
	</item>'''
	)


def main():
	lines = [
		'<?xml version="1.0" encoding="UTF-8" ?><!-- Generator: Manual Docs large demo (~1000 docs) -->',
		'<!-- Tools → Import → WordPress. Version root slugs: goat,flamingo,hummingbird -->',
		'''<rss version="2.0"
	xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:wp="http://wordpress.org/export/1.2/">
<channel>
	<title>Manual Docs Large Demo</title>
	<link>https://example.com</link>
	<description>Large demo (~1002 docs) across goat / flamingo / hummingbird for version switch testing</description>
	<pubDate>Thu, 06 Aug 2026 14:00:00 +0000</pubDate>
	<language>en-US</language>
	<wp:wxr_version>1.2</wp:wxr_version>
	<wp:base_site_url>https://example.com</wp:base_site_url>
	<wp:base_blog_url>https://example.com</wp:base_blog_url>
	<wp:author><wp:author_id>1</wp:author_id><wp:author_login><![CDATA[admin]]></wp:author_login><wp:author_email><![CDATA[admin@example.com]]></wp:author_email><wp:author_display_name><![CDATA[admin]]></wp:author_display_name><wp:author_first_name><![CDATA[]]></wp:author_first_name><wp:author_last_name><![CDATA[]]></wp:author_last_name></wp:author>''',
	]

	for tid, slug, name in CATEGORIES:
		lines.append(
			f'''	<wp:category>
		<wp:term_id>{tid}</wp:term_id>
		<wp:category_nicename>{slug}</wp:category_nicename>
		<wp:category_parent></wp:category_parent>
		<wp:cat_name><![CDATA[{name}]]></wp:cat_name>
		<wp:taxonomy>manualdocumentationcategory</wp:taxonomy>
	</wp:category>'''
		)

	for tid, slug, name in CATEGORIES:
		lines.append(
			f'''	<wp:term>
		<wp:term_id>{tid}</wp:term_id>
		<wp:term_taxonomy>manualdocumentationcategory</wp:term_taxonomy>
		<wp:term_slug>{slug}</wp:term_slug>
		<wp:term_parent></wp:term_parent>
		<wp:term_name><![CDATA[{name}]]></wp:term_name>
	</wp:term>'''
		)

	post_count = 0

	for v_idx, (v_slug, v_label) in enumerate(VERSIONS):
		base_id = 100000 + (v_idx + 1) * 10000
		local = [0]

		def nid():
			local[0] += 1
			return base_id + local[0]

		# Track with outer counter after each emit
		root_id = nid()
		post_count += 1
		emit_item(lines, root_id, v_label, v_slug, 0, v_idx, 'releases', 0, v_slug, v_slug, v_label)

		for c_i, (c_slug, c_title, c_cat) in enumerate(CHAPTERS):
			chapter_id = nid()
			post_count += 1
			chapter_path = f'{v_slug}/{c_slug}'
			emit_item(lines, chapter_id, c_title, c_slug, root_id, c_i, c_cat, 1, chapter_path, v_slug, v_label)

			for s_i, (s_slug, s_title) in enumerate(SECTIONS):
				section_id = nid()
				post_count += 1
				sec_slug = f'{c_slug}-{s_slug}'
				section_path = f'{chapter_path}/{sec_slug}'
				emit_item(
					lines,
					section_id,
					f'{c_title}: {s_title}',
					sec_slug,
					chapter_id,
					s_i,
					c_cat,
					2,
					section_path,
					v_slug,
					v_label,
				)

				for t_i, (t_slug, t_title) in enumerate(TOPICS):
					topic_id = nid()
					post_count += 1
					topic_slug = f'{sec_slug}-{t_slug}'
					topic_path = f'{section_path}/{topic_slug}'
					emit_item(
						lines,
						topic_id,
						f'{c_title} — {s_title}: {t_title}',
						topic_slug,
						section_id,
						t_i,
						c_cat,
						3,
						topic_path,
						v_slug,
						v_label,
					)

	lines.append('</channel>\n</rss>')
	OUT.write_text('\n'.join(lines), encoding='utf-8')
	size = OUT.stat().st_size
	print(f'Wrote {OUT}')
	print(f'Posts: {post_count}')
	print(f'Size: {size:,} bytes ({size / 1024 / 1024:.2f} MB)')
	print(f'Per version: {post_count // 3}')


if __name__ == '__main__':
	main()
