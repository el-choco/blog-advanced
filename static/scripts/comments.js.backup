// Comments System
var Comments = {
    postId: null,
    container: null,
    form: null,
    commentsList: null,
    
    init: function(postId) {
        console.log('💬 Initializing Comments for post', postId);
        this.postId = postId;
        this.container = $('.comments-section[data-post-id="' + postId + '"]');
        this.form = this.container.find('.comment-form');
        this.commentsList = this.container.find('.comments-list');
        
        if (!this.container.length) {
            console.error('❌ Comments container not found for post', postId);
            return;
        }
        
        // Bind events
        this.bindFormSubmit();
        
        // Load existing comments
        this.loadComments();
        
        console.log('✅ Comments initialized for post', postId);
    },
    
    bindFormSubmit: function() {
        var self = this;
        this.form.on('submit', function(e) {
            e.preventDefault();
            self.submitComment();
        });
    },
    
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
            url: 'ajax.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('📥 Comment response:', response);
                
                if (response.error) {
                    self.showStatus('error', response.msg || 'Error posting comment');
                } else {
                    self.showStatus('success', response.message || 'Comment posted successfully!');
                    self.form[0].reset();
                    
                    // Reload comments
                    setTimeout(function() {
                        self.loadComments();
                    }, 500);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ AJAX error:', status, error);
                self.showStatus('error', 'Failed to post comment. Please try again.');
            }
        });
    },
    
    loadComments: function() {
        var self = this;
        
        console.log('📥 Loading comments for post', this.postId);
        
        $.ajax({
            url: 'ajax.php',
            method: 'GET',
            data: {
                action: 'comment_get',
                post_id: this.postId
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
    
    renderComments: function(comments) {
        console.log('🎨 Rendering', comments.length, 'comments');
        
        this.commentsList.empty();
        
        if (comments.length === 0) {
            this.commentsList.html('<p class="no-comments">Noch keine Kommentare. Sei der Erste!</p>');
            return;
        }
        
        comments.forEach(function(comment) {
            var isPending = comment.status === 'pending';
            var statusBadge = isPending ? '<span class="comment-status-badge pending">Wartet auf Freigabe</span>' : '';
            
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
        }.bind(this));
    },
    
    updateCount: function(count) {
        this.container.find('.comment-count').text(count);
        console.log('📊 Updated count to', count);
    },
    
    showStatus: function(type, message) {
        var statusDiv = this.form.find('.comment-status');
        statusDiv.removeClass('success error').addClass(type).text(message).show();
        
        setTimeout(function() {
            statusDiv.fadeOut();
        }, 5000);
    },
    
    formatDate: function(dateString) {
        var date = new Date(dateString);
        var now = new Date();
        var diff = Math.floor((now - date) / 1000);
        
        if (diff < 60) return 'vor ' + diff + ' Sekunden';
        if (diff < 3600) return 'vor ' + Math.floor(diff / 60) + ' Minuten';
        if (diff < 86400) return 'vor ' + Math.floor(diff / 3600) + ' Stunden';
        
        return date.toLocaleDateString('de-DE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    escapeHtml: function(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};
