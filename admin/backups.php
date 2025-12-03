<?php
require_once 'common.php';

$page_title = $lang['Backup Management'];
$error = null;
$success = null;

// Load Backup class
require_once PROJECT_PATH . 'app/backup.class.php';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create':
                $backup = Backup::create();
                $success = $lang['Backup created successfully'] . ': ' . $backup['filename'];
                break;
                
            case 'restore':
                $filename = $_POST['file'] ?? '';
                if (empty($filename)) {
                    throw new Exception($lang['No backup file specified']);
                }
                Backup::restore($filename);
                $success = $lang['Backup restored successfully'];
                break;
                
            case 'delete':
                $filename = $_POST['file'] ?? '';
                if (empty($filename)) {
                    throw new Exception($lang['No backup file specified']);
                }
                Backup::delete($filename);
                $success = $lang['Backup deleted successfully'];
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get list of backups
try {
    $backups = Backup::get_list();
    $total_size = array_sum(array_column($backups, 'size'));
    $last_backup = ! empty($backups) ? $backups[0]['created'] : null;
} catch (Exception $e) {
    $error = $e->getMessage();
    $backups = [];
    $total_size = 0;
    $last_backup = null;
}

// Helper function to format file size
function format_bytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}
?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>💾 <?php echo escape($lang['Backups']); ?> - <?php echo escape(Config::get("title")); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />

    <!-- Main Blog Styles -->
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

    <!-- Admin Styles -->
    <link href="../static/styles/admin.css" rel="stylesheet" type="text/css" />

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&amp;subset=all" rel="stylesheet">
</head>
<body class="admin-body">

    <div class="admin-header">
        <div class="admin-container">
            <h1>💾 <?php echo escape($lang['Backup Management']); ?></h1>
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
                <a href="backups.php" class="active">💾 <?php echo escape($lang['Backups']); ?></a>
                <a href="trash.php">🗑️ <?php echo escape($lang['Trash']); ?></a>
                <a href="categories.php">🏷️ <?php echo escape($lang['Categories']); ?></a>
                <a href="settings.php">⚙️ <?php echo escape($lang['Settings']); ?></a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">

            <?php if($error): ?>
                <div class="alert alert-danger">
                    ❌ <?php echo escape($lang['Error']); ?>: <?php echo escape($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success">
                    ✅ <?php echo escape($success); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo count($backups); ?></div>
                        <div class="stat-label"><?php echo escape($lang['Available Backups']); ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">💾</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo format_bytes($total_size); ?></div>
                        <div class="stat-label"><?php echo escape($lang['Total Size']); ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $last_backup ? date('d. m.Y', $last_backup) : '-'; ?></div>
                        <div class="stat-label"><?php echo escape($lang['Last Backup']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Create Backup -->
            <div class="admin-panel">
                <div class="panel-header">
                    <h2><?php echo escape($lang['Create New Backup']); ?></h2>
                </div>
                <div class="panel-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="create">
                        <button type="submit" class="btn btn-primary" style="font-size: 16px;">
                            ➕ <?php echo escape($lang['Create New Backup']); ?>
                        </button>
                        <p style="margin-top: 10px; color: #666; font-size: 14px;">
                            <?php echo escape($lang['Creates a complete database backup as SQL file']); ?>
                        </p>
                    </form>
                </div>
            </div>

            <!-- Backup List -->
            <div class="admin-panel">
                <div class="panel-header">
                    <h2><?php echo escape($lang['Available Backups']); ?></h2>
                </div>
                <div class="panel-body">
                    <?php if(empty($backups)): ?>
                        <div class="empty-state">
                            <div style="font-size: 64px; opacity: 0.3; margin-bottom: 20px;">📦</div>
                            <h3><?php echo escape($lang['No backups available yet']); ?></h3>
                            <p style="color: #666;"><?php echo escape($lang['Click Create New Backup to create the first backup']); ?></p>
                        </div>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th><?php echo escape($lang['Filename']); ?></th>
                                    <th><?php echo escape($lang['Created']); ?></th>
                                    <th><?php echo escape($lang['Size']); ?></th>
                                    <th><?php echo escape($lang['Actions']); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($backups as $backup): ?>
                                    <tr>
                                        <td>
                                            <strong>📁 <?php echo escape($backup['filename']); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo date('d.m.Y H:i:s', $backup['created']); ?>
                                        </td>
                                        <td>
                                            <?php echo format_bytes($backup['size']); ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="restore">
                                                    <input type="hidden" name="file" value="<?php echo escape($backup['filename']); ?>">
                                                    <button type="submit" class="btn btn-sm btn-secondary" 
                                                            onclick="return confirm('<?php echo escape($lang['Restore backup confirmation']); ?>');"
                                                            title="<?php echo escape($lang['Restore']); ?>">
                                                        🔄
                                                    </button>
                                                </form>
                                                
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="file" value="<?php echo escape($backup['filename']); ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                            onclick="return confirm('<?php echo escape($lang['Delete backup confirmation']); ?>');"
                                                            title="<?php echo escape($lang['Delete Permanently']); ?>">
                                                        🗑️
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
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

</body>
</html>