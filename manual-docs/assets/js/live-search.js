/**
 * Live documentation search.
 */
(function () {
  'use strict';

  if (typeof manualDocs === 'undefined') return;

  var debounceTimer = null;
  var activeIndex = -1;
  var currentResults = [];
  var activeVersion = '';
  var activeVersionId = '';

  function debounce(fn, wait) {
    return function () {
      var args = arguments;
      var ctx = this;
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(function () {
        fn.apply(ctx, args);
      }, wait);
    };
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function getVersions() {
    return (manualDocs.versions && manualDocs.versions.length) ? manualDocs.versions : [];
  }

  function setOpenState(wrap, open) {
    wrap.classList.toggle('is-open', !!open);
    var hero = wrap.closest('.md-hero');
    if (hero) hero.classList.toggle('is-search-open', !!open);
  }

  function renderFilters() {
    var versions = getVersions();
    if (!versions.length) return '';

    var html = '<div class="md-live-search__filters" role="group" aria-label="Filter by release">';
    html += '<button type="button" class="md-live-search__filter' + (!activeVersion && !activeVersionId ? ' is-active' : '') +
      '" data-version="" data-version-id="">' + escapeHtml('All') + '</button>';
    versions.forEach(function (v) {
      var slug = v.slug || '';
      var id = String(v.id || '');
      var name = v.name || slug;
      var active = (activeVersionId && activeVersionId === id) || (!activeVersionId && activeVersion === slug);
      html += '<button type="button" class="md-live-search__filter' + (active ? ' is-active' : '') +
        '" data-version="' + escapeHtml(slug) + '" data-version-id="' + escapeHtml(id) + '">' +
        escapeHtml(name) + '</button>';
    });
    html += '</div>';
    return html;
  }

  function renderResults(container, results) {
    currentResults = results || [];
    activeIndex = -1;

    var filters = renderFilters();
    if (!currentResults.length) {
      container.innerHTML = filters + '<div class="md-live-search__empty">' + escapeHtml(manualDocs.i18n.noResults) + '</div>';
      container.hidden = false;
      return;
    }

    var list = currentResults.map(function (item, i) {
      var metaParts = [];
      if (item.version) metaParts.push(item.version);
      if (item.category) metaParts.push(item.category);
      if (item.excerpt) metaParts.push(item.excerpt);
      return (
        '<a class="md-live-search__item" role="option" data-index="' + i + '"' +
          (item.id ? ' data-md-doc-id="' + item.id + '" data-md-ajax-doc' : '') +
          ' href="' + escapeHtml(item.url) + '">' +
          '<span class="md-live-search__item-title">' + escapeHtml(item.title) + '</span>' +
          '<span class="md-live-search__item-meta">' + escapeHtml(metaParts.join(' — ')) + '</span>' +
        '</a>'
      );
    }).join('');

    container.innerHTML = filters + '<div class="md-live-search__list">' + list + '</div>';
    container.hidden = false;
  }

  function setActive(container, index) {
    var items = container.querySelectorAll('.md-live-search__item');
    items.forEach(function (el) { el.classList.remove('is-active'); });
    if (index >= 0 && items[index]) {
      items[index].classList.add('is-active');
      items[index].scrollIntoView({ block: 'nearest' });
    }
  }

  function versionQueryParam() {
    if (activeVersionId) return activeVersionId;
    if (activeVersion) return activeVersion;
    return '';
  }

  function search(query, wrap) {
    var resultsEl = wrap.querySelector('.md-live-search__results');
    var spinner = wrap.querySelector('.md-live-search__spinner');
    if (!resultsEl) return;

    if (!query || query.length < 2) {
      resultsEl.hidden = true;
      resultsEl.innerHTML = '';
      setOpenState(wrap, false);
      if (spinner) spinner.hidden = true;
      return;
    }

    if (spinner) spinner.hidden = false;
    setOpenState(wrap, true);

    var url = manualDocs.restUrl + 'search?q=' + encodeURIComponent(query);
    var version = versionQueryParam();
    if (version) url += '&version=' + encodeURIComponent(version);

    fetch(url, {
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'X-WP-Nonce': manualDocs.restNonce || manualDocs.nonce }
    })
      .then(function (res) {
        if (res.status === 401) {
          resultsEl.innerHTML = '<div class="md-live-search__empty">' + escapeHtml(manualDocs.i18n.loginRequired) +
            ' <a href="' + escapeHtml(manualDocs.loginUrl) + '">Log in</a></div>';
          resultsEl.hidden = false;
          setOpenState(wrap, true);
          throw new Error('auth');
        }
        if (!res.ok) throw new Error('search failed');
        return res.json();
      })
      .then(function (data) {
        renderResults(resultsEl, (data && data.results) || []);
        setOpenState(wrap, true);
      })
      .catch(function (err) {
        if (err && err.message === 'auth') return;
        var ajaxUrl = manualDocs.ajaxUrl + '?action=manual_docs_search&nonce=' + encodeURIComponent(manualDocs.nonce) +
          '&q=' + encodeURIComponent(query);
        if (version) ajaxUrl += '&version=' + encodeURIComponent(version);
        return fetch(ajaxUrl, { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (payload) {
            var results = (payload && payload.data && payload.data.results) || [];
            renderResults(resultsEl, results);
            setOpenState(wrap, true);
          });
      })
      .finally(function () {
        if (spinner) spinner.hidden = true;
      });
  }

  var runSearch = debounce(function (input, wrap) {
    search(input.value.trim(), wrap);
  }, 220);

  document.querySelectorAll('.md-live-search').forEach(function (wrap) {
    var input = wrap.querySelector('.md-live-search__input');
    var resultsEl = wrap.querySelector('.md-live-search__results');
    if (!input || !resultsEl) return;

    input.addEventListener('input', function () {
      runSearch(input, wrap);
    });

    input.addEventListener('focus', function () {
      if (input.value.trim().length >= 2 && resultsEl.innerHTML) {
        resultsEl.hidden = false;
        setOpenState(wrap, true);
      }
    });

    resultsEl.addEventListener('click', function (e) {
      var btn = e.target.closest('.md-live-search__filter');
      if (!btn) return;
      e.preventDefault();
      e.stopPropagation();
      activeVersion = btn.getAttribute('data-version') || '';
      activeVersionId = btn.getAttribute('data-version-id') || '';
      search(input.value.trim(), wrap);
    });

    input.addEventListener('keydown', function (e) {
      var items = resultsEl.querySelectorAll('.md-live-search__item');
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        activeIndex = Math.min(activeIndex + 1, items.length - 1);
        setActive(resultsEl, activeIndex);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        activeIndex = Math.max(activeIndex - 1, 0);
        setActive(resultsEl, activeIndex);
      } else if (e.key === 'Enter' && activeIndex >= 0 && items[activeIndex]) {
        e.preventDefault();
        var active = items[activeIndex];
        var docId = parseInt(active.getAttribute('data-md-doc-id') || '0', 10);
        if (docId && window.ManualDocsAjax && typeof window.ManualDocsAjax.navigateToDoc === 'function' && document.querySelector('[data-md-ajax-shell]')) {
          window.ManualDocsAjax.navigateToDoc(docId, { href: active.href, pushState: true });
          resultsEl.hidden = true;
          setOpenState(wrap, false);
          if (wrap.classList.contains('md-live-search--modal') && window.ManualDocsSearchModal) {
            window.ManualDocsSearchModal.close();
          }
        } else {
          window.location.href = active.href;
        }
      } else if (e.key === 'Escape') {
        resultsEl.hidden = true;
        setOpenState(wrap, false);
        if (wrap.classList.contains('md-live-search--modal') && window.ManualDocsSearchModal) {
          window.ManualDocsSearchModal.close();
        }
      }
    });

    document.addEventListener('click', function (e) {
      if (!wrap.contains(e.target)) {
        resultsEl.hidden = true;
        setOpenState(wrap, false);
      }
    });
  });
})();
