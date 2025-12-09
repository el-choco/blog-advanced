/**
 * Theme Toggle Script
 * - Creates a self-healing theme toggle + label (Light/Dark)
 * - Switches between light/dark with localStorage persistence
 * - Respects admin override data attribute (data-theme-override="1")
 * - Places the elements to the right in #headline (fallback: fixed top-right)
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'blog-theme-mode';

  function getAdminOverride() {
    return document.documentElement.getAttribute('data-theme-override') === '1';
  }
  function getCurrentTheme() {
    return document.documentElement.getAttribute('data-theme') || 'light';
  }
  function setTheme(theme, persist) {
    document.documentElement.setAttribute('data-theme', theme);
    if (persist) {
      try { localStorage.setItem(STORAGE_KEY, theme); } catch (e) {}
    }
    updateUI(theme);
  }
  function resolveInitialTheme() {
    if (getAdminOverride()) return getCurrentTheme();
    try {
      var stored = localStorage.getItem(STORAGE_KEY);
      if (stored === 'light' || stored === 'dark') return stored;
    } catch (e) {}
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) return 'dark';
    return getCurrentTheme();
  }

  function ensureControls() {
    var host = document.getElementById('headline') || document.body;

    // Label
    var label = document.getElementById('themeToggleLabel');
    if (!label) {
      label = document.createElement('span');
      label.id = 'themeToggleLabel';
      label.className = 'theme-toggle-label';
      label.textContent = 'Light';
      if (host === document.body) {
        label.style.position = 'fixed';
        label.style.right = '86px'; // leave room for the pill
        label.style.top = '22px';
        label.style.zIndex = '9999';
      }
      host.appendChild(label);
    }

    // Toggle button
    var btn = document.getElementById('themeToggle');
    if (!btn) {
      btn = document.createElement('button');
      btn.id = 'themeToggle';
      btn.type = 'button';
      btn.className = 'theme-toggle';
      btn.setAttribute('aria-pressed', 'false');
      btn.setAttribute('aria-label', 'Toggle theme');
      btn.setAttribute('title', 'Toggle theme');
      var sr = document.createElement('span');
      sr.className = 'sr-only';
      sr.textContent = 'Toggle theme';
      btn.appendChild(sr);

      if (host === document.body) {
        btn.style.position = 'fixed';
        btn.style.right = '16px';
        btn.style.top = '16px';
        btn.style.zIndex = '9999';
      }
      host.appendChild(btn);
    }

    return { label: label, button: btn };
  }

  function updateUI(theme) {
    var btn = document.getElementById('themeToggle');
    var label = document.getElementById('themeToggleLabel');
    if (btn) btn.setAttribute('aria-pressed', String(theme === 'dark'));
    if (label) label.textContent = (theme === 'dark' ? 'Dark' : 'Light');
  }

  function init() {
    var controls = ensureControls();
    var theme = resolveInitialTheme();
    setTheme(theme, false);

    if (getAdminOverride()) {
      controls.button.classList.add('is-disabled');
      controls.button.disabled = true;
      controls.button.title = 'Set by admin';
      return;
    }

    controls.button.addEventListener('click', function () {
      var next = getCurrentTheme() === 'dark' ? 'light' : 'dark';
      setTheme(next, true);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  window.toggleTheme = function () {
    if (getAdminOverride()) return;
    var next = getCurrentTheme() === 'dark' ? 'light' : 'dark';
    setTheme(next, true);
  };
})();