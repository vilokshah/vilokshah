/**
 * Content Elements inserter (classic + block editor shortcode insertion).
 */
(function ($) {
  'use strict';

  if (typeof manualDocsEditor === 'undefined') return;

  function escAttr(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  function escHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  function toast(msg) {
    var $box = $('#md-editor-tools');
    $box.find('.md-editor-tools__toast').remove();
    $('<p class="md-editor-tools__toast"/>').text(msg).appendTo($box);
  }

  function insertIntoEditor(content, asShortcodeBlock) {
    var mode = $('#md-editor-tools').attr('data-editor') || 'classic';

    // Gutenberg: insert as shortcode block or freeform HTML.
    if (mode === 'block' && window.wp && wp.data && wp.blocks) {
      try {
        var block;
        if (asShortcodeBlock) {
          block = wp.blocks.createBlock('core/shortcode', { text: content });
        } else if (content.indexOf('<pre') === 0 || content.indexOf('<table') === 0 || content.indexOf('<figure') === 0) {
          // Prefer HTML / freeform for tables & code.
          if (wp.blocks.getBlockType('core/html')) {
            block = wp.blocks.createBlock('core/html', { content: content });
          } else {
            block = wp.blocks.createBlock('core/freeform', { content: content });
          }
        } else {
          block = wp.blocks.createBlock('core/shortcode', { text: content });
        }
        var insert = wp.data.dispatch('core/block-editor').insertBlocks;
        insert(block);
        toast(manualDocsEditor.i18n.inserted);
        return true;
      } catch (e) {
        // fall through to classic paths
      }
    }

    // Classic TinyMCE / textarea.
    if (window.tinymce && tinymce.activeEditor && !tinymce.activeEditor.isHidden()) {
      tinymce.activeEditor.execCommand('mceInsertContent', false, content);
      toast(manualDocsEditor.i18n.inserted);
      return true;
    }
    var ta = document.getElementById('content');
    if (ta) {
      if (typeof QTags !== 'undefined') {
        QTags.insertContent(content);
      } else {
        ta.value += '\n' + content + '\n';
      }
      toast(manualDocsEditor.i18n.inserted);
      return true;
    }
    toast(manualDocsEditor.i18n.needEditor);
    return false;
  }

  function buildCallout() {
    var tag = $('#md-tool-callout-type').val() || 'md_note';
    var title = $('#md-tool-callout-title').val() || 'Note';
    var body = $('#md-tool-callout-body').val() || '';
    return '[' + tag + ' title="' + escAttr(title) + '"]\n' + body + '\n[/' + tag + ']';
  }

  function buildAccordion() {
    var parts = ['[md_accordion]'];
    $('#md-tool-acc-items [data-md-acc-row]').each(function (i) {
      var title = $(this).find('[data-md-acc-title]').val() || (manualDocsEditor.i18n.itemTitle + ' ' + (i + 1));
      var body = $(this).find('[data-md-acc-body]').val() || '';
      var open = i === 0 ? ' open="1"' : '';
      parts.push('[md_item title="' + escAttr(title) + '"' + open + ']\n' + body + '\n[/md_item]');
    });
    parts.push('[/md_accordion]');
    return parts.join('\n');
  }

  function buildTabs() {
    var parts = ['[md_tabs]'];
    $('#md-tool-tab-items [data-md-tab-row]').each(function (i) {
      var title = $(this).find('[data-md-tab-title]').val() || (manualDocsEditor.i18n.tabLabel + ' ' + (i + 1));
      var body = $(this).find('[data-md-tab-body]').val() || '';
      parts.push('[md_tab title="' + escAttr(title) + '"]\n' + body + '\n[/md_tab]');
    });
    parts.push('[/md_tabs]');
    return parts.join('\n');
  }

  function buildCode() {
    var lang = ($('#md-tool-code-lang').val() || '').trim();
    var code = $('#md-tool-code-body').val() || '';
    var label = lang ? ' data-lang="' + escAttr(lang) + '"' : '';
    return '<pre class="md-code"' + label + '><code>' + escHtml(code) + '</code></pre>';
  }

  function buildTable() {
    var cols = Math.max(2, Math.min(8, parseInt($('#md-tool-table-cols').val(), 10) || 3));
    var rows = Math.max(1, Math.min(20, parseInt($('#md-tool-table-rows').val(), 10) || 3));
    var headers = ($('#md-tool-table-headers').val() || '').split(',').map(function (s) { return s.trim(); });
    while (headers.length < cols) headers.push(manualDocsEditor.i18n.col + ' ' + (headers.length + 1));
    headers = headers.slice(0, cols);

    var html = '<figure class="wp-block-table md-table"><table><thead><tr>';
    headers.forEach(function (h) { html += '<th>' + escHtml(h) + '</th>'; });
    html += '</tr></thead><tbody>';
    for (var r = 0; r < rows; r++) {
      html += '<tr>';
      for (var c = 0; c < cols; c++) {
        html += '<td>' + escHtml(manualDocsEditor.i18n.cell) + '</td>';
      }
      html += '</tr>';
    }
    html += '</tbody></table></figure>';
    return html;
  }

  $(document).on('click', '[data-md-acc-add]', function (e) {
    e.preventDefault();
    var row = $(
      '<div class="md-editor-tools__item" data-md-acc-row>' +
        '<input type="text" class="widefat" placeholder="' + escAttr(manualDocsEditor.i18n.itemTitle) + '" data-md-acc-title />' +
        '<textarea class="widefat" rows="2" placeholder="' + escAttr(manualDocsEditor.i18n.itemBody) + '" data-md-acc-body></textarea>' +
      '</div>'
    );
    $('#md-tool-acc-items').append(row);
  });

  $(document).on('click', '[data-md-tab-add]', function (e) {
    e.preventDefault();
    var row = $(
      '<div class="md-editor-tools__item" data-md-tab-row>' +
        '<input type="text" class="widefat" placeholder="' + escAttr(manualDocsEditor.i18n.tabLabel) + '" data-md-tab-title />' +
        '<textarea class="widefat" rows="2" placeholder="' + escAttr(manualDocsEditor.i18n.tabBody) + '" data-md-tab-body></textarea>' +
      '</div>'
    );
    $('#md-tool-tab-items').append(row);
  });

  $(document).on('click', '[data-md-insert]', function (e) {
    e.preventDefault();
    var type = $(this).attr('data-md-insert');
    var content = '';
    var shortcode = true;
    if (type === 'callout') content = buildCallout();
    else if (type === 'accordion') content = buildAccordion();
    else if (type === 'tabs') content = buildTabs();
    else if (type === 'code') { content = buildCode(); shortcode = false; }
    else if (type === 'table') { content = buildTable(); shortcode = false; }
    if (content) insertIntoEditor(content, shortcode);
  });
})(jQuery);
