#!/bin/bash

###############################################################################
# BULK DELETE KOMPLETT ENTFERNEN
# Entfernt Bulk-Funktionalität aus allen Admin-Dateien
# Datum: 2025-11-24
# User: el-choco
###############################################################################

TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BLOG_DIR="/mnt/intenso2tb/blog-advanced"

cd "$BLOG_DIR" || exit 1

echo "==================================================================="
echo "🗑️  BULK DELETE REMOVAL SCRIPT"
echo "==================================================================="
echo ""
echo "Zeitstempel: $TIMESTAMP"
echo "Verzeichnis: $BLOG_DIR"
echo ""

###############################################################################
# BACKUPS ERSTELLEN
###############################################################################

echo "📦 Erstelle Backups..."
echo ""

cp admin/posts.php "admin/posts.php.backup-no-bulk-$TIMESTAMP"
cp admin/trash.php "admin/trash.php.backup-no-bulk-$TIMESTAMP"
cp admin/media.php "admin/media.php.backup-no-bulk-$TIMESTAMP"
cp admin/common.php "admin/common.php.backup-no-bulk-$TIMESTAMP"

echo "✅ Backups erstellt:"
echo "   - admin/posts.php.backup-no-bulk-$TIMESTAMP"
echo "   - admin/trash.php.backup-no-bulk-$TIMESTAMP"
echo "   - admin/media.php.backup-no-bulk-$TIMESTAMP"
echo "   - admin/common.php.backup-no-bulk-$TIMESTAMP"
echo ""

###############################################################################
# 1. ADMIN/POSTS.PHP - Bulk Code entfernen
###############################################################################

echo "==================================================================="
echo "🔧 1/4: admin/posts.php"
echo "==================================================================="
echo ""

cat > admin/posts.php << 'ENDPHP1'
<?php
require_once 'common.php';

