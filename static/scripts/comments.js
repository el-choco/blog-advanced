// Comments System
var Comments = {
    
    // Initialize comments for a post
    init: function(postId) {
        this.postId = postId;
        this.load();
        this.bindFormSubmit();
    },
    
    // Load comments from server
    load: function() {
        var self = this;
        
        $.get({
            dataType: "json",
            url: "ajax.php",
            data: {
                action: "comment_get",
                post_id: self.postId
            },
            success: function(data) {
                if (data.error) {
                    console.error("Failed to load comments:", data.msg);
                    return;
                }
                
                self.render(data.comments);
                self.updateCount(data.count);
            },
            error: function() {
                console.error("Failed to load comments");
            }
        });
    },
    
    // Render comments
    render: function(comments) {
        var container = $('.comments-list[data-post-id="' + this.postId + '"]');
        
        if (!container.length) {
            container = $('.post[data-id="' + this.postId + '"]').find('.comments-list');
        }
        
        container.empty();
        
        if (comments.length === 0) {
            container.html('<p class="no-comments">No comments yet. Be the first to comment!</p>');
            return;
        }
        
        comments.forEach(function(comment) {
            var commentHtml = Comments.buildCommentHtml(comment);
            container.append(commentHtml);
        });
    },
    
    // Build HTML for a single comment
    buildCommentHtml: function(comment) {
        var statusBadge = '';
        var adminActions = '';
        var commentClass = 'comment';
        
        if (comment.status === 'pending') {
            statusBadge = '<span class="comment-status-badge pending">Pending Moderation</span>';
            commentClass += ' pending';
        } else if (comment.status === 'spam') {
            statusBadge = '<span class="comment-status-badge spam">Spam</span>';
            commentClass += ' spam';
        }
        
        // Admin actions (only if logged in)
        if (typeof login !== 'undefined' && login.is) {
            adminActions = '<div class="comment-admin-actions">';
            if (comment.status !== 'approved') {
                adminActions += '<button class="btn-approve" data-id="' + comment.id + '">Approve</button>';
            }
            adminActions += '<button class="btn-spam" data-id="' + comment.id + '">Spam</button>';
            adminActions += '<button class="btn-delete" data-id="' + comment.id + '">Delete</button>';
            adminActions += '</div>';
        }
        
        var date = new Date(comment.created_at);
        var formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        
        return '<div class="' + commentClass + '" data-id="' + comment.id + '">' +
                   '<div class="comment-header">' +
                       '<span class="comment-author">' + this.escapeHtml(comment.author_name) + '</span>' +
                       statusBadge +
                       '<span class="comment-date">' + formattedDate + '</span>' +
                   '</div>' +
                   '<div class="comment-content">' + this.escapeHtml(comment.content) + '</div>' +
                   adminActions +
               '</div>';
    },
    
    // Bind form submit
    bindFormSubmit: function() {
        var self = this;
        
        $('.comment-form[data-post-id="' + this.postId + '"]').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var data = {
                action: 'comment_add',
                post_id: self.postId,
                author_name: form.find('[name="author_name"]').val(),
                author_email: form.find('[name="author_email"]').val(),
                author_website: form.find('[name="author_website"]').val(),
                content: form.find('[name="content"]').val(),
                website_check: form.find('[name="website_check"]').val()
            };
            
            $.post({
                dataType: "json",
                url: "ajax.php",
                data: data,
                success: function(response) {
                    if (response.error) {
                        self.showStatus(form, 'error', response.msg);
                        return;
                    }
                    
                    self.showStatus(form, 'success', response.message);
                    form[0].reset();
                    
                    // Reload comments
                    setTimeout(function() {
                        self.load();
                    }, 1000);
                },
                error: function() {
                    self.showStatus(form, 'error', 'Failed to post comment. Please try again.');
                }
            });
        });
        
        // Bind admin actions
        this.bindAdminActions();
    },
    
    // Bind admin action buttons
    bindAdminActions: function() {
        var self = this;
        
        $(document).on('click', '.btn-approve', function() {
            var commentId = $(this).data('id');
            self.adminAction('comment_approve', commentId);
        });
        
        $(document).on('click', '.btn-spam', function() {
            var commentId = $(this).data('id');
            self.adminAction('comment_spam', commentId);
        });
        
        $(document).on('click', '.btn-delete', function() {
            if (confirm('Delete this comment?')) {
                var commentId = $(this).data('id');
                self.adminAction('comment_delete', commentId);
            }
        });
    },
    
    // Admin action
    adminAction: function(action, commentId) {
        var self = this;
        
        $.post({
            dataType: "json",
            url: "ajax.php",
            data: {
                action: action,
                id: commentId
            },
            success: function(response) {
                if (response.error) {
                    alert('Error: ' + response.msg);
                    return;
                }
                
                // Reload comments
                self.load();
            }
        });
    },
    
    // Show status message
    showStatus: function(form, type, message) {
        var status = form.find('.comment-status');
        status.removeClass('success error').addClass(type);
        status.text(message);
        status.fadeIn();
        
        setTimeout(function() {
            status.fadeOut();
        }, 5000);
    },
    
    // Update comment count
    updateCount: function(count) {
        $('.comments-section[data-post-id="' + this.postId + '"] .comment-count').text(count);
    },
    
    // Escape HTML
    escapeHtml: function(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
};
