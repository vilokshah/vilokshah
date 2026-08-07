/**
 * On-this-page TOC builder (reusable after AJAX loads).
 */
(function (window, document) {
  'use strict';

  var observer = null;

  function slugify(text, index) {
    var slug = String(text || 'section')
      .toLowerCase()
      .replace(/[^a-z0-9\s-]/g, '')
      .trim()
      .replace(/\s+/g, '-');
    return slug || ('section-' + index);
  }

  function disconnect() {
    if (observer) {
      observer.disconnect();
      observer = null;
    }
  }

  /**
   * @param {Object} [options]
   * @param {Array<{id:string,text:string,level:number}>} [options.toc]
   */
  function build(options) {
    options = options || {};
    var content = document.getElementById('md-doc-content') || document.querySelector('[data-md-doc-content]');
    var list = document.getElementById('md-toc-list') || document.querySelector('[data-md-toc-list]');
    var tocAside = document.querySelector('[data-md-toc]') || (list && list.closest('.md-doc-toc'));

    if (!content || !list) return;

    disconnect();
    list.innerHTML = '';

    var headings = [];
    var tocData = options.toc;

    if (tocData && tocData.length) {
      // Ensure DOM ids match payload.
      var domHeadings = content.querySelectorAll('h2, h3');
      tocData.forEach(function (item, index) {
        if (domHeadings[index] && item.id) {
          domHeadings[index].id = item.id;
        }
        headings.push({
          id: item.id,
          text: item.text,
          level: item.level || 2,
          el: domHeadings[index] || null
        });
      });
    } else {
      Array.prototype.forEach.call(content.querySelectorAll('h2, h3'), function (heading, index) {
        if (!heading.id) {
          heading.id = slugify(heading.textContent, index);
        }
        headings.push({
          id: heading.id,
          text: heading.textContent,
          level: heading.tagName === 'H3' ? 3 : 2,
          el: heading
        });
      });
    }

    if (!headings.length) {
      if (tocAside) tocAside.hidden = true;
      return;
    }

    if (tocAside) {
      tocAside.hidden = false;
      // Preserve user collapsed preference after AJAX rebuilds (content expands).
      var collapsed = false;
      try { collapsed = localStorage.getItem('manualDocsTocCollapsed') === '1'; } catch (e) {}
      tocAside.classList.toggle('is-collapsed', collapsed);
      var layout = document.querySelector('.md-doc-layout');
      if (layout) layout.classList.toggle('is-toc-collapsed', collapsed);
      if (collapsed) list.setAttribute('hidden', '');
      else list.removeAttribute('hidden');
      var toggleBtn = tocAside.querySelector('[data-md-toc-toggle]');
      if (toggleBtn) {
        toggleBtn.textContent = collapsed ? 'show' : 'hide';
        toggleBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        toggleBtn.title = collapsed ? 'Show table of contents' : 'Hide table of contents';
      }
    }

    var frag = document.createDocumentFragment();
    headings.forEach(function (item) {
      var a = document.createElement('a');
      a.href = '#' + item.id;
      a.textContent = item.text;
      a.className = item.level === 3 ? 'toc-h3' : 'toc-h2';
      frag.appendChild(a);
    });
    list.appendChild(frag);

    var links = list.querySelectorAll('a');
    observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var id = entry.target.id;
        links.forEach(function (link) {
          link.classList.toggle('is-active', link.getAttribute('href') === '#' + id);
        });
      });
    }, { rootMargin: '-20% 0px -65% 0px', threshold: 0 });

    headings.forEach(function (item) {
      if (item.el) observer.observe(item.el);
    });
  }

  window.ManualDocsTOC = {
    build: build,
    destroy: disconnect
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { build(); });
  } else {
    build();
  }
})(window, document);
