/**
 * ============================================
 * ADMIN INLINE EDITOR WITH LIVE PREVIEW
 * ============================================
 */
(function () {
  'use strict';

  function getCsrfToken() {
    // Prefer meta tag set by server; fall back to cookie/localStorage
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
      const v = metaTag.getAttribute('content') || '';
      if (v) return v;
    }
    const cookies = document.cookie.split(';');
    for (let cookie of cookies) {
      const [name, value] = cookie.trim().split('=');
      if (name === 'csrf_token') return decodeURIComponent(value || '');
    }
    return localStorage.getItem('csrf_token') || '';
  }

  function __(key, fallback) {
    if (typeof window.ADMIN_LANG !== 'undefined' && window.ADMIN_LANG[key]) {
      return window.ADMIN_LANG[key];
    }
    return fallback || key;
  }

  function getMd() {
    if (!window.markdownit) return null;
    if (!window.__ADMIN_MD) {
      window.__ADMIN_MD = window.markdownit({
        html: true,
        linkify: true,
        breaks: true,
        typographer: true,
        highlight: function (str, lang) {
          if (window.hljs) {
            try {
              if (lang && hljs.getLanguage(lang)) {
                return hljs.highlight(str, { language: lang }).value;
              }
              return hljs.highlightAuto(str).value;
            } catch (e) {}
          }
          return str;
        }
      });
    }
    return window.__ADMIN_MD;
  }

  function renderMarkdownFallback(raw) {
    let html = String(raw || '');
    html = html.replace(/```([\w+-]*)\n([\s\S]*?)\n```/g, function (_, lang, code) {
      const langClass = lang ? ' class="language-' + lang + '"' : '';
      return '<pre><code' + langClass + '>' + escapeHtml(code) + '</code></pre>';
    });
    html = html.replace(/^######[ \t]+(.+)$/gm, '<h6>$1</h6>');
    html = html.replace(/^##### [ \t]+(.+)$/gm, '<h5>$1</h5>');
    html = html.replace(/^####[ \t]+(.+)$/gm, '<h4>$1</h4>');
    html = html.replace(/^### [ \t]+(.+)$/gm, '<h3>$1</h3>');
    html = html.replace(/^##[ \t]+(.+)$/gm, '<h2>$1</h2>');
    html = html.replace(/^#[ \t]+(.+)$/gm, '<h1>$1</h1>');
    html = html.replace(/!\[([^\]]*)\]\(([^)]+)\)/g, '<img src="$2" alt="$1" style="max-width:100%;">');
    html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>');
    html = html.replace(/`([^`]+?)`/g, '<code>$1</code>');
    html = html.replace(/\*\*([\s\S]+?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\*([^*]+?)\*/g, '<em>$1</em>');
    html = html.replace(/\n/g, '<br>');
    return html;
  }

  function escapeHtml(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  function sanitize(html) {
    if (window.DOMPurify) {
      return DOMPurify.sanitize(html, { ADD_TAGS: ['center', 'details', 'summary'], ADD_ATTR: ['class', 'target', 'rel'] });
    }
    return html.replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, '');
  }

  // Robust GET helper (avoids null by forcing json and disabling cache)
  function getJson(url, data, onSuccess, onError) {
    $.ajax({
      url: url,
      method: 'GET',
      dataType: 'json',
      cache: false,
      headers: { 'Csrf-Token': getCsrfToken() }, // matches $.ajaxSetup in PHP templates
      data: data,
      success: function (resp, status, xhr) {
        // Some servers respond with empty body; normalize to {}
        if (resp === null || typeof resp === 'undefined') {
          if (typeof onError === 'function') onError({ responseText: 'Empty response (null)', responseJSON: null });
          return;
        }
        if (typeof onSuccess === 'function') onSuccess(resp, status, xhr);
      },
      error: function (xhr) {
        if (typeof onError === 'function') onError(xhr);
      }
    });
  }

  window.openInlineEditor = function (postId) {
    document.querySelectorAll('.inline-editor-row').forEach(row => (row.style.display = 'none'));
    const editorRow = document.getElementById('inline-editor-' + postId);
    if (!editorRow) return;
    editorRow.style.display = 'table-row';

    // Absolute path to avoid relative path issues; robust GET wrapper to avoid null
    getJson('/ajax.php', { action: 'edit_data', id: postId }, function (response) {
      const textarea = document.getElementById('editor-' + postId);
      if (response && typeof response.plain_text !== 'undefined' && textarea) {
        textarea.value = response.plain_text;
        updatePreview(postId);
      } else {
        const msg = (response && response.msg) ? response.msg : '';
        alert(__('errorPostData', 'Error: Post data could not be loaded') + (msg ? '\n\n' + msg : ''));
      }
    }, function (xhr) {
      let extra = '';
      try {
        const json = xhr.responseJSON;
        if (json && json.msg) extra = '\n\n' + json.msg;
      } catch (e) {}
      alert(__('errorLoadingPost', 'Error loading post!') + '\n\n' + (xhr.responseText || 'null response') + extra);
    });

    setTimeout(() => editorRow.scrollIntoView({ behavior: 'smooth', block: 'center' }), 100);
  };

  window.closeInlineEditor = function (postId) {
    const editorRow = document.getElementById('inline-editor-' + postId);
    if (editorRow) editorRow.style.display = 'none';
  };

  window.saveInlinePost = function (postId) {
    const textarea = document.getElementById('editor-' + postId);
    if (!textarea) return;
    const content = textarea.value;

    $.ajax({
      url: '/ajax.php',
      method: 'POST',
      dataType: 'json',
      cache: false,
      headers: { 'Csrf-Token': getCsrfToken() },
      data: { action: 'update', id: postId, text: content },
      success: function (response) {
        if (response && !response.error) {
          alert('✅ ' + __('postSaved', 'Post saved!'));
          closeInlineEditor(postId);
          location.reload();
        } else {
          alert('❌ ' + __('errorSaving', 'Error saving:') + ' ' + ((response && response.msg) ? response.msg : 'Unknown error'));
        }
      },
      error: function (xhr) {
        let extra = '';
        try {
          const json = xhr.responseJSON;
          if (json && json.msg) extra = '\n\n' + json.msg;
        } catch (e) {}
        alert('❌ ' + __('networkErrorSaving', 'Network error saving!') + '\n\n' + (xhr.responseText || ''));
      }
    });
  };

  function updatePreview(postId) {
    const textarea = document.getElementById('editor-' + postId);
    const preview = document.getElementById('preview-' + postId);
    if (!textarea || !preview) return;

    const raw = textarea.value;
    const md = getMd();
    let html = md ? md.render(raw) : renderMarkdownFallback(raw);
    html = sanitize(html);
    preview.innerHTML = html;

    if (!md && window.hljs) {
      preview.querySelectorAll('pre code').forEach((el) => {
        try { hljs.highlightElement(el); } catch (e) {}
      });
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.inline-edit-btn').forEach((btn) => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        openInlineEditor(this.getAttribute('data-post-id'));
      });
    });

    document.querySelectorAll('.close-editor').forEach((btn) => {
      btn.addEventListener('click', function () {
        closeInlineEditor(this.getAttribute('data-post-id'));
      });
    });

    document.querySelectorAll('.inline-editor-textarea').forEach((ta) => {
      ta.addEventListener('input', function () {
        updatePreview(this.id.replace('editor-', ''));
      });
    });

    document.querySelectorAll('.toolbar-btn').forEach((btn) => {
      btn.addEventListener('click', function () {
        const action = this.getAttribute('data-action');
        const textarea = this.closest('.inline-editor-body')?.querySelector('textarea');
        if (!textarea) return;

        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selected = textarea.value.substring(start, end);

        function insert(before, after) {
          const newText =
            textarea.value.substring(0, start) +
            before + selected + after +
            textarea.value.substring(end);
          textarea.value = newText;
          const selStart = start + before.length;
          const selEnd = selStart + selected.length;
          textarea.focus();
          textarea.setSelectionRange(selStart, selEnd);
          updatePreview(textarea.id.replace('editor-', ''));
        }

        function insertLinePrefix(prefix) {
          const text = textarea.value;
          const lineStart = text.lastIndexOf('\n', start - 1) + 1;
          const lineEnd = text.indexOf('\n', end);
          const blockEnd = lineEnd === -1 ? text.length : lineEnd;
          const block = text.substring(lineStart, blockEnd);
          const prefixed = block.split('\n').map(l => (l.length ? prefix + l : l)).join('\n');
          const newText = text.substring(0, lineStart) + prefixed + text.substring(blockEnd);
          textarea.value = newText;
          textarea.focus();
          textarea.setSelectionRange(lineStart, lineStart + prefixed.length);
          updatePreview(textarea.id.replace('editor-', ''));
        }

        switch (action) {
          case 'bold': insert('**', '**'); break;
          case 'italic': insert('*', '*'); break;
          case 'code': insert('`', '`'); break;
          case 'link': {
            const url = prompt(__('enterURL', 'Enter URL:'), 'https://');
            if (url) insert('[', '](' + url + ')');
            break;
          }
          case 'image': {
            const imgUrl = prompt(__('enterImageURL', 'Enter Image URL:'), 'https://');
            if (imgUrl) insert('![Alt-Text](', imgUrl + ')');
            break;
          }
          case 'h1': insertLinePrefix('# '); break;
          case 'h2': insertLinePrefix('## '); break;
          case 'h3': insertLinePrefix('### '); break;
          case 'hr': {
            const text = textarea.value;
            const insertPos = end;
            const newText = text.slice(0, insertPos) + '\n\n---\n\n' + text.slice(insertPos);
            textarea.value = newText;
            textarea.focus();
            textarea.setSelectionRange(insertPos + 5, insertPos + 5);
            updatePreview(textarea.id.replace('editor-', ''));
            break;
          }
          case 'quote': insertLinePrefix('> '); break;
          case 'ul': insertLinePrefix('- '); break;
          case 'ol': {
            const text = textarea.value;
            const lineStart = text.lastIndexOf('\n', start - 1) + 1;
            const lineEnd = text.indexOf('\n', end);
            const blockEnd = lineEnd === -1 ? text.length : lineEnd;
            const block = text.substring(lineStart, blockEnd);
            const lines = block.split('\n');
            const prefixed = lines.map((l, i) => (l.length ? (i + 1) + '. ' + l : l)).join('\n');
            const newText = text.substring(0, lineStart) + prefixed + text.substring(blockEnd);
            textarea.value = newText;
            textarea.focus();
            textarea.setSelectionRange(lineStart, lineStart + prefixed.length);
            updatePreview(textarea.id.replace('editor-', ''));
            break;
          }
          case 'codeblock': {
            const lang = prompt(__('Language optional', 'Language optional, e.g. bash:'), '');
            const before = '```' + (lang || '') + '\n';
            const after = '\n```\n';
            insert(before, after);
            break;
          }
          case 'spoiler': {
            insert('<details><summary>' + __('Spoiler Title', 'Spoiler Title:') + '</summary>\n\n', '\n\n</details>\n');
            break;
          }
        }
      });
    });
  });
})();