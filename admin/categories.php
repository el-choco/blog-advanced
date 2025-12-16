<?php
require_once 'common.php';
require_once PROJECT_PATH . 'app/categories.class.php';

function t($key, $fallback = '') {
    global $lang; return $lang[$key] ?? ($fallback !== '' ? $fallback : $key);
}

$message = ''; $message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'create') {
            Categories::create($_POST['name'] ?? '');
            $message = t('Saved','Saved'); $message_type = 'success';
        } elseif ($action === 'rename') {
            Categories::rename((int)($_POST['id'] ?? 0), $_POST['name'] ?? '');
            $message = t('Saved','Saved'); $message_type = 'success';
        } elseif ($action === 'delete') {
            Categories::delete((int)($_POST['id'] ?? 0));
            $message = t('Deleted','Deleted'); $message_type = 'success';
        }
    } catch (Exception $e) {
        $message = $e->getMessage(); $message_type = 'error';
    }
}

$cats = Categories::withCounts();

// Theme sanitize
$theme = Config::get_safe('theme', 'theme01');
$theme = trim((string)$theme);
$theme = preg_replace('/\.css$/i', '', $theme);
$theme = preg_replace('/[^a-zA-Z0-9_-]/', '', $theme);
if ($theme === '') { $theme = 'theme01'; }
?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>🏷️ <?php echo escape(t('Categories','Categories')); ?> - <?php echo escape(Config::get('title')); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />

    <link href="../static/styles/main.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/<?php echo htmlspecialchars($theme, ENT_QUOTES, 'UTF-8'); ?>.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/custom1.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/admin.css" rel="stylesheet" type="text/css" />
</head>
<body class="admin-body">
    <div class="admin-header">
        <div class="admin-container">
            <h1>🏷️ <?php echo escape(t('Categories','Categories')); ?></h1>
            <div class="admin-user">
                <span>👤 <?php echo escape(Config::get('name')); ?></span>
                <a href="../" class="btn btn-sm">← <?php echo escape(t('Back to Blog','Back to Blog')); ?></a>
            </div>
        </div>
    </div>

    <div class="admin-layout">
        <aside class="admin-sidebar">
            <nav class="admin-nav">
                <a href="index.php">📊 <?php echo escape(t('Dashboard')); ?></a>
                <a href="posts.php">📝 <?php echo escape(t('Posts')); ?></a>
                <a href="comments.php">💬 <?php echo escape(t('Comments')); ?></a>
                <a href="media.php">📁 <?php echo escape(t('Files')); ?></a>
                <a href="backups.php">💾 <?php echo escape(t('Backups')); ?></a>
                <a href="trash.php">🗑️ <?php echo escape(t('Trash')); ?></a>
                <a href="categories.php" class="active">🏷️ <?php echo escape(t('Categories','Categories')); ?></a>
                <a href="theme_editor.php">🎨 Theme Editor</a>
                <a href="settings.php">⚙️ <?php echo escape(t('Settings')); ?></a>
            </nav>
        </aside>

        <main class="admin-content">
            <?php if ($message): ?>
                <div class="message message-<?php echo $message_type === 'error' ? 'error' : 'success'; ?>">
                    <?php echo escape($message); ?>
                </div>
            <?php endif; ?>

            <div class="admin-panel">
                <div class="panel-header"><h2><?php echo escape(t('Create','Create')); ?></h2></div>
                <div class="panel-body">
                    <form method="POST" class="form-inline">
                        <input type="hidden" name="action" value="create">
                        <input type="text" name="name" class="form-input" placeholder="<?php echo escape(t('Category Name','Category Name')); ?>" required>
                        <button class="btn btn-primary">💾 <?php echo escape(t('Save','Save')); ?></button>
                    </form>
                </div>
            </div>

            <div class="admin-panel">
                <div class="panel-header"><h2><?php echo escape(t('Categories','Categories')); ?></h2></div>
                <div class="panel-body" style="padding:0;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th><?php echo escape(t('Name','Name')); ?></th>
                                <th>Slug</th>
                                <th><?php echo escape(t('Posts','Posts')); ?></th>
                                <th><?php echo escape(t('Actions','Actions')); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($cats)): ?>
                            <tr><td colspan="5" style="text-align:center;color:#999;padding:24px;"><?php echo escape(t('No categories yet','No categories yet')); ?></td></tr>
                        <?php else: foreach ($cats as $c): ?>
                            <tr>
                                <td><code>#<?php echo (int)$c['id']; ?></code></td>
                                <td><?php echo escape($c['name']); ?></td>
                                <td><?php echo escape($c['slug']); ?></td>
                                <td><?php echo (int)$c['post_count']; ?></td>
                                <td>
                                    <form method="POST" style="display:inline-block;">
                                        <input type="hidden" name="action" value="rename">
                                        <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                                        <input type="text" name="name" class="form-input" value="<?php echo escape($c['name']); ?>" style="max-width:200px;">
                                        <button class="btn btn-sm">✏️ <?php echo escape(t('Rename','Rename')); ?></button>
                                    </form>
                                    <form method="POST" style="display:inline-block;margin-left:6px;" onsubmit="return confirm('<?php echo escape(t('Delete backup confirmation','Delete?')); ?>');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                                        <button class="btn btn-sm btn-danger">🗑️ <?php echo escape(t('Delete','Delete')); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</body>
</html>