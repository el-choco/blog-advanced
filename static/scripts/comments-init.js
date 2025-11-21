// Initialize comments after posts load
(function() {
    'use strict';
    
    console.log('🔧 Comments init starting...');
    
    // Initialize comments for visible posts
    function initComments() {
        console.log('🔍 Looking for posts...');
        
        $('.b_post.post_row').each(function(index) {
            var $post = $(this);
            
            // Try to get post ID from various sources
            var postId = $post.attr('data-id') 
                      || $post.data('id') 
                      || $post.find('[data-id]').attr('data-id')
                      || $post.attr('id');
            
            // Try to extract from URL hash or links
            if (!postId) {
                var $dateLink = $post.find('.b_date');
                if ($dateLink.length) {
                    var href = $dateLink.attr('href');
                    if (href && href.indexOf('id=') > -1) {
                        postId = href.split('id=')[1].split('&')[0];
                    }
                }
            }
            
            console.log('Post #' + index + ' ID:', postId);
            
            if (!postId || $post.data('comments-initialized')) {
                console.log('  → Skipping (no ID or already initialized)');
                return;
            }
            
            // Set data-id if missing
            if (!$post.attr('data-id')) {
                $post.attr('data-id', postId);
            }
            
            console.log('  ✅ Initializing comments for post', postId);
            
            // Mark as initialized
            $post.data('comments-initialized', true);
            
            // Show comments wrapper if exists
            var $wrapper = $post.find('.comments-wrapper');
            if ($wrapper.length) {
                $wrapper.show();
                $post.find('.comment-form').attr('data-post-id', postId);
                console.log('  ✅ Wrapper shown');
            } else {
                // Add comments section dynamically
                var commentsHTML = `
                    <div class="comments-wrapper" style="margin-top:20px; padding:15px; background:#f9f9f9; border-radius:8px;">
                        <div class="comments-section" data-post-id="${postId}">
                            <h3 class="comments-title">
                                <span class="comment-count">0</span> Kommentare
                            </h3>
                            <div class="comments-list"></div>
                            <div class="comment-form-wrapper" style="margin-top:15px; background:white; padding:15px; border-radius:5px;">
                                <h4>💬 Kommentar hinterlassen</h4>
                                <form class="comment-form" data-post-id="${postId}">
                                    <input type="text" name="website_check" style="display:none;" tabindex="-1">
                                    <div class="form-group" style="margin-bottom:10px;">
                                        <input type="text" name="author_name" required placeholder="Dein Name *" 
                                               style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                                    </div>
                                    <div class="form-group" style="margin-bottom:10px;">
                                        <textarea name="content" required rows="4" placeholder="Dein Kommentar *" 
                                                  style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px; resize:vertical;"></textarea>
                                    </div>
                                    <button type="submit" class="button blue" style="padding:8px 16px;">Kommentar posten</button>
                                    <div class="comment-status" style="margin-top:10px;"></div>
                                </form>
                            </div>
                        </div>
                    </div>
                `;
                $post.append(commentsHTML);
                console.log('  ✅ HTML injected');
            }
            
            // Initialize Comments object if available
            if (typeof Comments !== 'undefined') {
                var commentsObj = Object.create(Comments);
                commentsObj.init(postId);
                console.log('  ✅ Comments object initialized');
            } else {
                console.warn('  ⚠️ Comments object not available yet');
            }
        });
        
        console.log('✅ initComments() complete');
    }
    
    // Hook into posts.load if available
    if (typeof posts !== 'undefined') {
        console.log('✅ posts object found, hooking into load');
        var originalLoad = posts.load;
        posts.load = function() {
            originalLoad.call(this);
            setTimeout(initComments, 500);
        };
    }
    
    // Initial load
    $(document).ready(function() {
        console.log('✅ Document ready, initializing comments in 1s...');
        setTimeout(function() {
            initComments();
            // Check if Comments loaded now
            if (typeof Comments === 'undefined') {
                console.error('❌ Comments object still not loaded!');
                console.log('Checking script tags...');
                $('script').each(function() {
                    var src = $(this).attr('src');
                    if (src && src.indexOf('comments') > -1) {
                        console.log('  Found:', src);
                    }
                });
            }
        }, 1000);
    });
    
    console.log("✅ Comments init script loaded");
})();
