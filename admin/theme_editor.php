<?php
require_once 'common.php';

// Helper function
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

$stats = AdminHelper::getStats();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>🎨 <?php echo escape(t('Theme-Editor', 'Theme-Editor')); ?> - <?php echo escape(Config::get('title')); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />

    <link href="../static/styles/main.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/<?php echo htmlspecialchars($theme, ENT_QUOTES, 'UTF-8'); ?>.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/custom1.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/admin.css" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css?family=Open+Sans&amp;subset=all" rel="stylesheet">

    <style>
    .theme-editor-container {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,.12);
    }
    .theme-info {
        margin-bottom: 20px;
        padding: 15px;
        background: #f8fafc;
        border-radius: 6px;
        border-left: 4px solid #3b82f6;
    }
    </style>
</head>
<body class="admin-body">

    <div class="admin-header">
        <div class="admin-container">
            <h1>🎨 <?php echo escape(t('Theme-Editor', 'Theme-Editor')); ?></h1>
            <div class="admin-user">
                <span>👤 <?php echo escape(Config::get('name')); ?></span>
                <a href="../" class="btn btn-sm">← <?php echo escape(t('Back to Blog', 'Back to Blog')); ?></a>
            </div>
        </div>
    </div>

    <div class="admin-layout">
        <aside class="admin-sidebar">
            <nav class="admin-nav">
                <a href="index.php">📊 <?php echo escape(t('Dashboard','Dashboard')); ?></a>
                <a href="posts.php">📝 <?php echo escape(t('Posts','Posts')); ?></a>
                <a href="comments.php">💬 <?php echo escape(t('Comments','Comments')); ?></a>
                <a href="media.php">📁 <?php echo escape(t('Files','Files')); ?></a>
                <a href="backups.php">💾 <?php echo escape(t('Backups','Backups')); ?></a>
                <a href="trash.php">🗑️ <?php echo escape(t('Trash','Trash')); ?> <span class="badge"><?php echo (int)$stats['trash_posts']; ?></span></a>
                <a href="categories.php">🏷️ <?php echo escape(t('Categories','Categories')); ?></a>
                <a href="theme_editor.php" class="active">🎨 <?php echo escape(t('Theme-Editor','Theme-Editor')); ?></a>
                <a href="settings.php">⚙️ <?php echo escape(t('Settings','Settings')); ?></a>
            </nav>
        </aside>

        <main class="admin-content">
            <div class="theme-editor-container">
                <div class="theme-info">
                    <h3>🎨 <?php echo escape(t('Theme-Editor', 'Theme-Editor')); ?></h3>
                    <p><?php echo escape(t('Current Theme', 'Current Theme')); ?>: <strong><?php echo escape($theme); ?></strong></p>
                    <p><?php echo escape(t('Customize your blog theme colors and styles', 'Customize your blog theme colors and styles')); ?>.</p>
                </div>
                
                <div class="admin-panel">
                    <div class="panel-header">
                        <h2><?php echo escape(t('Theme Customization', 'Theme Customization')); ?></h2>
                    </div>
                    <div class="panel-body">
                        <p><?php echo escape(t('Theme customization features coming soon', 'Theme customization features coming soon')); ?>...</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

</body>
</html>
