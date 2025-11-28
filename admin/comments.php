<?php
require_once 'common.php';

// Check if user is logged in
if (!User::is_logged_in()) {
    header('Location: ../');
    exit;
}

// Create CSRF token if not exists
if (empty($_SESSION['token'])) {
    if (function_exists('random_bytes')) {
        $_SESSION['token'] = bin2hex(random_bytes(5));
    } else {
        $_SESSION['token'] = bin2hex(openssl_random_pseudo_bytes(5));
    }
}

// Get filter status
$filter_status = $_GET['status'] ?? 'all';
$page = (int)($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Fetch comments
$db = DB::get_instance();

// Build query based on filter
if ($filter_status === 'all') {
    $comments = $db->query("
        SELECT c.*, p.plain_text as post_excerpt
        FROM comments c
        LEFT JOIN posts p ON c.post_id = p.id
        WHERE c.status IN ('pending', 'approved', 'spam')
        ORDER BY c.created_at DESC
        LIMIT ? OFFSET ?
    ", $per_page, $offset)->all();
    
    $total = $db->query("
        SELECT COUNT(*) as count FROM comments 
        WHERE status IN ('pending', 'approved', 'spam')
    ")->first('count');
} else {
    $comments = $db->query("
        SELECT c.*, p.plain_text as post_excerpt
        FROM comments c
        LEFT JOIN posts p ON c.post_id = p.id
        WHERE c.status = ? 
        ORDER BY c.created_at DESC
        LIMIT ? OFFSET ?
    ", $filter_status, $per_page, $offset)->all();
    
    $total = $db->query("
        SELECT COUNT(*) as count FROM comments WHERE status = ?
    ", $filter_status)->first('count');
}

// Count by status
$pending_count = $db->query("SELECT COUNT(*) as count FROM comments WHERE status = 'pending'")->first('count');
$approved_count = $db->query("SELECT COUNT(*) as count FROM comments WHERE status = 'approved'")->first('count');
$spam_count = $db->query("SELECT COUNT(*) as count FROM comments WHERE status = 'spam'")->first('count');

$total_pages = ceil($total / $per_page);

function getStatusBadge($status) {
    global $lang;
    switch($status) {
        case 'pending': return '<span class="badge badge-warning">⏳ ' . escape($lang['Pending']) . '</span>';
        case 'approved': return '<span class="badge badge-success">✅ ' . escape($lang['Approved']) . '</span>';
        case 'spam': return '<span class="badge badge-danger">🚫 ' . escape($lang['Spam']) . '</span>';
        default: return '<span class="badge">❓ ' . escape($lang['Unknown']) . '</span>';
    }
}
?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo escape($lang['Comments']); ?> - <?php echo escape(Config::get("title")); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    
    <link href="../static/styles/main.css" rel="stylesheet" type="text/css" />
    <?php
    // Theme sicher bereinigen (4‑Zeilen‑Variante)
    $theme = Config::get_safe('theme', 'theme01');
    $theme = trim((string)$theme);
    $theme = preg_replace('/\.css$/i', '', $theme);
    $theme = preg_replace('/[^a-zA-Z0-9_-]/', '', $theme);
    if ($theme === '') { $theme = 'theme01'; }
    ?>
    <link href="../static/styles/<?php echo htmlspecialchars($theme, ENT_QUOTES, 'UTF-8'); ?>.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/admin.css" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css?family=Open+Sans&amp;subset=all" rel="stylesheet">
    
    <script src="../static/scripts/jquery.min.js"></script>
    <script>
    // Setup CSRF token for AJAX requests (klar und ohne Obfuscation)
    $.ajaxSetup({
        headers: {
            'Csrf-Token': '<?php echo $_SESSION['token']; ?>'
        }
    });
    </script>
</head>
<body class="admin-body">
    
    <div class="admin-header">
        <div class="admin-container">
            <h1>💬 <?php echo escape($lang['Manage Comments']); ?></h1>
            <div class="admin-user">
                <span>👤 <?php echo escape(Config::get("name")); ?></span>
                <a href="../" class="btn btn-sm">← <?php echo escape($lang['Back to Blog']); ?></a>
            </div>
        </div>
    </div>
    
    <div class="admin-layout">
        
        <aside class="admin-sidebar">
            <nav class="admin-nav">
                <a href="index.php">📊 <?php echo escape($lang['Dashboard']); ?></a>
                <a href="posts.php">📝 <?php echo escape($lang['Posts']); ?></a>
                <a href="comments.php" class="active">💬 <?php echo escape($lang['Comments']); ?> <span class="badge"><?php echo $pending_count; ?></span></a>
                <a href="media.php">📁 <?php echo escape($lang['Files']); ?></a>
                <a href="backups.php">💾 <?php echo escape($lang['Backups']); ?></a>
                <a href="trash.php">🗑️ <?php echo escape($lang['Trash']); ?></a>
                <a href="settings.php">⚙️ <?php echo escape($lang['Settings']); ?></a>
            </nav>
        </aside>
        
        <main class="admin-content">
            
            <!-- Filter Tabs -->
            <div class="filter-tabs" style="margin-bottom: 20px;">
                <a href="?status=all" class="tab <?php echo $filter_status === 'all' ? 'active' : ''; ?>">
                    <?php echo escape($lang['All']); ?> (<?php echo $total; ?>)
                </a>
                <a href="?status=pending" class="tab <?php echo $filter_status === 'pending' ? 'active' : ''; ?>">
                    ⏳ <?php echo escape($lang['Pending']); ?> (<?php echo $pending_count; ?>)
                </a>
                <a href="?status=approved" class="tab <?php echo $filter_status === 'approved' ? 'active' : ''; ?>">
                    ✅ <?php echo escape($lang['Approved']); ?> (<?php echo $approved_count; ?>)
                </a>
                <a href="?status=spam" class="tab <?php echo $filter_status === 'spam' ? 'active' : ''; ?>">
                    🚫 <?php echo escape($lang['Spam']); ?> (<?php echo $spam_count; ?>)
                </a>
            </div>
            
            <!-- Comments Table -->
            <div class="admin-panel">
                <div class="panel-body">
                    <?php if(empty($comments)): ?>
                        <p style="text-align: center; padding: 40px; color: #999;">
                            <?php echo escape($lang['No comments found']); ?>
                        </p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th><?php echo escape($lang['Author']); ?></th>
                                    <th><?php echo escape($lang['Comment']); ?></th>
                                    <th><?php echo escape($lang['Post']); ?></th>
                                    <th><?php echo escape($lang['Date']); ?></th>
                                    <th><?php echo escape($lang['Status']); ?></th>
                                    <th><?php echo escape($lang['Actions']); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($comments as $comment): ?>
                                    <tr data-comment-id="<?php echo $comment['id']; ?>">
                                        <td>
                                            <strong><?php echo escape($comment['author_name']); ?></strong>
                                            <?php if($comment['author_email']): ?>
                                                <br><small><?php echo escape($comment['author_email']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="max-width: 300px; word-wrap: break-word;">
                                                <?php echo escape(substr($comment['content'], 0, 150)); ?>
                                                <?php if(strlen($comment['content']) > 150): ?>... <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="../#id=<?php echo $comment['post_id']; ?>" target="_blank">
                                                <?php echo escape(substr($comment['post_excerpt'] ?? 'Post #' . $comment['post_id'], 0, 50)); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <small><?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?></small>
                                        </td>
                                        <td><?php echo getStatusBadge($comment['status']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if($comment['status'] !== 'approved'): ?>
                                                    <button class="btn btn-sm btn-success approve-btn" data-id="<?php echo $comment['id']; ?>" title="<?php echo escape($lang['Approve']); ?>">✅</button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-danger spam-btn" data-id="<?php echo $comment['id']; ?>" title="<?php echo escape($lang['Mark as Spam']); ?>">🚫</button>
                                                <button class="btn btn-sm btn-secondary delete-btn" data-id="<?php echo $comment['id']; ?>" title="<?php echo escape($lang['Delete Permanently']); ?>">🗑️</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- Pagination -->
                        <?php if($total_pages > 1): ?>
                            <div class="pagination" style="margin-top: 20px; text-align: center;">
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?status=<?php echo $filter_status; ?>&page=<?php echo $i; ?>" 
                                       class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
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
    $(document).ready(function() {
        // Approve comment
        $('.approve-btn').click(function() {
            var commentId = $(this).data('id');
            var row = $('tr[data-comment-id="' + commentId + '"]');
            
            $.post('../ajax.php', {
                action: 'comment_approve',
                id: commentId
            }, function(response) {
                if (response.error) {
                    alert('<?php echo escape($lang['Error']); ?>: ' + response.msg);
                } else {
                    row.fadeOut(300, function() {
                        location.reload();
                    });
                }
            }, 'json');
        });
        
        // Mark as spam
        $('.spam-btn').click(function() {
            if (!confirm('<?php echo escape($lang['Mark as spam confirmation']); ?>')) return;
            
            var commentId = $(this).data('id');
            var row = $('tr[data-comment-id="' + commentId + '"]');
            
            $.post('../ajax.php', {
                action: 'comment_spam',
                id: commentId
            }, function(response) {
                if (response.error) {
                    alert('<?php echo escape($lang['Error']); ?>: ' + response.msg);
                } else {
                    row.fadeOut(300, function() {
                        location.reload();
                    });
                }
            }, 'json');
        });
        
        // Delete comment
        $('.delete-btn').click(function() {
            if (!confirm('<?php echo escape($lang['Delete comment confirmation']); ?>')) return;
            
            var commentId = $(this).data('id');
            var row = $('tr[data-comment-id="' + commentId + '"]');
            
            $.post('../ajax.php', {
                action: 'comment_delete',
                id: commentId
            }, function(response) {
                if (response.error) {
                    alert('<?php echo escape($lang['Error']); ?>: ' + response.msg);
                } else {
                    row.fadeOut(300);
                }
            }, 'json');
        });
    });
    </script>
    
</body>
</html>