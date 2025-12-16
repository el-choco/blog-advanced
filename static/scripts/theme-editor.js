(function(){
  'use strict';

  function $(sel, root){ return (root||document).querySelector(sel); }
  function $all(sel, root){ return Array.prototype.slice.call((root||document).querySelectorAll(sel)); }

  // iFrame helpers
  function getPreviewFrame(){ return $('#te_preview'); }
  function getPreviewDoc(){
    var f = getPreviewFrame();
    try { return f && f.contentWindow && f.contentWindow.document; } catch(e){ return null; }
  }

  // Find theme link in iframe (theme*.css)
  function findThemeLink(doc){
    if (!doc) return null;
    var links = doc.querySelectorAll('link[rel="stylesheet"]');
    var found = null;
    links.forEach(function(link){
      var href = link.getAttribute('href') || '';
      if (/\/static\/styles\/theme[a-z0-9_\-]*\.css(\?|$)/i.test(href)) {
        found = link;
      }
    });
    return found;
  }

  // Swap theme css in preview
  function loadThemeInPreview(filename){
    var doc = getPreviewDoc();
    if (!doc) return;
    var link = findThemeLink(doc);
    if (!link) { console.warn('[Theme-Editor] Kein Theme-Link im iFrame gefunden.'); return; }
    var newHref = '../static/styles/' + String(filename).replace(/[^a-zA-Z0-9_.\-]/g,'');
    newHref += (newHref.indexOf('?')>-1 ? '&' : '?') + 'v=' + Date.now();
    link.setAttribute('href', newHref);
  }

  // Live apply variables (only preview)
  function applyVariablesPreview(map){
    var doc = getPreviewDoc();
    if (!doc) return;
    var styleId = 'te-vars-preview';
    var style = doc.getElementById(styleId);
    if (!style) { style = doc.createElement('style'); style.id = styleId; doc.head.appendChild(style); }
    var lines = [];
    Object.keys(map).forEach(function(k){ lines.push('  ' + k + ': ' + map[k] + ';'); });
    style.textContent = ':root {\n' + lines.join('\n') + '\n}';
  }

  function collectVarsFromForm(){
    var map = {};
    $all('.te-text').forEach(function(inp){
      var v = inp.getAttribute('data-var');
      var val = inp.value.trim();
      if (v && val) map[v] = val;
    });
    return map;
  }

  function bind(){
    var sel = $('#te_theme_select');
    var btnPreview = $('#btn_preview_theme');
    var btnReload = $('#btn_reload_preview');
    var btnApplyAll = $('#btn_apply_all');

    if (btnPreview && sel) {
      btnPreview.addEventListener('click', function(e){
        e.preventDefault();
        var file = sel.value || '';
        if (!file) return;
        loadThemeInPreview(file);
      });
    }
    if (btnReload) {
      btnReload.addEventListener('click', function(e){
        e.preventDefault();
        var f = getPreviewFrame();
        if (f) f.src = f.src.replace(/([?&])v=\d+/, '$1v=' + Date.now());
      });
    }

    // Einzelne Farbfelder
    $all('.te-apply').forEach(function(btn){
      btn.addEventListener('click', function(e){
        e.preventDefault();
        var name = btn.getAttribute('data-var');
        var text = $('.te-text[data-var="'+name+'"]');
        if (!text) return;
        var map = {}; map[name] = text.value.trim();
        applyVariablesPreview(map);
      });
    });

    // Alle Farbfelder
    if (btnApplyAll) {
      btnApplyAll.addEventListener('click', function(e){
        e.preventDefault();
        applyVariablesPreview(collectVarsFromForm());
      });
    }

    // Color-Picker koppeln
    $all('.te-color').forEach(function(col){
      col.addEventListener('input', function(){
        var v = col.getAttribute('data-var');
        var text = $('.te-text[data-var="'+v+'"]');
        if (text) text.value = col.value;
      });
    });

    // Galerie
    $all('.g-preview').forEach(function(b){
      b.addEventListener('click', function(e){
        e.preventDefault();
        var theme = b.getAttribute('data-theme');
        if (theme) loadThemeInPreview(theme);
      });
    });

    // Custom CSS nur Vorschau
    var btnPreviewCss = $('#btn_preview_custom_css');
    if (btnPreviewCss) {
      btnPreviewCss.addEventListener('click', function(e){
        e.preventDefault();
        var ta = $('textarea[name="custom_css"]');
        var doc = getPreviewDoc();
        if (!ta || !doc) return;
        var styleId = 'te-custom-preview';
        var style = doc.getElementById(styleId);
        if (!style) { style = doc.createElement('style'); style.id = styleId; doc.head.appendChild(style); }
        style.textContent = ta.value || '';
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bind);
  } else {
    bind();
  }
})();
