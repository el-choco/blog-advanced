/**
 * Theme Toggle Script
 * Handles persistent dark/light theme switching with admin override support
 */
(function() {
    'use strict';

    const STORAGE_KEY = 'app_theme_mode';
    const ATTR_THEME = 'data-theme';
    const ATTR_OVERRIDE = 'data-theme-override';
    const ATTR_DEFAULT = 'data-theme-default';
    
    /**
     * Get the theme mode from various sources in priority order
     */
    function resolveTheme() {
        const body = document.body;
        const isOverride = body.getAttribute(ATTR_OVERRIDE) === '1';
        const defaultMode = body.getAttribute(ATTR_DEFAULT) || 'light';
        
        // If admin has enforced a mode, use that
        if (isOverride) {
            return defaultMode;
        }
        
        // Check localStorage for user preference
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored === 'light' || stored === 'dark') {
            return stored;
        }
        
        // Check system preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        
        // Fall back to config default
        return defaultMode;
    }
    
    /**
     * Apply theme to the document
     */
    function applyTheme(mode) {
        document.body.setAttribute(ATTR_THEME, mode);
        
        // Update toggle button state
        const toggleBtn = document.getElementById('themeToggle');
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-pressed', mode === 'dark' ? 'true' : 'false');
            toggleBtn.setAttribute('aria-label', mode === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
            // Update button icon
            toggleBtn.innerHTML = mode === 'dark' ? '☀️' : '🌙';
        }
    }
    
    /**
     * Toggle between light and dark mode
     */
    function toggleTheme() {
        const body = document.body;
        const isOverride = body.getAttribute(ATTR_OVERRIDE) === '1';
        
        // Don't allow toggle if admin has enforced a mode
        if (isOverride) {
            return;
        }
        
        const currentMode = body.getAttribute(ATTR_THEME) || 'light';
        const newMode = currentMode === 'light' ? 'dark' : 'light';
        
        // Save to localStorage
        localStorage.setItem(STORAGE_KEY, newMode);
        
        // Apply the new theme
        applyTheme(newMode);
    }
    
    /**
     * Initialize theme on page load
     */
    function initTheme() {
        const mode = resolveTheme();
        applyTheme(mode);
        
        // Set up toggle button listener
        const toggleBtn = document.getElementById('themeToggle');
        if (toggleBtn) {
            const isOverride = document.body.getAttribute(ATTR_OVERRIDE) === '1';
            
            // Disable button if override is active
            if (isOverride) {
                toggleBtn.disabled = true;
                toggleBtn.style.opacity = '0.5';
                toggleBtn.style.cursor = 'not-allowed';
                toggleBtn.title = 'Theme is locked by administrator';
            } else {
                toggleBtn.addEventListener('click', toggleTheme);
            }
        }
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTheme);
    } else {
        initTheme();
    }
})();
