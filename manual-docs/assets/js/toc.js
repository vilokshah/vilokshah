/**
 * Auto-generate "On this page" TOC from document headings.
 */
(function () {
  'use strict';

  var content = document.getElementById('md-doc-content');
  var list = document.getElementById('md-toc-list');
  if (!content || !list) return;

  var headings = content.querySelectorAll('h2, h3');
  if (!headings.length) {
    var tocAside = list.closest('.md-doc-toc');
    if (tocAside) tocAside.hidden = true;
    return;
  }

  var frag = document.createDocumentFragment();

  headings.forEach(function (heading, index) {
    if (!heading.id) {
      var slug = (heading.textContent || 'section')
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .trim()
        .replace(/\s+/g, '-');
      heading.id = slug || ('section-' + index);
    }

    var a = document.createElement('a');
    a.href = '#' + heading.id;
    a.textContent = heading.textContent;
    a.className = heading.tagName === 'H3' ? 'toc-h3' : 'toc-h2';
    frag.appendChild(a);
  });

  list.appendChild(frag);

  var links = list.querySelectorAll('a');
  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      var id = entry.target.id;
      links.forEach(function (link) {
        link.classList.toggle('is-active', link.getAttribute('href') === '#' + id);
      });
    });
  }, { rootMargin: '-20% 0px -65% 0px', threshold: 0 });

  headings.forEach(function (h) { observer.observe(h); });
})();