// Handle actions
$message = '';
$message_type = '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Single Quick Action
    if(isset($_POST['post_id']) && isset($_POST['quick_action'])) {
        $post_id = $_POST['post_id'];
        $quick_action = $_POST['quick_action'];
        
        $db = DB::get_instance();
        
        switch($quick_action) {
            case 'toggle_sticky':
                // Get current status
                $current = $db->query("SELECT is_sticky FROM posts WHERE id = ?", [$post_id])->first();
                
                if ($current) {
                    $new_sticky = $current['is_sticky'] ? 0 : 1;
                    $db->query("UPDATE posts SET is_sticky = ? WHERE id = ?", [$new_sticky, $post_id]);
                    $message = $new_sticky ? 'Beitrag als Sticky markiert' : 'Sticky entfernt';
                    $message_type = 'success';
                }
                break;

            case 'toggle_hidden':
                // Get current status
                $current = $db->query("SELECT status FROM posts WHERE id = ?", [$post_id])->first();
                
                if ($current) {
                    $new_status = ($current['status'] == 4) ? 1 : 4;
                    $db->query("UPDATE posts SET status = ? WHERE id = ?", [$new_status, $post_id]);
                    $message = ($new_status == 4) ? 'Beitrag versteckt' : 'Beitrag sichtbar';
                    $message_type = 'success';
                }
                break;
                
            case 'delete':
                $db->query("UPDATE posts SET status = ? WHERE id = ?", [5, $post_id]);
                $message = 'Beitrag in den Papierkorb verschoben';
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

function escape($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Beiträge - <?php echo escape(Config::get("title")); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    
    <link href="../static/styles/main.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/<?php echo rawurlencode(Config::get_safe("theme", "theme01")); ?>.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/custom1.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/admin.css" rel="stylesheet" type="text/css" />
    
    <link href="https://fonts.googleapis.com/css?family=Open+Sans&amp;subset=all" rel="stylesheet">
    
    <style>
    /* Bulk Actions komplett versteckt */
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
    </style>
</head>
<body class="admin-body">
    
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="admin-container">
            <h1>📝 Beiträge verwalten</h1>
            <div class="admin-user">
                <span>👤 <?php echo escape(Config::get("name")); ?></span>
                <a href="../" class="btn btn-sm">← Zurück zum Blog</a>
            </div>
        </div>
    </div>
    
    <div class="admin-layout">
        
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar">
            <nav class="admin-nav">
                <a href="index.php">📊 Dashboard</a>
                <a href="posts.php" class="active">📝 Beiträge</a>
                <a href="comments.php">💬 Kommentare</a>
                <a href="media.php">📁 Dateien</a>
                <a href="trash.php">🗑️ Papierkorb <?php if($trash_count > 0): ?><span class="badge"><?php echo $trash_count; ?></span><?php endif; ?></a>
                <a href="settings.php">⚙️ Einstellungen</a>
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
                    <div class="stat-label">Gesamt</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['published']; ?></div>
                    <div class="stat-label">Veröffentlicht</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['hidden']; ?></div>
                    <div class="stat-label">Versteckt</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['sticky']; ?></div>
                    <div class="stat-label">Sticky</div>
                </div>
            </div>
            
            <!-- Filters -->
            <form method="GET" class="filters-bar">
                <select name="filter" class="filter-select" onchange="this.form.submit()">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Alle Beiträge</option>
                    <option value="published" <?php echo $filter === 'published' ? 'selected' : ''; ?>>Veröffentlicht</option>
                    <option value="hidden" <?php echo $filter === 'hidden' ? 'selected' : ''; ?>>Versteckt</option>
                    <option value="sticky" <?php echo $filter === 'sticky' ? 'selected' : ''; ?>>Sticky</option>
                </select>
                
                <select name="sort" class="filter-select" onchange="this.form.submit()">
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Neueste zuerst</option>
                    <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Älteste zuerst</option>
                </select>
                
                <div class="search-box">
                    <input type="text" name="search" class="search-input" placeholder="Beiträge durchsuchen..." value="<?php echo escape($search); ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">Suchen</button>
            </form>
            
            <!-- Posts Table -->
            <div class="admin-panel">
                <div class="panel-header">
                    <h2>📝 <?php echo count($posts); ?> Beiträge</h2>
                </div>
                <div class="panel-body" style="padding: 0;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Inhalt</th>
                                <th>Datum</th>
                                <th>Status</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($posts)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: #999;">
                                        Keine Beiträge gefunden
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($posts as $post): ?>
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
                                            <?php if($post['is_sticky']): ?>
                                                <span class="badge badge-sticky">📌 Sticky</span>
                                            <?php endif; ?>
                                            <?php if($post['status'] == 4): ?>
                                                <span class="badge badge-hidden">👁️ Versteckt</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="">
                                                    <input type="hidden" name="post_id" value="<?php echo escape($post['id']); ?>">
                                                    <input type="hidden" name="quick_action" value="toggle_sticky">
                                                    <button type="submit" class="quick-action-btn" title="<?php echo $post['is_sticky'] ? 'Sticky entfernen' : 'Als Sticky markieren'; ?>">
                                                        📌
                                                    </button>
                                                </form>
                                                
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="">
                                                    <input type="hidden" name="post_id" value="<?php echo escape($post['id']); ?>">
                                                    <input type="hidden" name="quick_action" value="toggle_hidden">
                                                    <button type="submit" class="quick-action-btn" title="<?php echo ($post['status'] == 4) ? 'Sichtbar machen' : 'Verstecken'; ?>">
                                                        👁️
                                                    </button>
                                                </form>
                                                
                                                <a href="edit.php?id=<?php echo $post['id']; ?>" class="quick-action-btn" title="Bearbeiten">✏️</a>
                                                
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Beitrag in den Papierkorb verschieben?');">
                                                    <input type="hidden" name="action" value="">
                                                    <input type="hidden" name="post_id" value="<?php echo escape($post['id']); ?>">
                                                    <input type="hidden" name="quick_action" value="delete">
                                                    <button type="submit" class="quick-action-btn" title="In Papierkorb" style="color: #dc3545;">🗑️</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </main>
        
    </div>
    
</body>
</html>
ENDPHP1

echo "✅ admin/posts.php - Bulk Code entfernt"
echo ""

###############################################################################
# 2. ADMIN/TRASH.PHP - Bulk Code entfernen
###############################################################################

echo "==================================================================="
echo "🔧 2/4: admin/trash.php"
echo "==================================================================="
echo ""

cat > admin/trash.php << 'ENDPHP2'
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
            $message = 'Beitrag wiederhergestellt';
            $message_type = 'success';
        } elseif($quick_action === 'delete_permanent') {
            if(AdminHelper::permanentDelete($post_id)) {
                $message = 'Beitrag endgültig gelöscht';
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
        
        $message = "$count Beiträge wurden endgültig gelöscht";
        $message_type = 'success';
    }
}

// Get trash posts
$trash_posts = AdminHelper::getAllPosts(true);

function escape($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Papierkorb - <?php echo escape(Config::get("title")); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    
    <link href="../static/styles/main.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/<?php echo rawurlencode(Config::get_safe("theme", "theme01")); ?>.css" rel="stylesheet" type="text/css" />
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
            <h1>🗑️ Papierkorb</h1>
            <div class="admin-user">
                <span>👤 <?php echo escape(Config::get("name")); ?></span>
                <a href="../" class="btn btn-sm">← Zurück zum Blog</a>
            </div>
        </div>
    </div>
    
    <div class="admin-layout">
        
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar">
            <nav class="admin-nav">
                <a href="index.php">📊 Dashboard</a>
                <a href="posts.php">📝 Beiträge</a>
                <a href="comments.php">💬 Kommentare</a>
                <a href="media.php">📁 Dateien</a>
                <a href="trash.php" class="active">🗑️ Papierkorb <span class="badge"><?php echo count($trash_posts); ?></span></a>
                <a href="settings.php">⚙️ Einstellungen</a>
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
                    <div class="trash-warning-title">Gelöschte Beiträge</div>
                    <div>Beiträge im Papierkorb können wiederhergestellt oder endgültig gelöscht werden.</div>
                </div>
            </div>
            
            <?php if(empty($trash_posts)): ?>
                
                <!-- Empty Trash -->
                <div class="admin-panel">
                    <div class="panel-body">
                        <div class="empty-trash">
                            <div class="empty-trash-icon">🗑️</div>
                            <h2>Papierkorb ist leer</h2>
                            <p>Keine gelöschten Beiträge vorhanden</p>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                
                <!-- Trash Actions -->
                <div class="trash-actions">
                    <div>
                        <strong><?php echo count($trash_posts); ?> Beiträge</strong> im Papierkorb
                    </div>
                    <form method="POST" onsubmit="return confirm('Wirklich ALLE Beiträge im Papierkorb endgültig löschen?');">
                        <input type="hidden" name="action" value="empty_trash">
                        <button type="submit" class="btn-danger">🗑️ Papierkorb leeren</button>
                    </form>
                </div>
                
                <!-- Trash Table -->
                <div class="admin-panel">
                    <div class="panel-body" style="padding: 0;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Inhalt</th>
                                    <th>Gelöscht am</th>
                                    <th>Aktionen</th>
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
                                                    <button type="submit" class="quick-action-btn" title="Wiederherstellen">♻️</button>
                                                </form>
                                                
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Wirklich endgültig löschen? Diese Aktion kann nicht rückgängig gemacht werden!');">
                                                    <input type="hidden" name="action" value="">
                                                    <input type="hidden" name="post_id" value="<?php echo escape($post['id']); ?>">
                                                    <input type="hidden" name="quick_action" value="delete_permanent">
                                                    <button type="submit" class="quick-action-btn" title="Endgültig löschen" style="color: #dc3545;">🗑️</button>
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
            
        </main>
        
    </div>
    
</body>
</html>
ENDPHP2

echo "✅ admin/trash.php - Bulk Code entfernt"
echo ""

###############################################################################
# 3. ADMIN/COMMON.PHP - bulkUpdate Funktion entfernen
###############################################################################

echo "==================================================================="
echo "🔧 3/4: admin/common.php - AdminHelper::bulkUpdate() entfernen"
echo "==================================================================="
echo ""

# Finde Zeile wo bulkUpdate beginnt
BULK_START=$(grep -n "public static function bulkUpdate" admin/common.php | cut -d: -f1)

if [ ! -z "$BULK_START" ]; then
    # Finde Ende der Funktion (nächste "public static function" oder Ende der Klasse)
    BULK_END=$(tail -n +$((BULK_START + 1)) admin/common.php | grep -n "public static function\|^}" | head -1 | cut -d: -f1)
    BULK_END=$((BULK_START + BULK_END - 1))
    
    echo "📍 bulkUpdate() gefunden: Zeile $BULK_START bis $BULK_END"
    
    # Entferne die Funktion
    sed -i "${BULK_START},${BULK_END}d" admin/common.php
    
    echo "✅ admin/common.php - bulkUpdate() entfernt"
else
    echo "ℹ️  admin/common.php - bulkUpdate() nicht gefunden (bereits entfernt?)"
fi

echo ""

###############################################################################
# 4. ADMIN/MEDIA.PHP - Bulk Code kommentieren
###############################################################################

echo "==================================================================="
echo "🔧 4/4: admin/media.php - Bulk Code verstecken"
echo "==================================================================="
echo ""

# Füge CSS hinzu um Bulk UI zu verstecken
if grep -q "\.bulk-actions-bar" admin/media.php; then
    echo "ℹ️  Bulk CSS bereits vorhanden, füge display:none hinzu..."
    
    # Füge display: none !important; zu allen Bulk-Klassen hinzu
    sed -i 's/\.bulk-actions-bar {/\.bulk-actions-bar { display: none !important;/g' admin/media.php
    
    echo "✅ admin/media.php - Bulk UI versteckt"
else
    echo "ℹ️  admin/media.php - kein Bulk CSS gefunden"
fi

echo ""

###############################################################################
# ABSCHLUSS
###############################################################################

echo "==================================================================="
echo "✅ BULK DELETE ERFOLGREICH ENTFERNT!"
echo "==================================================================="
echo ""

echo "📋 ÄNDERUNGEN:"
echo ""
echo "  ✅ admin/posts.php     - Bulk Code komplett entfernt"
echo "  ✅ admin/trash.php     - Bulk Code komplett entfernt"
echo "  ✅ admin/common.php    - bulkUpdate() Funktion entfernt"
echo "  ✅ admin/media.php     - Bulk UI versteckt"
echo ""

echo "💾 BACKUPS unter:"
echo "  📦 admin/posts.php.backup-no-bulk-$TIMESTAMP"
echo "  📦 admin/trash.php.backup-no-bulk-$TIMESTAMP"
echo "  📦 admin/media.php.backup-no-bulk-$TIMESTAMP"
echo "  📦 admin/common.php.backup-no-bulk-$TIMESTAMP"
echo ""

echo "🔍 WAS FUNKTIONIERT NOCH:"
echo ""
echo "  ✅ Einzelnes Löschen (Papierkorb-Icon)"
echo "  ✅ Toggle Sticky (📌)"
echo "  ✅ Toggle Hidden (👁️)"
echo "  ✅ Wiederherstellen aus Papierkorb (♻️)"
echo "  ✅ Endgültig löschen (🗑️)"
echo "  ✅ Papierkorb leeren"
echo ""

echo "❌ WAS NICHT MEHR DA IST:"
echo ""
echo "  ❌ Bulk Delete (mehrere Beiträge auswählen)"
echo "  ❌ Bulk Restore"
echo "  ❌ Bulk Actions Bar"
echo "  ❌ Checkboxen"
echo ""

echo "🧪 TESTE JETZT:"
echo ""
echo "  1. https://blog.home-office.sbs/admin/posts.php"
echo "  2. https://blog.home-office.sbs/admin/trash.php"
echo ""
echo "  Einzelne Aktionen sollten funktionieren!"
echo "  Bulk UI ist komplett weg!"
echo ""

echo "==================================================================="
echo "Script beendet: $(date)"
echo "==================================================================="

