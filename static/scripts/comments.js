/**
 * Comments System
 * Handles comment submission, loading, and rendering
 */
var Comments = {
    postId: null,
    container: null,
    form: null,
    commentsList: null,
    csrfToken: null,

    /** Initialize comments for a specific post */
    init: function(postId) {
        console.log('💬 Initializing Comments for post', postId);

        this.postId = postId;
        this.container = $('.comments-section[data-post-id="' + postId + '"]');

        if (!this.container.length) {
            console.error('❌ Comments container not found for post', postId);
            return;
        }

        // Cache form and list after container check
        this.form = this.container.find('.comment-form');
        this.commentsList = this.container.find('.comments-list');

        // CSRF token from meta/data/global
        this.csrfToken =
            $('meta[name="csrf-token"]').attr('content') ||
            this.container.data('csrf-token') ||
            window.csrfToken ||
            '';

        if (!this.csrfToken) {
            console.warn('⚠️ CSRF token missing; backend may reject requests.');
        }

        // Bind events
        this.bindFormSubmit();

        // Load existing comments
        this.loadComments();

        console.log('✅ Comments initialized for post', postId);
    },

    /**
     * Bind form submit event
     */
    bindFormSubmit: function() {
        var self = this;
        if (!this.form || !this.form.length) {
            console.warn('⚠️ Comment form not found for post', this.postId);
            return;
        }
        this.form.on('submit', function(e) {
            e.preventDefault();
            self.submitComment();
        });
    },

    /**
     * Submit a new comment
     */
    submitComment: function() {
        var self = this;

        // Backend expects: name + text
        var formData = {
            action: 'comment_add',
            post_id: this.postId,
            name: this.form.find('[name="author_name"], [name="name"]').val(),
            text: this.form.find('[name="content"], [name="text"]').val(),
            website_check: this.form.find('[name="website_check"]').val() || '',
            csrf_token: this.csrfToken
        };

        console.log('📤 Submitting comment:', formData);

        $.ajax({
            url: '/ajax.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            headers: { 'Csrf-Token': self.csrfToken },
            success: function(response) {
                console.log('📥 Comment response:', response);

                if (response && response.error) {
                    self.showStatus('error', response.msg || (window.LANG && LANG.errorPosting) || 'Fehler beim Posten');
                } else {
                    self.showStatus('success', (response && (response.message || response.msg)) || (window.LANG && LANG.commentSuccess) || 'Kommentar gepostet!');
                    if (self.form && self.form[0]) self.form[0].reset();
                    setTimeout(function(){ self.loadComments(); }, 500);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ AJAX error:', status, error);
                console.error('Response:', xhr && xhr.responseText);
                self.showStatus('error', (window.LANG && LANG.commentFailed) || 'Fehler beim Posten. Bitte nochmal versuchen.');
            }
        });
    },

    /**
     * Load comments from server
     */
    loadComments: function() {
        var self = this;

        console.log('📥 Loading comments for post', this.postId);

        $.ajax({
            url: '/ajax.php',
            method: 'GET',
            data: {
                action: 'comment_get',
                post_id: this.postId
            },
            dataType: 'json',
            success: function(response) {
                console.log('📦 Received comments:', response);

                if (response && response.error) {
                    console.error('❌ Error loading comments:', response.msg);
                    self.renderComments([]);
                    self.updateCount(0);
                } else {
                    var list = (response && response.comments) || [];
                    var count = (response && (response.count != null ? response.count : list.length)) || 0;
                    self.renderComments(list);
                    self.updateCount(count);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Failed to load comments:', status, error);
                self.renderComments([]);
                self.updateCount(0);
            }
        });
    },

    /**
     * Render comments list
     */
    renderComments: function(comments) {
        console.log('🎨 Rendering', (comments || []).length, 'comments');

        if (!this.commentsList || !this.commentsList.length) return;

        this.commentsList.empty();

        if (!comments || comments.length === 0) {
            this.commentsList.html('<p class="no-comments">' + ((window.LANG && LANG.noComments) || 'Noch keine Kommentare. Sei der Erste!') + '</p>');
            return;
        }

        var self = this;
        comments.forEach(function(comment) {
            var isPending = comment.status === 'pending';
            var statusBadge = isPending ? '<span class="comment-status-badge pending">' + ((window.LANG && LANG.waitingApproval) || 'Wartet auf Freigabe') + '</span>' : '';

            var author = self.escapeHtml(comment.name || comment.author_name || '');
            var content = self.escapeHtml(comment.text || comment.content || '');
            var created = self.formatDate(comment.created_at || new Date().toISOString());

            var commentHTML =
                '<div class="comment ' + (isPending ? 'pending' : '') + '" data-comment-id="' + (comment.id || '') + '">' +
                    '<div class="comment-header">' +
                        '<span class="comment-author">' + author + '</span>' +
                        statusBadge +
                        '<span class="comment-date">' + created + '</span>' +
                    '</div>' +
                    '<div class="comment-content">' + content + '</div>' +
                '</div>';

            self.commentsList.append(commentHTML);
        });
    },

    /**
     * Update comment count
     */
    updateCount: function(count) {
        if (!this.container || !this.container.length) return;
        this.container.find('.comment-count').text(count);
        console.log('📊 Updated count to', count);
    },

    /**
     * Show status message (success or error)
     */
    showStatus: function(type, message) {
        var statusDiv = this.form && this.form.length ? this.form.find('.comment-status') : $();
        if (!statusDiv.length) return;

        statusDiv.removeClass('success error').addClass(type).text(message).show();

        setTimeout(function() {
            statusDiv.fadeOut();
        }, 5000);
    },

    /**
     * Format date relative to now (e.g., "2 hours ago")
     */
    formatDate: function(dateString) {
        var date = new Date(dateString);
        var now = new Date();
        var diff = Math.floor((now - date) / 1000); // seconds

        if (diff < 60) {
            return ((window.LANG && LANG.secondsAgo) || 'vor {0} Sekunden').replace('{0}', diff);
        }
        if (diff < 3600) {
            return ((window.LANG && LANG.minutesAgo) || 'vor {0} Minuten').replace('{0}', Math.floor(diff / 60));
        }
        if (diff < 86400) {
            return ((window.LANG && LANG.hoursAgo) || 'vor {0} Stunden').replace('{0}', Math.floor(diff / 3600));
        }

        // Fallback to formatted date
        try {
            return date.toLocaleDateString('de-DE', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch(e) {
            return date.toISOString();
        }
    },

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml: function(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }
};