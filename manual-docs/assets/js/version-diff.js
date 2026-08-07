/**
 * Version diff UI — compare menu + expandable summary rows.
 * Only loaded when the admin toggle is enabled.
 */
(function () {
  'use strict';

  function closest(el, sel) {
    return el && el.closest ? el.closest(sel) : null;
  }

  document.addEventListener('click', function (e) {
    var toggle = closest(e.target, '[data-md-compare-toggle]');
    if (toggle) {
      e.preventDefault();
      e.stopPropagation();
      var wrap = closest(toggle, '[data-md-compare]');
      var menu = wrap && wrap.querySelector('[data-md-compare-menu]');
      if (!menu) return;
      var open = menu.hasAttribute('hidden');
      // Close other open menus first.
      document.querySelectorAll('[data-md-compare-menu]').forEach(function (m) {
        if (m !== menu) {
          m.setAttribute('hidden', '');
          var t = m.parentNode && m.parentNode.querySelector('[data-md-compare-toggle]');
          if (t) t.setAttribute('aria-expanded', 'false');
        }
      });
      if (open) menu.removeAttribute('hidden');
      else menu.setAttribute('hidden', '');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      return;
    }

    var row = closest(e.target, '[data-md-diff-toggle]');
    if (row) {
      e.preventDefault();
      var panelId = row.getAttribute('aria-controls');
      var panel = panelId ? document.getElementById(panelId) : null;
      if (!panel) return;
      var expanded = row.getAttribute('aria-expanded') === 'true';
      row.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      if (expanded) panel.setAttribute('hidden', '');
      else panel.removeAttribute('hidden');
      var item = closest(row, '.md-diff__item');
      if (item) item.classList.toggle('is-open', !expanded);
      return;
    }

    // Outside click closes compare menus.
    if (!closest(e.target, '[data-md-compare]')) {
      document.querySelectorAll('[data-md-compare-menu]').forEach(function (m) {
        m.setAttribute('hidden', '');
        var t = m.parentNode && m.parentNode.querySelector('[data-md-compare-toggle]');
        if (t) t.setAttribute('aria-expanded', 'false');
      });
    }
  });
})();
