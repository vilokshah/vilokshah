/**
 * AJAX document loader — tree menu, pager, version switcher, History API + TOC.
 *
 * Performance notes:
 * - Same-version navigations skip rebuilding sidebar treeHtml (biggest cost on large libraries).
 * - Tree is refreshed when the version root changes, or when the target doc is not in the tree.
 * - Prev/Next docs are prefetched into the in-memory cache after each successful load.
 */
(function (window, document) {
  'use strict';

  if (typeof manualDocs === 'undefined') return;

  var shell = document.querySelector('[data-md-ajax-shell]');
  if (!shell) return;

  var article = document.getElementById('md-doc-article');
  var contentEl = document.querySelector('[data-md-doc-content]');
  if (!article || !contentEl) return;

  var cache = {};
  var currentController = null;
  var navigating = false;
  var currentRootId = parseInt(article.getAttribute('data-md-version-root') || '0', 10) || 0;

  function qs(sel, ctx) {
    return (ctx || document).querySelector(sel);
  }

  function setLoading(isLoading) {
    article.classList.toggle('is-ajax-loading', isLoading);
    var bar = qs('[data-md-ajax-progress]', article);
    if (bar) bar.hidden = !isLoading;
    shell.setAttribute('aria-busy', isLoading ? 'true' : 'false');
  }

  function treeHasDoc(docId) {
    var tree = qs('[data-md-doc-tree]', shell) || qs('.md-docs-sidebar__nav', shell);
    if (!tree) return false;
    return !!tree.querySelector('[data-md-doc-id="' + docId + '"]');
  }

  function updateTreeActive(docId) {
    var tree = qs('[data-md-doc-tree]', shell) || qs('.md-docs-sidebar__nav', shell);
    if (!tree) return false;

    tree.querySelectorAll('.md-doc-nav__item.is-active').forEach(function (li) {
      li.classList.remove('is-active');
    });
    tree.querySelectorAll('[aria-current="page"]').forEach(function (a) {
      a.removeAttribute('aria-current');
    });

    var link = tree.querySelector('[data-md-doc-id="' + docId + '"]');
    if (!link) {
      var path = window.location.pathname.replace(/\/$/, '');
      tree.querySelectorAll('a[href]').forEach(function (a) {
        try {
          var aPath = new URL(a.href, window.location.origin).pathname.replace(/\/$/, '');
          if (aPath === path) link = a;
        } catch (e) { /* ignore */ }
      });
    }
    if (!link) return false;

    link.setAttribute('aria-current', 'page');
    var li = link.closest('.md-doc-nav__item') || link.parentElement;
    if (li) li.classList.add('is-active');

    var parent = link.parentElement;
    while (parent && parent !== tree) {
      if (parent.classList && parent.classList.contains('md-doc-nav__item')) {
        parent.classList.add('is-expanded');
        parent.classList.remove('is-collapsed');
        var kids = parent.querySelector('.md-doc-nav__children');
        if (kids) kids.hidden = false;
      }
      parent = parent.parentElement;
    }
    return true;
  }

  function closeMobileSidebar() {
    var sidebar = document.getElementById('md-docs-sidebar');
    var toggle = qs('[data-md-sidebar-toggle]');
    if (sidebar) sidebar.classList.remove('is-open');
    if (toggle) toggle.setAttribute('aria-expanded', 'false');
  }

  function applyDoc(data, pushState) {
    article.setAttribute('data-md-doc-id', String(data.id));
    if (data.versionRootId) {
      currentRootId = parseInt(data.versionRootId, 10) || 0;
      article.setAttribute('data-md-version-root', String(currentRootId));
    }

    var titleEl = qs('[data-md-doc-title]', article);
    if (titleEl) titleEl.textContent = data.title;

    var crumbs = qs('[data-md-breadcrumbs]', article);
    if (crumbs && data.breadcrumbs) crumbs.innerHTML = data.breadcrumbs;

    var versionSlot = qs('[data-md-version-slot]', article);
    if (versionSlot) versionSlot.innerHTML = data.versionHtml || '';

    var pdfBtn = qs('[data-md-pdf-btn]', article);
    if (pdfBtn && data.pdfUrl) pdfBtn.href = data.pdfUrl;

    var badge = qs('[data-md-version-badge]', article);
    if (badge) {
      if (data.versionBadge) {
        badge.textContent = data.versionBadge;
        badge.hidden = false;
      } else {
        badge.textContent = '';
        badge.hidden = true;
      }
    }

    var modified = qs('[data-md-modified]', article);
    if (modified) {
      modified.innerHTML = modified.querySelector('svg')
        ? modified.querySelector('svg').outerHTML + ' ' + (data.modifiedHuman || '')
        : (data.modifiedHuman || '');
    }

    var edit = qs('.md-meta-edit', article);
    if (edit) {
      if (data.editUrl) {
        edit.href = data.editUrl;
        edit.hidden = false;
      } else {
        edit.hidden = true;
      }
    }

    contentEl.innerHTML = data.content || '';

    if (typeof data.treeHtml === 'string' && data.treeHtml) {
      var tree = qs('[data-md-doc-tree]', shell);
      if (tree) tree.innerHTML = data.treeHtml;
    }

    var pager = qs('[data-md-pager]', article);
    if (pager) pager.innerHTML = data.pagerHtml || '';

    var community = qs('[data-md-community-slot]', article);
    if (community && typeof data.communityHtml === 'string') {
      community.innerHTML = data.communityHtml;
    }

    if (window.ManualDocsTOC && typeof window.ManualDocsTOC.build === 'function') {
      window.ManualDocsTOC.build({ toc: data.toc || [] });
    }

    var foundInTree = updateTreeActive(data.id);
    document.title = data.title + ' — ' + (document.title.split(' — ').pop() || document.title);

    if (pushState) {
      window.history.pushState({ mdDocId: data.id }, data.title, data.url);
    }

    var top = article.getBoundingClientRect().top + window.pageYOffset - 72;
    window.scrollTo({ top: Math.max(top, 0), behavior: 'smooth' });
    article.focus({ preventScroll: true });

    return foundInTree;
  }

  function requestDoc(id, includeTree) {
    var url = manualDocs.restUrl + 'doc/' + encodeURIComponent(id) +
      '?include_tree=' + (includeTree ? '1' : '0');
    var opts = {
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'X-WP-Nonce': manualDocs.restNonce || manualDocs.nonce
      }
    };
    if (currentController) opts.signal = currentController.signal;

    return fetch(url, opts)
      .then(function (res) {
        if (res.status === 401) {
          window.location.href = manualDocs.loginUrl;
          throw new Error('auth');
        }
        if (!res.ok) throw new Error('rest-fail');
        return res.json();
      })
      .catch(function (err) {
        if (err && err.name === 'AbortError') throw err;
        if (err && err.message === 'auth') throw err;

        var ajaxUrl = manualDocs.ajaxUrl +
          '?action=manual_docs_get_doc&nonce=' + encodeURIComponent(manualDocs.nonce) +
          '&id=' + encodeURIComponent(id) +
          '&include_tree=' + (includeTree ? '1' : '0');

        return fetch(ajaxUrl, { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (payload) {
            if (!payload || !payload.success) {
              throw new Error((payload && payload.data && payload.data.message) || 'ajax-fail');
            }
            return payload.data;
          });
      });
  }

  function fetchDoc(id, opts) {
    opts = opts || {};
    var includeTree = !!opts.includeTree;
    var forceNetwork = !!opts.forceNetwork;

    if (!forceNetwork && cache[id]) {
      var cached = cache[id];
      // Cached payload without tree is fine unless caller requires tree.
      if (!includeTree || (cached.treeHtml && cached.treeHtml.length)) {
        return Promise.resolve(cached);
      }
    }

    if (!opts.background) {
      if (currentController) {
        currentController.abort();
      }
      currentController = window.AbortController ? new AbortController() : null;
    }

    // Background prefetch must not share/abort the main controller.
    var fetchPromise;
    if (opts.background) {
      var bgUrl = manualDocs.restUrl + 'doc/' + encodeURIComponent(id) + '?include_tree=0';
      fetchPromise = fetch(bgUrl, {
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-WP-Nonce': manualDocs.restNonce || manualDocs.nonce
        }
      }).then(function (res) {
        if (!res.ok) throw new Error('prefetch-fail');
        return res.json();
      }).catch(function () {
        return null;
      });
    } else {
      fetchPromise = requestDoc(id, includeTree);
    }

    return fetchPromise.then(function (data) {
      if (!data) return data;
      // Prefer payload that includes tree when merging into cache.
      if (!cache[id] || (data.treeHtml && data.treeHtml.length) || includeTree) {
        cache[id] = data;
      } else if (!cache[id]) {
        cache[id] = data;
      }
      return data;
    });
  }

  function prefetchNeighbors(data) {
    var ids = [data.prevId, data.nextId];
    ids.forEach(function (nid) {
      nid = parseInt(nid, 10) || 0;
      if (!nid || cache[nid]) return;
      fetchDoc(nid, { includeTree: false, background: true });
    });
  }

  function navigateToDoc(id, opts) {
    opts = opts || {};
    id = parseInt(id, 10);
    if (!id || navigating) return Promise.resolve();

    var currentId = parseInt(article.getAttribute('data-md-doc-id'), 10);
    if (currentId === id && !opts.force) return Promise.resolve();

    navigating = true;
    setLoading(true);

    // Same-version clicks: skip tree rebuild. Version switches / missing nodes: include tree.
    var includeTree = !!opts.includeTree;
    if (!includeTree && opts.forceTree) includeTree = true;
    if (!includeTree && !treeHasDoc(id) && !opts.allowMissingTree) {
      // Target not visible in sidebar yet (collapsed lazy branch / other version) — need tree.
      includeTree = true;
    }

    return fetchDoc(id, { includeTree: includeTree, forceNetwork: !!opts.forceNetwork })
      .then(function (data) {
        var found = applyDoc(data, opts.pushState !== false);
        closeMobileSidebar();

        // Recovery: doc still missing from tree after a no-tree fetch (other branch / version).
        if (!found && !data.treeHtml) {
          return fetchDoc(id, { includeTree: true, forceNetwork: true }).then(function (full) {
            applyDoc(full, false);
            prefetchNeighbors(full);
          });
        }

        prefetchNeighbors(data);
        return data;
      })
      .catch(function (err) {
        if (err && err.name === 'AbortError') return;
        if (opts.href) {
          window.location.href = opts.href;
        }
      })
      .finally(function () {
        navigating = false;
        setLoading(false);
      });
  }

  function resolveDocIdFromLink(link) {
    if (!link) return 0;
    var id = link.getAttribute('data-md-doc-id');
    if (id) return parseInt(id, 10);
    return 0;
  }

  document.addEventListener('click', function (e) {
    var link = e.target.closest('a[data-md-ajax-doc], .md-docs-sidebar__nav a, [data-md-doc-tree] a, [data-md-pager] a');
    if (!link || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    if (link.target && link.target !== '' && link.target !== '_self') return;

    var href = link.getAttribute('href');
    if (!href || href.charAt(0) === '#') return;

    var id = resolveDocIdFromLink(link);
    if (!id && link.closest('.md-docs-sidebar__nav, [data-md-doc-tree]')) {
      return;
    }

    if (!id) return;

    e.preventDefault();
    // Sidebar click: link is already in the tree — skip tree rebuild.
    var fromTree = !!link.closest('[data-md-doc-tree], .md-docs-sidebar__nav, [data-md-pager]');
    navigateToDoc(id, {
      href: link.href,
      pushState: true,
      includeTree: false,
      allowMissingTree: fromTree
    });
  });

  document.addEventListener('change', function (e) {
    var select = e.target.closest('.md-version-select');
    if (!select) return;

    var option = select.options[select.selectedIndex];
    if (!option || option.disabled) return;

    var id = parseInt(option.getAttribute('data-md-doc-id') || '0', 10);
    var url = option.value;
    if (id) {
      // Version change always needs a fresh tree for the new root.
      navigateToDoc(id, { href: url, pushState: true, includeTree: true, forceNetwork: true });
    } else if (url) {
      window.location.href = url;
    }
  });

  document.addEventListener('click', function (e) {
    var item = e.target.closest('.md-live-search__item');
    if (!item) return;
    var wrap = item.closest('.md-live-search');
    var inShell = wrap && shell.contains(wrap);
    var inModal = wrap && wrap.classList.contains('md-live-search--modal');
    if (!inShell && !inModal) return;
    var id = parseInt(item.getAttribute('data-md-doc-id') || '0', 10);
    if (!id) return;
    e.preventDefault();
    navigateToDoc(id, { href: item.href, pushState: true });
    if (inModal && window.ManualDocsSearchModal && typeof window.ManualDocsSearchModal.close === 'function') {
      window.ManualDocsSearchModal.close();
    }
  });

  window.addEventListener('popstate', function (e) {
    var id = e.state && e.state.mdDocId;
    if (!id) {
      window.location.reload();
      return;
    }
    navigateToDoc(id, { pushState: false, force: true });
  });

  if (!window.history.state || !window.history.state.mdDocId) {
    var initialId = parseInt(article.getAttribute('data-md-doc-id'), 10);
    if (initialId) {
      window.history.replaceState({ mdDocId: initialId }, document.title, window.location.href);
    }
  }

  // Prefetch neighbors for the initially rendered document.
  (function seedPrefetch() {
    var pager = qs('[data-md-pager]', article);
    if (!pager) return;
    pager.querySelectorAll('a[data-md-doc-id]').forEach(function (a) {
      var nid = parseInt(a.getAttribute('data-md-doc-id') || '0', 10);
      if (nid && !cache[nid]) {
        fetchDoc(nid, { includeTree: false, background: true });
      }
    });
  })();

  window.ManualDocsAjax = {
    navigateToDoc: navigateToDoc,
    fetchDoc: fetchDoc
  };
})(window, document);
