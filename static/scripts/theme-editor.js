(function(){
  function ready(fn){
    if(document.readyState!=='loading'){ fn(); }
    else { document.addEventListener('DOMContentLoaded', fn); }
  }
  function cacheBust(url){
    var sep = url.indexOf('?')===-1 ? '?' : '&';
    return url + sep + 'v=' + Date.now();
  }
  function loadTheme(file){
    try{
      var iframe = document.getElementById('previewFrame');
      if(!iframe) return;
      var doc = iframe.contentDocument || iframe.contentWindow.document;
      if(!doc) return;
      var links = doc.querySelectorAll('link[rel="stylesheet"]');
      links.forEach(function(l){
        if(/\/static\/styles\/theme[^.]*\.css$/i.test(l.href)){
          l.href = cacheBust(l.href.replace(/theme[^.]*\.css$/i, file));
        }
      });
    }catch(e){ console.warn('Theme preview failed', e); }
  }
  function reloadPreview(){
    try{
      var iframe = document.getElementById('previewFrame');
      if(!iframe) return;
      var win = iframe.contentWindow;
      var doc = iframe.contentDocument || win.document;
      if(doc){
        var custom = doc.querySelector('link[href*="/static/styles/custom1.css"]');
        if(custom){ custom.href = cacheBust(custom.href.replace(/(\?v=\d+|&v=\d+)$/,'')); }
        var url = iframe.src.replace(/(\?pv=\d+|&pv=\d+)$/,'');
        iframe.src = cacheBust(url);
      }
    }catch(e){ console.warn('Preview reload failed', e); }
  }
  ready(function(){
    window.ThemeEditorPreview = { loadTheme: loadTheme, reloadPreview: reloadPreview };
  });
})();
