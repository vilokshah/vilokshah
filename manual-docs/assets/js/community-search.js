/**
 * Live search for bbPress forums / topics / replies.
 */
(function () {
  'use strict';

  if (typeof manualDocsCommunity === 'undefined') return;

  var debounceTimer = null;
  var wrap = null;
  var input = null;
  var panel = null;
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

  function ensurePanel() {
    if (panel) return panel;
    panel = document.createElement('div');
    panel.className = 'md-community-search__results';
    panel.hidden = true;
    panel.setAttribute('role', 'listbox');
    wrap.appendChild(panel);
    return panel;
  }

  function setOpen(open) {
    wrap.classList.toggle('is-open', !!open);
    if (panel) panel.hidden = !open;
  }

  function render(results) {
    currentResults = results || [];
    activeIndex = -1;
    ensurePanel();

    if (!currentResults.length) {
      panel.innerHTML = '<div class="md-community-search__empty">' + escapeHtml(manualDocsCommunity.i18n.noResults) + '</div>';
      setOpen(true);
      return;
    }

    panel.innerHTML = currentResults.map(function (item, i) {
      return (
        '<a class="md-community-search__item" role="option" data-index="' + i + '" href="' + escapeHtml(item.url) + '">' +
          '<span class="md-community-search__type">' + escapeHtml(item.label || item.type) + '</span>' +
          '<span class="md-community-search__title">' + escapeHtml(item.title) + '</span>' +
          (item.excerpt ? '<span class="md-community-search__excerpt">' + escapeHtml(item.excerpt) + '</span>' : '') +
        '</a>'
      );
    }).join('');
    setOpen(true);
  }

  function search(q) {
    q = String(q || '').trim();
    if (q.length < 2) {
      setOpen(false);
      return;
    }
    ensurePanel();
    panel.innerHTML = '<div class="md-community-search__empty">' + escapeHtml(manualDocsCommunity.i18n.searching) + '</div>';
    setOpen(true);

    var url = manualDocsCommunity.restUrl + (manualDocsCommunity.restUrl.indexOf('?') >= 0 ? '&' : '?') + 'q=' + encodeURIComponent(q);
    fetch(url, {
      credentials: 'same-origin',
      headers: { 'X-WP-Nonce': manualDocsCommunity.nonce || '' }
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        render((data && data.results) || []);
      })
      .catch(function () {
        render([]);
      });
  }

  function onKey(e) {
    if (!wrap.classList.contains('is-open') || !currentResults.length) return;
    var items = panel.querySelectorAll('.md-community-search__item');
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      activeIndex = Math.min(activeIndex + 1, items.length - 1);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      activeIndex = Math.max(activeIndex - 1, 0);
    } else if (e.key === 'Enter' && activeIndex >= 0 && currentResults[activeIndex]) {
      e.preventDefault();
      window.location.href = currentResults[activeIndex].url;
      return;
    } else if (e.key === 'Escape') {
      setOpen(false);
      return;
    } else {
      return;
    }
    items.forEach(function (el, i) {
      el.classList.toggle('is-active', i === activeIndex);
    });
  }

  function init() {
    wrap = document.querySelector('.md-community-search');
    input = document.getElementById('md-community-search-input');
    if (!wrap || !input) return;

    // Live AJAX — prevent full-page submit when JS works.
    wrap.addEventListener('submit', function (e) {
      var q = input.value.trim();
      if (q.length >= 2 && wrap.classList.contains('is-open') && currentResults.length) {
        e.preventDefault();
        window.location.href = currentResults[0].url;
      }
    });

    input.addEventListener('input', debounce(function () {
      search(input.value);
    }, 220));

    input.addEventListener('keydown', onKey);
    input.addEventListener('focus', function () {
      if (input.value.trim().length >= 2) search(input.value);
    });

    document.addEventListener('click', function (e) {
      if (!wrap.contains(e.target)) setOpen(false);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
