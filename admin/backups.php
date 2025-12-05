<?php
require_once 'common.php';

$page_title = $lang['Backup Management'] ?? 'Backup Management';
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
                $success = ($lang['Backup created successfully'] ?? 'Backup created successfully') . ': ' . $backup['filename'];
                break;
                
            case 'restore':
                $filename = $_POST['file'] ?? '';
                if (empty($filename)) {
                    throw new Exception($lang['No backup file specified'] ?? 'No backup file specified');
                }
                Backup::restore($filename);
                $success = $lang['Backup restored successfully'] ?? 'Backup restored successfully';
                break;
                
            case 'delete':
                $filename = $_POST['file'] ?? '';
                if (empty($filename)) {
                    throw new Exception($lang['No backup file specified'] ?? 'No backup file specified');
                }
                Backup::delete($filename);
                $success = $lang['Backup deleted successfully'] ?? 'Backup deleted successfully';
                break;
                
            case 'export_json':
                $result = Backup::export_json();
                $success = ($lang['JSON export created successfully'] ?? 'JSON export created successfully') . ': ' . $result['filename'];
                break;
                
            case 'export_csv':
                $result = Backup::export_csv();
                $success = ($lang['CSV export created successfully'] ?? 'CSV export created successfully') . ': ' . $result['filename'];
                break;
                
            case 'export_full':
                $result = Backup::export_full();
                $success = ($lang['Full backup created successfully'] ?? 'Full backup created successfully') . ': ' . $result['filename'];
                break;
                
            case 'import':
                if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception($lang['Please select a file to import'] ?? 'Please select a file to import');
                }
                
                $file = $_FILES['import_file'];
                $filename = $file['name'];
                $tmpPath = $file['tmp_name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if ($ext === 'json') {
                    $stats = Backup::import_json($tmpPath);
                    $success = ($lang['Import completed successfully'] ?? 'Import completed successfully') . ': ' .
                               $stats['categories'] . ' ' . ($lang['categories imported'] ?? 'categories imported') . ', ' .
                               $stats['posts'] . ' ' . ($lang['posts imported'] ?? 'posts imported') . ', ' .
                               $stats['comments'] . ' ' . ($lang['comments imported'] ?? 'comments imported');
                } elseif ($ext === 'zip') {
                    $stats = Backup::import_full($tmpPath);
                    $success = ($lang['Full backup restored successfully'] ?? 'Full backup restored successfully') . ': ' .
                               $stats['categories'] . ' ' . ($lang['categories imported'] ?? 'categories imported') . ', ' .
                               $stats['posts'] . ' ' . ($lang['posts imported'] ?? 'posts imported') . ', ' .
                               $stats['comments'] . ' ' . ($lang['comments imported'] ?? 'comments imported') . ', ' .
                               ($stats['media_files'] ?? 0) . ' ' . ($lang['media files restored'] ?? 'media files restored');
                } else {
                    throw new Exception($lang['Invalid file format'] ?? 'Invalid file format');
                }
                break;
                
            case 'download':
                $filename = $_POST['file'] ?? '';
                if (empty($filename)) {
                    throw new Exception($lang['No backup file specified'] ?? 'No backup file specified');
                }
                $filepath = Backup::download($filename);
                
                // Send file headers and content
                $mime = 'application/octet-stream';
                if (preg_match('/\.json$/', $filename)) {
                    $mime = 'application/json';
                } elseif (preg_match('/\.zip$/', $filename)) {
                    $mime = 'application/zip';
                } elseif (preg_match('/\.sql$/', $filename)) {
                    $mime = 'application/sql';
                }
                
                header('Content-Type: ' . $mime);
                header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
                header('Content-Length: ' . filesize($filepath));
                header('Cache-Control: private, max-age=0, must-revalidate');
                header('Pragma: public');
                readfile($filepath);
                exit;
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

// Helper function to get backup type label
function get_backup_type_label($type, $lang) {
    switch ($type) {
        case 'json': return $lang['JSON Export'] ?? 'JSON Export';
        case 'csv': return $lang['CSV Export'] ?? 'CSV Export';
        case 'full': return $lang['Full Backup ZIP'] ?? 'Full Backup ZIP';
        case 'sql': 
        default: return $lang['SQL Backup'] ?? 'SQL Backup';
    }
}

