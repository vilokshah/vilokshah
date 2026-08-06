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

  // Tree expand/collapse (delegated for AJAX-replaced trees) + lazy children.
  function loadLazyChildren(li, kids, open) {
    if (!open || !kids || !kids.hasAttribute('data-md-lazy-parent')) return Promise.resolve();
    if (kids.getAttribute('data-md-loading') === '1') return Promise.resolve();
    if (kids.querySelector('li')) {
      kids.removeAttribute('data-md-lazy-parent');
      return Promise.resolve();
    }
    if (typeof manualDocs === 'undefined' || !manualDocs.restUrl) return Promise.resolve();

    var parentId = kids.getAttribute('data-md-lazy-parent');
    var article = document.getElementById('md-doc-article');
    var currentId = article ? (article.getAttribute('data-md-doc-id') || '0') : '0';
    kids.setAttribute('data-md-loading', '1');
    kids.innerHTML = '<li class="md-doc-nav__item"><span class="md-nav-empty">Loading…</span></li>';

    var url = manualDocs.restUrl + 'nav-children?parent=' + encodeURIComponent(parentId) +
      '&current=' + encodeURIComponent(currentId);

    return fetch(url, {
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'X-WP-Nonce': manualDocs.restNonce || manualDocs.nonce
      }
    })
      .then(function (res) {
        if (!res.ok) throw new Error('nav-fail');
        return res.json();
      })
      .then(function (data) {
        kids.innerHTML = (data && data.html) ? data.html : '';
        kids.removeAttribute('data-md-lazy-parent');
        kids.removeAttribute('data-md-loading');
      })
      .catch(function () {
        kids.innerHTML = '<li class="md-doc-nav__item"><span class="md-nav-empty">Could not load</span></li>';
        kids.removeAttribute('data-md-loading');
      });
  }

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
      loadLazyChildren(li, kids, open);
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

  // Centered docs search modal (tree chrome magnifier).
  var docsSearchLastFocus = null;

  function getDocsSearchModal() {
    return qs('[data-md-docs-search-modal]');
  }

  function openDocsSearchModal() {
    var modal = getDocsSearchModal();
    if (!modal) return;
    docsSearchLastFocus = document.activeElement;
    modal.hidden = false;
    document.documentElement.classList.add('md-docs-search-open');
    var input = qs('.md-live-search--modal .md-live-search__input', modal) || qs('.md-live-search__input', modal);
    if (input) {
      setTimeout(function () { input.focus(); input.select && input.select(); }, 10);
    }
  }

  function closeDocsSearchModal() {
    var modal = getDocsSearchModal();
    if (!modal || modal.hidden) return;
    modal.hidden = true;
    document.documentElement.classList.remove('md-docs-search-open');
    var results = qs('.md-live-search__results', modal);
    if (results) results.hidden = true;
    if (docsSearchLastFocus && typeof docsSearchLastFocus.focus === 'function') {
      docsSearchLastFocus.focus();
    }
  }

  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-md-docs-search-open]')) {
      e.preventDefault();
      openDocsSearchModal();
      return;
    }
    if (e.target.closest('[data-md-docs-search-close]')) {
      e.preventDefault();
      closeDocsSearchModal();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      var modal = getDocsSearchModal();
      if (modal && !modal.hidden) {
        closeDocsSearchModal();
      }
    }
  });

  window.ManualDocsSearchModal = {
    open: openDocsSearchModal,
    close: closeDocsSearchModal
  };

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
    if (getDocsSearchModal()) {
      openDocsSearchModal();
      return;
    }
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
