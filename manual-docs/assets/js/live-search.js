/**
 * Live documentation search.
 */
(function () {
  'use strict';

  if (typeof manualDocs === 'undefined') return;

  var debounceTimer = null;
  var activeIndex = -1;
  var currentResults = [];

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

  function getVersionSlug() {
    var switcher = document.querySelector('.md-version-switcher');
    return switcher ? switcher.getAttribute('data-current') || '' : '';
  }

  function renderResults(container, results) {
    currentResults = results || [];
    activeIndex = -1;

    if (!currentResults.length) {
      container.innerHTML = '<div class="md-live-search__empty">' + escapeHtml(manualDocs.i18n.noResults) + '</div>';
      container.hidden = false;
      return;
    }

    container.innerHTML = currentResults.map(function (item, i) {
      return (
        '<a class="md-live-search__item" role="option" data-index="' + i + '" href="' + escapeHtml(item.url) + '">' +
          '<span class="md-live-search__item-title">' + escapeHtml(item.title) + '</span>' +
          '<span class="md-live-search__item-meta">' +
            escapeHtml(item.category || '') +
            (item.excerpt ? ' — ' + escapeHtml(item.excerpt) : '') +
          '</span>' +
        '</a>'
      );
    }).join('');
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

  function search(query, wrap) {
    var resultsEl = wrap.querySelector('.md-live-search__results');
    var spinner = wrap.querySelector('.md-live-search__spinner');
    if (!resultsEl) return;

    if (!query || query.length < 2) {
      resultsEl.hidden = true;
      resultsEl.innerHTML = '';
      if (spinner) spinner.hidden = true;
      return;
    }

    if (spinner) spinner.hidden = false;

    var url = manualDocs.restUrl + 'search?q=' + encodeURIComponent(query);
    var version = getVersionSlug();
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
          throw new Error('auth');
        }
        if (!res.ok) throw new Error('search failed');
        return res.json();
      })
      .then(function (data) {
        renderResults(resultsEl, (data && data.results) || []);
      })
      .catch(function (err) {
        if (err && err.message === 'auth') return;
        // AJAX fallback
        var ajaxUrl = manualDocs.ajaxUrl + '?action=manual_docs_search&nonce=' + encodeURIComponent(manualDocs.nonce) +
          '&q=' + encodeURIComponent(query);
        if (version) ajaxUrl += '&version=' + encodeURIComponent(version);
        return fetch(ajaxUrl, { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (payload) {
            var results = (payload && payload.data && payload.data.results) || [];
            renderResults(resultsEl, results);
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
        window.location.href = items[activeIndex].href;
      } else if (e.key === 'Escape') {
        resultsEl.hidden = true;
      }
    });

    document.addEventListener('click', function (e) {
      if (!wrap.contains(e.target)) {
        resultsEl.hidden = true;
      }
    });
  });
})();
