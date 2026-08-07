#!/usr/bin/env python3
"""Generate bbPress sample WXR: 5 forums, 30 topics, 40 replies.

Requires bbPress to be active before import. After import, run
Tools → Forums → Repair (parent forum/topic + counts).

Writes: bbpress-demo.xml
"""

from datetime import datetime, timedelta
from pathlib import Path
from xml.sax.saxutils import escape

DIR = Path(__file__).resolve().parent
OUT = DIR / "bbpress-demo.xml"

FORUMS = [
	("general-discussion", "General Discussion", "Announcements, introductions, and community chat."),
	("getting-started", "Getting Started", "Onboarding questions, first installs, and setup tips."),
	("platform-features", "Platform & Features", "How the product works day to day."),
	("releases-upgrades", "Releases & Upgrades", "Release notes discussion and upgrade help."),
	("academy-support", "Academy Support", "Course labs, access, and Academy learning questions."),
]

# 6 topic titles per forum (30 total)
TOPIC_TITLES = [
	[
		"Welcome — introduce yourself",
		"Where should new questions go?",
		"Forum guidelines reminder",
		"Share a recent win",
		"What are you working on this week?",
		"Off-topic but useful links",
	],
	[
		"First-time install checklist",
		"Login / SSO confusion",
		"Where is the docs portal?",
		"Permissions for editors vs readers",
		"Importing sample documentation",
		"Local vs staging setup",
	],
	[
		"Search not returning expected pages",
		"Version switcher behaviour",
		"PDF download tips",
		"TOC collapse / expand UX",
		"Dark vs light theme preferences",
		"Keyboard shortcuts wish list",
	],
	[
		"Goat → Flamingo upgrade notes",
		"What changed in Hummingbird?",
		"Breaking changes checklist",
		"Rollback strategy discussion",
		"Release cadence feedback",
		"Deprecation notices — how loud?",
	],
	[
		"User Activation",
		"Request for Lab Access Extension",
		"Course enrollment not showing",
		"Lab environment reset help",
		"Certificate download issue",
		"Where to find Course ID",
	],
]

ACADEMY_META = [
	("77534", "ignio AIOps Intermediate E2"),
	("77534", "ignio AIOps Intermediate E2"),
	("88102", "Platform Fundamentals Lab"),
	("88102", "Platform Fundamentals Lab"),
	("90211", "Automation Practitioner"),
	("10001", "Academy Orientation"),
]

# Reply counts per topic (6 per forum) — totals 40
REPLY_COUNTS = [
	[3, 2, 2, 1, 1, 0],  # 9
	[3, 2, 2, 1, 1, 0],  # 9
	[2, 2, 2, 1, 1, 0],  # 8
	[2, 2, 1, 1, 1, 0],  # 7
	[2, 2, 1, 1, 1, 0],  # 7
]

BASE = datetime(2026, 8, 1, 10, 0, 0)
# Stable IDs in a high range so they rarely collide with existing content
FORUM_ID0 = 81001
TOPIC_ID0 = 81101
REPLY_ID0 = 81201


def meta(key: str, value) -> str:
	return (
		"\t\t<wp:postmeta>\n"
		f"\t\t\t<wp:meta_key><![CDATA[{key}]]></wp:meta_key>\n"
		f"\t\t\t<wp:meta_value><![CDATA[{value}]]></wp:meta_value>\n"
		"\t\t</wp:postmeta>\n"
	)


