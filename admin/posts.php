<?php
/**
 * Posts Management (posts.php)
 * - Filter / search posts
 * - Quick actions (sticky / hide / delete)
 * - Inline editor with extended Markdown toolbar and live preview
 */
require_once 'common.php';

// CSRF Token
if (empty($_SESSION['token'])) {
    if (function_exists('random_bytes')) {
        $_SESSION['token'] = bin2hex(random_bytes(5));
    } else {
        $_SESSION['token'] = bin2hex(openssl_random_pseudo_bytes(5));
    }
}

// Handle quick actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (isset($_POST['post_id']) && isset($_POST['quick_action'])) {
        $post_id      = (int)$_POST['post_id'];
        $quick_action = $_POST['quick_action'];
        $db = DB::get_instance();
        switch ($quick_action) {
            case 'toggle_sticky':
                $current = $db->query("SELECT is_sticky FROM posts WHERE id = ?", [$post_id])->first();
                if ($current) {
                    $new = $current['is_sticky'] ? 0 : 1;
                    $db->query("UPDATE posts SET is_sticky = ? WHERE id = ?", [$new, $post_id]);
                    $message = $new ? $lang['Post marked as sticky'] : $lang['Sticky removed from post'];
                    $message_type = 'success';
                }
                break;
            case 'toggle_hidden':
                $current = $db->query("SELECT status FROM posts WHERE id = ?", [$post_id])->first();
                if ($current) {
                    $new_status = ($current['status'] == 4) ? 1 : 4;
                    $db->query("UPDATE posts SET status = ? WHERE id = ?", [$new_status, $post_id]);
                    $message = ($new_status == 4) ? $lang['Post hidden'] : $lang['Post visible'];
                    $message_type = 'success';
                }
                break;
            case 'delete':
                // Soft delete (status=5 -> trash)
                $db->query("UPDATE posts SET status = ? WHERE id = ?", [5, $post_id]);
                $message = $lang['Post moved to trash'];
                $message_type = 'success';
                break;
        }
    }
}

// Filter / sort / search
$filter  = $_GET['filter'] ?? 'all';
$sort    = $_GET['sort'] ?? 'newest';
$search  = $_GET['search'] ?? '';
$stats   = AdminHelper::getPostStats();
$posts   = AdminHelper::getAllPosts(false, $filter, $sort, $search);
$trash_count = AdminHelper::getTrashCount();

function t($key, $fallback = '') {
    global $lang;
    if (isset($lang[$key])) return $lang[$key];
    return $fallback !== '' ? $fallback : $key;
}

