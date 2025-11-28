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

    /*** Initialize comments for a specific post */
    init: function(postId) {
        console.log('💬 Initializing Comments for post', postId);
        this.postId = postId;
        this.container = $('.comments-section[data-post-id="' + postId + '"]');
        this.form = this.container.find('.comment-form');
        this.commentsList = this.container.find('.comments-list');

        // Get CSRF token from meta tag or data attribute
        this.csrfToken = $('meta[name="csrf-token"]').attr('content') || 
                        this.container.data('csrf-token') ||
                        window.csrfToken;

        console.log('🔐 CSRF Token:', this.csrfToken ?  'Found' : 'MISSING! ');

        if (!this.container.length) {
            console. error('❌ Comments container not found for post', postId);
            return;
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
        this. form.on('submit', function(e) {
            e.preventDefault();
            self.submitComment();
        });
    },

    /**
     * Submit a new comment
     */
    submitComment: function() {
        var self = this;
        var formData = {
            action: 'comment_add',
            post_id: this.postId,
            author_name: this.form.find('[name="author_name"]').val(),
            content: this.form.find('[name="content"]').val(),
            website_check: this.form.find('[name="website_check"]').val()
        };

        console.log('📤 Submitting comment:', formData);

        $.ajax({
            url: 'ajax. php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            headers: {
                'Csrf-Token': self.csrfToken
            },
            success: function(response) {
                console.log('📥 Comment response:', response);

                if (response.error) {
                    self.showStatus('error', response.msg || LANG.errorPosting);
                } else {
                    self.showStatus('success', response.message || LANG.commentSuccess);
                    self.form[0].reset();

                    // Reload comments after short delay
                    setTimeout(function() {
                        self.loadComments();
                    }, 500);
                }
            },
            error: function(xhr, status, error) {
                console. error('❌ AJAX error:', status, error);
                console.error('Response:', xhr.responseText);
                self.showStatus('error', LANG.commentFailed);
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
            url: 'ajax.php',
            method: 'GET',
            data: {
                action: 'comment_get',
                post_id: this. postId
            },
            dataType: 'json',
            success: function(response) {
                console.log('📦 Received comments:', response);

                if (response.error) {
                    console.error('❌ Error loading comments:', response.msg);
                } else {
                    self.renderComments(response.comments || []);
                    self.updateCount(response.count || 0);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Failed to load comments:', status, error);
            }
        });
    },

    /**
     * Render comments list
     */
    renderComments: function(comments) {
        console.log('🎨 Rendering', comments.length, 'comments');

        this.commentsList.empty();

        if (comments.length === 0) {
            this.commentsList.html('<p class="no-comments">' + LANG.noComments + '</p>');
            return;
        }

        comments.forEach(function(comment) {
            var isPending = comment.status === 'pending';
            var statusBadge = isPending ? '<span class="comment-status-badge pending">' + LANG.waitingApproval + '</span>' : '';

            var commentHTML = `
                <div class="comment ${isPending ? 'pending' : ''}" data-comment-id="${comment.id}">
                    <div class="comment-header">
                        <span class="comment-author">${this.escapeHtml(comment.author_name)}</span>
                        ${statusBadge}
                        <span class="comment-date">${this.formatDate(comment.created_at)}</span>
                    </div>
                    <div class="comment-content">${this.escapeHtml(comment.content)}</div>
                </div>
            `;

            this.commentsList.append(commentHTML);
        }. bind(this));
    },

    /**
     * Update comment count
     */
    updateCount: function(count) {
        this.container.find('.comment-count').text(count);
        console.log('📊 Updated count to', count);
    },

    /**
     * Show status message (success or error)
     */
    showStatus: function(type, message) {
        var statusDiv = this.form.find('.comment-status');
        statusDiv.removeClass('success error'). addClass(type). text(message). show();

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
            return LANG. secondsAgo.replace('{0}', diff);
        }
        if (diff < 3600) {
            return LANG. minutesAgo.replace('{0}', Math.floor(diff / 60));
        }
        if (diff < 86400) {
            return LANG. hoursAgo.replace('{0}', Math.floor(diff / 3600));
        }

        // Fallback to formatted date
        return date.toLocaleDateString('de-DE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml: function(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};