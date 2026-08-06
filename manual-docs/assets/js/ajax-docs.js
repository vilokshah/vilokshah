/**
 * AJAX document loader — tree menu, pager, version switcher, History API + TOC.
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

  function qs(sel, ctx) {
    return (ctx || document).querySelector(sel);
  }

  function setLoading(isLoading) {
    article.classList.toggle('is-ajax-loading', isLoading);
    var bar = qs('[data-md-ajax-progress]', article);
    if (bar) bar.hidden = !isLoading;
    shell.setAttribute('aria-busy', isLoading ? 'true' : 'false');
  }

  function updateTreeActive(docId) {
    var tree = qs('[data-md-doc-tree]', shell) || qs('.md-docs-sidebar__nav', shell);
    if (!tree) return;

    tree.querySelectorAll('.md-doc-nav__item.is-active').forEach(function (li) {
      li.classList.remove('is-active');
    });
    tree.querySelectorAll('[aria-current="page"]').forEach(function (a) {
      a.removeAttribute('aria-current');
    });

    var link = tree.querySelector('[data-md-doc-id="' + docId + '"]');
    if (!link) {
      // Fallback: match by pathname.
      var path = window.location.pathname.replace(/\/$/, '');
      tree.querySelectorAll('a[href]').forEach(function (a) {
        try {
          var aPath = new URL(a.href, window.location.origin).pathname.replace(/\/$/, '');
          if (aPath === path) link = a;
        } catch (e) { /* ignore */ }
      });
    }
    if (!link) return;

    link.setAttribute('aria-current', 'page');
    var li = link.closest('.md-doc-nav__item') || link.parentElement;
    if (li) li.classList.add('is-active');

    // Expand ancestors visually if nested.
    var parent = link.parentElement;
    while (parent && parent !== tree) {
      if (parent.classList && parent.classList.contains('md-doc-nav__item')) {
        parent.classList.add('is-expanded');
      }
      parent = parent.parentElement;
    }
  }

  function closeMobileSidebar() {
    var sidebar = document.getElementById('md-docs-sidebar');
    var toggle = qs('[data-md-sidebar-toggle]');
    if (sidebar) sidebar.classList.remove('is-open');
    if (toggle) toggle.setAttribute('aria-expanded', 'false');
  }

  function applyDoc(data, pushState) {
    article.setAttribute('data-md-doc-id', String(data.id));

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
    if (modified) modified.textContent = data.modifiedHuman || '';

    contentEl.innerHTML = data.content || '';

    var pager = qs('[data-md-pager]', article);
    if (pager) pager.innerHTML = data.pagerHtml || '';

    var community = qs('[data-md-community-slot]', article);
    if (community && typeof data.communityHtml === 'string') {
      community.innerHTML = data.communityHtml;
    }

    if (window.ManualDocsTOC && typeof window.ManualDocsTOC.build === 'function') {
      window.ManualDocsTOC.build({ toc: data.toc || [] });
    }

    updateTreeActive(data.id);
    document.title = data.title + ' — ' + (document.title.split(' — ').pop() || document.title);

    if (pushState) {
      window.history.pushState({ mdDocId: data.id }, data.title, data.url);
    }

    // Scroll content into view smoothly.
    var top = article.getBoundingClientRect().top + window.pageYOffset - 72;
    window.scrollTo({ top: Math.max(top, 0), behavior: 'smooth' });

    // Announce for screen readers.
    article.focus({ preventScroll: true });
  }

  function fetchDoc(id) {
    if (cache[id]) {
      return Promise.resolve(cache[id]);
    }

    if (currentController) {
      currentController.abort();
    }
    currentController = window.AbortController ? new AbortController() : null;

    var url = manualDocs.restUrl + 'doc/' + encodeURIComponent(id);
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
          '&id=' + encodeURIComponent(id);

        return fetch(ajaxUrl, { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (payload) {
            if (!payload || !payload.success) {
              throw new Error((payload && payload.data && payload.data.message) || 'ajax-fail');
            }
            return payload.data;
          });
      })
      .then(function (data) {
        cache[id] = data;
        return data;
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

    return fetchDoc(id)
      .then(function (data) {
        applyDoc(data, opts.pushState !== false);
        closeMobileSidebar();
      })
      .catch(function (err) {
        if (err && err.name === 'AbortError') return;
        // Hard fallback.
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

  // Intercept tree / pager / in-content doc links marked for AJAX.
  document.addEventListener('click', function (e) {
    var link = e.target.closest('a[data-md-ajax-doc], .md-docs-sidebar__nav a, [data-md-doc-tree] a, [data-md-pager] a');
    if (!link || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    if (link.target && link.target !== '' && link.target !== '_self') return;

    var href = link.getAttribute('href');
    if (!href || href.charAt(0) === '#') return;

    var id = resolveDocIdFromLink(link);
    // Sidebar menu items without data attr: try cache miss via URL path load fallback.
    if (!id && link.closest('.md-docs-sidebar__nav, [data-md-doc-tree]')) {
      // Allow WP menus: still try to AJAX if URL looks like a docs permalink by fetching via full navigation fallback.
      // Without an ID we cannot use the REST id endpoint; fall through to normal navigation.
      return;
    }

    if (!id) return;

    e.preventDefault();
    navigateToDoc(id, { href: link.href, pushState: true });
  });

  // Version switcher (delegated — HTML is replaced after AJAX).
  document.addEventListener('change', function (e) {
    var select = e.target.closest('.md-version-select');
    if (!select) return;

    var option = select.options[select.selectedIndex];
    if (!option || option.disabled) return;

    var id = parseInt(option.getAttribute('data-md-doc-id') || '0', 10);
    var url = option.value;
    if (id) {
      navigateToDoc(id, { href: url, pushState: true });
    } else if (url) {
      window.location.href = url;
    }
  });

  // Live search result clicks inside docs shell.
  document.addEventListener('click', function (e) {
    var item = e.target.closest('.md-live-search__item');
    if (!item || !shell.contains(item.closest('.md-live-search') || item)) return;
    // Search items may not have doc id; enhance live-search separately.
    var id = parseInt(item.getAttribute('data-md-doc-id') || '0', 10);
    if (!id) return;
    e.preventDefault();
    navigateToDoc(id, { href: item.href, pushState: true });
  });

  window.addEventListener('popstate', function (e) {
    var id = e.state && e.state.mdDocId;
    if (!id) {
      // Try parse from current article mapping — reload if unknown.
      window.location.reload();
      return;
    }
    navigateToDoc(id, { pushState: false, force: true });
  });

  // Seed history state for back-button support.
  if (!window.history.state || !window.history.state.mdDocId) {
    var initialId = parseInt(article.getAttribute('data-md-doc-id'), 10);
    if (initialId) {
      window.history.replaceState({ mdDocId: initialId }, document.title, window.location.href);
    }
  }

  // Expose for live-search integration.
  window.ManualDocsAjax = {
    navigateToDoc: navigateToDoc,
    fetchDoc: fetchDoc
  };
})(window, document);
