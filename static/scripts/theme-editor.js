/**
 * Theme Editor Client-Side Script
 * Provides color picker integration and live preview functionality
 */

(function() {
    'use strict';

    // Initialize color inputs with native color picker support
    function initColorInputs() {
        const colorInputs = document.querySelectorAll('.color-input');
        
        colorInputs.forEach(input => {
            // Create a wrapper for text input + color picker
            const wrapper = document.createElement('div');
            wrapper.style.display = 'flex';
            wrapper.style.gap = '8px';
            
            // Clone the original input
            const textInput = input.cloneNode(true);
            textInput.style.flex = '1';
            
            // Create color picker
            const colorPicker = document.createElement('input');
            colorPicker.type = 'color';
            colorPicker.style.width = '50px';
            colorPicker.style.height = '34px';
            colorPicker.style.border = '1px solid #d0d7de';
            colorPicker.style.borderRadius = '6px';
            colorPicker.style.cursor = 'pointer';
            
            // Try to parse color value
            const currentValue = input.value.trim();
            if (isValidColor(currentValue)) {
                colorPicker.value = normalizeColor(currentValue);
            }
            
            // Sync color picker -> text input
            colorPicker.addEventListener('input', function() {
                textInput.value = this.value;
            });
            
            // Sync text input -> color picker
            textInput.addEventListener('input', function() {
                const val = this.value.trim();
                if (isValidColor(val)) {
                    colorPicker.value = normalizeColor(val);
                }
            });
            
            // Replace original input with wrapper
            input.parentNode.replaceChild(wrapper, input);
            wrapper.appendChild(textInput);
            wrapper.appendChild(colorPicker);
        });
    }

    // Check if a string is a valid color
    function isValidColor(color) {
        if (!color) return false;
        // Hex colors
        if (/^#[0-9A-Fa-f]{3,6}$/.test(color)) return true;
        // RGB/RGBA
        if (/^rgba?\(/.test(color)) return false; // Color picker only supports hex
        return false;
    }

    // Normalize color to hex format for color input
    function normalizeColor(color) {
        color = color.trim();
        
        // Already a hex color
        if (/^#[0-9A-Fa-f]{6}$/.test(color)) {
            return color;
        }
        
        // Short hex (#abc -> #aabbcc)
        if (/^#[0-9A-Fa-f]{3}$/.test(color)) {
            return '#' + color[1] + color[1] + color[2] + color[2] + color[3] + color[3];
        }
        
        return '#000000'; // fallback
    }

    // Add confirmation for replace mode
    function initUploadForm() {
        const uploadForm = document.querySelector('form[action=""][enctype="multipart/form-data"]');
        if (!uploadForm) return;
        
        uploadForm.addEventListener('submit', function(e) {
            const modeSelect = uploadForm.querySelector('select[name="upload_mode"]');
            if (modeSelect && modeSelect.value === 'replace') {
                if (!confirm('Achtung: Diese Aktion ersetzt alle bestehenden CSS-Regeln. Fortfahren?')) {
                    e.preventDefault();
                }
            }
        });
    }

    // Auto-save indicator (optional enhancement)
    function addSaveIndicators() {
        const forms = document.querySelectorAll('form[method="POST"]');
        
        forms.forEach(form => {
            const submitBtn = form.querySelector('button[type="submit"], button:not([type])');
            if (!submitBtn) return;
            
            form.addEventListener('submit', function() {
                submitBtn.disabled = true;
                submitBtn.textContent = submitBtn.textContent.replace(/💾|📤/, '⏳');
                
                // Re-enable after a delay (in case of page reload failure)
                setTimeout(() => {
                    submitBtn.disabled = false;
                }, 3000);
            });
        });
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initColorInputs();
            initUploadForm();
            addSaveIndicators();
        });
    } else {
        initColorInputs();
        initUploadForm();
        addSaveIndicators();
    }

})();
