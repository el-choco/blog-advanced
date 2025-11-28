<?php
require_once 'common.php';

// CSRF Token Setup
if (empty($_SESSION['token'])) {
    if (function_exists('random_bytes')) {
        $_SESSION['token'] = bin2hex(random_bytes(5));
    } else {
        $_SESSION['token'] = bin2hex(openssl_random_pseudo_bytes(5));
    }
}

// Handle actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Single Quick Action
    if (isset($_POST['post_id']) && isset($_POST['quick_action'])) {
        $post_id = $_POST['post_id'];
        $quick_action = $_POST['quick_action'];

        $db = DB::get_instance();

        switch ($quick_action) {
            case 'toggle_sticky':
                // Get current status
                $current = $db->query("SELECT is_sticky FROM posts WHERE id = ?", [$post_id])->first();

                if ($current) {
                    $new_sticky = $current['is_sticky'] ? 0 : 1;
                    $db->query("UPDATE posts SET is_sticky = ? WHERE id = ?", [$new_sticky, $post_id]);
                    $message = $new_sticky ? $lang['Post marked as sticky'] : $lang['Sticky removed from post'];
                    $message_type = 'success';
                }
                break;

            case 'toggle_hidden':
                // Get current status
                $current = $db->query("SELECT status FROM posts WHERE id = ?", [$post_id])->first();

                if ($current) {
                    $new_status = ($current['status'] == 4) ? 1 : 4;
                    $db->query("UPDATE posts SET status = ? WHERE id = ?", [$new_status, $post_id]);
                    $message = ($new_status == 4) ? $lang['Post hidden'] : $lang['Post visible'];
                    $message_type = 'success';
                }
                break;

            case 'delete':
                $db->query("UPDATE posts SET status = ? WHERE id = ?", [5, $post_id]);
                $message = $lang['Post moved to trash'];
                $message_type = 'success';
                break;
        }
    }
}

// Get filter and sort parameters
$filter = $_GET['filter'] ?? 'all';
$sort = $_GET['sort'] ?? 'newest';
$search = $_GET['search'] ?? '';

// Get statistics
$stats = AdminHelper::getPostStats();

// Get filtered posts
$posts = AdminHelper::getAllPosts(false, $filter, $sort, $search);

