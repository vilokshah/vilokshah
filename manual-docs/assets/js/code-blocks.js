/**
 * Enhance documentation code blocks: line numbers + copy button.
 * Works for Gutenberg core/code, Classic <pre><code>, and .md-code.
 */
(function () {
  'use strict';

  function qs(sel, ctx) {
    return (ctx || document).querySelector(sel);
  }

  function qsa(sel, ctx) {
    return Array.prototype.slice.call((ctx || document).querySelectorAll(sel));
  }

  function enhancePre(pre) {
    if (!pre || pre.getAttribute('data-md-code-ready')) return;
    // Never treat version-diff prose as a code block.
    if (pre.closest && (pre.closest('.md-diff') || pre.closest('[data-md-diff]'))) return;
    if (pre.classList && pre.classList.contains('md-diff__text')) return;
    pre.setAttribute('data-md-code-ready', '1');

    var code = pre.querySelector('code') || pre;
    var raw = code.textContent || '';
    // Normalize trailing newline so line count matches visible lines.
    var lines = raw.replace(/\n$/, '').split('\n');
    if (lines.length === 1 && lines[0] === '') lines = [''];

    var wrap = document.createElement('div');
    wrap.className = 'md-code-block';
    var lang = pre.getAttribute('data-lang') || pre.getAttribute('data-language') || '';
    if (!lang && pre.className) {
      var m = pre.className.match(/(?:language|lang)-([a-z0-9_+-]+)/i);
      if (m) lang = m[1];
    }

    var toolbar = document.createElement('div');
    toolbar.className = 'md-code-block__toolbar';
    toolbar.innerHTML =
      '<span class="md-code-block__lang">' + (lang ? String(lang) : 'code') + '</span>' +
      '<button type="button" class="md-code-block__copy" data-md-copy-code>Copy</button>';

    var body = document.createElement('div');
    body.className = 'md-code-block__body';

    var nums = document.createElement('div');
    nums.className = 'md-code-block__lines';
    nums.setAttribute('aria-hidden', 'true');
    nums.textContent = lines.map(function (_, i) { return String(i + 1); }).join('\n');

    pre.parentNode.insertBefore(wrap, pre);
    wrap.appendChild(toolbar);
    body.appendChild(nums);
    body.appendChild(pre);
    wrap.appendChild(body);

    var btn = toolbar.querySelector('[data-md-copy-code]');
    btn.addEventListener('click', function () {
      var text = code.textContent || '';
      var done = function () {
        btn.textContent = 'Copied';
        btn.classList.add('is-copied');
        setTimeout(function () {
          btn.textContent = 'Copy';
          btn.classList.remove('is-copied');
        }, 1600);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done).catch(function () {
          fallbackCopy(text, done);
        });
      } else {
        fallbackCopy(text, done);
      }
    });
  }

  function fallbackCopy(text, done) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.setAttribute('readonly', '');
    ta.style.position = 'fixed';
    ta.style.left = '-9999px';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); } catch (e) {}
    document.body.removeChild(ta);
    if (done) done();
  }

  function enhanceTables(root) {
    qsa('.md-doc-content table, [data-md-doc-content] table', root).forEach(function (table) {
      if (table.closest('.md-table-scroll')) return;
      var scroll = document.createElement('div');
      scroll.className = 'md-table-scroll';
      table.parentNode.insertBefore(scroll, table);
      scroll.appendChild(table);
    });
  }

  function enhanceAll(root) {
    var scope = root || document;
    qsa('.md-doc-content pre, [data-md-doc-content] pre', scope).forEach(enhancePre);
    enhanceTables(scope);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { enhanceAll(); });
  } else {
    enhanceAll();
  }

  // Re-run after AJAX doc loads.
  var moTarget = document.getElementById('md-doc-content');
  if (moTarget && window.MutationObserver) {
    var t = null;
    new MutationObserver(function () {
      clearTimeout(t);
      t = setTimeout(function () { enhanceAll(moTarget.parentNode || document); }, 40);
    }).observe(moTarget, { childList: true });
  }

  window.ManualDocsCodeBlocks = { enhance: enhanceAll };
})();