def item(
	*,
	post_id: int,
	title: str,
	slug: str,
	post_type: str,
	content: str,
	excerpt: str,
	parent: int,
	date: datetime,
	menu_order: int = 0,
	metas: list,
) -> str:
	ds = date.strftime("%Y-%m-%d %H:%M:%S")
	rfc = date.strftime("%a, %d %b %Y %H:%M:%S +0000")
	parts = [
		"\t<item>\n",
		f"\t\t<title><![CDATA[{title}]]></title>\n",
		f"\t\t<link>https://example.com/?p={post_id}</link>\n",
		f"\t\t<pubDate>{rfc}</pubDate>\n",
		"\t\t<dc:creator><![CDATA[admin]]></dc:creator>\n",
		f'\t\t<guid isPermaLink="false">https://example.com/?post_type={post_type}&amp;p={post_id}</guid>\n',
		"\t\t<description></description>\n",
		f"\t\t<content:encoded><![CDATA[{content}]]></content:encoded>\n",
		f"\t\t<excerpt:encoded><![CDATA[{excerpt}]]></excerpt:encoded>\n",
		f"\t\t<wp:post_id>{post_id}</wp:post_id>\n",
		f"\t\t<wp:post_date><![CDATA[{ds}]]></wp:post_date>\n",
		f"\t\t<wp:post_date_gmt><![CDATA[{ds}]]></wp:post_date_gmt>\n",
		"\t\t<wp:comment_status><![CDATA[closed]]></wp:comment_status>\n",
		"\t\t<wp:ping_status><![CDATA[closed]]></wp:ping_status>\n",
		f"\t\t<wp:post_name><![CDATA[{slug}]]></wp:post_name>\n",
		"\t\t<wp:status><![CDATA[publish]]></wp:status>\n",
		f"\t\t<wp:post_parent>{parent}</wp:post_parent>\n",
		f"\t\t<wp:menu_order>{menu_order}</wp:menu_order>\n",
		f"\t\t<wp:post_type><![CDATA[{post_type}]]></wp:post_type>\n",
		"\t\t<wp:post_password><![CDATA[]]></wp:post_password>\n",
		"\t\t<wp:is_sticky>0</wp:is_sticky>\n",
	]
	parts.extend(metas)
	parts.append("\t</item>\n")
	return "".join(parts)


def topic_body(title: str, forum_label: str) -> str:
	return (
		f"<p>Sample topic in <strong>{escape(forum_label)}</strong>.</p>"
		f"<p><strong>{escape(title)}</strong> — use this thread to try the Manual Docs + bbPress look "
		"in light and dark themes.</p>"
		"<p>Steps we usually try:</p>"
		"<ol><li>Search docs first</li><li>Post a clear question with environment details</li>"
		"<li>Link related documentation pages</li></ol>"
	)


def reply_body(n: int, topic_title: str) -> str:
	snippets = [
		f"<p>Thanks for starting this — for <em>{escape(topic_title)}</em> I would start with the docs portal search.</p>",
		"<p>We hit something similar on staging. Clearing permalinks and re-saving Forums settings helped.</p>",
		"<p>+1. Also check whether bbPress is active and that Manual Docs is the current theme.</p>",
		"<p>Here is a short checklist: confirm version roots, flush rewrite rules, then retest.</p>",
		"<p>If counts look wrong after import, run <strong>Tools → Forums → Repair</strong>.</p>",
	]
	return snippets[(n - 1) % len(snippets)]


