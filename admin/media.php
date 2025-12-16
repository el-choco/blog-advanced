<?php
require_once 'common.php';

// Handle actions
$message = '';
$message_type = '';

// Resolve directory paths from config with safe defaults
$images_path_cfg     = rtrim(trim(Config::get('images_path', 'data/i/')), '/').'/';
$thumbnails_path_cfg = rtrim(trim(Config::get('thumbnails_path', 'data/t/')), '/').'/';

// Build absolute paths
$images_dir_abs      = PROJECT_PATH . $images_path_cfg;       // e.g. PROJECT_PATH.'data/i/'
$thumbnails_dir_abs  = PROJECT_PATH . $thumbnails_path_cfg;   // e.g. PROJECT_PATH.'data/t/'
$uploads_images_abs  = PROJECT_PATH . 'uploads/images/';
$uploads_files_abs   = PROJECT_PATH . 'uploads/files/';

// Allowed directories for delete/bulk delete (include all scanned dirs)
$allowed_dirs = [
    $images_dir_abs,
    $thumbnails_dir_abs,
    $uploads_images_abs,
    $uploads_files_abs,
];

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Delete file
    if($action === 'delete_file' && isset($_POST['file_path'])) {
        $file_path = $_POST['file_path'];
        
        // Security: ensure file is within allowed directories
        $is_allowed = false;
        $real_file = @realpath($file_path);
        foreach($allowed_dirs as $dir) {
            $real_dir = @realpath($dir);
            if($real_file && $real_dir && strpos($real_file, $real_dir) === 0) {
                $is_allowed = true;
                break;
            }
        }
        
        if($is_allowed && is_file($file_path)) {
            if(@unlink($file_path)) {
                $message = $lang['File deleted'];
                $message_type = 'success';
            } else {
                $message = $lang['Error deleting file'];
                $message_type = 'error';
            }
        } else {
            $message = $lang['File not found or not allowed'];
            $message_type = 'error';
        }
    }
    
    // Bulk delete
    if($action === 'bulk_delete' && isset($_POST['file_paths']) && is_array($_POST['file_paths'])) {
        $file_paths = $_POST['file_paths'];
        $count = 0;
        
        foreach($file_paths as $file_path) {
            $is_allowed = false;
            $real_file = @realpath($file_path);
            foreach($allowed_dirs as $dir) {
                $real_dir = @realpath($dir);
                if($real_file && $real_dir && strpos($real_file, $real_dir) === 0) {
                    $is_allowed = true;
                    break;
                }
            }
            
            if($is_allowed && is_file($file_path)) {
                if(@unlink($file_path)) {
                    $count++;
                }
            }
        }
        
        $message = "$count " . $lang['files were deleted'];
        $message_type = 'success';
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'all';

// Collect all media files
$media_files = [];

// Helper to add file into list
$addFile = function($file, $typeOverride = null) use (&$media_files) {
    if(!is_file($file)) return;
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $is_image = in_array($ext, ['jpg','jpeg','png','gif','webp','svg','bmp','avif']);

    $type = $typeOverride ?: ($is_image ? 'image' : 'file');

    $media_files[] = [
        'path'     => $file,
        'name'     => basename($file),
        'size'     => @filesize($file) ?: 0,
        'type'     => $type,
        'modified' => @filemtime($file) ?: 0,
        'url'      => str_replace(PROJECT_PATH, '', $file),
        'ext'      => $ext
    ];
};

// Scan images_path (configured)
if(is_dir($images_dir_abs)) {
    foreach(glob($images_dir_abs . '*') as $file) {
        $addFile($file, 'image');
    }
}

// Scan thumbnails_path (optional – meist ebenfalls Bilder)
// Wenn du Thumbnails NICHT anzeigen willst, kommentiere diesen Block aus.
if(is_dir($thumbnails_dir_abs)) {
    foreach(glob($thumbnails_dir_abs . '*') as $file) {
        $addFile($file, 'image');
    }
}

// Scan uploads/images
if(is_dir($uploads_images_abs)) {
    foreach(glob($uploads_images_abs . '*') as $file) {
        $addFile($file, 'image');
    }
}

// Scan uploads/files (kann Bilder oder andere Dateien enthalten)
if(is_dir($uploads_files_abs)) {
    foreach(glob($uploads_files_abs . '*') as $file) {
        $addFile($file, null);
    }
}

// Apply filter
if($filter === 'images') {
    $media_files = array_filter($media_files, fn($f) => $f['type'] === 'image');
} elseif($filter === 'files') {
    $media_files = array_filter($media_files, fn($f) => $f['type'] === 'file');
}

// Sort by modified date (newest first)
usort($media_files, fn($a, $b) => ($b['modified'] ?? 0) - ($a['modified'] ?? 0));

// Count by type
$count_all   = count($media_files);
$count_images= count(array_filter($media_files, fn($f) => $f['type'] === 'image'));
$count_files = count(array_filter($media_files, fn($f) => $f['type'] === 'file'));

// Calculate total size
$total_size = array_sum(array_map(fn($f) => $f['size'], $media_files));

function formatFileSize($bytes) {
    if($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}
?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo escape($lang['Files']); ?> - <?php echo escape(Config::get("title")); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    
    <link href="../static/styles/main.css" rel="stylesheet" type="text/css" />
    <?php
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
    .media-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .media-stat-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .media-stat-label {
        font-size: 13px;
        color: #666;
        margin-bottom: 8px;
    }
    
    .media-stat-value {
        font-size: 24px;
        font-weight: 700;
        color: #1877f2;
    }
    
    .filter-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    
    .filter-tab {
        padding: 8px 16px;
        border-radius: 6px;
        text-decoration: none;
        color: #666;
        background: white;
        border: 1px solid #e5e5e5;
        transition: all 0.2s;
        font-size: 14px;
    }
    
    .filter-tab:hover {
        background: #f0f2f5;
    }
    
    .filter-tab.active {
        background: #1877f2;
        color: white;
        border-color: #1877f2;
        font-weight: 600;
    }
    
    .filter-count {
        background: rgba(0,0,0,0.1);
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 12px;
        margin-left: 5px;
    }
    
    .media-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
        padding: 20px;
    }
    
    .media-item {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: transform 0.2s, box-shadow 0.2s;
        position: relative;
    }
    
    .media-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .media-item.selected {
        outline: 3px solid #1877f2;
    }
    
    .media-preview {
        width: 100%;
        height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f0f2f5;
        position: relative;
        overflow: hidden;
    }
    
    .media-preview img {
        max-width: 100%;
        max-height: 100%;
        object-fit: cover;
        width: 100%;
        height: 100%;
    }
    
    .media-file-icon {
        font-size: 64px;
    }
    
    .media-checkbox {
        position: absolute;
        top: 10px;
        left: 10px;
        width: 20px;
        height: 20px;
        cursor: pointer;
        z-index: 10;
    }
    
    .media-info {
        padding: 12px;
    }
    
    .media-name {
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 5px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .media-meta {
        font-size: 11px;
        color: #999;
        display: flex;
        justify-content: space-between;
    }
    
    .media-actions {
        padding: 10px;
        border-top: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-around;
    }
    
    .media-action-btn {
        background: transparent;
        border: none;
        cursor: pointer;
        font-size: 18px;
        padding: 5px 10px;
        border-radius: 4px;
        transition: background 0.2s;
    }
    
    .media-action-btn:hover {
        background: #f0f2f5;
    }
    
    .bulk-actions-bar {
        display: none !important;
        background: #fff3cd;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        display: none;
        align-items: center;
        gap: 15px;
    }
    
    .bulk-actions-bar.active {
        display: flex;
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
    
    .message-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .empty-media {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }
    
    .empty-media-icon {
        font-size: 64px;
        margin-bottom: 20px;
    }
    </style>
</head>
<body class="admin-body">
    
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="admin-container">
            <h1>📁 <?php echo escape($lang['Files']); ?></h1>
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
                <a href="media.php" class="active">📁 <?php echo escape($lang['Files']); ?> <span class="badge"><?php echo $count_all; ?></span></a>
                <a href="backups.php">💾 <?php echo escape($lang['Backups']); ?></a>
                <a href="trash.php">🗑️ <?php echo escape($lang['Trash']); ?></a>
                <a href="categories.php">🏷️ <?php echo escape($lang['Categories']); ?></a>
                <a href="theme_editor.php">🎨 <?php echo escape($lang['Theme Editor']); ?></a>
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
            
            <!-- Stats -->
            <div class="media-stats">
                <div class="media-stat-card">
                    <div class="media-stat-label"><?php echo escape($lang['Total Files']); ?></div>
                    <div class="media-stat-value"><?php echo $count_all; ?></div>
                </div>
                <div class="media-stat-card">
                    <div class="media-stat-label"><?php echo escape($lang['Images']); ?></div>
                    <div class="media-stat-value"><?php echo $count_images; ?></div>
                </div>
                <div class="media-stat-card">
                    <div class="media-stat-label"><?php echo escape($lang['Other Files']); ?></div>
                    <div class="media-stat-value"><?php echo $count_files; ?></div>
                </div>
                <div class="media-stat-card">
                    <div class="media-stat-label"><?php echo escape($lang['Total Size']); ?></div>
                    <div class="media-stat-value" style="font-size: 20px;"><?php echo formatFileSize($total_size); ?></div>
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    <?php echo escape($lang['All']); ?> <span class="filter-count"><?php echo $count_all; ?></span>
                </a>
                <a href="?filter=images" class="filter-tab <?php echo $filter === 'images' ? 'active' : ''; ?>">
                    🖼️ <?php echo escape($lang['Images']); ?> <span class="filter-count"><?php echo $count_images; ?></span>
                </a>
                <a href="?filter=files" class="filter-tab <?php echo $filter === 'files' ? 'active' : ''; ?>">
                    📄 <?php echo escape($lang['Files']); ?> <span class="filter-count"><?php echo $count_files; ?></span>
                </a>
            </div>
            
            <?php if(empty($media_files)): ?>
                
                <!-- Empty State -->
                <div class="admin-panel">
                    <div class="panel-body">
                        <div class="empty-media">
                            <div class="empty-media-icon">📁</div>
                            <h2><?php echo escape($lang['No files available']); ?></h2>
                            <p><?php echo escape($lang['Upload images or files in your posts']); ?></p>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                
                <!-- Bulk Actions Bar -->
                <form method="POST" id="bulkForm">
                    <input type="hidden" name="action" value="bulk_delete">
                    
                    <div class="bulk-actions-bar" id="bulkBar">
                        <span id="selectedCount">0 <?php echo escape($lang['selected']); ?></span>
                        <button type="submit" class="btn-danger" onclick="return confirm('<?php echo escape($lang['Delete selected files confirmation']); ?>');">🗑️ <?php echo escape($lang['Delete Permanently']); ?></button>
                        <button type="button" class="btn btn-sm" onclick="clearSelection()"><?php echo escape($lang['Cancel']); ?></button>
                    </div>
                    
                    <!-- Media Grid -->
                    <div class="admin-panel">
                        <div class="media-grid">
                            <?php foreach($media_files as $file): ?>
                                <div class="media-item" data-path="<?php echo escape($file['path']); ?>">
                                    <div class="media-preview">
                                        <input type="checkbox" name="file_paths[]" value="<?php echo escape($file['path']); ?>" class="media-checkbox">
                                        
                                        <?php if($file['type'] === 'image'): ?>
                                            <img src="../<?php echo escape($file['url']); ?>" alt="<?php echo escape($file['name']); ?>" loading="lazy">
                                        <?php else: ?>
                                            <div class="media-file-icon">📄</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="media-info">
                                        <div class="media-name" title="<?php echo escape($file['name']); ?>">
                                            <?php echo escape($file['name']); ?>
                                        </div>
                                        <div class="media-meta">
                                            <span><?php echo formatFileSize($file['size']); ?></span>
                                            <span><?php echo date('d.m.Y', $file['modified']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="media-actions">
                                        <a href="../<?php echo escape($file['url']); ?>" target="_blank" class="media-action-btn" title="<?php echo escape($lang['Open']); ?>">👁️</a>
                                        <a href="../<?php echo escape($file['url']); ?>" download class="media-action-btn" title="<?php echo escape($lang['Download']); ?>">⬇️</a>
                                        <button type="button" class="media-action-btn" title="<?php echo escape($lang['Delete Permanently']); ?>" onclick="deleteFile('<?php echo escape($file['path']); ?>', '<?php echo escape($file['name']); ?>')">🗑️</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </form>
                
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
    
    <!-- Hidden delete form -->
    <form method="POST" id="deleteForm" style="display: none;">
        <input type="hidden" name="action" value="delete_file">
        <input type="hidden" name="file_path" id="deleteFilePath">
    </form>
    
    <script>
    // Checkbox functionality
    document.querySelectorAll('.media-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const item = this.closest('.media-item');
            if(this.checked) {
                item.classList.add('selected');
            } else {
                item.classList.remove('selected');
            }
            updateBulkBar();
        });
    });
    
    function updateBulkBar() {
        const checked = document.querySelectorAll('.media-checkbox:checked');
        const bulkBar = document.getElementById('bulkBar');
        const count = document.getElementById('selectedCount');
        
        if(checked.length > 0) {
            bulkBar.classList.add('active');
            count.textContent = checked.length + ' <?php echo escape($lang['selected']); ?>';
        } else {
            bulkBar.classList.remove('active');
        }
    }
    
    function clearSelection() {
        document.querySelectorAll('.media-checkbox').forEach(cb => {
            cb.checked = false;
            cb.closest('.media-item').classList.remove('selected');
        });
        updateBulkBar();
    }
    
    function deleteFile(path, name) {
        if(confirm('<?php echo escape($lang['Delete file confirmation']); ?> "' + name + '"?')) {
            document.getElementById('deleteFilePath').value = path;
            document.getElementById('deleteForm').submit();
        }
    }
    </script>
    
</body>
</html>