// Helper function to get backup type icon
function get_backup_type_icon($type) {
    switch ($type) {
        case 'json': return '📋';
        case 'csv': return '📊';
        case 'full': return '📦';
        case 'sql':
        default: return '🗄️';
    }
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
                    <h2><?php echo escape($lang['Create New Backup'] ?? 'Create New Backup'); ?></h2>
                </div>
                <div class="panel-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="create">
                        <button type="submit" class="btn btn-primary" style="font-size: 16px;">
                            🗄️ <?php echo escape($lang['Create New Backup'] ?? 'Create New Backup'); ?>
                        </button>
                        <p style="margin-top: 10px; color: #666; font-size: 14px;">
                            <?php echo escape($lang['Creates a complete database backup as SQL file'] ?? 'Creates a complete database backup as SQL file'); ?>
                        </p>
                    </form>
                </div>
            </div>

            <!-- Export / Import Panel -->
            <div class="admin-panel">
                <div class="panel-header">
                    <h2>📤 <?php echo escape($lang['Export Import'] ?? 'Export / Import'); ?></h2>
                </div>
                <div class="panel-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                        <!-- Export JSON -->
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
                            <h3 style="margin: 0 0 10px 0; font-size: 16px;">📋 <?php echo escape($lang['Export JSON'] ?? 'Export JSON'); ?></h3>
                            <p style="color: #666; font-size: 13px; margin: 0 0 15px 0;">
                                <?php echo escape($lang['Creates JSON export with posts categories and comments'] ?? 'Creates JSON export with posts, categories and comments'); ?>
                            </p>
                            <form method="POST">
                                <input type="hidden" name="action" value="export_json">
                                <button type="submit" class="btn btn-secondary" style="width: 100%;">
                                    <?php echo escape($lang['Export as JSON'] ?? 'Export as JSON'); ?>
                                </button>
                            </form>
                        </div>
                        
                        <!-- Export CSV -->
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
                            <h3 style="margin: 0 0 10px 0; font-size: 16px;">📊 <?php echo escape($lang['Export CSV'] ?? 'Export CSV'); ?></h3>
                            <p style="color: #666; font-size: 13px; margin: 0 0 15px 0;">
                                <?php echo escape($lang['Creates CSV files in a ZIP archive'] ?? 'Creates CSV files in a ZIP archive'); ?>
                            </p>
                            <form method="POST">
                                <input type="hidden" name="action" value="export_csv">
                                <button type="submit" class="btn btn-secondary" style="width: 100%;">
                                    <?php echo escape($lang['Export as CSV ZIP'] ?? 'Export as CSV ZIP'); ?>
                                </button>
                            </form>
                        </div>
                        
                        <!-- Full Backup -->
                        <div style="background: #e8f4fd; padding: 20px; border-radius: 8px; border: 1px solid #1877f2;">
                            <h3 style="margin: 0 0 10px 0; font-size: 16px;">📦 <?php echo escape($lang['Full Backup'] ?? 'Full Backup'); ?></h3>
                            <p style="color: #666; font-size: 13px; margin: 0 0 15px 0;">
                                <?php echo escape($lang['Creates full backup with database and media files'] ?? 'Creates full backup with database and media files'); ?>
                            </p>
                            <form method="POST">
                                <input type="hidden" name="action" value="export_full">
                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    <?php echo escape($lang['Create Full Backup'] ?? 'Create Full Backup'); ?>
                                </button>
                            </form>
                        </div>
                        
                        <!-- Import -->
                        <div style="background: #fff3cd; padding: 20px; border-radius: 8px; border: 1px solid #ffc107;">
                            <h3 style="margin: 0 0 10px 0; font-size: 16px;">📥 <?php echo escape($lang['Import'] ?? 'Import'); ?></h3>
                            <p style="color: #666; font-size: 13px; margin: 0 0 15px 0;">
                                <?php echo escape($lang['Supported formats JSON and ZIP'] ?? 'Supported formats: JSON and ZIP (full backup)'); ?>
                            </p>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="import">
                                <input type="file" name="import_file" accept=".json,.zip" style="width: 100%; margin-bottom: 10px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                <button type="submit" class="btn btn-warning" style="width: 100%;" onclick="return confirm('<?php echo escape($lang['Restore backup confirmation'] ?? 'Really restore backup? This will overwrite the current database!'); ?>');">
                                    <?php echo escape($lang['Import Backup'] ?? 'Import Backup'); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backup List -->
            <div class="admin-panel">
                <div class="panel-header">
                    <h2><?php echo escape($lang['Available Backups'] ?? 'Available Backups'); ?></h2>
                </div>
                <div class="panel-body">
                    <?php if(empty($backups)): ?>
                        <div class="empty-state">
                            <div style="font-size: 64px; opacity: 0.3; margin-bottom: 20px;">📦</div>
                            <h3><?php echo escape($lang['No backups available yet'] ?? 'No backups available yet'); ?></h3>
                            <p style="color: #666;"><?php echo escape($lang['Click Create New Backup to create the first backup'] ?? 'Click Create New Backup to create the first backup'); ?></p>
                        </div>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th><?php echo escape($lang['Filename'] ?? 'Filename'); ?></th>
                                    <th><?php echo escape($lang['Type'] ?? 'Type'); ?></th>
                                    <th><?php echo escape($lang['Created'] ?? 'Created'); ?></th>
                                    <th><?php echo escape($lang['Size'] ?? 'Size'); ?></th>
                                    <th><?php echo escape($lang['Actions'] ?? 'Actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($backups as $backup): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo get_backup_type_icon($backup['type'] ?? 'sql'); ?> <?php echo escape($backup['filename']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-info"><?php echo escape(get_backup_type_label($backup['type'] ?? 'sql', $lang)); ?></span>
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
                                                    <input type="hidden" name="action" value="download">
                                                    <input type="hidden" name="file" value="<?php echo escape($backup['filename']); ?>">
                                                    <button type="submit" class="btn btn-sm btn-primary" 
                                                            title="<?php echo escape($lang['Download'] ?? 'Download'); ?>">
                                                        ⬇️
                                                    </button>
                                                </form>
                                                
                                                <?php if (($backup['type'] ?? 'sql') === 'sql'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="restore">
                                                    <input type="hidden" name="file" value="<?php echo escape($backup['filename']); ?>">
                                                    <button type="submit" class="btn btn-sm btn-secondary" 
                                                            onclick="return confirm('<?php echo escape($lang['Restore backup confirmation'] ?? 'Really restore backup?'); ?>');"
                                                            title="<?php echo escape($lang['Restore'] ?? 'Restore'); ?>">
                                                        🔄
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                                
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="file" value="<?php echo escape($backup['filename']); ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                            onclick="return confirm('<?php echo escape($lang['Delete backup confirmation'] ?? 'Really delete backup?'); ?>');"
                                                            title="<?php echo escape($lang['Delete Permanently'] ?? 'Delete Permanently'); ?>">
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
                    <h2><?php echo escape($lang['Quick Access'] ?? 'Quick Access'); ?></h2>
                </div>
                <div class="panel-body">
                    <div class="quick-actions">
                        <a href="../#new-post" class="quick-action-card">
                            <div class="qa-icon">✏️</div>
                            <div class="qa-label"><?php echo escape($lang['New Post'] ?? 'New Post'); ?></div>
                        </a>
                        <a href="backups.php" class="quick-action-card">
                            <div class="qa-icon">💾</div>
                            <div class="qa-label"><?php echo escape($lang['Backups'] ?? 'Backups'); ?></div>
                        </a>
                        <a href="comments.php" class="quick-action-card">
                            <div class="qa-icon">💬</div>
                            <div class="qa-label"><?php echo escape($lang['Comments'] ?? 'Comments'); ?></div>
                        </a>
                        <a href="posts.php" class="quick-action-card">
                            <div class="qa-icon">📝</div>
                            <div class="qa-label"><?php echo escape($lang['Manage Posts'] ?? 'Manage Posts'); ?></div>
                        </a>
                        <a href="media.php" class="quick-action-card">
                            <div class="qa-icon">📁</div>
                            <div class="qa-label"><?php echo escape($lang['Files'] ?? 'Files'); ?></div>
                        </a>
                        <a href="trash.php" class="quick-action-card">
                            <div class="qa-icon">🗑️</div>
                            <div class="qa-label"><?php echo escape($lang['Trash'] ?? 'Trash'); ?></div>
                        </a>
                    </div>
                </div>
            </div>

        </main>

    </div>

</body>
</html>