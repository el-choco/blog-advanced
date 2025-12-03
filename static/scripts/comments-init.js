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
            var postId = $post.attr('data-id') || $post.data('id');
            
            // Extract from date link (most reliable!)
            if (!postId) {
                var $dateLink = $post.find('.b_date');
                if ($dateLink.length) {
                    var href = $dateLink.attr('href');
                    if (href && href.indexOf('#id=') > -1) {
                        postId = href.split('#id=')[1].split('&')[0];
                        console.log('  → Found ID in URL hash:', postId);
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
            
            // Add comments section HTML
            var commentsHTML = [
                '<div class="comments-wrapper" style="margin-top:20px; padding:16px; background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 6px 20px -6px rgba(0,0,0,0.12);">',
                  '<div class="comments-section" data-post-id="', postId ,'">',
                    '<h3 class="comments-title">',
                      '<span class="comment-count">0</span> Kommentare',
                    '</h3>',
                    '<div class="comments-list"></div>',
                    '<div class="comment-form-wrapper" style="margin-top:15px;">',
                      '<h4>💬 Kommentar hinterlassen</h4>',
                      '<form class="comment-form" data-post-id="', postId ,'">',
                        '<input type="text" name="website_check" style="display:none;" tabindex="-1">',
                        '<div class="form-group" style="margin-bottom:10px;">',
                          '<input type="text" name="author_name" required placeholder="Dein Name *" ',
                          'style="width:100%; padding:12px; border:1px solid #d0d7de; border-radius:8px; background:#f8fafc;">',
                        '</div>',
                        '<div class="form-group" style="margin-bottom:10px;">',
                          '<textarea name="content" required rows="4" placeholder="Dein Kommentar *" ',
                          'style="width:100%; padding:12px; border:1px solid #d0d7de; border-radius:8px; resize:vertical; background:#f8fafc;"></textarea>',
                        '</div>',
                        '<button type="submit" class="button blue" style="padding:10px 14px; border-radius:8px;">Kommentar posten</button>',
                        '<div class="comment-status" style="margin-top:10px; display:none;"></div>',
                      '</form>',
                    '</div>',
                  '</div>',
                '</div>'
            ].join('');
            $post.append(commentsHTML);
            console.log('  ✅ HTML injected');
            
            // Initialize Comments object if available
            if (typeof Comments !== 'undefined') {
                try {
                    var commentsObj = Object.create(Comments);
                    commentsObj.init(postId);
                    console.log('  ✅ Comments object initialized for post', postId);
                } catch(e) {
                    console.error('  ❌ Error initializing Comments:', e);
                }
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
            setTimeout(initComments, 800);
        };
        
        var originalAddNew = posts.add_new;
        if (originalAddNew) {
            posts.add_new = function(post) {
                originalAddNew.call(this, post);
                setTimeout(initComments, 300);
            };
        }
    }
    
    // Initial load - multiple attempts with increasing delays
    $(document).ready(function() {
        console.log('✅ Document ready');
        
        // Try immediately
        setTimeout(initComments, 500);
        
        // Try again after posts likely loaded
        setTimeout(initComments, 1500);
        setTimeout(initComments, 3000);
        
        // Periodic check (remove after development)
        var checkCount = 0;
        var checkInterval = setInterval(function() {
            checkCount++;
            if (checkCount > 10) {
                clearInterval(checkInterval);
                return;
            }
            
            var uninitializedPosts = $('.b_post.post_row').not('[data-comments-initialized="true"]').length;
            if (uninitializedPosts > 0) {
                console.log('🔄 Found ' + uninitializedPosts + ' uninitialized posts, retrying...');
                initComments();
            }
        }, 2000);
    });
    
    console.log("✅ Comments init script loaded");
})();