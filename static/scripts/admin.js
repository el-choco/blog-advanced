/**
 * ============================================
 * ADMIN INLINE EDITOR WITH LIVE PREVIEW
 * ============================================
 */

(function() {
    'use strict';

    // Get CSRF Token from cookie or meta tag
    function getCsrfToken() {
        // Try to get from cookie
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === 'csrf_token') {
                return decodeURIComponent(value);
            }
        }
        
        // Try to get from meta tag
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            return metaTag.getAttribute('content');
        }
        
        // Try to get from localStorage
        return localStorage.getItem('csrf_token') || '';
    }

    // Open Inline Editor
    window.openInlineEditor = function(postId) {
        console.log('🔧 Opening editor for post:', postId);
        
        // Hide all other editors
        document.querySelectorAll('.inline-editor-row').forEach(row => {
            row.style.display = 'none';
        });

        // Show this editor
        const editorRow = document.getElementById('inline-editor-' + postId);
        if (!editorRow) {
            console.error('❌ Editor row not found:', 'inline-editor-' + postId);
            return;
        }

        editorRow.style.display = 'table-row';

        // Load post content via AJAX
        $.ajax({
            url: '../ajax.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'edit_data',  // ✅ KORRIGIERT von 'do' zu 'action'
                id: postId,
                csrf_token: getCsrfToken()
            },
            success: function(response) {
                console.log('✅ AJAX Response:', response);
                
                if (response && response.plain_text !== undefined) {
                    const textarea = document.getElementById('editor-' + postId);
                    if (textarea) {
                        textarea.value = response.plain_text;
                        updatePreview(postId);
                        console.log('✅ Post loaded into editor');
                    } else {
                        console.error('❌ Textarea not found:', 'editor-' + postId);
                    }
                } else {
                    console.error('❌ Keine plain_text Daten:', response);
                    alert('Fehler: Post-Daten konnten nicht geladen werden');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ AJAX Error:', xhr.responseText);
                alert('Fehler beim Laden des Beitrags!\n\n' + xhr.responseText);
            }
        });

        // Scroll to editor
        setTimeout(() => {
            editorRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
    };

    // Close Inline Editor
    window.closeInlineEditor = function(postId) {
        const editorRow = document.getElementById('inline-editor-' + postId);
        if (editorRow) {
            editorRow.style.display = 'none';
        }
    };

    // Save Post
    window.saveInlinePost = function(postId) {
        const textarea = document.getElementById('editor-' + postId);
        if (!textarea) return;

        const content = textarea.value;

        console.log('💾 Saving post:', postId);

        // Save via AJAX
        $.ajax({
            url: '../ajax.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'update',  // ✅ KORRIGIERT von 'do' zu 'action'
                id: postId,
                text: content,
                csrf_token: getCsrfToken()
            },
            success: function(response) {
                console.log('✅ Save Response:', response);
                
                if (response && !response.error) {
                    alert('✅ Beitrag gespeichert!');
                    closeInlineEditor(postId);
                    // Reload page to show updated content
                    location.reload();
                } else {
                    alert('❌ Fehler beim Speichern: ' + (response.msg || 'Unbekannter Fehler'));
                }
            },
            error: function(xhr) {
                console.error('❌ Save Error:', xhr.responseText);
                alert('❌ Netzwerkfehler beim Speichern!\n\n' + xhr.responseText);
            }
        });
    };

    // Update Live Preview
    function updatePreview(postId) {
        const textarea = document.getElementById('editor-' + postId);
        const preview = document.getElementById('preview-' + postId);
        
        if (!textarea || !preview) return;

        const content = textarea.value;
        
        // Simple Markdown to HTML conversion
        let html = content;

        // Headers
        html = html.replace(/^### (.+)$/gm, '<h3>$1</h3>');
        html = html.replace(/^## (.+)$/gm, '<h2>$1</h2>');
        html = html.replace(/^# (.+)$/gm, '<h1>$1</h1>');

        // Bold
        html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');

        // Italic
        html = html.replace(/\*(.+?)\*/g, '<em>$1</em>');

        // Code
        html = html.replace(/`(.+?)`/g, '<code>$1</code>');

        // Links
        html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank">$1</a>');

        // Images
        html = html.replace(/!\[([^\]]*)\]\(([^)]+)\)/g, '<img src="$2" alt="$1" style="max-width:100%;">');

        // Line breaks
        html = html.replace(/\n/g, '<br>');

        preview.innerHTML = html;
    }

    // Event Listeners
    document.addEventListener('DOMContentLoaded', function() {
        
        console.log('🚀 Initializing Admin Inline Editor...');
        
        // Inline Edit Button Click
        const editButtons = document.querySelectorAll('.inline-edit-btn');
        console.log('📝 Found edit buttons:', editButtons.length);
        
        editButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const postId = this.getAttribute('data-post-id');
                console.log('🖱️ Edit button clicked for post:', postId);
                openInlineEditor(postId);
            });
        });

        // Close Editor Button
        document.querySelectorAll('.close-editor').forEach(btn => {
            btn.addEventListener('click', function() {
                const postId = this.getAttribute('data-post-id');
                closeInlineEditor(postId);
            });
        });

        // Live Preview Update
        document.querySelectorAll('.inline-editor-textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                const postId = this.id.replace('editor-', '');
                updatePreview(postId);
            });
        });

        // Toolbar Buttons
        document.querySelectorAll('.toolbar-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const action = this.getAttribute('data-action');
                const textarea = this.closest('.inline-editor-body').querySelector('textarea');
                
                if (!textarea) return;

                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const selectedText = textarea.value.substring(start, end);
                let before = '', after = '';

                switch(action) {
                    case 'bold':
                        before = '**';
                        after = '**';
                        break;
                    case 'italic':
                        before = '*';
                        after = '*';
                        break;
                    case 'code':
                        before = '`';
                        after = '`';
                        break;
                    case 'link':
                        const url = prompt('URL eingeben:', 'https://');
                        if (url) {
                            before = '[';
                            after = '](' + url + ')';
                        }
                        break;
                    case 'image':
                        const imgUrl = prompt('Bild-URL eingeben:', 'https://');
                        if (imgUrl) {
                            before = '![Alt-Text](';
                            after = imgUrl + ')';
                        }
                        break;
                }

                if (before || after) {
                    const newText = textarea.value.substring(0, start) + before + selectedText + after + textarea.value.substring(end);
                    textarea.value = newText;
                    textarea.focus();
                    textarea.setSelectionRange(start + before.length, start + before.length + selectedText.length);
                    
                    // Update preview
                    const postId = textarea.id.replace('editor-', '');
                    updatePreview(postId);
                }
            });
        });

        console.log('✅ Admin Inline Editor initialized');
    });

})();