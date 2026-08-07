/**
 * TinyMCE button — opens Content Elements focus.
 */
(function () {
  tinymce.PluginManager.add('manual_docs_elements', function (editor) {
    editor.addButton('manual_docs_elements', {
      text: 'Elements',
      icon: false,
      tooltip: 'Manual Docs content elements',
      onclick: function () {
        var box = document.getElementById('manual_docs_content_elements');
        if (box) {
          box.scrollIntoView({ behavior: 'smooth', block: 'center' });
          var first = box.querySelector('details');
          if (first) first.open = true;
        } else {
          editor.windowManager.alert('Use the Content Elements panel in the sidebar to insert accordion, tabs, callouts, code, and tables.');
        }
      }
    });
  });
})();
