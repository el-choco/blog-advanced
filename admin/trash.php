<?php
require_once 'common.php';

// Handle actions
$message = '';
$message_type = '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    $db = DB::get_instance();
    
    // Single Quick Action
    if(isset($_POST['post_id']) && isset($_POST['quick_action'])) {
        $post_id = $_POST['post_id'];
        $quick_action = $_POST['quick_action'];
        
        if($quick_action === 'restore') {
            // Status 1 = Published (wiederhergestellt)
            $db->query("UPDATE posts SET status = ? WHERE id = ?", [1, $post_id]);
            $message = $lang['Post restored'];
            $message_type = 'success';
        } elseif($quick_action === 'delete_permanent') {
            if(AdminHelper::permanentDelete($post_id)) {
                $message = $lang['Post permanently deleted'];
                $message_type = 'success';
            }
        }
    }
    
    // Empty trash
    if($action === 'empty_trash') {
        $trash_posts = $db->query("SELECT id FROM posts WHERE status = 5")->all();
        $count = 0;
        
        foreach($trash_posts as $post) {
            if(AdminHelper::permanentDelete($post['id'])) {
                $count++;
            }
        }
        
        $message = "$count " . $lang['posts were permanently deleted'];
        $message_type = 'success';
    }
}

// Get trash posts
$trash_posts = AdminHelper::getAllPosts(true);
?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo escape($lang['Trash']); ?> - <?php echo escape(Config::get("title")); ?></title>
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
    /* Bulk Actions komplett versteckt */
    .bulk-actions-bar,
    .bulk-select,
    .post-checkbox,
    .file-checkbox,
    #select-all,
    #selectAll,
    .checkbox-cell,
    #bulkBar,
    #bulkForm {
        display: none !important;
    }
    
    .trash-warning {
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .trash-warning-icon {
        font-size: 32px;
    }
    
    .trash-warning-text {
        flex: 1;
    }
    
    .trash-warning-title {
        font-weight: 600;
        margin-bottom: 5px;
    }
    
    .trash-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .btn-danger {
        background: #dc3545;
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
    }
    
    .btn-danger:hover {
        background: #c82333;
    }
    
    .empty-trash {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }
    
    .empty-trash-icon {
        font-size: 64px;
        margin-bottom: 20px;
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
    
    .quick-action-btn {
        background: transparent;
        border: none;
        cursor: pointer;
        font-size: 16px;
        padding: 4px 8px;
        border-radius: 4px;
        transition: background 0.2s;
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
    
    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .badge-info {
        background: #17a2b8;
        color: white;
    }
    </style>
</head>
<body class="admin-body">
    
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="admin-container">
            <h1>🗑️ <?php echo escape($lang['Trash']); ?></h1>
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
                <a href="posts.php">📝 <?php echo escape($lang['Posts']); ?></a>
                <a href="comments.php">💬 <?php echo escape($lang['Comments']); ?></a>
                <a href="media.php">📁 <?php echo escape($lang['Files']); ?></a>
                <a href="backups.php">💾 <?php echo escape($lang['Backups']); ?></a>
                <a href="trash.php" class="active">🗑️ <?php echo escape($lang['Trash']); ?> <span class="badge"><?php echo count($trash_posts); ?></span></a>
                <a href="categories.php">🏷️ <?php echo escape($lang['Categories']); ?></a>
                <a href="theme_editor.php">🎨 Theme Editor</a>
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
            
            <!-- Warning -->
            <div class="trash-warning">
                <div class="trash-warning-icon">⚠️</div>
                <div class="trash-warning-text">
                    <div class="trash-warning-title"><?php echo escape($lang['Deleted Posts']); ?></div>
                    <div><?php echo escape($lang['Posts in trash can be restored or permanently deleted']); ?></div>
                </div>
            </div>
            
            <?php if(empty($trash_posts)): ?>
                
                <!-- Empty Trash -->
                <div class="admin-panel">
                    <div class="panel-body">
                        <div class="empty-trash">
                            <div class="empty-trash-icon">🗑️</div>
                            <h2><?php echo escape($lang['Trash is empty']); ?></h2>
                            <p><?php echo escape($lang['No deleted posts']); ?></p>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                
                <!-- Trash Actions -->
                <div class="trash-actions">
                    <div>
                        <strong><?php echo count($trash_posts); ?> <?php echo escape($lang['posts in trash']); ?></strong>
                    </div>
                    <form method="POST" onsubmit="return confirm('<?php echo escape($lang['Empty trash confirmation']); ?>');">
                        <input type="hidden" name="action" value="empty_trash">
                        <button type="submit" class="btn-danger">🗑️ <?php echo escape($lang['Empty Trash']); ?></button>
                    </form>
                </div>
                
                <!-- Trash Table -->
                <div class="admin-panel">
                    <div class="panel-body" style="padding: 0;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th><?php echo escape($lang['Content']); ?></th>
                                    <th><?php echo escape($lang['Deleted on']); ?></th>
                                    <th><?php echo escape($lang['Actions']); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($trash_posts as $post): ?>
                                    <tr>
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
                                            <div class="action-buttons">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="">
                                                    <input type="hidden" name="post_id" value="<?php echo escape($post['id']); ?>">
                                                    <input type="hidden" name="quick_action" value="restore">
                                                    <button type="submit" class="quick-action-btn" title="<?php echo escape($lang['Restore']); ?>">♻️</button>
                                                </form>
                                                
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('<?php echo escape($lang['Delete permanently confirmation']); ?>');">
                                                    <input type="hidden" name="action" value="">
                                                    <input type="hidden" name="post_id" value="<?php echo escape($post['id']); ?>">
                                                    <input type="hidden" name="quick_action" value="delete_permanent">
                                                    <button type="submit" class="quick-action-btn" title="<?php echo escape($lang['Delete Permanently']); ?>" style="color: #dc3545;">🗑️</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            <?php endif; ?>
            

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
    
</body>
</html>