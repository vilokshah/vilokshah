/**
 * Main theme interactions: nav, search toggle, version switcher, sidebar.
 */
(function () {
  'use strict';

  function qs(sel, ctx) {
    return (ctx || document).querySelector(sel);
  }

  function qsa(sel, ctx) {
    return Array.prototype.slice.call((ctx || document).querySelectorAll(sel));
  }

  function toggleHidden(el, force) {
    if (!el) return;
    var hide = typeof force === 'boolean' ? force : !el.hasAttribute('hidden');
    if (hide) {
      el.setAttribute('hidden', '');
    } else {
      el.removeAttribute('hidden');
    }
  }

  // Header search toggle
  qsa('[data-md-search-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var panel = qs('#md-header-search');
      var open = panel && panel.hasAttribute('hidden');
      toggleHidden(panel, !open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) {
        var input = qs('#md-live-search-input', panel) || qs('.md-live-search__input', panel);
        if (input) input.focus();
      }
    });
  });

  // Mobile nav
  qsa('[data-md-nav-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var panel = qs('#md-mobile-nav');
      var open = panel && panel.hasAttribute('hidden');
      toggleHidden(panel, !open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });

  // Docs sidebar (mobile)
  qsa('[data-md-sidebar-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var sidebar = qs('#md-docs-sidebar');
      if (!sidebar) return;
      var open = !sidebar.classList.contains('is-open');
      sidebar.classList.toggle('is-open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });

  // Version switcher
  qsa('.md-version-select').forEach(function (select) {
    select.addEventListener('change', function () {
      var url = select.value;
      if (url) {
        window.location.href = url;
      }
    });
  });

  // Keyboard shortcut: "/" focuses search when not typing
  document.addEventListener('keydown', function (e) {
    if (e.key !== '/' || e.metaKey || e.ctrlKey || e.altKey) return;
    var tag = (e.target && e.target.tagName) || '';
    if (/INPUT|TEXTAREA|SELECT/.test(tag) || (e.target && e.target.isContentEditable)) return;
    e.preventDefault();
    var input = qs('.md-live-search--hero .md-live-search__input') ||
      qs('.md-live-search--sidebar .md-live-search__input') ||
      qs('.md-live-search__input');
    if (input) {
      var headerSearch = qs('#md-header-search');
      if (headerSearch && headerSearch.hasAttribute('hidden') && !qs('.md-live-search--hero') && !qs('.md-live-search--sidebar')) {
        headerSearch.removeAttribute('hidden');
      }
      input.focus();
    }
  });
})();