// Theme sanitize
$theme = Config::get_safe('theme', 'theme01');
$theme = trim((string)$theme);
$theme = preg_replace('/\.css$/i', '', $theme);
$theme = preg_replace('/[^a-zA-Z0-9_-]/', '', $theme);
if ($theme === '') { $theme = 'theme01'; }
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo escape(t('Posts','Posts')); ?> - <?php echo escape(Config::get('title')); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta name="csrf-token" content="<?php echo escape($_SESSION['token']); ?>">

    <link href="../static/styles/main.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/<?php echo htmlspecialchars($theme, ENT_QUOTES, 'UTF-8'); ?>.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/custom1.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/admin.css" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css?family=Open+Sans&amp;subset=all" rel="stylesheet">

    <style>
    .message {padding:12px 20px;border-radius:6px;margin-bottom:20px;font-size:14px;}
    .message-success {background:#d4edda;color:#155724;border:1px solid #c3e6cb;}
    .stats-grid {display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:20px;margin-bottom:30px;}
    .stat-card {background:#fff;padding:16px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.12);}
    .stat-value {font-size:28px;font-weight:700;color:#2c5aa0;}
    .stat-label {color:#666;font-size:12px;font-weight:600;text-transform:uppercase;margin-top:6px;}
    .filters-bar {background:#fff;padding:20px;border-radius:8px;margin-bottom:20px;display:flex;gap:15px;flex-wrap:wrap;align-items:center;}
    .filter-select {padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;}
    .search-box {flex:1;min-width:200px;}
    .search-input {width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;}
    .admin-table {width:100%;border-collapse:collapse;}
    .admin-table th, .admin-table td {padding:10px 12px;border-bottom:1px solid #e5e5e5;font-size:14px;vertical-align:top;}
    .admin-table th {background:#f9f9f9;font-weight:600;text-align:left;}
    .badge {display:inline-block;padding:3px 6px;border-radius:4px;font-size:11px;font-weight:600;}
    .badge-sticky {background:#ffc107;color:#000;}
    .badge-hidden {background:#6c757d;color:#fff;}
    .badge-info {background:#17a2b8;color:#fff;}
    .action-buttons {display:flex;gap:6px;}
    .quick-action-btn {background:#fff;border:1px solid #d0d7de;border-radius:4px;cursor:pointer;font-size:16px;padding:4px 8px;transition:.15s;}
    .quick-action-btn:hover {background:#eef4fa;}
    .post-text-preview {max-width:400px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
    .inline-editor-container {background:#fff;border:1px solid #d8dee4;border-radius:8px;padding:16px 20px;margin-top:8px;box-shadow:0 2px 6px rgba(0,0,0,.08);}
    .inline-editor-header {display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;}
    .inline-editor-body {display:grid;grid-template-columns:1fr 1fr;gap:18px;}
    .inline-editor-body textarea {
        width:100%;min-height:380px;font-family:monospace;font-size:13px;line-height:1.4;
        padding:12px;border:1px solid #d0d7de;border-radius:6px;background:#fafbfc;resize:vertical;
    }
    .inline-editor-preview {
        border:1px solid #d0d7de;border-radius:6px;background:#fff;padding:12px;max-height:404px;overflow:auto;
    }
    .editor-toolbar {
        display:flex;flex-wrap:wrap;gap:6px;background:#f5f7fa;
        border:1px solid #d0d7de;border-radius:6px;padding:8px;margin-top:10px;
    }
    .toolbar-btn {
        background:#fff;border:1px solid #d0d7de;border-radius:4px;padding:4px 10px;
        font-size:12px;font-weight:600;cursor:pointer;transition:background .15s,border-color .15s;
    }
    .toolbar-btn:hover {background:#e6f0fa;border-color:#91b4d9;}
    .inline-editor-footer {display:flex;justify-content:flex-end;gap:10px;margin-top:16px;}
    pre code {font-size:12px;line-height:1.35;}
    details {margin:8px 0;padding:6px 8px;border:1px solid #d0d7de;border-radius:4px;background:#f8fafc;}
    details[open] {background:#eef4fa;}
    details summary {cursor:pointer;font-weight:600;}
    </style>
</head>
<body class="admin-body">

    <div class="admin-header">
        <div class="admin-container">
            <h1>📝 <?php echo escape(t('Manage Posts')); ?></h1>
            <div class="admin-user">
                <span>👤 <?php echo escape(Config::get('name')); ?></span>
                <a href="../" class="btn btn-sm">← <?php echo escape(t('Back to Blog')); ?></a>
            </div>
        </div>
    </div>

    <div class="admin-layout">
        <aside class="admin-sidebar">
            <nav class="admin-nav">
                <a href="index.php">📊 <?php echo escape(t('Dashboard')); ?></a>
                <a href="posts.php" class="active">📝 <?php echo escape(t('Posts')); ?></a>
                <a href="comments.php">💬 <?php echo escape(t('Comments')); ?></a>
                <a href="media.php">📁 <?php echo escape(t('Files')); ?></a>
                <a href="backups.php">💾 <?php echo escape(t('Backups')); ?></a>
                <a href="trash.php">🗑️ <?php echo escape(t('Trash')); ?> <?php if($trash_count > 0): ?><span class="badge"><?php echo (int)$trash_count; ?></span><?php endif; ?></a>
                <a href="settings.php">⚙️ <?php echo escape(t('Settings')); ?></a>
            </nav>
        </aside>

        <main class="admin-content">
            <?php if ($message): ?>
                <div class="message message-<?php echo escape($message_type); ?>">
                    <?php echo escape($message); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo (int)$stats['total']; ?></div>
                    <div class="stat-label"><?php echo escape(t('Total')); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo (int)$stats['published']; ?></div>
                    <div class="stat-label"><?php echo escape(t('Published')); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo (int)$stats['hidden']; ?></div>
                    <div class="stat-label"><?php echo escape(t('Hidden')); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo (int)$stats['sticky']; ?></div>
                    <div class="stat-label"><?php echo escape(t('Sticky Posts')); ?></div>
                </div>
            </div>

            <!-- Filters -->
            <form method="GET" class="filters-bar">
                <select name="filter" class="filter-select" onchange="this.form.submit()">
                    <option value="all" <?php echo $filter==='all'?'selected':''; ?>><?php echo escape(t('All Posts')); ?></option>
                    <option value="published" <?php echo $filter==='published'?'selected':''; ?>><?php echo escape(t('Published Posts')); ?></option>
                    <option value="hidden" <?php echo $filter==='hidden'?'selected':''; ?>><?php echo escape(t('Hidden Posts')); ?></option>
                    <option value="sticky" <?php echo $filter==='sticky'?'selected':''; ?>><?php echo escape(t('Sticky Posts Filter')); ?></option>
                </select>

                <select name="sort" class="filter-select" onchange="this.form.submit()">
                    <option value="newest" <?php echo $sort==='newest'?'selected':''; ?>><?php echo escape(t('Newest First')); ?></option>
                    <option value="oldest" <?php echo $sort==='oldest'?'selected':''; ?>><?php echo escape(t('Oldest First')); ?></option>
                </select>

                <div class="search-box">
                    <input type="text" name="search" class="search-input" placeholder="<?php echo escape(t('Search posts')); ?>" value="<?php echo escape($search); ?>">
                </div>

                <button type="submit" class="btn btn-primary"><?php echo escape(t('Search')); ?></button>
            </form>

            <!-- Posts Table -->
            <div class="admin-panel">
                <div class="panel-header">
                    <h2>📝 <?php echo count($posts); ?> <?php echo escape(t('posts','posts')); ?></h2>
                </div>
                <div class="panel-body" style="padding:0;">
                    <table class="admin-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th><?php echo escape(t('Content')); ?></th>
                            <th><?php echo escape(t('Date')); ?></th>
                            <th><?php echo escape(t('Status')); ?></th>
                            <th><?php echo escape(t('Actions')); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($posts)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center;padding:40px;color:#999;"><?php echo escape(t('No posts found')); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($posts as $post): ?>
                                <tr data-post-id="<?php echo (int)$post['id']; ?>">
                                    <td><code>#<?php echo escape($post['id']); ?></code></td>
                                    <td>
                                        <div class="post-text-preview">
                                            <?php echo escape(AdminHelper::getExcerpt($post['text'], 100)); ?>
                                        </div>
                                        <?php if ($post['has_images']): ?>
                                            <span class="badge badge-info">🖼️ <?php echo (int)$post['image_count']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo AdminHelper::formatDate($post['time']); ?></td>
                                    <td>
                                        <?php if ($post['sticky']): ?>
                                            <span class="badge badge-sticky">📌 <?php echo escape(t('Sticky Posts')); ?></span>
                                        <?php endif; ?>
                                        <?php if ($post['hidden']): ?>
                                            <span class="badge badge-hidden">👁️ <?php echo escape(t('Hidden')); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <form method="POST">
                                                <input type="hidden" name="action" value="">
                                                <input type="hidden" name="post_id" value="<?php echo (int)$post['id']; ?>">
                                                <input type="hidden" name="quick_action" value="toggle_sticky">
                                                <button type="submit" class="quick-action-btn" title="<?php echo $post['sticky']?escape(t('Remove Sticky')):escape(t('Mark as Sticky')); ?>">📌</button>
                                            </form>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="">
                                                <input type="hidden" name="post_id" value="<?php echo (int)$post['id']; ?>">
                                                <input type="hidden" name="quick_action" value="toggle_hidden">
                                                <button type="submit" class="quick-action-btn" title="<?php echo $post['hidden']?escape(t('Make visible')):escape(t('Hide')); ?>">👁️</button>
                                            </form>
                                            <button class="quick-action-btn inline-edit-btn" data-post-id="<?php echo (int)$post['id']; ?>" title="<?php echo escape(t('Inline Edit')); ?>">✏️</button>
                                            <form method="POST" onsubmit="return confirm('<?php echo escape(t('Move to trash confirmation')); ?>');">
                                                <input type="hidden" name="action" value="">
                                                <input type="hidden" name="post_id" value="<?php echo (int)$post['id']; ?>">
                                                <input type="hidden" name="quick_action" value="delete">
                                                <button type="submit" class="quick-action-btn" style="color:#dc3545;" title="<?php echo escape(t('Move to trash')); ?>">🗑️</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <!-- Inline Editor Row -->
                                <tr class="inline-editor-row" id="inline-editor-<?php echo (int)$post['id']; ?>" style="display:none;">
                                    <td colspan="5">
                                        <div class="inline-editor-container">
                                            <div class="inline-editor-header">
                                                <h3>✏️ <?php echo escape(t('Edit Post')); ?> #<?php echo (int)$post['id']; ?></h3>
                                                <button class="btn btn-sm close-editor" data-post-id="<?php echo (int)$post['id']; ?>">✖️ <?php echo escape(t('Close')); ?></button>
                                            </div>
                                            <div class="inline-editor-body">
                                                <div>
                                                    <h4>📝 <?php echo escape(t('Editor')); ?></h4>
                                                    <textarea class="inline-editor-textarea" id="editor-<?php echo (int)$post['id']; ?>" rows="15"></textarea>
                                                    <div class="editor-toolbar">
                                                        <button class="toolbar-btn" data-action="bold" title="<?php echo escape(t('Bold')); ?>">**B**</button>
                                                        <button class="toolbar-btn" data-action="italic" title="<?php echo escape(t('Italic')); ?>">*I*</button>
                                                        <button class="toolbar-btn" data-action="code" title="<?php echo escape(t('Inline Code')); ?>">`code`</button>
                                                        <button class="toolbar-btn" data-action="link" title="<?php echo escape(t('Link')); ?>">🔗</button>
                                                        <button class="toolbar-btn" data-action="image" title="<?php echo escape(t('Image')); ?>">🖼️</button>
                                                        <button class="toolbar-btn" data-action="h1" title="<?php echo escape(t('Heading 1')); ?>">H1</button>
                                                        <button class="toolbar-btn" data-action="h2" title="<?php echo escape(t('Heading 2')); ?>">H2</button>
                                                        <button class="toolbar-btn" data-action="h3" title="<?php echo escape(t('Heading 3')); ?>">H3</button>
                                                        <button class="toolbar-btn" data-action="hr" title="<?php echo escape(t('Horizontal Line')); ?>">—</button>
                                                        <button class="toolbar-btn" data-action="quote" title="<?php echo escape(t('Quote')); ?>">❝</button>
                                                        <button class="toolbar-btn" data-action="ul" title="<?php echo escape(t('List')); ?>">• List</button>
                                                        <button class="toolbar-btn" data-action="ol" title="<?php echo escape(t('Numbered List')); ?>">1. List</button>
                                                        <button class="toolbar-btn" data-action="codeblock" title="<?php echo escape(t('Code Block')); ?>">```</button>
                                                        <button class="toolbar-btn" data-action="spoiler" title="<?php echo escape(t('Spoiler')); ?>">🫣</button>
                                                    </div>
                                                </div>
                                                <div>
                                                    <h4>👁️ <?php echo escape(t('Live Preview')); ?></h4>
                                                    <div class="inline-editor-preview" id="preview-<?php echo (int)$post['id']; ?>"></div>
                                                </div>
                                            </div>
                                            <div class="inline-editor-footer">
                                                <button class="btn btn-sm" onclick="closeInlineEditor(<?php echo (int)$post['id']; ?>)"><?php echo escape(t('Cancel')); ?></button>
                                                <button class="btn btn-primary" onclick="saveInlinePost(<?php echo (int)$post['id']; ?>)">💾 <?php echo escape(t('Save')); ?></button>
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

            <!-- Quick Access -->
            <div class="admin-panel">
                <div class="panel-header">
                    <h2><?php echo escape(t('Quick Access')); ?></h2>
                </div>
                <div class="panel-body">
                    <div class="quick-actions">
                        <a href="../#new-post" class="quick-action-card">
                            <div class="qa-icon">✏️</div><div class="qa-label"><?php echo escape(t('New Post')); ?></div>
                        </a>
                        <a href="backups.php" class="quick-action-card">
                            <div class="qa-icon">💾</div><div class="qa-label"><?php echo escape(t('Backups')); ?></div>
                        </a>
                        <a href="comments.php" class="quick-action-card">
                            <div class="qa-icon">💬</div><div class="qa-label"><?php echo escape(t('Comments')); ?></div>
                        </a>
                        <a href="posts.php" class="quick-action-card">
                            <div class="qa-icon">📝</div><div class="qa-label"><?php echo escape(t('Manage Posts')); ?></div>
                        </a>
                        <a href="media.php" class="quick-action-card">
                            <div class="qa-icon">📁</div><div class="qa-label"><?php echo escape(t('Files')); ?></div>
                        </a>
                        <a href="trash.php" class="quick-action-card">
                            <div class="qa-icon">🗑️</div><div class="qa-label"><?php echo escape(t('Trash')); ?></div>
                        </a>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script src="../static/scripts/jquery.min.js"></script>
    <script>
    $.ajaxSetup({ headers: { 'Csrf-Token': '<?php echo $_SESSION['token']; ?>' } });
    var ADMIN_LANG = {
        errorPostData: '<?php echo escape(t("Error Post data could not be loaded")); ?>',
        errorLoadingPost: '<?php echo escape(t("Error loading post")); ?>',
        postSaved: '<?php echo escape(t("Post saved")); ?>',
        errorSaving: '<?php echo escape(t("Error saving")); ?>',
        networkErrorSaving: '<?php echo escape(t("Network error saving")); ?>',
        enterURL: '<?php echo escape(t("Enter URL")); ?>',
        enterImageURL: '<?php echo escape(t("Enter Image URL")); ?>',
        'Language optional': '<?php echo escape(t("Language optional")); ?>',
        'Spoiler Title': '<?php echo escape(t("Spoiler Title")); ?>'
    };
    </script>
    <script src="../static/scripts/admin.js"></script>
</body>
</html>