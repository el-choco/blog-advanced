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

            <!-- Export/Import Section -->
            <div class="admin-panel">
                <div class="panel-header">
                    <h2>📦 <?php echo escape($lang['Export Import'] ?? 'Export / Import'); ?></h2>
                </div>
                <div class="panel-body">
                    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                        <!-- Export Section -->
                        <div style="flex: 1; min-width: 280px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                            <h3 style="margin: 0 0 15px 0; font-size: 16px;">📤 <?php echo escape($lang['Export'] ?? 'Export'); ?></h3>
                            <p style="color: #666; font-size: 13px; margin-bottom: 15px;">
                                <?php echo escape($lang['Export description'] ?? 'Create a full backup ZIP including database and media files.'); ?>
                            </p>
                            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                                <a href="../ajax.php?action=export_zip" class="btn btn-primary" id="export-zip-btn">
                                    📦 <?php echo escape($lang['Export ZIP'] ?? 'Export ZIP'); ?>
                                </a>
                                <a href="../ajax.php?action=export_json" class="btn btn-secondary">
                                    📄 <?php echo escape($lang['Export JSON'] ?? 'Export JSON'); ?>
                                </a>
                                <a href="../ajax.php?action=export_csv" class="btn btn-secondary">
                                    📊 <?php echo escape($lang['Export CSV'] ?? 'Export CSV'); ?>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Import Section -->
                        <div style="flex: 1; min-width: 280px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                            <h3 style="margin: 0 0 15px 0; font-size: 16px;">📥 <?php echo escape($lang['Import'] ?? 'Import'); ?></h3>
                            <p style="color: #666; font-size: 13px; margin-bottom: 15px;">
                                <?php echo escape($lang['Import description'] ?? 'Restore from a backup ZIP or import data from JSON.'); ?>
                            </p>
                            
                            <form id="import-form" enctype="multipart/form-data" style="margin-bottom: 10px;">
                                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                                    <input type="file" name="file" id="import-file" accept=".zip,.json" 
                                           style="flex: 1; min-width: 150px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    <button type="submit" class="btn btn-warning" id="import-btn">
                                        📥 <?php echo escape($lang['Import'] ?? 'Import'); ?>
                                    </button>
                                </div>
                            </form>
                            
                            <div id="import-status" style="margin-top: 10px; display: none;">
                                <div class="progress-bar" style="height: 4px; background: #e0e0e0; border-radius: 2px; overflow: hidden;">
                                    <div id="import-progress" style="height: 100%; width: 0%; background: #4CAF50; transition: width 0.3s;"></div>
                                </div>
                                <p id="import-message" style="margin-top: 8px; font-size: 13px; color: #666;"></p>
                            </div>
                        </div>
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
                                    <th><?php echo escape($lang['Type'] ?? 'Type'); ?></th>
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
                                            <?php 
                                            $type = $backup['type'] ?? pathinfo($backup['filename'], PATHINFO_EXTENSION);
                                            $typeLabel = strtoupper($type);
                                            $typeIcon = $type === 'zip' ? '📦' : ($type === 'json' ? '📄' : '💾');
                                            echo $typeIcon . ' ' . escape($typeLabel);
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo date('d.m.Y H:i:s', $backup['created']); ?>
                                        </td>
                                        <td>
                                            <?php echo format_bytes($backup['size']); ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if($type === 'sql'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="restore">
                                                    <input type="hidden" name="file" value="<?php echo escape($backup['filename']); ?>">
                                                    <button type="submit" class="btn btn-sm btn-secondary" 
                                                            onclick="return confirm('<?php echo escape($lang['Restore backup confirmation']); ?>');"
                                                            title="<?php echo escape($lang['Restore']); ?>">
                                                        🔄
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                                
                                                <a href="../data/backups/<?php echo escape($backup['filename']); ?>" 
                                                   class="btn btn-sm btn-secondary" 
                                                   download
                                                   title="<?php echo escape($lang['Download'] ?? 'Download'); ?>">
                                                    ⬇️
                                                </a>
                                                
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const importForm = document.getElementById('import-form');
        const importFile = document.getElementById('import-file');
        const importBtn = document.getElementById('import-btn');
        const importStatus = document.getElementById('import-status');
        const importProgress = document.getElementById('import-progress');
        const importMessage = document.getElementById('import-message');
        
        importForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const file = importFile.files[0];
            if (!file) {
                alert('<?php echo escape($lang['Please select a file'] ?? 'Please select a file'); ?>');
                return;
            }
            
            const ext = file.name.split('.').pop().toLowerCase();
            if (ext !== 'zip' && ext !== 'json') {
                alert('<?php echo escape($lang['Invalid file type'] ?? 'Invalid file type. Only ZIP or JSON files are allowed.'); ?>');
                return;
            }
            
            const action = ext === 'zip' ? 'import_zip' : 'import_json';
            
            if (!confirm('<?php echo escape($lang['Import confirmation'] ?? 'This will import data and may overwrite existing data. Continue?'); ?>')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('file', file);
            formData.append('action', action);
            
            importBtn.disabled = true;
            importStatus.style.display = 'block';
            importProgress.style.width = '10%';
            importMessage.textContent = '<?php echo escape($lang['Uploading'] ?? 'Uploading...'); ?>';
            importMessage.style.color = '#666';
            
            fetch('../ajax.php?action=' + action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                importProgress.style.width = '100%';
                
                if (data.error) {
                    importMessage.textContent = '❌ ' + data.msg;
                    importMessage.style.color = '#dc3545';
                    importProgress.style.background = '#dc3545';
                } else {
                    importMessage.textContent = '✅ ' + (data.msg || '<?php echo escape($lang['Import successful'] ?? 'Import successful!'); ?>');
                    importMessage.style.color = '#28a745';
                    importProgress.style.background = '#28a745';
                    
                    // Reload page after successful import
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                }
            })
            .catch(error => {
                importProgress.style.width = '100%';
                importProgress.style.background = '#dc3545';
                importMessage.textContent = '❌ <?php echo escape($lang['Error'] ?? 'Error'); ?>: ' + error.message;
                importMessage.style.color = '#dc3545';
            })
            .finally(function() {
                importBtn.disabled = false;
            });
        });
    });
    </script>

</body>
</html>