def main() -> None:
	assert sum(sum(row) for row in REPLY_COUNTS) == 40
	assert all(len(row) == 6 for row in TOPIC_TITLES)
	assert all(len(row) == 6 for row in REPLY_COUNTS)

	# Build structure first so forum/topic metas can include accurate counts
	forums = []
	topics = []
	replies = []

	for fi, (slug, label, desc) in enumerate(FORUMS):
		forum_id = FORUM_ID0 + fi
		forum_topics = []
		for ti, title in enumerate(TOPIC_TITLES[fi]):
			topic_id = TOPIC_ID0 + len(topics)
			topic_date = BASE + timedelta(days=fi, hours=ti + 1)
			reply_n = REPLY_COUNTS[fi][ti]
			topic_replies = []
			for ri in range(reply_n):
				reply_id = REPLY_ID0 + len(replies) + len(topic_replies)
				# placeholder; real id assigned when flushing replies list
				topic_replies.append(
					{
						"temp": True,
						"n": ri + 1,
						"topic_id": topic_id,
						"forum_id": forum_id,
						"date": topic_date + timedelta(hours=ri + 1),
						"topic_title": title,
					}
				)
			# assign real reply ids sequentially into global replies
			for tr in topic_replies:
				rid = REPLY_ID0 + len(replies)
				replies.append(
					{
						"id": rid,
						"n": tr["n"],
						"topic_id": topic_id,
						"forum_id": forum_id,
						"date": tr["date"],
						"topic_title": title,
						"slug": f"reply-to-{slug}-t{ti + 1}-r{tr['n']}",
					}
				)
			last_reply_id = replies[-1]["id"] if reply_n else 0
			last_active_id = last_reply_id if reply_n else topic_id
			last_active = (replies[-1]["date"] if reply_n else topic_date).strftime("%Y-%m-%d %H:%M:%S")
			topics.append(
				{
					"id": topic_id,
					"forum_id": forum_id,
					"forum_label": label,
					"forum_slug": slug,
					"title": title,
					"slug": f"{slug}-{ti + 1}-{title.lower().replace(' ', '-')[:48].replace('/', '-')}",
					"date": topic_date,
					"reply_count": reply_n,
					"last_reply_id": last_reply_id,
					"last_active_id": last_active_id,
					"last_active": last_active,
					"menu_order": ti,
				}
			)
			forum_topics.append(topics[-1])

		last_topic = forum_topics[-1]
		# Prefer most recently active topic/reply in this forum
		active = max(forum_topics, key=lambda t: t["last_active"])
		forums.append(
			{
				"id": forum_id,
				"slug": slug,
				"label": label,
				"desc": desc,
				"date": BASE + timedelta(hours=fi),
				"menu_order": fi,
				"topic_count": len(forum_topics),
				"reply_count": sum(t["reply_count"] for t in forum_topics),
				"last_topic_id": last_topic["id"],
				"last_reply_id": active["last_reply_id"],
				"last_active_id": active["last_active_id"],
				"last_active": active["last_active"],
			}
		)

	assert len(forums) == 5
	assert len(topics) == 30
	assert len(replies) == 40

	out = []
	out.append('<?xml version="1.0" encoding="UTF-8" ?><!-- Generator: Manual Docs bbPress sample -->\n')
	out.append(
		"<!-- Activate bbPress first. Tools → Import → WordPress → upload this file.\n"
		"     Assign author to an admin. Then Tools → Forums → Repair:\n"
		"     parent topic, parent forum, topic/reply/voice counts, sticky, last activity.\n"
		"     Then Settings → Permalinks → Save. -->\n"
	)
	out.append(
		'<rss version="2.0"\n'
		'\txmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"\n'
		'\txmlns:content="http://purl.org/rss/1.0/modules/content/"\n'
		'\txmlns:wfw="http://wellformedweb.org/CommentAPI/"\n'
		'\txmlns:dc="http://purl.org/dc/elements/1.1/"\n'
		'\txmlns:wp="http://wordpress.org/export/1.2/">\n'
		"<channel>\n"
		"\t<title>Manual Docs — bbPress Demo</title>\n"
		"\t<link>https://example.com</link>\n"
		"\t<description>5 forums, 30 topics, 40 replies for Manual Docs + bbPress styling</description>\n"
		"\t<pubDate>Fri, 07 Aug 2026 14:00:00 +0000</pubDate>\n"
		"\t<language>en-US</language>\n"
		"\t<wp:wxr_version>1.2</wp:wxr_version>\n"
		"\t<wp:base_site_url>https://example.com</wp:base_site_url>\n"
		"\t<wp:base_blog_url>https://example.com</wp:base_blog_url>\n"
		"\t<wp:author><wp:author_id>1</wp:author_id>"
		"<wp:author_login><![CDATA[admin]]></wp:author_login>"
		"<wp:author_email><![CDATA[admin@example.com]]></wp:author_email>"
		"<wp:author_display_name><![CDATA[admin]]></wp:author_display_name>"
		"<wp:author_first_name><![CDATA[]]></wp:author_first_name>"
		"<wp:author_last_name><![CDATA[]]></wp:author_last_name></wp:author>\n"
	)

	for f in forums:
		metas = [
			meta("_bbp_forum_type", "forum"),
			meta("_bbp_status", "open"),
			meta("_bbp_forum_id", f["id"]),
			meta("_bbp_topic_count", f["topic_count"]),
			meta("_bbp_reply_count", f["reply_count"]),
			meta("_bbp_total_topic_count", f["topic_count"]),
			meta("_bbp_total_reply_count", f["reply_count"]),
			meta("_bbp_voice_count", max(1, f["topic_count"] // 2)),
			meta("_bbp_last_topic_id", f["last_topic_id"]),
			meta("_bbp_last_reply_id", f["last_reply_id"]),
			meta("_bbp_last_active_id", f["last_active_id"]),
			meta("_bbp_last_active_time", f["last_active"]),
		]
		# Sticky the first topic in General Discussion
		if f["slug"] == "general-discussion":
			metas.append(meta("_bbp_sticky_topics", str(TOPIC_ID0)))
		out.append(
			item(
				post_id=f["id"],
				title=f["label"],
				slug=f["slug"],
				post_type="forum",
				content=f"<p>{escape(f['desc'])}</p>",
				excerpt=f["desc"],
				parent=0,
				date=f["date"],
				menu_order=f["menu_order"],
				metas=metas,
			)
		)

	for t in topics:
		voice = 1 + (1 if t["reply_count"] else 0)
		metas = [
			meta("_bbp_forum_id", t["forum_id"]),
			meta("_bbp_topic_id", t["id"]),
			meta("_bbp_author_ip", "127.0.0.1"),
			meta("_bbp_voice_count", voice),
			meta("_bbp_reply_count", t["reply_count"]),
			meta("_bbp_reply_count_hidden", 0),
			meta("_bbp_last_reply_id", t["last_reply_id"]),
			meta("_bbp_last_active_id", t["last_active_id"]),
			meta("_bbp_last_active_time", t["last_active"]),
			meta("_bbp_status", "open"),
		]
		if t.get("forum_slug") == "academy-support":
			# menu_order aligns with ACADEMY_META index within the forum
			ai = t.get("menu_order", 0)
			if 0 <= ai < len(ACADEMY_META):
				metas.append(meta("_md_academy_course_id", ACADEMY_META[ai][0]))
				metas.append(meta("_md_academy_course_name", ACADEMY_META[ai][1]))
		out.append(
			item(
				post_id=t["id"],
				title=t["title"],
				slug=t["slug"][:200],
				post_type="topic",
				content=topic_body(t["title"], t["forum_label"]),
				excerpt=t["title"],
				parent=t["forum_id"],
				date=t["date"],
				menu_order=t["menu_order"],
				metas=metas,
			)
		)

	for r in replies:
		metas = [
			meta("_bbp_forum_id", r["forum_id"]),
			meta("_bbp_topic_id", r["topic_id"]),
			meta("_bbp_reply_to", 0),
			meta("_bbp_author_ip", "127.0.0.1"),
		]
		out.append(
			item(
				post_id=r["id"],
				title=f"Reply to: {r['topic_title']}",
				slug=r["slug"],
				post_type="reply",
				content=reply_body(r["n"], r["topic_title"]),
				excerpt="",
				parent=r["topic_id"],
				date=r["date"],
				metas=metas,
			)
		)

	out.append("</channel>\n</rss>\n")
	OUT.write_text("".join(out), encoding="utf-8")
	print(f"Wrote {OUT.name}: {len(forums)} forums, {len(topics)} topics, {len(replies)} replies")


if __name__ == "__main__":
	main()
