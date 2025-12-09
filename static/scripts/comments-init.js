/**
 * Comments initialization
 * - Injects a comments section into each loaded post ('.b_post.post_row')
 * - Removes inline styles and uses CSS classes that are defined in custom1.css:
 *   .comments-wrapper, .comment-form-wrapper, .form-group, .comment-input, .hidden-field, .comment-status
 * - Hooks into posts.load and posts.add_new to initialize comments for newly loaded/added posts
 */
(function() {
  'use strict';

  console.log('🔧 Comments init starting...');

  /**
   * Try to extract post ID from the post element or its date link
   */
  function resolvePostId($post) {
    var postId = $post.attr('data-id') || $post.data('id');

    // Fallback: parse from .b_date href (#id=123)
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
    return postId;
  }

  /**
   * Build the comments HTML using classes (no inline styles)
   */
  function buildCommentsHTML(postId) {
    return [
      '<div class="comments-wrapper">',
        '<div class="comments-section" data-post-id="', postId ,'">',
          '<h3 class="comments-title">',
            '<span class="comment-count">0</span> Kommentare',
          '</h3>',
          '<div class="comments-list"></div>',
          '<div class="comment-form-wrapper">',
            '<h4>💬 Kommentar hinterlassen</h4>',
            '<form class="comment-form" data-post-id="', postId ,'">',

              // Honeypot (hidden spam protection)
              '<input type="text" name="website_check" class="hidden-field" tabindex="-1" autocomplete="off">',

              // Author name
              '<div class="form-group">',
                '<input type="text" name="author_name" required ',
                  'placeholder="Dein Name *" maxlength="100" class="comment-input">',
              '</div>',

              // Comment content
              '<div class="form-group">',
                '<textarea name="content" required rows="4" ',
                  'placeholder="Dein Kommentar *" class="comment-input"></textarea>',
              '</div>',

              // Submit button (uses existing .button.blue styles)
              '<button type="submit" class="button blue">Kommentar posten</button>',

              // Status area
              '<div class="comment-status"></div>',
            '</form>',
          '</div>',
        '</div>',
      '</div>'
    ].join('');
  }

  /**
   * Initialize comments for visible posts
   */
  function initComments() {
    console.log('🔍 Looking for posts...');

    $('.b_post.post_row').each(function(index) {
      var $post = $(this);

      // Skip if already initialized
      if ($post.data('comments-initialized')) {
        return;
      }

      var postId = resolvePostId($post);
      console.log('Post #' + index + ' ID:', postId);

      if (!postId) {
        console.log('  → Skipping (no ID)');
        return;
      }

      // Ensure data-id exists on the element
      if (!$post.attr('data-id')) {
        $post.attr('data-id', postId);
      }

      console.log('  ✅ Initializing comments for post', postId);

      // Mark as initialized
      $post.data('comments-initialized', true);

      // Inject comments HTML (class-based, CSS handled by custom1.css)
      var commentsHTML = buildCommentsHTML(postId);
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

  /**
   * Hook into posts.load and posts.add_new if available
   */
  if (typeof posts !== 'undefined') {
    console.log('✅ posts object found, hooking into load');

    var originalLoad = posts.load;
    if (typeof originalLoad === 'function') {
      posts.load = function() {
        originalLoad.call(this);
        setTimeout(initComments, 800);
      };
    }

    var originalAddNew = posts.add_new;
    if (typeof originalAddNew === 'function') {
      posts.add_new = function(post) {
        originalAddNew.call(this, post);
        setTimeout(initComments, 300);
      };
    }
  }

  /**
   * Initial attempts with increasing delays
   */
  $(document).ready(function() {
    console.log('✅ Document ready');

    // Try soon after ready
    setTimeout(initComments, 500);

    // Additional retries after posts likely loaded
    setTimeout(initComments, 1500);
    setTimeout(initComments, 3000);

    // Periodic check during development (up to 10 tries)
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