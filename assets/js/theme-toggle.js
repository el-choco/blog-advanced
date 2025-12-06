/**
 * Theme Toggle Script
 * Handles dark/light theme switching with localStorage persistence
 * Respects admin override setting from server
 */
(function() {
    'use strict';

    const STORAGE_KEY = 'blog-theme-mode';
    
    /**
     * Get the current theme mode from data attribute
     */
    function getCurrentTheme() {
        return document.documentElement.getAttribute('data-theme') || 'light';
    }

    /**
     * Get admin override setting from data attribute
     */
    function getAdminOverride() {
        return document.documentElement.getAttribute('data-theme-override') === '1';
    }

    /**
     * Apply theme to document
     */
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
    }

    /**
     * Toggle between light and dark themes
     */
    function toggleTheme() {
        const adminOverride = getAdminOverride();
        
        // If admin has enabled override, don't allow client toggle
        if (adminOverride) {
            console.log('Theme toggle disabled by admin override');
            return;
        }

        const currentTheme = getCurrentTheme();
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        // Save to localStorage
        try {
            localStorage.setItem(STORAGE_KEY, newTheme);
        } catch (e) {
            console.warn('Could not save theme preference:', e);
        }
        
        // Apply theme
        applyTheme(newTheme);
        
        // Update toggle button if it exists
        updateToggleButton(newTheme);
    }

    /**
     * Update toggle button state
     */
    function updateToggleButton(theme) {
        const toggleBtn = document.getElementById('theme-toggle-btn');
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
            toggleBtn.setAttribute('title', theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode');
        }
    }

    /**
     * Initialize theme on page load
     */
    function initTheme() {
        const adminOverride = getAdminOverride();
        let theme = getCurrentTheme();

        // If no admin override, check localStorage for user preference
        if (!adminOverride) {
            try {
                const savedTheme = localStorage.getItem(STORAGE_KEY);
                if (savedTheme && (savedTheme === 'dark' || savedTheme === 'light')) {
                    theme = savedTheme;
                }
            } catch (e) {
                console.warn('Could not read theme preference:', e);
            }
        }

        // Apply the determined theme
        applyTheme(theme);
        updateToggleButton(theme);
    }

    /**
     * Create and add theme toggle button
     */
    function createToggleButton() {
        // Only create if it doesn't exist
        if (document.getElementById('theme-toggle-btn')) {
            return;
        }

        const toggleBtn = document.createElement('button');
        toggleBtn.id = 'theme-toggle-btn';
        toggleBtn.className = 'theme-toggle-button';
        toggleBtn.setAttribute('aria-label', 'Toggle theme');
        toggleBtn.innerHTML = '🌓';
        
        toggleBtn.addEventListener('click', toggleTheme);
        
        // Add to page (you can customize the position)
        document.body.appendChild(toggleBtn);
        
        // Basic styling if not in CSS
        if (!document.querySelector('style[data-theme-toggle]')) {
            const style = document.createElement('style');
            style.setAttribute('data-theme-toggle', 'true');
            style.textContent = `
                .theme-toggle-button {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    width: 44px;
                    height: 44px;
                    border-radius: 50%;
                    border: 2px solid var(--primary-border, #ccc);
                    background: var(--primary-background, #fff);
                    color: var(--primary-text, #000);
                    font-size: 20px;
                    cursor: pointer;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
                    z-index: 9998;
                    transition: all 0.2s ease;
                }
                .theme-toggle-button:hover {
                    transform: scale(1.1);
                    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                }
            `;
            document.head.appendChild(style);
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initTheme();
            createToggleButton();
        });
    } else {
        initTheme();
        createToggleButton();
    }

    // Expose toggle function globally for manual triggers
    window.toggleTheme = toggleTheme;
})();
