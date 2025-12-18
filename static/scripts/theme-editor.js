/**
 * Theme Editor Client-Side Script
 * - Color picker helper for CSS variable inputs
 * - Upload guard for replace mode
 * - Small UX save indicators
 * - Live preview helper (switch theme*.css inside iframe + reload/bust custom1.css)
 */

(function () {
  'use strict';

  // --------------------------
  // DOM ready helper
  // --------------------------
  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  // --------------------------
  // Color inputs
  // --------------------------
  function initColorInputs() {
    var colorInputs = document.querySelectorAll('.color-input');
    colorInputs.forEach(function (input) {
      // Wrapper for text input + color picker
      var wrapper = document.createElement('div');
      wrapper.style.display = 'flex';
      wrapper.style.gap = '8px';
      wrapper.style.alignItems = 'center';

      // Clone original text input (keeps name/value/attrs)
      var textInput = input.cloneNode(true);
      textInput.style.flex = '1';

      // Color picker
      var colorPicker = document.createElement('input');
      colorPicker.type = 'color';
      colorPicker.style.width = '50px';
      colorPicker.style.height = '34px';
      colorPicker.style.border = '1px solid #d0d7de';
      colorPicker.style.borderRadius = '6px';
      colorPicker.style.cursor = 'pointer';

      var currentValue = (input.value || '').trim();
      if (isValidColor(currentValue)) {
        colorPicker.value = normalizeColor(currentValue);
      }

      // color -> text
      colorPicker.addEventListener('input', function () {
        textInput.value = this.value;
      });

      // text -> color
      textInput.addEventListener('input', function () {
        var val = (this.value || '').trim();
        if (isValidColor(val)) {
          colorPicker.value = normalizeColor(val);
        }
      });

      // Replace original node
      input.parentNode.replaceChild(wrapper, input);
      wrapper.appendChild(textInput);
      wrapper.appendChild(colorPicker);
    });
  }

  function isValidColor(color) {
    if (!color) return false;
    // Hex colors only (color input supports hex)
    if (/^#[0-9A-Fa-f]{3}$/.test(color)) return true;
    if (/^#[0-9A-Fa-f]{6}$/.test(color)) return true;
    return false;
  }

  function normalizeColor(color) {
    color = color.trim();
    if (/^#[0-9A-Fa-f]{6}$/.test(color)) return color;
    if (/^#[0-9A-Fa-f]{3}$/.test(color)) {
      return (
        '#' +
        color[1] + color[1] +
        color[2] + color[2] +
        color[3] + color[3]
      );
    }
    return '#000000';
  }

  // --------------------------
  // Upload form guard
  // --------------------------
  function initUploadForm() {
    // Select by enctype only (attribute action="" may not exist)
    var uploadForm = document.querySelector('form[enctype="multipart/form-data"]');
    if (!uploadForm) return;

    uploadForm.addEventListener('submit', function (e) {
      var modeSelect = uploadForm.querySelector('select[name="upload_mode"]');
      if (modeSelect && modeSelect.value === 'replace') {
        // Could be localized via dataset or injected string
        var msg = uploadForm.getAttribute('data-confirm-replace')
          || 'Warning: This action will replace all existing CSS rules. Continue?';
        if (!confirm(msg)) e.preventDefault();
      }
    });
  }

  // --------------------------
  // Save indicators
  // --------------------------
  function addSaveIndicators() {
    var forms = document.querySelectorAll('form[method="POST"]');
    forms.forEach(function (form) {
      var submitBtn = form.querySelector('button[type="submit"], button:not([type])');
      if (!submitBtn) return;

      form.addEventListener('submit', function () {
        submitBtn.disabled = true;
        submitBtn.textContent = submitBtn.textContent.replace(/💾|📤/, '⏳');
        setTimeout(function () {
          submitBtn.disabled = false;
        }, 3000);
      });
    });
  }

  // --------------------------
  // Preview helpers
  // --------------------------
  function cacheBust(url) {
    var clean = url.replace(/([?&])v=\d+(&?)/, function (_m, p1, p2) {
      return p2 ? p1 : '';
    });
    var joiner = clean.indexOf('?') === -1 ? '?' : '&';
    return clean + joiner + 'v=' + Date.now();
  }

  function withIframeDoc(cb) {
    try {
      var iframe = document.getElementById('previewFrame');
      if (!iframe) return;
      var doc = iframe.contentDocument || (iframe.contentWindow && iframe.contentWindow.document);
      if (!doc) return;

      // If the iframe document is not yet ready, wait for load
      if (doc.readyState !== 'complete' && doc.readyState !== 'interactive') {
        iframe.addEventListener('load', function once() {
          iframe.removeEventListener('load', once);
          try { cb(iframe.contentDocument || iframe.contentWindow.document); } catch (_) {}
        });
        return;
      }
      cb(doc);
    } catch (e) {
      console.warn('Theme editor: iframe access failed', e);
    }
  }

  function loadTheme(file) {
    withIframeDoc(function (doc) {
      var links = doc.querySelectorAll('link[rel="stylesheet"]');
      links.forEach(function (link) {
        // Only rewrite the theme*.css sheet
        if (/\/static\/styles\/theme[^/]*\.css(?:\?.*)?$/i.test(link.href)) {
          var newHref = link.href.replace(/theme[^/]*\.css(?:\?.*)?$/i, file);
          link.href = cacheBust(newHref);
        }
      });
    });
  }

  function reloadPreview() {
    var iframe = document.getElementById('previewFrame');
    if (!iframe) return;

    withIframeDoc(function (doc) {
      // Bust custom1.css if present
      var custom = doc.querySelector('link[href*="/static/styles/custom1.css"]');
      if (custom) custom.href = cacheBust(custom.href);
    });

    // Soft-reload iframe to ensure recalculation
    try {
      var src = iframe.src || '';
      src = src.replace(/([?&])pv=\d+(&?)/, function (_m, p1, p2) {
        return p2 ? p1 : '';
      });
      var joiner = src.indexOf('?') === -1 ? '?' : '&';
      iframe.src = src + joiner + 'pv=' + Date.now();
    } catch (e) {
      console.warn('Theme editor: iframe reload failed', e);
    }
  }

  // Expose tiny API for buttons in PHP template
  function exposeAPI() {
    window.ThemeEditorPreview = {
      loadTheme: loadTheme,
      reloadPreview: reloadPreview
    };
  }

  // --------------------------
  // Boot
  // --------------------------
  ready(function () {
    initColorInputs();
    initUploadForm();
    addSaveIndicators();
    exposeAPI();
  });
})();