// Get trash count
$trash_count = AdminHelper::getTrashCount();
?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo escape($lang['Posts']); ?> - <?php echo escape(Config::get("title")); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    
    <link href="../static/styles/main.css" rel="stylesheet" type="text/css" />
    <?php
    // Theme sicher bereinigen (4-Zeilen-Variante)
    $theme = Config::get_safe('theme', 'theme01');
    $theme = trim((string)$theme);
    $theme = preg_replace('/\.css$/i', '', $theme);
    $theme = preg_replace('/[^a-zA-Z0-9_-]/', '', $theme);
    if ($theme === '') { $theme = 'theme01'; }
    ?>
    <link href="../static/styles/<?php echo htmlspecialchars($theme, ENT_QUOTES, 'UTF-8'); ?>.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/custom1.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/admin.css" rel="stylesheet" type="text/css" />
    
    <link href="https://fonts.googleapis.com/css?family=Open+Sans&amp;subset=all" rel="stylesheet">
    
    <style>
    .bulk-actions-bar,
    .bulk-select,
    .post-checkbox,
    #select-all,
    #selectAll,
    .checkbox-cell,
    #bulkBar,
    #bulkForm {
        display: none !important;
    }
    
    .message {
        padding: 12px 20px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-size: 14px;
    }
    
    .message-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .stat-value {
        font-size: 32px;
        font-weight: bold;
        color: #2c5aa0;
    }
    
    .stat-label {
        color: #666;
        margin-top: 8px;
    }
    
    .filters-bar {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: center;
    }
    
    .filter-select {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
    }
    
    .search-box {
        flex: 1;
        min-width: 200px;
    }
    
    .search-input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
    }
    
    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .badge-sticky {
        background: #ffc107;
        color: #000;
    }
    
    .badge-hidden {
        background: #6c757d;
        color: white;
    }
    
    .badge-info {
        background: #17a2b8;
        color: white;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
        align-items: flex-start !important;
    }
    
    .quick-action-btn {
        background: transparent;
        border: none;
        cursor: pointer;
        font-size: 16px;
        padding: 4px 8px;
        border-radius: 4px;
        transition: background 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        vertical-align: middle;
    }
        
    .quick-action-btn:hover {
        background: #f0f2f5;
    }
    
    .post-text-preview {
        max-width: 400px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .action-buttons form {
        display: inline-block;
        margin: 0;
        padding: 0;
    }
    </style>
</head>
<body class="admin-body">
    
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="admin-container">
            <h1>📝 <?php echo escape($lang['Manage Posts']); ?></h1>
            <div class="admin-user">
                <span>👤 <?php echo escape(Config::get("name")); ?></span>
                <a href="../" class="btn btn-sm">← <?php echo escape($lang['Back to Blog']); ?></a>
            </div>
        </div>
    </div>
    
    <div class="admin-layout">
        
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar">
            <nav class="admin-nav">
                <a href="index.php">📊 <?php echo escape($lang['Dashboard']); ?></a>
                <a href="posts.php" class="active">📝 <?php echo escape($lang['Posts']); ?></a>
                <a href="comments.php">💬 <?php echo escape($lang['Comments']); ?></a>
                <a href="media.php">📁 <?php echo escape($lang['Files']); ?></a>
                <a href="backups.php">💾 <?php echo escape($lang['Backups']); ?></a>
                <a href="trash.php">🗑️ <?php echo escape($lang['Trash']); ?> <?php if($trash_count > 0): ?><span class="badge"><?php echo $trash_count; ?></span><?php endif; ?></a>
                <a href="settings.php">⚙️ <?php echo escape($lang['Settings']); ?></a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-content">
            
            <?php if($message): ?>
            <div class="message message-<?php echo $message_type; ?>">
                <?php echo escape($message); ?>
            </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-label"><?php echo escape($lang['Total']); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['published']; ?></div>
                    <div class="stat-label"><?php echo escape($lang['Published']); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['hidden']; ?></div>
                    <div class="stat-label"><?php echo escape($lang['Hidden']); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['sticky']; ?></div>
                    <div class="stat-label"><?php echo escape($lang['Sticky Posts']); ?></div>
                </div>
            </div>
            
            <!-- Filters -->
            <form method="GET" class="filters-bar">
                <select name="filter" class="filter-select" onchange="this.form.submit()">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>><?php echo escape($lang['All Posts']); ?></option>
                    <option value="published" <?php echo $filter === 'published' ? 'selected' : ''; ?>><?php echo escape($lang['Published Posts']); ?></option>
                    <option value="hidden" <?php echo $filter === 'hidden' ? 'selected' : ''; ?>><?php echo escape($lang['Hidden Posts']); ?></option>
                    <option value="sticky" <?php echo $filter === 'sticky' ? 'selected' : ''; ?>><?php echo escape($lang['Sticky Posts Filter']); ?></option>
                </select>
                
                <select name="sort" class="filter-select" onchange="this.form.submit()">
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>><?php echo escape($lang['Newest First']); ?></option>
                    <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>><?php echo escape($lang['Oldest First']); ?></option>
                </select>
                
                <div class="search-box">
                    <input type="text" name="search" class="search-input" placeholder="<?php echo escape($lang['Search posts']); ?>" value="<?php echo escape($search); ?>">
                </div>
                
                <button type="submit" class="btn btn-primary"><?php echo escape($lang['Search']); ?></button>
            </form>
            
            <!-- Posts Table -->
            <div class="admin-panel">
                <div class="panel-header">
                    <h2>📝 <?php echo count($posts); ?> <?php echo escape($lang['posts']); ?></h2>
                </div>
                <div class="panel-body" style="padding: 0;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th><?php echo escape($lang['Content']); ?></th>
                                <th><?php echo escape($lang['Date']); ?></th>
                                <th><?php echo escape($lang['Status']); ?></th>
                                <th><?php echo escape($lang['Actions']); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($posts)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: #999;">
                                        <?php echo escape($lang['No posts found']); ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($posts as $post): ?>
                                    <tr data-post-id="<?php echo $post['id']; ?>">
                                        <td><code>#<?php echo escape($post['id']); ?></code></td>
                                        <td>
                                            <div class="post-text-preview">
                                                <?php echo escape(AdminHelper::getExcerpt($post['text'], 100)); ?>
                                            </div>
                                            <?php if($post['has_images']): ?>
                                                <span class="badge badge-info">🖼️ <?php echo $post['image_count']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo AdminHelper::formatDate($post['time']); ?></td>
                                        <td>
                                            <?php if($post['sticky']): ?>
                                                <span class="badge badge-sticky">📌 <?php echo escape($lang['Sticky Posts']); ?></span>
                                            <?php endif; ?>
                                            <?php if($post['hidden']): ?>
                                                <span class="badge badge-hidden">👁️ <?php echo escape($lang['Hidden']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="">
                                                    <input type="hidden" name="post_id" value="<?php echo escape($post['id']); ?>">
                                                    <input type="hidden" name="quick_action" value="toggle_sticky">
                                                    <button type="submit" class="quick-action-btn" title="<?php echo $post['sticky'] ? escape($lang['Remove Sticky']) : escape($lang['Mark as Sticky']); ?>">
                                                        📌
                                                    </button>
                                                </form>
                                                
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="">
                                                    <input type="hidden" name="post_id" value="<?php echo escape($post['id']); ?>">
                                                    <input type="hidden" name="quick_action" value="toggle_hidden">
                                                    <button type="submit" class="quick-action-btn" title="<?php echo ($post['hidden']) ? escape($lang['Make visible']) : escape($lang['Hide']); ?>">
                                                        👁️
                                                    </button>
                                                </form>
                                                
                                                <button class="quick-action-btn inline-edit-btn" data-post-id="<?php echo $post['id']; ?>" title="<?php echo escape($lang['Inline Edit']); ?>">✏️</button>
                                                
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('<?php echo escape($lang['Move to trash confirmation']); ?>');">
                                                    <input type="hidden" name="action" value="">
                                                    <input type="hidden" name="post_id" value="<?php echo escape($post['id']); ?>">
                                                    <input type="hidden" name="quick_action" value="delete">
                                                    <button type="submit" class="quick-action-btn" title="<?php echo escape($lang['Move to trash']); ?>" style="color: #dc3545;">🗑️</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Inline Editor Row (hidden by default) -->
                                    <tr class="inline-editor-row" id="inline-editor-<?php echo $post['id']; ?>" style="display: none;">
                                        <td colspan="5">
                                            <div class="inline-editor-container">
                                                <div class="inline-editor-header">
                                                    <h3>✏️ <?php echo escape($lang['Edit Post']); ?> #<?php echo $post['id']; ?></h3>
                                                    <button class="btn btn-sm close-editor" data-post-id="<?php echo $post['id']; ?>">✖️ <?php echo escape($lang['Close']); ?></button>
                                                </div>
                                                <div class="inline-editor-body">
                                                    <div class="editor-column">
                                                        <h4>📝 <?php echo escape($lang['Editor']); ?></h4>
                                                        <textarea class="inline-editor-textarea" id="editor-<?php echo $post['id']; ?>" rows="15"></textarea>
                                                        <div class="editor-toolbar">
                                                            <button class="toolbar-btn" data-action="bold" title="<?php echo escape($lang['Bold']); ?>">**B**</button>
                                                            <button class="toolbar-btn" data-action="italic" title="<?php echo escape($lang['Italic']); ?>">*I*</button>
                                                            <button class="toolbar-btn" data-action="code" title="<?php echo escape($lang['Inline Code']); ?>">`code`</button>
                                                            <button class="toolbar-btn" data-action="link" title="<?php echo escape($lang['Link']); ?>">🔗</button>
                                                            <button class="toolbar-btn" data-action="image" title="<?php echo escape($lang['Image']); ?>">🖼️</button>
                                                        </div>
                                                    </div>
                                                    <div class="preview-column">
                                                        <h4>👁️ <?php echo escape($lang['Live Preview']); ?></h4>
                                                        <div class="inline-editor-preview" id="preview-<?php echo $post['id']; ?>"></div>
                                                    </div>
                                                </div>
                                                <div class="inline-editor-footer">
                                                    <button class="btn btn-sm" onclick="closeInlineEditor(<?php echo $post['id']; ?>)"><?php echo escape($lang['Cancel']); ?></button>
                                                    <button class="btn btn-primary" onclick="saveInlinePost(<?php echo $post['id']; ?>)">💾 <?php echo escape($lang['Save']); ?></button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            

            <!-- Quick Actions -->
            <div class="admin-panel">
                <div class="panel-header">
                    <h2><?php echo escape($lang['Quick Access']); ?></h2>
                </div>
                <div class="panel-body">
                    <div class="quick-actions">
                        <a href="../#new-post" class="quick-action-card">
                            <div class="qa-icon">✏️</div>
                            <div class="qa-label"><?php echo escape($lang['New Post']); ?></div>
                        </a>
                        <a href="backups.php" class="quick-action-card">
                            <div class="qa-icon">💾</div>
                            <div class="qa-label"><?php echo escape($lang['Backups']); ?></div>
                        </a>
                        <a href="comments.php" class="quick-action-card">
                            <div class="qa-icon">💬</div>
                            <div class="qa-label"><?php echo escape($lang['Comments']); ?></div>
                        </a>
                        <a href="posts.php" class="quick-action-card">
                            <div class="qa-icon">📝</div>
                            <div class="qa-label"><?php echo escape($lang['Manage Posts']); ?></div>
                        </a>
                        <a href="media.php" class="quick-action-card">
                            <div class="qa-icon">📁</div>
                            <div class="qa-label"><?php echo escape($lang['Files']); ?></div>
                        </a>
                        <a href="trash.php" class="quick-action-card">
                            <div class="qa-icon">🗑️</div>
                            <div class="qa-label"><?php echo escape($lang['Trash']); ?></div>
                        </a>
                    </div>
                </div>
            </div>

        </main>
        
    </div>

    <!-- jQuery (for AJAX) -->
    <script src="../static/scripts/jquery.min.js"></script>
    
    <!-- CSRF Token Setup -->
    <script>
    $.ajaxSetup({
        headers: {
            'Csrf-Token': '<?php echo $_SESSION['token']; ?>'
        }
    });
    console.log('🔑 CSRF Token configured');
    </script>
    
    <script>
    var ADMIN_LANG = {
        errorPostData: '<?php echo escape($lang["Error Post data could not be loaded"]); ?>',
        errorLoadingPost: '<?php echo escape($lang["Error loading post"]); ?>',
        postSaved: '<?php echo escape($lang["Post saved"]); ?>',
        errorSaving: '<?php echo escape($lang["Error saving"]); ?>',
        networkErrorSaving: '<?php echo escape($lang["Network error saving"]); ?>',
        enterURL: '<?php echo escape($lang["Enter URL"]); ?>',
        enterImageURL: '<?php echo escape($lang["Enter Image URL"]); ?>'
    };
    </script>
    <script src="../static/scripts/admin.js"></script>
    
</body>
</html>