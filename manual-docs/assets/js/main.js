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

  // Theme toggle (light / dark)
  function applyTheme(theme) {
    var next = theme === 'light' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-md-theme', next);
    try { localStorage.setItem('manualDocsTheme', next); } catch (e) {}
    qsa('[data-md-theme-toggle]').forEach(function (btn) {
      btn.setAttribute('aria-pressed', next === 'light' ? 'true' : 'false');
      btn.title = next === 'light' ? 'Switch to dark mode' : 'Switch to light mode';
    });
  }

  qsa('[data-md-theme-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var current = document.documentElement.getAttribute('data-md-theme') || 'dark';
      applyTheme(current === 'light' ? 'dark' : 'light');
    });
  });
  applyTheme(document.documentElement.getAttribute('data-md-theme') || 'dark');

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

  // Tree expand/collapse (delegated for AJAX-replaced trees)
  document.addEventListener('click', function (e) {
    var twist = e.target.closest('[data-md-tree-toggle]');
    if (!twist) return;
    e.preventDefault();
    var li = twist.closest('.md-doc-nav__item');
    if (!li) return;
    var kids = li.querySelector(':scope > .md-doc-nav__children');
    var open = !li.classList.contains('is-expanded');
    li.classList.toggle('is-expanded', open);
    twist.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (kids) {
      if (open) kids.removeAttribute('hidden');
      else kids.setAttribute('hidden', '');
    }
  });

  // TOC hide/show toggle (class-based so CSS display:flex cannot override [hidden]).
  function syncTocToggle(btn, collapsed) {
    if (!btn) return;
    btn.textContent = collapsed ? 'show' : 'hide';
    btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-md-toc-toggle]');
    if (!btn) return;
    e.preventDefault();
    var aside = btn.closest('[data-md-toc]');
    var card = btn.closest('.md-doc-toc__card') || aside;
    if (!card) return;
    var list = card.querySelector('[data-md-toc-list]');
    if (!list) return;
    var collapsed = !(aside && aside.classList.contains('is-collapsed'));
    if (aside) aside.classList.toggle('is-collapsed', collapsed);
    if (collapsed) list.setAttribute('hidden', '');
    else list.removeAttribute('hidden');
    syncTocToggle(btn, collapsed);
    try { localStorage.setItem('manualDocsTocCollapsed', collapsed ? '1' : '0'); } catch (err) {}
  });

  // Restore TOC collapsed preference.
  (function initTocState() {
    var aside = qs('[data-md-toc]');
    if (!aside) return;
    var btn = aside.querySelector('[data-md-toc-toggle]');
    var list = aside.querySelector('[data-md-toc-list]');
    var collapsed = false;
    try { collapsed = localStorage.getItem('manualDocsTocCollapsed') === '1'; } catch (err) {}
    aside.classList.toggle('is-collapsed', collapsed);
    if (list) {
      if (collapsed) list.setAttribute('hidden', '');
      else list.removeAttribute('hidden');
    }
    syncTocToggle(btn, collapsed);
  })();

  // Docs tree sidebar collapse (maximize content width).
  function applyTreeCollapsed(collapsed) {
    var shell = qs('[data-md-ajax-shell]') || qs('.md-docs-shell');
    if (!shell) return;
    shell.classList.toggle('is-tree-collapsed', !!collapsed);
    qsa('[data-md-tree-collapse]').forEach(function (btn) {
      btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
      btn.title = collapsed ? 'Show documentation tree' : 'Hide documentation tree';
    });
    try { localStorage.setItem('manualDocsTreeCollapsed', collapsed ? '1' : '0'); } catch (err) {}
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-md-tree-collapse]');
    if (!btn) return;
    e.preventDefault();
    var shell = qs('[data-md-ajax-shell]') || qs('.md-docs-shell');
    var collapsed = !(shell && shell.classList.contains('is-tree-collapsed'));
    applyTreeCollapsed(collapsed);
  });

  (function initTreeCollapse() {
    var collapsed = false;
    try { collapsed = localStorage.getItem('manualDocsTreeCollapsed') === '1'; } catch (err) {}
    if (collapsed) applyTreeCollapsed(true);
  })();

  // Version switcher is handled by ajax-docs.js when the AJAX shell is present.
  if (!document.querySelector('[data-md-ajax-shell]')) {
    qsa('.md-version-select').forEach(function (select) {
      select.addEventListener('change', function () {
        var url = select.value;
        if (url) {
          window.location.href = url;
        }
      });
    });
  }

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
