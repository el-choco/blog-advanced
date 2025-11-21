// Initialize comments for all visible posts
(function() {
    'use strict';
    
    // Wait for posts to be loaded
    if (typeof posts !== 'undefined') {
        // Hook into post load
        var originalLoad = posts.load;
        posts.load = function() {
            originalLoad.call(this);
            
            // Initialize comments after posts are loaded
            setTimeout(function() {
                initCommentsForPosts();
            }, 500);
        };
    }
    
    // Initialize comments for all posts on page
    function initCommentsForPosts() {
        $('.b_post.post_row').each(function() {
            var post = $(this);
            var postId = post.attr('data-id');
            
            if (!postId) return;
            
            // Check if comments already added
            if (post.find('.comments-section').length > 0) return;
            
            // Add comments section
            var commentsHtml = `
                <div class="comments-section" data-post-id="${postId}">
                    <h3 class="comments-title">
                        <span class="comment-count">0</span> Comments
                    </h3>
                    
                    <div class="comments-list"></div>
                    
                    <div class="comment-form-wrapper">
                        <h4>Leave a Comment</h4>
                        <form class="comment-form" data-post-id="${postId}">
                            <input type="text" name="website_check" style="display:none;" tabindex="-1" autocomplete="off">
                            
                            <div class="form-group">
                                <label for="author_name_${postId}">Name *</label>
                                <input type="text" id="author_name_${postId}" name="author_name" required maxlength="100" placeholder="Your name">
                            </div>
                            
                            <div class="form-group">
                                <label for="author_email_${postId}">Email (optional)</label>
                                <input type="email" id="author_email_${postId}" name="author_email" maxlength="100" placeholder="your@email.com">
                            </div>
                            
                            <div class="form-group">
                                <label for="content_${postId}">Comment *</label>
                                <textarea id="content_${postId}" name="content" required rows="4" maxlength="5000" placeholder="Write your comment..."></textarea>
                            </div>
                            
                            <button type="submit" class="button blue">Post Comment</button>
                            <div class="comment-status"></div>
                        </form>
                    </div>
                </div>
            `;
            
            post.append(commentsHtml);
            
            // Initialize Comments object for this post
            var commentsObj = Object.create(Comments);
            commentsObj.init(postId);
        });
    }
    
    // Initialize on page load
    $(document).ready(function() {
        setTimeout(initCommentsForPosts, 1000);
    });
    
    console.log("✅ Comments initialization ready");
})();
