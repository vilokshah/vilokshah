/**
 * Community UI helpers: strip leftover Archives/Categories cards and
 * hide meaningless “Viewing 0 posts” labels.
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
      if (/\b0\s+posts?\b/i.test(text) && !/\b[1-9]\d*\s+topics?\b/i.test(text)) {
        var wrap = el.closest('.bbp-pagination, li.bbp-footer, .md-reply-list__footer') || el;
        wrap.style.display = 'none';
      }
    });
    // Standalone centered “Viewing 0 posts” paragraphs.
    document.querySelectorAll('.md-bbpress p, #bbpress-forums p').forEach(function (el) {
      var text = String(el.textContent || '').replace(/\s+/g, ' ').trim();
      if (/^viewing\s+0\s+posts?\.?$/i.test(text)) {
        el.style.display = 'none';
      }
    });
  }

  function run() {
    cleanSidebar();
    hideZeroPosts();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
