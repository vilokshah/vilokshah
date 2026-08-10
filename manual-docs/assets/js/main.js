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
  function directChildList(li) {
    if (!li || !li.children) return null;
    for (var i = 0; i < li.children.length; i++) {
      if (li.children[i].classList && li.children[i].classList.contains('md-doc-nav__children')) {
        return li.children[i];
      }
    }
    return null;
  }

  function kidsNeedLazyLoad(kids) {
    return !!(kids && kids.hasAttribute('data-md-lazy-parent') &&
      !kids.querySelector('a[data-md-doc-id], a[data-md-ajax-doc]'));
  }

  function loadLazyChildren(li, kids, open) {
    if (!open || !kids || !kids.hasAttribute('data-md-lazy-parent')) return Promise.resolve();
    if (kids.getAttribute('data-md-loading') === '1') {
      return kids._mdLazyPromise || Promise.resolve();
    }
    // Already hydrated with real child docs — clear lazy flag.
    if (kids.querySelector('a[data-md-doc-id], a[data-md-ajax-doc]')) {
      kids.removeAttribute('data-md-lazy-parent');
      return Promise.resolve();
    }
    if (typeof manualDocs === 'undefined') return Promise.resolve();

    var parentId = kids.getAttribute('data-md-lazy-parent');
    var article = document.getElementById('md-doc-article');
    var currentId = article ? (article.getAttribute('data-md-doc-id') || '0') : '0';
    kids.setAttribute('data-md-loading', '1');
    kids.innerHTML = '<li class="md-doc-nav__item"><span class="md-nav-empty">Loading…</span></li>';

    function applyHtml(html) {
      // Bail if a newer injectDocChildren already filled this node.
      if (!kids.hasAttribute('data-md-loading') && kids.querySelector('a[data-md-doc-id]')) {
        return;
      }
      kids.innerHTML = html || '';
      kids.removeAttribute('data-md-lazy-parent');
      kids.removeAttribute('data-md-loading');
      kids._mdLazyPromise = null;
    }

    function fail() {
      if (kids.querySelector('a[data-md-doc-id]')) {
        kids.removeAttribute('data-md-loading');
        kids._mdLazyPromise = null;
        return;
      }
      kids.innerHTML = '<li class="md-doc-nav__item"><span class="md-nav-empty">Could not load</span></li>';
      kids.removeAttribute('data-md-loading');
      kids._mdLazyPromise = null;
    }

    function fetchViaRest() {
      if (!manualDocs.restUrl) return Promise.reject(new Error('no-rest'));
      var url = manualDocs.restUrl + 'nav-children?parent=' + encodeURIComponent(parentId) +
        '&current=' + encodeURIComponent(currentId);
      return fetch(url, {
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-WP-Nonce': manualDocs.restNonce || ''
        }
      }).then(function (res) {
        if (!res.ok) throw new Error('nav-fail');
        return res.json();
      }).then(function (data) {
        applyHtml(data && data.html);
      });
    }

    function fetchViaAjax() {
      if (!manualDocs.ajaxUrl) return Promise.reject(new Error('no-ajax'));
      var url = manualDocs.ajaxUrl +
        '?action=manual_docs_nav_children&nonce=' + encodeURIComponent(manualDocs.nonce || '') +
        '&parent=' + encodeURIComponent(parentId) +
        '&current=' + encodeURIComponent(currentId);
      return fetch(url, { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (payload) {
          if (!payload || !payload.success) throw new Error('ajax-nav-fail');
          applyHtml(payload.data && payload.data.html);
        });
    }

    var promise = fetchViaRest().catch(function () {
      return fetchViaAjax();
    }).catch(function () {
      fail();
    });

    kids._mdLazyPromise = promise;
    return promise;
  }

  /**
   * Expand a nav item and lazy-load children if needed (used after AJAX doc navigation).
   */
  function getTwistButton(li) {
    if (!li || !li.children) return null;
    for (var i = 0; i < li.children.length; i++) {
      if (li.children[i].hasAttribute && li.children[i].hasAttribute('data-md-tree-toggle')) {
        return li.children[i];
      }
    }
    return null;
  }

  function collapseNavItem(li) {
    if (!li) return;
    li.classList.remove('is-expanded');
    var twist = getTwistButton(li);
    if (twist) twist.setAttribute('aria-expanded', 'false');
    var kids = directChildList(li);
    if (kids) {
      kids.setAttribute('hidden', '');
      kids.hidden = true;
    }
  }

  /**
   * Accordion: keep only the active doc path expanded; collapse every other branch.
   */
  function collapseBranchesOutsidePath(activeLi) {
    var tree = qs('[data-md-doc-tree]') || qs('.md-docs-sidebar__nav');
    if (!tree || !activeLi) return;

    var keep = [];
    var node = activeLi;
    while (node && node !== tree) {
      if (node.classList && node.classList.contains('md-doc-nav__item')) {
        keep.push(node);
      }
      node = node.parentElement;
    }

    qsa('.md-doc-nav__item.is-expanded', tree).forEach(function (li) {
      if (keep.indexOf(li) === -1) {
        collapseNavItem(li);
      }
    });
  }

  /**
   * Accordion: when opening a node via the chevron, close its siblings.
   */
  function collapseSiblingBranches(li) {
    if (!li || !li.parentElement) return;
    var siblings = li.parentElement.children;
    for (var i = 0; i < siblings.length; i++) {
      var sib = siblings[i];
      if (sib !== li && sib.classList && sib.classList.contains('md-doc-nav__item') && sib.classList.contains('is-expanded')) {
        collapseNavItem(sib);
      }
    }
  }

  function ensureNavItemExpanded(li) {
    if (!li || !li.classList.contains('has-children')) return Promise.resolve();
    var kids = directChildList(li);
    var twist = getTwistButton(li);
    li.classList.add('is-expanded');
    if (twist) twist.setAttribute('aria-expanded', 'true');
    if (kids) {
      kids.removeAttribute('hidden');
      kids.hidden = false;
      return loadLazyChildren(li, kids, true);
    }
    return Promise.resolve();
  }

  function ensureDocExpandedInTree(docId) {
    var tree = qs('[data-md-doc-tree]') || qs('.md-docs-sidebar__nav');
    if (!tree || !docId) return Promise.resolve();
    var link = tree.querySelector('[data-md-doc-id="' + docId + '"]');
    if (!link) return Promise.resolve();
    var li = link.closest('.md-doc-nav__item');
    if (!li) return Promise.resolve();

    collapseBranchesOutsidePath(li);

    var chain = [];
    var node = li;
    while (node && node !== tree) {
      if (node.classList && node.classList.contains('md-doc-nav__item')) {
        chain.unshift(node);
      }
      node = node.parentElement;
    }
    var seq = Promise.resolve();
    chain.forEach(function (item) {
      seq = seq.then(function () { return ensureNavItemExpanded(item); });
    });
    return seq;
  }

  /** Hydrate any expanded-but-empty lazy branches (e.g. active path on first paint). */
  function hydrateExpandedLazyBranches() {
    var tree = qs('[data-md-doc-tree]') || qs('.md-docs-sidebar__nav');
    if (!tree) return;
    qsa('.md-doc-nav__item.is-expanded.has-children', tree).forEach(function (li) {
      var kids = directChildList(li);
      if (kidsNeedLazyLoad(kids)) {
        ensureNavItemExpanded(li);
      }
    });
  }

  window.ManualDocsTree = {
    loadLazyChildren: loadLazyChildren,
    ensureNavItemExpanded: ensureNavItemExpanded,
    ensureDocExpandedInTree: ensureDocExpandedInTree,
    hydrateExpandedLazyBranches: hydrateExpandedLazyBranches,
    collapseBranchesOutsidePath: collapseBranchesOutsidePath,
    collapseSiblingBranches: collapseSiblingBranches,
    collapseNavItem: collapseNavItem,
    directChildList: directChildList
  };

  document.addEventListener('click', function (e) {
    var twist = e.target.closest('[data-md-tree-toggle]');
    if (!twist) return;
    e.preventDefault();
    e.stopPropagation();
    var li = twist.closest('.md-doc-nav__item');
    if (!li) return;
    var kids = directChildList(li);
    var isExpanded = li.classList.contains('is-expanded');
    // If marked expanded but children never loaded (AJAX skip-tree race), load on this click.
    var needsLoad = kids && kids.hasAttribute('data-md-lazy-parent') &&
      !kids.querySelector('a[data-md-doc-id], a[data-md-ajax-doc]');
    var open = needsLoad ? true : !isExpanded;
    if (open) {
      collapseSiblingBranches(li);
    }
    li.classList.toggle('is-expanded', open);
    twist.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (kids) {
      if (open) {
        kids.removeAttribute('hidden');
        kids.hidden = false;
      } else {
        kids.setAttribute('hidden', '');
        kids.hidden = true;
      }
      loadLazyChildren(li, kids, open);
    }
  });

  // First paint: active ancestors may be is-expanded with an empty lazy UL.
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', hydrateExpandedLazyBranches);
  } else {
    hydrateExpandedLazyBranches();
  }

  // TOC hide/show — collapses column so content expands (like tree menu).
  function applyTocCollapsed(collapsed) {
    var layout = qs('.md-doc-layout');
    var aside = qs('[data-md-toc]');
    if (layout) layout.classList.toggle('is-toc-collapsed', !!collapsed);
    if (aside) aside.classList.toggle('is-collapsed', !!collapsed);
    var list = aside ? aside.querySelector('[data-md-toc-list]') : null;
    if (list) {
      if (collapsed) list.setAttribute('hidden', '');
      else list.removeAttribute('hidden');
    }
    qsa('[data-md-toc-toggle]').forEach(function (btn) {
      btn.textContent = collapsed ? 'show' : 'hide';
      btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
      btn.title = collapsed ? 'Show table of contents' : 'Hide table of contents';
    });
    try { localStorage.setItem('manualDocsTocCollapsed', collapsed ? '1' : '0'); } catch (err) {}
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-md-toc-toggle]');
    if (!btn) return;
    e.preventDefault();
    var aside = btn.closest('[data-md-toc]');
    var collapsed = !(aside && aside.classList.contains('is-collapsed'));
    applyTocCollapsed(collapsed);
  });

  (function initTocState() {
    var aside = qs('[data-md-toc]');
    if (!aside) return;
    var collapsed = false;
    try { collapsed = localStorage.getItem('manualDocsTocCollapsed') === '1'; } catch (err) {}
    applyTocCollapsed(collapsed);
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

  // Tabs shortcode — build tablist from panels.
  function initTabs(root) {
    qsa('[data-md-tabs]', root || document).forEach(function (wrap) {
      if (wrap.getAttribute('data-md-tabs-ready')) return;
      var panels = qsa('[data-md-tab-panel]', wrap);
      if (!panels.length) return;
      wrap.setAttribute('data-md-tabs-ready', '1');
      var list = document.createElement('div');
      list.className = 'md-tabs__list';
      list.setAttribute('role', 'tablist');
      panels.forEach(function (panel, i) {
        var title = panel.getAttribute('data-title') || ('Tab ' + (i + 1));
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'md-tabs__tab' + (i === 0 ? ' is-active' : '');
        btn.setAttribute('role', 'tab');
        btn.setAttribute('aria-selected', i === 0 ? 'true' : 'false');
        btn.setAttribute('aria-controls', panel.id);
        btn.textContent = title;
        if (i !== 0) panel.setAttribute('hidden', '');
        else panel.removeAttribute('hidden');
        btn.addEventListener('click', function () {
          panels.forEach(function (p) { p.setAttribute('hidden', ''); });
          qsa('.md-tabs__tab', list).forEach(function (t) {
            t.classList.remove('is-active');
            t.setAttribute('aria-selected', 'false');
          });
          panel.removeAttribute('hidden');
          btn.classList.add('is-active');
          btn.setAttribute('aria-selected', 'true');
        });
        list.appendChild(btn);
      });
      wrap.insertBefore(list, wrap.firstChild);
    });
  }

  document.addEventListener('click', function (e) {
    var trigger = e.target.closest('[data-md-acc-trigger]');
    if (!trigger) return;
    var item = trigger.closest('[data-md-acc-item]');
    if (!item) return;
    var panel = item.querySelector('.md-accordion__panel');
    var open = item.classList.contains('is-open');
    item.classList.toggle('is-open', !open);
    trigger.setAttribute('aria-expanded', open ? 'false' : 'true');
    if (panel) {
      if (open) panel.setAttribute('hidden', '');
      else panel.removeAttribute('hidden');
    }
  });

  initTabs();
  document.addEventListener('manualDocs:contentReady', function () {
    initTabs(document.getElementById('md-doc-content') || document);
  });
  // Also init after AJAX content swaps if MutationObserver-friendly path is missing.
  var moTarget = document.getElementById('md-doc-content');
  if (moTarget && window.MutationObserver) {
    var moTimer = null;
    new MutationObserver(function () {
      clearTimeout(moTimer);
      moTimer = setTimeout(function () { initTabs(moTarget); }, 50);
    }).observe(moTarget, { childList: true, subtree: false });
  }
})();
