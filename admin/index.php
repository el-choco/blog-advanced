<?php
/**
 * Admin Dashboard (index.php)
 * - Statistics
 * - Recent posts with inline editor
 * - Extended Markdown toolbar (H1/H2/H3/HR/Quote/UL/OL/Codeblock/Spoiler)
 * - Live preview via markdown-it + highlight.js + DOMPurify (loaded at bottom)
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

$stats        = AdminHelper::getStats();
$recent_posts = array_slice(AdminHelper::getAllPosts(), 0, 5);

// Helper Fallback Translation
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
    <title><?php echo escape(t('Admin Dashboard', 'Admin Dashboard')); ?> - <?php echo escape(Config::get('title')); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta name="csrf-token" content="<?php echo escape($_SESSION['token']); ?>">

    <link href="../static/styles/main.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/<?php echo htmlspecialchars($theme, ENT_QUOTES, 'UTF-8'); ?>.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/custom1.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/admin.css" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css?family=Open+Sans&amp;subset=all" rel="stylesheet">

    <style>
    .stats-grid {
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(160px,1fr));
        gap:18px;
        margin-bottom:30px;
    }
    .stat-card {
        background:#fff;
        padding:16px 18px;
        border-radius:8px;
        box-shadow:0 1px 3px rgba(0,0,0,.12);
        display:flex;
        align-items:center;
        gap:14px;
    }
    .stat-icon {font-size:28px;}
    .stat-value {font-size:28px;font-weight:700;color:#2c5aa0;line-height:1;}
    .stat-label {font-size:12px;text-transform:uppercase;font-weight:600;color:#666;letter-spacing:.5px;}
    .admin-table {width:100%;border-collapse:collapse;}
    .admin-table th, .admin-table td {padding:10px 12px;border-bottom:1px solid #e5e5e5;vertical-align:top;font-size:14px;}
    .admin-table th {background:#f9f9f9;text-align:left;font-weight:600;}
    .badge {display:inline-block;padding:3px 6px;border-radius:4px;font-size:11px;font-weight:600;}
    .badge-info {background:#17a2b8;color:#fff;}
    .badge-sticky {background:#ffc107;color:#000;}
    .badge-hidden {background:#6c757d;color:#fff;}
    .inline-editor-container {
        background:#fff;
        border:1px solid #d8dee4;
        border-radius:8px;
        padding:16px 20px;
        box-shadow:0 2px 6px rgba(0,0,0,.08);
        margin-top:8px;
    }
    .inline-editor-header {display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;}
    .inline-editor-body {display:grid;grid-template-columns:1fr 1fr;gap:18px;}
    .editor-column textarea {
        width:100%;min-height:380px;
        font-family:monospace;
        font-size:13px;
        line-height:1.4;
        padding:12px;
        border:1px solid #d0d7de;
        border-radius:6px;
        resize:vertical;
        background:#fafbfc;
    }
    .preview-column {
        border:1px solid #d0d7de;
        border-radius:6px;
        background:#fff;
        padding:12px;
        overflow:auto;
        max-height:404px;
    }
    .editor-toolbar {
        display:flex;
        flex-wrap:wrap;
        gap:6px;
        background:#f5f7fa;
        border:1px solid #d0d7de;
        border-radius:6px;
        padding:8px;
        margin-top:10px;
    }
    .toolbar-btn {
        background:#fff;
        border:1px solid #d0d7de;
        border-radius:4px;
        padding:4px 10px;
        font-size:12px;
        cursor:pointer;
        font-weight:600;
        line-height:1.2;
        transition:background .15s,border-color .15s;
    }
    .toolbar-btn:hover {background:#e6f0fa;border-color:#91b4d9;}
    .inline-editor-footer {display:flex;justify-content:flex-end;gap:10px;margin-top:16px;}
    .post-preview {max-width:420px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
    pre code {font-size:12px;line-height:1.35;}
    .inline-editor-preview h1,h2,h3,h4,h5 {margin:10px 0 6px;}
    .inline-editor-preview img {max-width:100%;border-radius:4px;}
    details {margin:8px 0;padding:6px 8px;border:1px solid #d0d7de;border-radius:4px;background:#f8fafc;}
    details[open] {background:#eef4fa;}
    details summary {cursor:pointer;font-weight:600;}
    </style>
</head>
<body class="admin-body">

    <div class="admin-header">
        <div class="admin-container">
            <h1>📊 <?php echo escape(t('Admin Dashboard')); ?></h1>
            <div class="admin-user">
                <span>👤 <?php echo escape(Config::get('name')); ?></span>
                <a href="../" class="btn btn-sm">← <?php echo escape(t('Back to Blog')); ?></a>
            </div>
        </div>
    </div>

    <div class="admin-layout">
        <aside class="admin-sidebar">
            <nav class="admin-nav">
                <a href="index.php" class="active">📊 <?php echo escape(t('Dashboard','Dashboard')); ?></a>
                <a href="posts.php">📝 <?php echo escape(t('Posts','Posts')); ?></a>
                <a href="comments.php">💬 <?php echo escape(t('Comments','Comments')); ?></a>
                <a href="media.php">📁 <?php echo escape(t('Files','Files')); ?></a>
                <a href="backups.php">💾 <?php echo escape(t('Backups','Backups')); ?></a>
                <a href="trash.php">🗑️ <?php echo escape(t('Trash','Trash')); ?> <span class="badge"><?php echo (int)$stats['trash_posts']; ?></span></a>
                <a href="categories.php">🏷️ <?php echo escape(t('Categories','Categories')); ?></a>
                <a href="theme_editor.php">🎨 <?php echo escape(t('Theme Editor','Theme Editor')); ?></a>
                <a href="settings.php">⚙️ <?php echo escape(t('Settings','Settings')); ?></a>
            </nav>
        </aside>

        <main class="admin-content">

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📝</div>
                    <div>
                        <div class="stat-value"><?php echo (int)$stats['total_posts']; ?></div>
                        <div class="stat-label"><?php echo escape(t('Total Posts')); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div>
                        <div class="stat-value"><?php echo (int)$stats['public_posts']; ?></div>
                        <div class="stat-label"><?php echo escape(t('Public','Public')); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📌</div>
                    <div>
                        <div class="stat-value"><?php echo (int)$stats['sticky_posts']; ?></div>
                        <div class="stat-label"><?php echo escape(t('Sticky Posts','Sticky Posts')); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🖼️</div>
                    <div>
                        <div class="stat-value"><?php echo (int)$stats['total_images']; ?></div>
                        <div class="stat-label"><?php echo escape(t('Images')); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📎</div>
                    <div>
                        <div class="stat-value"><?php echo (int)$stats['total_files']; ?></div>
                        <div class="stat-label"><?php echo escape(t('Files')); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🗑️</div>
                    <div>
                        <div class="stat-value"><?php echo (int)$stats['trash_posts']; ?></div>
                        <div class="stat-label"><?php echo escape(t('In Trash','In Trash')); ?></div>
                    </div>
                </div>
            </div>

            <!-- Recent Posts -->
            <div class="admin-panel">
                <div class="panel-header">
                    <h2><?php echo escape(t('Recent Posts')); ?></h2>
                    <a href="posts.php" class="btn btn-primary"><?php echo escape(t('Show All')); ?> →</a>
                </div>
                <div class="panel-body">
                    <table class="admin-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th><?php echo escape(t('Content')); ?></th>
                            <th><?php echo escape(t('Status')); ?></th>
                            <th><?php echo escape(t('Date')); ?></th>
                            <th><?php echo escape(t('Actions')); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($recent_posts)): ?>
                            <tr>
                                <td colspan="5" class="text-center"><?php echo escape(t('No posts available')); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_posts as $post): ?>
                                <tr data-post-id="<?php echo (int)$post['id']; ?>">
                                    <td><code>#<?php echo escape($post['id']); ?></code></td>
                                    <td>
                                        <div class="post-preview">
                                            <?php echo escape(AdminHelper::getExcerpt($post['text'], 80)); ?>
                                            <?php if ($post['has_images']): ?>
                                                <span class="badge badge-info">🖼️ <?php echo (int)$post['image_count']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo AdminHelper::getStatusBadge($post); ?></td>
                                    <td><?php echo AdminHelper::formatDate($post['time']); ?></td>
                                    <td>
                                        <div class="action-buttons" style="display:flex;gap:6px;">
                                            <a href="../#id=<?php echo (int)$post['id']; ?>" class="btn btn-sm" title="<?php echo escape(t('View')); ?>">👁️</a>
                                            <button class="btn btn-sm inline-edit-btn" data-post-id="<?php echo (int)$post['id']; ?>" title="<?php echo escape(t('Inline Edit')); ?>">✏️</button>
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
                                                <div class="editor-column">
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
                                                <div class="preview-column">
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