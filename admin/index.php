<?php
require_once 'common.php';

// CSRF Token Setup
if (empty($_SESSION['token'])) {
    if (function_exists('random_bytes')) {
        $_SESSION['token'] = bin2hex(random_bytes(5));
    } else {
        $_SESSION['token'] = bin2hex(openssl_random_pseudo_bytes(5));
    }
}

$stats = AdminHelper::getStats();
$recent_posts = array_slice(AdminHelper::getAllPosts(), 0, 5);
?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo escape($lang['Admin Dashboard']); ?> - <?php echo escape(Config::get("title")); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />

    <!-- Main Blog Styles -->
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
    <link href="../static/styles/custom1.css" rel="stylesheet" type="text/css" />

    <!-- Admin Styles -->
    <link href="../static/styles/admin.css" rel="stylesheet" type="text/css" />

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&amp;subset=all" rel="stylesheet">
</head>
<body class="admin-body">

    <div class="admin-header">
        <div class="admin-container">
            <h1>📊 <?php echo escape($lang['Admin Dashboard']); ?></h1>
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
                <a href="index.php" class="active">📊 <?php echo escape($lang['Dashboard']); ?></a>
                <a href="posts.php">📝 <?php echo escape($lang['Posts']); ?></a>
                <a href="comments.php">💬 <?php echo escape($lang['Comments']); ?></a>
                <a href="media.php">📁 <?php echo escape($lang['Files']); ?></a>
                <a href="backups.php">💾 <?php echo escape($lang['Backups']); ?></a>
                <a href="trash.php">🗑️ <?php echo escape($lang['Trash']); ?> <span class="badge"><?php echo $stats['trash_posts']; ?></span></a>
                <a href="settings.php">⚙️ <?php echo escape($lang['Settings']); ?></a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['total_posts']; ?></div>
                        <div class="stat-label"><?php echo escape($lang['Total Posts']); ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['public_posts']; ?></div>
                        <div class="stat-label"><?php echo escape($lang['Public']); ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">📌</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['sticky_posts']; ?></div>
                        <div class="stat-label"><?php echo escape($lang['Sticky Posts']); ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">🖼️</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['total_images']); ?></div>
                        <div class="stat-label"><?php echo escape($lang['Images']); ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">📎</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['total_files']; ?></div>
                        <div class="stat-label"><?php echo escape($lang['Files']); ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">🗑️</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['trash_posts']; ?></div>
                        <div class="stat-label"><?php echo escape($lang['In Trash']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Recent Posts -->
            <div class="admin-panel">
                <div class="panel-header">
                    <h2><?php echo escape($lang['Recent Posts']); ?></h2>
                    <a href="posts.php" class="btn btn-primary"><?php echo escape($lang['Show All']); ?> →</a>
                </div>
                <div class="panel-body">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th><?php echo escape($lang['Content']); ?></th>
                                <th><?php echo escape($lang['Status']); ?></th>
                                <th><?php echo escape($lang['Date']); ?></th>
                                <th><?php echo escape($lang['Actions']); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($recent_posts)): ?>
                                <tr>
                                    <td colspan="5" class="text-center"><?php echo escape($lang['No posts available']); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($recent_posts as $post): ?>
                                    <tr data-post-id="<?php echo $post['id']; ?>">
                                        <td><code>#<?php echo escape($post['id']); ?></code></td>
                                        <td>
                                            <div class="post-preview">
                                                <?php echo escape(AdminHelper::getExcerpt($post['text'], 80)); ?>
                                                <?php if($post['has_images']): ?>
                                                    <span class="badge badge-info">🖼️ <?php echo $post['image_count']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo AdminHelper::getStatusBadge($post); ?></td>
                                        <td><?php echo AdminHelper::formatDate($post['time']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="../#id=<?php echo $post['id']; ?>" class="btn btn-sm" title="<?php echo escape($lang['View']); ?>">👁️</a>
                                                <button class="btn btn-sm inline-edit-btn" data-post-id="<?php echo $post['id']; ?>" title="<?php echo escape($lang['Inline Edit']); ?>">✏️</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Inline Editor Row (hidden by default) -->
                                    <tr class="inline-editor-row" id="inline-editor-<?php echo $post['id']; ?>" style="display: none;">
                                        <td colspan="5">
                                            <div class="inline-editor-container">
                                                <div class="inline-editor-header">
                                                    <h3>✏️ <?php echo escape($lang['Edit Post']); ?> #<?php echo $post['id']; ?></h3>
                                                    <button class="btn btn-sm close-editor" data-post-id="<?php echo $post['id']; ?>">✖️ <?php echo escape($lang['Close']); ?></button>
                                                </div>
                                                <div class="inline-editor-body">
                                                    <div class="editor-column">
                                                        <h4>📝 <?php echo escape($lang['Editor']); ?></h4>
                                                        <textarea class="inline-editor-textarea" id="editor-<?php echo $post['id']; ?>" rows="15"></textarea>
                                                        <div class="editor-toolbar">
                                                            <button class="toolbar-btn" data-action="bold" title="<?php echo escape($lang['Bold']); ?>">**B**</button>
                                                            <button class="toolbar-btn" data-action="italic" title="<?php echo escape($lang['Italic']); ?>">*I*</button>
                                                            <button class="toolbar-btn" data-action="code" title="<?php echo escape($lang['Inline Code']); ?>">`code`</button>
                                                            <button class="toolbar-btn" data-action="link" title="<?php echo escape($lang['Link']); ?>">🔗</button>
                                                            <button class="toolbar-btn" data-action="image" title="<?php echo escape($lang['Image']); ?>">🖼️</button>
                                                        </div>
                                                    </div>
                                                    <div class="preview-column">
                                                        <h4>👁️ <?php echo escape($lang['Live Preview']); ?></h4>
                                                        <div class="inline-editor-preview" id="preview-<?php echo $post['id']; ?>"></div>
                                                    </div>
                                                </div>
                                                <div class="inline-editor-footer">
                                                    <button class="btn btn-sm" onclick="closeInlineEditor(<?php echo $post['id']; ?>)"><?php echo escape($lang['Cancel']); ?></button>
                                                    <button class="btn btn-primary" onclick="saveInlinePost(<?php echo $post['id']; ?>)">💾 <?php echo escape($lang['Save']); ?></button>
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

    <!-- jQuery (for AJAX) -->
    <script src="../static/scripts/jquery.min.js"></script>
    
    <!-- CSRF Token Setup -->
    <script>
    $.ajaxSetup({
        headers: {
            'Csrf-Token': '<?php echo $_SESSION['token']; ?>'
        }
    });
    console.log('🔑 CSRF Token configured');
    </script>
    
    <script>
    var ADMIN_LANG = {
        errorPostData: '<?php echo escape($lang["Error Post data could not be loaded"]); ?>',
        errorLoadingPost: '<?php echo escape($lang["Error loading post"]); ?>',
        postSaved: '<?php echo escape($lang["Post saved"]); ?>',
        errorSaving: '<?php echo escape($lang["Error saving"]); ?>',
        networkErrorSaving: '<?php echo escape($lang["Network error saving"]); ?>',
        enterURL: '<?php echo escape($lang["Enter URL"]); ?>',
        enterImageURL: '<?php echo escape($lang["Enter Image URL"]); ?>'
    };
    </script>
    <script src="../static/scripts/admin.js"></script>

</body>
</html>