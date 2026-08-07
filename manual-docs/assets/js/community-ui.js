/**
 * Community UI helpers: strip leftover Archives/Categories cards,
 * hide meaningless “Viewing 0 posts” labels, and remove orphan vote scores.
 */
(function () {
  'use strict';

  function cleanSidebar() {
    var sidebar = document.querySelector('.md-community-sidebar');
    if (!sidebar) return;
    var widgets = sidebar.querySelectorAll('.widget, section.widget, .widget_block');
    widgets.forEach(function (el) {
      if (el.classList.contains('md-recent-topics')) return;
      var titleEl = el.querySelector('.widget-title, h2, h3');
      var title = titleEl ? String(titleEl.textContent || '').trim().toLowerCase() : '';
      var cls = String(el.className || '').toLowerCase();
      var isArchive = cls.indexOf('archive') !== -1 || title === 'archives' || title === 'archive';
      var isCats = cls.indexOf('categories') !== -1 || cls.indexOf('category') !== -1 || title === 'categories' || title === 'category';
      if (isArchive || isCats) {
        el.remove();
      }
    });
  }

  function hideZeroPosts() {
    var nodes = document.querySelectorAll('.bbp-pagination-count, .bbp-topic-pagination, .bbp-pagination');
    nodes.forEach(function (el) {
      var text = String(el.textContent || '').replace(/\s+/g, ' ').trim();
      if (/\b0\s+(?:posts?|replies|reply)\b/i.test(text) && !/\b[1-9]\d*\s+topics?\b/i.test(text)) {
        var wrap = el.closest('.bbp-pagination, li.bbp-footer, .md-reply-list__footer') || el;
        wrap.style.display = 'none';
      }
    });
    document.querySelectorAll('.md-bbpress p, #bbpress-forums p').forEach(function (el) {
      var text = String(el.textContent || '').replace(/\s+/g, ' ').trim();
      if (/^viewing\s+0\s+(?:posts?|replies|reply)\.?$/i.test(text)) {
        el.style.display = 'none';
      }
    });
  }

  function removeOrphanScores() {
    var root = document.querySelector('.md-bbpress') || document.getElementById('bbpress-forums');
    if (!root) return;

    root.querySelectorAll(
      '.gdwpro-voting, .gd-rating, .gdrts-rating-block, .gdrts-rating-fonticon, .wp-ulike-is-single, .wpulike, .wp_ulike_general_class, .thumbs-rating-container, .rate-response, .post-ratings, .kk-star-ratings'
    ).forEach(function (el) {
      el.remove();
    });

    // Bare “0” nodes left between reply cards and pagination (old reply-count bug / vote leftovers).
    var candidates = root.querySelectorAll('div, span, p, li');
    candidates.forEach(function (el) {
      if (el.closest('.md-community-toolbar, .md-community-search, form, .bbp-pagination-links, a, button')) {
        return;
      }
      if (el.children.length > 0) return;
      var text = String(el.textContent || '').replace(/\s+/g, ' ').trim();
      if (text !== '0') return;
      if (
        el.closest('.md-reply-list, .bbp-replies, .bbp-reply-content, .md-reply-card, .bbp-footer, .md-reply-list__footer') ||
        el.parentElement === root ||
        (el.parentElement && el.parentElement.id === 'bbpress-forums')
      ) {
        el.remove();
      }
    });
  }

  function run() {
    cleanSidebar();
    hideZeroPosts();
    removeOrphanScores();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
