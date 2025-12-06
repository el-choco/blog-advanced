<?php
require_once 'common.php';

$message = '';
$message_type = '';

// Process POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action      = $_POST['action'];
    $config_file = PROJECT_PATH . 'config.ini';
    $config      = parse_ini_file($config_file, true);

    // Helper: sanitize theme name
    $sanitizeTheme = function ($value) {
        $v = trim((string)$value);
        $v = preg_replace('/\.css$/i', '', $v);
        $v = preg_replace('/[^a-zA-Z0-9_-]/', '', $v);
        return $v === '' ? 'theme01' : $v;
    };

    /* GENERAL (no language here) */
    if ($action === 'update_general') {
        if (!isset($config['profile'])) $config['profile'] = [];
        if (isset($_POST['title'])) $config['profile']['title'] = $_POST['title'];
        if (isset($_POST['name']))  $config['profile']['name']  = $_POST['name'];

        if (isset($_POST['title']))    $config['title']    = $_POST['title'];
        if (isset($_POST['name']))     $config['name']     = $_POST['name'];
        if (isset($_POST['subtitle'])) $config['subtitle'] = $_POST['subtitle'];
        if (isset($_POST['timezone'])) $config['timezone'] = $_POST['timezone'];

        if (!isset($config['visitor'])) $config['visitor'] = [];
        $config['visitor']['enabled'] = isset($_POST['visitor_enabled']) ? '1' : '0';
        if (isset($_POST['title']))    $config['visitor']['title']    = $_POST['title'];
        if (isset($_POST['name']))     $config['visitor']['name']     = $_POST['name'];
        if (isset($_POST['subtitle'])) $config['visitor']['subtitle'] = $_POST['subtitle'];
        if (isset($_POST['timezone'])) $config['visitor']['timezone'] = $_POST['timezone'];

        if (!isset($config['system'])) $config['system'] = [];
        if (isset($_POST['timezone'])) $config['system']['timezone'] = $_POST['timezone'];

        if (writeConfig($config_file, $config)) {
            $message = $lang['General settings saved'];
            $message_type = 'success';
        } else {
            $message = $lang['Error saving'];
            $message_type = 'error';
        }
    }

    /* LANGUAGE (redirect on success for full reload) */
    if ($action === 'update_language') {
        $supported_langs = ['en','de','es','fr','it','pt','ru','zh','ja'];
        $new_lang        = $_POST['lang'] ?? '';

        if (in_array($new_lang, $supported_langs, true)) {
            if (!isset($config['language'])) $config['language'] = [];
            $config['language']['lang'] = $new_lang;
            $config['lang'] = $new_lang;

            if (!isset($config['visitor'])) $config['visitor'] = [];
            $config['visitor']['lang'] = $new_lang;

            if (session_status() !== PHP_SESSION_ACTIVE) {
                @session_start();
            }
            $_SESSION['lang'] = $new_lang;

            if (writeConfig($config_file, $config)) {
                header('Location: settings.php?language_changed=1&lang=' . urlencode($new_lang));
                exit;
            } else {
                $message = $lang['Error saving'];
                $message_type = 'error';
            }
        } else {
            $message = $lang['Error saving'];
            $message_type = 'error';
        }
    }

    /* THEME */
    if ($action === 'update_theme') {
        if (!isset($config['custom'])) $config['custom'] = [];
        if (isset($_POST['theme'])) {
            $t = $sanitizeTheme($_POST['theme']);
            $config['custom']['theme'] = $t;
            $config['theme'] = $t;
        }
        if (writeConfig($config_file, $config)) {
            $message = $lang['General settings saved'];
            $message_type = 'success';
        } else {
            $message = $lang['Error saving'];
            $message_type = 'error';
        }
    }

    /* THEME MODE (Dark/Light) */
    if ($action === 'update_theme_mode') {
        if (!isset($config['theme'])) $config['theme'] = [];
        
        // Validate and set theme_mode
        $theme_mode = isset($_POST['theme_mode']) && in_array($_POST['theme_mode'], ['light', 'dark']) 
            ? $_POST['theme_mode'] 
            : 'light';
        $config['theme']['theme_mode'] = $theme_mode;
        $config['theme_mode'] = $theme_mode;
        
        // Set theme_mode_override
        $theme_mode_override = isset($_POST['theme_mode_override']) ? '1' : '0';
        $config['theme']['theme_mode_override'] = $theme_mode_override;
        $config['theme_mode_override'] = $theme_mode_override;
        
        if (writeConfig($config_file, $config)) {
            $message = $lang['General settings saved'];
            $message_type = 'success';
        } else {
            $message = $lang['Error saving'];
            $message_type = 'error';
        }
    }


    /* COMPONENTS */
    if ($action === 'update_components') {
        if (!isset($config['components'])) $config['components'] = [];
        if (array_key_exists('highlight', $_POST)) {
            $config['components']['highlight'] = isset($_POST['highlight']) ? '1' : '0';
        }
        if (writeConfig($config_file, $config)) {
            $message = $lang['General settings saved'];
            $message_type = 'success';
        } else {
            $message = $lang['Error saving'];
            $message_type = 'error';
        }
    }

    /* SYSTEM */
    if ($action === 'update_system') {
        if (!isset($config['system'])) $config['system'] = [];
        if (array_key_exists('soft_delete', $_POST)) {
            $config['system']['SOFT_DELETE'] = isset($_POST['soft_delete']) ? '1' : '';
        }
        if (array_key_exists('hard_delete_files', $_POST)) {
            $config['system']['HARD_DELETE_FILES'] = isset($_POST['hard_delete_files']) ? '1' : '0';
        }
        if (array_key_exists('auto_cleanup', $_POST)) {
            $config['system']['AUTO_CLEANUP_IMAGES'] = isset($_POST['auto_cleanup']) ? '1' : '';
        }
        if (array_key_exists('debug', $_POST)) {
            $config['system']['debug'] = isset($_POST['debug']) ? '1' : '';
        }
        if (array_key_exists('logs', $_POST)) {
            $config['system']['logs'] = isset($_POST['logs']) ? '1' : '0';
        }
        if (writeConfig($config_file, $config)) {
            $message = $lang['General settings saved'];
            $message_type = 'success';
        } else {
            $message = $lang['Error saving'];
            $message_type = 'error';
        }
    }

    /* EMAIL */
    if ($action === 'update_email') {
        if (!isset($config['email'])) $config['email'] = [];
        $config['email']['notifications_enabled']    = isset($_POST['notifications_enabled']) ? '1' : '0';
        $config['email']['admin_email']              = $_POST['admin_email'] ?? '';
        $config['email']['notify_admin_new_comment'] = isset($_POST['notify_admin_new_comment']) ? '1' : '0';
        $config['email']['notify_user_approved']     = isset($_POST['notify_user_approved']) ? '1' : '0';
        $config['email']['from_email']               = $_POST['from_email'] ?? '';
        $config['email']['from_name']                = $_POST['from_name'] ?? '';
        if (writeConfig($config_file, $config)) {
            $message = $lang['Email settings saved'];
            $message_type = 'success';
        } else {
            $message = $lang['Error saving'];
            $message_type = 'error';
        }
    }

    /* DATABASE */
    if ($action === 'update_database') {
        if (!isset($config['database'])) $config['database'] = [];
        $config['database']['mysql_host'] = $_POST['mysql_host'] ?? 'db';
        $config['database']['mysql_port'] = $_POST['mysql_port'] ?? '3306';
        $config['database']['mysql_user'] = $_POST['mysql_user'] ?? '';
        if (!empty($_POST['mysql_pass'])) {
            $config['database']['mysql_pass'] = $_POST['mysql_pass'];
        }
        $config['database']['db_name'] = $_POST['db_name'] ?? 'blog';
        if (writeConfig($config_file, $config)) {
            $message = $lang['Database settings saved'];
            $message_type = 'success';
        } else {
            $message = $lang['Error saving'];
            $message_type = 'error';
        }
    }

    /* ADMIN */
    if ($action === 'update_admin') {
        if (!isset($config['admin'])) $config['admin'] = [];
        $config['admin']['force_login'] = isset($_POST['force_login']) ? '1' : '0';
        $config['admin']['nick']        = $_POST['admin_nick'] ?? 'admin';
        if (writeConfig($config_file, $config)) {
            $message = $lang['Admin settings saved'];
            $message_type = 'success';
        } else {
            $message = $lang['Error saving'];
            $message_type = 'error';
        }
    }

    /* CHANGE PASSWORD */
    if ($action === 'change_password') {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass     = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';
        $stored_hash  = $config['admin']['pass'] ?? ($config['pass'] ?? '');
        $is_valid     = false;

        if (password_get_info($stored_hash)['algo'] !== null) {
            $is_valid = password_verify($current_pass, $stored_hash);
        } else {
            $is_valid = ($current_pass === $stored_hash);
        }

        if ($is_valid) {
            if ($new_pass === $confirm_pass && strlen($new_pass) >= 6) {
                $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                if (!isset($config['admin'])) $config['admin'] = [];
                $config['admin']['pass'] = $new_hash;
                $config['pass']          = $new_hash;
                if (writeConfig($config_file, $config)) {
                    $message = $lang['Password changed'];
                    $message_type = 'success';
                } else {
                    $message = $lang['Error saving'];
                    $message_type = 'error';
                }
            } else {
                $message = $lang['Passwords do not match or too short'];
                $message_type = 'error';
            }
        } else {
            $message = $lang['Current password incorrect'];
            $message_type = 'error';
        }
    }

    /* COVER IMAGE */
    if ($action === 'update_cover' && isset($_FILES['cover'])) {
        $file = $_FILES['cover'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo   = finfo_open(FILEINFO_MIME_TYPE);
            $mime    = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if (in_array($mime, $allowed)) {
                $upload_dir = PROJECT_PATH . 'static/images/';
                if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);
                $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
                $dest = $upload_dir . 'cover.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    if (!isset($config['profile'])) $config['profile'] = [];
                    $config['profile']['cover'] = 'static/images/cover.' . $ext;
                    $config['cover']            = 'static/images/cover.' . $ext;
                    if (writeConfig($config_file, $config)) {
                        $message = $lang['Cover image uploaded'];
                        $message_type = 'success';
                    }
                } else {
                    $message = $lang['Error uploading'];
                    $message_type = 'error';
                }
            } else {
                $message = $lang['Only images allowed'];
                $message_type = 'error';
            }
        }
    }

    $config = parse_ini_file($config_file, true);
}

// Load config for initial render
$config = (isset($config) && is_array($config))
    ? $config
    : parse_ini_file(PROJECT_PATH . 'config.ini', true);

// Show success message after redirect language change
if (isset($_GET['language_changed'])) {
    $message = $lang['General settings saved'];
    $message_type = 'success';
}

// Helper: write config
function writeConfig($file, $config) {
    $content = '';
    foreach ($config as $key => $value) {
        if (is_array($value)) {
            $content .= "[$key]\n";
            foreach ($value as $k => $v) {
                $content .= "$k = \"$v\"\n";
            }
        } else {
            $content .= "$key = \"$value\"\n";
        }
    }
    return file_put_contents($file, $content);
}

// Helper: read config
function getConfig($config, $section, $key, $default = '') {
    if (isset($config[$section][$key])) return $config[$section][$key];
    if (isset($config[$key])) return $config[$key];
    return $default;
}

// Themes
$themes = [];
foreach (glob(PROJECT_PATH . 'static/styles/theme*.css') as $theme_file) {
    $themes[] = basename($theme_file, '.css');
}

// Languages & Timezones
$languages = [
    'en' => '🇬🇧 English','de' => '🇩🇪 Deutsch','es' => '🇪🇸 Español','fr' => '🇫🇷 Français',
    'it' => '🇮🇹 Italiano','pt' => '🇵🇹 Português','ru' => '🇷🇺 Русский','zh' => '🇨🇳 中文','ja' => '🇯🇵 日本語'
];
$timezones = [
    'UTC'=>'UTC','Europe/Berlin'=>'Europe/Berlin (CET/CEST)','Europe/London'=>'Europe/London (GMT/BST)',
    'Europe/Paris'=>'Europe/Paris (CET/CEST)','Europe/Rome'=>'Europe/Rome (CET/CEST)','Europe/Vienna'=>'Europe/Vienna (CET/CEST)',
    'Europe/Zurich'=>'Europe/Zurich (CET/CEST)','America/New_York'=>'America/New York (EST/EDT)','America/Chicago'=>'America/Chicago (CST/CDT)',
    'America/Los_Angeles'=>'America/Los Angeles (PST/PDT)','Asia/Tokyo'=>'Asia/Tokyo (JST)','Asia/Shanghai'=>'Asia/Shanghai (CST)',
    'Australia/Sydney'=>'Australia/Sydney (AEDT/AEST)'
];
?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>⚙️ <?php echo escape($lang['Settings']); ?> - <?php echo escape(getConfig($config,'profile','title','Blog')); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <link href="../static/styles/main.css" rel="stylesheet" type="text/css" />
    <?php
    $theme_for_view = getConfig($config,'custom','theme','theme01');
    $theme_for_view = preg_replace('/\.css$/i','',trim((string)$theme_for_view));
    $theme_for_view = preg_replace('/[^a-zA-Z0-9_-]/','',$theme_for_view);
    if ($theme_for_view === '') $theme_for_view = 'theme01';
    ?>
    <link href="../static/styles/<?php echo htmlspecialchars($theme_for_view,ENT_QUOTES,'UTF-8'); ?>.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/admin.css" rel="stylesheet" type="text/css" />
    <style>
    /* Tabs */
    .settings-tabs{display:flex;gap:10px;margin-bottom:30px;border-bottom:2px solid #e5e5e5;overflow-x:auto;}
    .tab-button{padding:12px 20px;background:none;border:none;border-bottom:3px solid transparent;cursor:pointer;font-size:14px;font-weight:600;color:#666;white-space:nowrap;transition:.2s;}
    .tab-button.active{color:#1877f2;border-bottom-color:#1877f2;}
    .tab-button:hover{color:#1877f2;}
    .tab-content{display:none;}
    .tab-content.active{display:block;}

    /* Panel: außen sichtbar */
    .settings-section{
        box-sizing:border-box !important;
        background:#fff !important;
        border:1px solid #e5e7eb !important;
        border-radius:12px !important;
        box-shadow:0 6px 20px -6px rgba(0,0,0,0.12) !important;
        padding:16px !important;
        margin-bottom:18px !important;
    }

    /* Titel */
    .section-title{
        font-size:16px !important;
        font-weight:600 !important;
        margin-bottom:12px !important;
        padding-bottom:8px !important;
        border-bottom:2px solid #f0f0f0 !important;
        color:#111827 !important;
    }

    /* Grid */
    .form-grid{display:grid !important; gap:16px !important;}
    .form-grid-2{grid-template-columns:1fr 1fr !important;}
    @media (max-width: 900px){
        .form-grid-2{grid-template-columns:1fr !important;}
    }

    /* Labels */
    .form-label{
        display:block !important;
        font-weight:600 !important;
        margin-bottom:6px !important;
        font-size:14px !important;
        color:#374151 !important;
    }

    /* Inputs, Selects, Textareas */
    .form-input,.form-select,.form-textarea{
        box-sizing:border-box !important;
        width:100% !important;
        display:block !important;
        padding:12px !important;
        border:1px solid #d0d7de !important;
        border-radius:8px !important;
        background:#f8fafc !important;
        outline:none !important;
        font-size:14px !important;
        color:#111827 !important;
    }

    /* Textarea specifics */
    .form-textarea{
        min-height:110px !important;
        resize:vertical !important;
    }

    /* Checkbox-Row */
    .form-checkbox{
        display:flex !important;
        align-items:center !important;
        gap:8px !important;
        margin:8px 0 12px !important;
    }
    .form-checkbox input[type="checkbox"]{width:16px !important; height:16px !important;}

    /* Save Buttons */
    .btn-save{
        box-sizing:border-box !important;
        display:inline-block !important;
        padding:10px 14px !important;
        border-radius:8px !important;
        background:linear-gradient(180deg,#3b82f6,#1e67d6) !important;
        color:#fff !important;
        border:none !important;
        box-shadow:0 4px 12px rgba(30,103,214,0.25) !important;
        cursor:pointer !important;
        font-weight:600 !important;
    }
    .btn-save:hover{filter:brightness(0.98) !important;}

    /* Messages */
    .message{padding:15px 20px;border-radius:8px;margin-bottom:20px;font-size:14px;font-weight:500;}
    .message-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb;}
    .message-error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;}

    /* Warning box */
    .warning-box{
        background:#fff8e6 !important;
        border-left:4px solid #f3cf73 !important;
        padding:12px !important;
        border-radius:8px !important;
        margin-bottom:12px !important;
        color:#8a6d1e !important;
    }

    /* Focus state */
    .form-input:focus,.form-select:focus,.form-textarea:focus{
        border-color:#3b82f6 !important;
        box-shadow:0 0 0 3px rgba(59,130,246,0.15) !important;
        background:#fff !important;
    }

    /* Theme grid remains */
    .theme-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:15px;}
    .theme-option{position:relative;cursor:pointer;}
    .theme-option input{position:absolute;opacity:0;}
    .theme-card{border:3px solid #e5e5e5;border-radius:8px;padding:20px;text-align:center;transition:.2s;}
    .theme-option input:checked + .theme-card{border-color:#1877f2;background:#e7f3ff;}
    </style>
</head>
<body class="admin-body">
    <div class="admin-header">
        <div class="admin-container">
            <h1>⚙️ <?php echo escape($lang['Settings']); ?></h1>
            <div class="admin-user">
                <span>👤 <?php echo escape(getConfig($config,'profile','name','Admin')); ?></span>
                <a href="../" class="btn btn-sm">← <?php echo escape($lang['Back to Blog']); ?></a>
            </div>
        </div>
    </div>

    <div class="admin-layout">
        <aside class="admin-sidebar">
            <nav class="admin-nav">
                <a href="index.php">📊 <?php echo escape($lang['Dashboard']); ?></a>
                <a href="posts.php">📝 <?php echo escape($lang['Posts']); ?></a>
                <a href="comments.php">💬 <?php echo escape($lang['Comments']); ?></a>
                <a href="media.php">📁 <?php echo escape($lang['Files']); ?></a>
                <a href="backups.php">💾 <?php echo escape($lang['Backups']); ?></a>
                <a href="trash.php">🗑️ <?php echo escape($lang['Trash']); ?></a>
                <a href="categories.php">🏷️ <?php echo escape($lang['Categories']); ?></a>
                <a href="settings.php" class="active">⚙️ <?php echo escape($lang['Settings']); ?></a>
            </nav>
        </aside>

        <main class="admin-content">
            <?php if ($message): ?>
                <div class="message message-<?php echo $message_type; ?>">
                    <?php echo escape($message); ?>
                </div>
            <?php endif; ?>

            <div class="settings-tabs">
                <button class="tab-button active" data-tab="general">📝 <?php echo escape($lang['General']); ?></button>
                <button class="tab-button" data-tab="language">🌐 <?php echo escape($lang['Language']); ?></button>
                <button class="tab-button" data-tab="appearance">🎨 <?php echo escape($lang['Appearance']); ?></button>
                <button class="tab-button" data-tab="email">📧 <?php echo escape($lang['Email']); ?></button>
                <button class="tab-button" data-tab="database">🗄️ <?php echo escape($lang['Database']); ?></button>
                <button class="tab-button" data-tab="admin">🔐 <?php echo escape($lang['Admin']); ?></button>
                <button class="tab-button" data-tab="system">⚙️ <?php echo escape($lang['System']); ?></button>
                <button class="tab-button" data-tab="info">ℹ️ <?php echo escape($lang['Info']); ?></button>
            </div>

            <!-- General -->
            <div class="tab-content active" id="tab-general">
                <form method="POST">
                    <input type="hidden" name="action" value="update_general">
                    <div class="settings-section">
                        <h2 class="section-title">📝 <?php echo escape($lang['Blog Information']); ?></h2>
                        <div class="form-grid form-grid-2">
                            <div>
                                <label class="form-label"><?php echo escape($lang['Blog Title']); ?> *</label>
                                <input type="text" name="title" class="form-input" value="<?php echo escape(getConfig($config,'profile','title','')); ?>" required>
                            </div>
                            <div>
                                <label class="form-label"><?php echo escape($lang['Your Name']); ?> *</label>
                                <input type="text" name="name" class="form-input" value="<?php echo escape(getConfig($config,'profile','name','')); ?>" required>
                            </div>
                        </div>
                        <div>
                            <label class="form-label"><?php echo escape($lang['Subtitle Description']); ?></label>
                            <textarea name="subtitle" class="form-textarea"><?php echo escape(getConfig($config,'visitor','subtitle','')); ?></textarea>
                        </div>
                    </div>
                    <div class="settings-section">
                        <h2 class="section-title">🕑 <?php echo escape($lang['Timezone']); ?></h2>
                        <select name="timezone" class="form-select">
                            <?php $current_tz = getConfig($config,'system','timezone','Europe/Berlin');
                            foreach ($timezones as $tz => $label): ?>
                                <option value="<?php echo $tz; ?>" <?php echo $current_tz === $tz ? 'selected':''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="settings-section">
                        <h2 class="section-title">👁️ <?php echo escape($lang['Visitor View']); ?></h2>
                        <div class="form-checkbox">
                            <input type="checkbox" name="visitor_enabled" id="visitor_enabled" <?php echo getConfig($config,'visitor','enabled')==='1'?'checked':''; ?>>
                            <label for="visitor_enabled"><?php echo escape($lang['Blog visible to visitors without login']); ?></label>
                        </div>
                    </div>
                    <button type="submit" class="btn-save">💾 <?php echo escape($lang['Save settings']); ?></button>
                </form>
            </div>

            <!-- Language -->
            <div class="tab-content" id="tab-language">
                <form method="POST">
                    <input type="hidden" name="action" value="update_language">
                    <div class="settings-section">
                        <h2 class="section-title">🌐 <?php echo escape($lang['Language']); ?></h2>
                        <select name="lang" class="form-select">
                            <?php $current_lang = getConfig($config,'language','lang','de');
                            foreach ($languages as $code => $name): ?>
                                <option value="<?php echo $code; ?>" <?php echo $current_lang === $code ? 'selected':''; ?>>
                                    <?php echo $name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-help"><?php echo escape($lang['Change interface language']); ?></div>
                    </div>
                    <button type="submit" class="btn-save">💾 <?php echo escape($lang['Save']); ?></button>
                </form>
            </div>

            <!-- Appearance -->
            <div class="tab-content" id="tab-appearance">
                <div class="settings-section">
                    <h2 class="section-title">🎨 <?php echo escape($lang['Select theme']); ?></h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_theme">
                        <div class="theme-grid">
                            <?php $current_theme = getConfig($config,'custom','theme','theme01');
                            foreach ($themes as $theme_option): ?>
                                <label class="theme-option">
                                    <input type="radio" name="theme" value="<?php echo $theme_option; ?>" <?php echo $current_theme === $theme_option?'checked':''; ?>>
                                    <div class="theme-card">
                                        <div style="width:50px;height:50px;background:#1877f2;border-radius:50%;margin:0 auto 10px;"></div>
                                        <div><?php echo ucfirst($theme_option); ?></div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <br>
                        <button type="submit" class="btn-save">💾 <?php echo escape($lang['Save theme']); ?></button>
                    </form>
                </div>
                <div class="settings-section">
                    <h2 class="section-title">🌓 Dark/Light Mode</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_theme_mode">
                        <div>
                            <label class="form-label">Default Theme Mode</label>
                            <select name="theme_mode" class="form-select">
                                <?php $current_theme_mode = getConfig($config,'theme','theme_mode','light'); ?>
                                <option value="light" <?php echo $current_theme_mode === 'light' ? 'selected':''; ?>>☀️ Light Mode</option>
                                <option value="dark" <?php echo $current_theme_mode === 'dark' ? 'selected':''; ?>>🌙 Dark Mode</option>
                            </select>
                            <div class="form-help">Select the default theme mode for visitors</div>
                        </div>
                        <br>
                        <div class="form-checkbox">
                            <input type="checkbox" name="theme_mode_override" id="theme_mode_override" <?php echo getConfig($config,'theme','theme_mode_override')==='1'?'checked':''; ?>>
                            <label for="theme_mode_override">🔒 Lock theme mode (disable user toggle)</label>
                        </div>
                        <div class="form-help">When enabled, users cannot change the theme - only the admin setting applies</div>
                        <br>
                        <button type="submit" class="btn-save">💾 Save Theme Mode</button>
                    </form>
                </div>
                <div class="settings-section">
                    <h2 class="section-title">✨ <?php echo escape($lang['Components']); ?></h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_components">
                        <div class="form-checkbox">
                            <input type="checkbox" name="highlight" id="highlight" <?php echo getConfig($config,'components','highlight')==='1'?'checked':''; ?>>
                            <label for="highlight"><?php echo escape($lang['Enable syntax highlighting']); ?></label>
                        </div>
                        <br>
                        <button type="submit" class="btn-save">💾 <?php echo escape($lang['Save']); ?></button>
                    </form>
                </div>
            </div>

            <!-- Email -->
            <div class="tab-content" id="tab-email">
                <form method="POST">
                    <input type="hidden" name="action" value="update_email">
                    <div class="settings-section">
                        <h2 class="section-title">📧 <?php echo escape($lang['Email Notifications']); ?></h2>
                        <div class="form-checkbox">
                            <input type="checkbox" name="notifications_enabled" id="notifications_enabled" <?php echo getConfig($config,'email','notifications_enabled')==='1'?'checked':''; ?>>
                            <label for="notifications_enabled"><?php echo escape($lang['Enable email notifications']); ?></label>
                        </div>
                        <br>
                        <div class="form-grid form-grid-2">
                            <div>
                                <label class="form-label"><?php echo escape($lang['Admin Email']); ?></label>
                                <input type="email" name="admin_email" class="form-input" value="<?php echo escape(getConfig($config,'email','admin_email','')); ?>">
                            </div>
                            <div>
                                <label class="form-label"><?php echo escape($lang['From Email']); ?></label>
                                <input type="email" name="from_email" class="form-input" value="<?php echo escape(getConfig($config,'email','from_email','')); ?>">
                            </div>
                        </div>
                        <div>
                            <label class="form-label"><?php echo escape($lang['From Name']); ?></label>
                            <input type="text" name="from_name" class="form-input" value="<?php echo escape(getConfig($config,'email','from_name','')); ?>">
                        </div>
                    </div>
                    <div class="settings-section">
                        <h2 class="section-title">🔔 <?php echo escape($lang['Notification Options']); ?></h2>
                        <div class="form-checkbox">
                            <input type="checkbox" name="notify_admin_new_comment" id="notify_admin_new_comment" <?php echo getConfig($config,'email','notify_admin_new_comment')==='1'?'checked':''; ?>>
                            <label for="notify_admin_new_comment"><?php echo escape($lang['Notify on new comments']); ?></label>
                        </div>
                        <br>
                        <div class="form-checkbox">
                            <input type="checkbox" name="notify_user_approved" id="notify_user_approved" <?php echo getConfig($config,'email','notify_user_approved')==='1'?'checked':''; ?>>
                            <label for="notify_user_approved"><?php echo escape($lang['Notify user on approval']); ?></label>
                        </div>
                    </div>
                    <button type="submit" class="btn-save">💾 <?php echo escape($lang['Save email settings']); ?></button>
                </form>
            </div>

            <!-- Database -->
            <div class="tab-content" id="tab-database">
                <div class="warning-box"><strong>⚠️ <?php echo escape($lang['Database changes require container restart']); ?></strong></div>
                <form method="POST">
                    <input type="hidden" name="action" value="update_database">
                    <div class="settings-section">
                        <h2 class="section-title">🗄️ <?php echo escape($lang['MySQL Database']); ?></h2>
                        <div class="form-grid form-grid-2">
                            <div>
                                <label class="form-label"><?php echo escape($lang['Host']); ?></label>
                                <input type="text" name="mysql_host" class="form-input" value="<?php echo escape(getConfig($config,'database','mysql_host','db')); ?>">
                            </div>
                            <div>
                                <label class="form-label"><?php echo escape($lang['Port']); ?></label>
                                <input type="text" name="mysql_port" class="form-input" value="<?php echo escape(getConfig($config,'database','mysql_port','3306')); ?>">
                            </div>
                        </div>
                        <div class="form-grid form-grid-2">
                            <div>
                                <label class="form-label"><?php echo escape($lang['Database Name']); ?></label>
                                <input type="text" name="db_name" class="form-input" value="<?php echo escape(getConfig($config,'database','db_name','blog')); ?>">
                            </div>
                            <div>
                                <label class="form-label"><?php echo escape($lang['Username']); ?></label>
                                <input type="text" name="mysql_user" class="form-input" value="<?php echo escape(getConfig($config,'database','mysql_user','')); ?>">
                            </div>
                        </div>
                        <div>
                            <label class="form-label"><?php echo escape($lang['Password']); ?></label>
                            <input type="password" name="mysql_pass" class="form-input" placeholder="••••••••">
                            <div class="form-help"><?php echo escape($lang['Leave empty to keep current password']); ?></div>
                        </div>
                    </div>
                    <button type="submit" class="btn-save">💾 <?php echo escape($lang['Save database settings']); ?></button>
                </form>
            </div>

            <!-- Admin -->
            <div class="tab-content" id="tab-admin">
                <div class="settings-section">
                    <h2 class="section-title">🔐 <?php echo escape($lang['Admin Access']); ?></h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_admin">
                        <div>
                            <label class="form-label"><?php echo escape($lang['Admin Username']); ?></label>
                            <input type="text" name="admin_nick" class="form-input" value="<?php echo escape(getConfig($config,'admin','nick','admin')); ?>">
                        </div>
                        <div class="form-checkbox">
                            <input type="checkbox" name="force_login" id="force_login" <?php echo getConfig($config,'admin','force_login')==='1'?'checked':''; ?>>
                            <label for="force_login"><?php echo escape($lang['Force login']); ?></label>
                        </div>
                        <br>
                        <button type="submit" class="btn-save">💾 <?php echo escape($lang['Save']); ?></button>
                    </form>
                </div>
                <div class="settings-section">
                    <h2 class="section-title">🔑 <?php echo escape($lang['Change Password']); ?></h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div>
                            <label class="form-label"><?php echo escape($lang['Current Password']); ?></label>
                            <input type="password" name="current_password" class="form-input" required>
                        </div>
                        <div>
                            <label class="form-label"><?php echo escape($lang['New Password']); ?></label>
                            <input type="password" name="new_password" class="form-input" required minlength="6">
                            <div class="form-help"><?php echo escape($lang['Minimum 6 characters']); ?></div>
                        </div>
                        <div>
                            <label class="form-label"><?php echo escape($lang['Confirm Password']); ?></label>
                            <input type="password" name="confirm_password" class="form-input" required>
                        </div>
                        <button type="submit" class="btn-save">🔐 <?php echo escape($lang['Change Password']); ?></button>
                    </form>
                </div>
            </div>

            <!-- System -->
            <div class="tab-content" id="tab-system">
                <form method="POST">
                    <input type="hidden" name="action" value="update_system">
                    <div class="settings-section">
                        <h2 class="section-title">🗑️ <?php echo escape($lang['Delete Behavior']); ?></h2>
                        <div class="form-checkbox">
                            <input type="checkbox" name="soft_delete" id="soft_delete" <?php echo !empty(getConfig($config,'system','SOFT_DELETE'))?'checked':''; ?>>
                            <label for="soft_delete"><?php echo escape($lang['Soft delete posts to trash']); ?></label>
                        </div>
                        <br>
                        <div class="form-checkbox">
                            <input type="checkbox" name="hard_delete_files" id="hard_delete_files" <?php echo getConfig($config,'system','HARD_DELETE_FILES')==='1'?'checked':''; ?>>
                            <label for="hard_delete_files"><?php echo escape($lang['Permanently delete files']); ?></label>
                        </div>
                        <br>
                        <div class="form-checkbox">
                            <input type="checkbox" name="auto_cleanup" id="auto_cleanup" <?php echo !empty(getConfig($config,'system','AUTO_CLEANUP_IMAGES'))?'checked':''; ?>>
                            <label for="auto_cleanup"><?php echo escape($lang['Auto cleanup unused images']); ?></label>
                        </div>
                    </div>
                    <div class="settings-section">
                        <h2 class="section-title">🐛 <?php echo escape($lang['Debug Logs']); ?></h2>
                        <div class="form-checkbox">
                            <input type="checkbox" name="debug" id="debug" <?php echo !empty(getConfig($config,'system','debug'))?'checked':''; ?>>
                            <label for="debug"><?php echo escape($lang['Enable debug mode']); ?></label>
                        </div>
                        <br>
                        <div class="form-checkbox">
                            <input type="checkbox" name="logs" id="logs" <?php echo getConfig($config,'system','logs')==='1'?'checked':''; ?>>
                            <label for="logs"><?php echo escape($lang['Enable logs']); ?></label>
                        </div>
                    </div>
                    <button type="submit" class="btn-save">💾 <?php echo escape($lang['Save system settings']); ?></button>
                </form>
            </div>

            <!-- Info -->
            <div class="tab-content" id="tab-info">
                <div class="settings-section">
                    <h2 class="section-title">ℹ️ <?php echo escape($lang['System Information']); ?></h2>
                    <div class="form-grid">
                        <div><strong><?php echo escape($lang['PHP Version']); ?>:</strong><br><?php echo PHP_VERSION; ?></div>
                        <div><strong><?php echo escape($lang['Web Server']); ?>:</strong><br><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></div>
                        <div><strong><?php echo escape($lang['Blog Version']); ?>:</strong><br><?php echo getConfig($config,'system','version','1.0'); ?></div>
                        <div><strong><?php echo escape($lang['Blog Path']); ?>:</strong><br><code><?php echo PROJECT_PATH; ?></code></div>
                        <div><strong><?php echo escape($lang['Database']); ?>:</strong><br><?php echo getConfig($config,'database','db_connection','mysql'); ?> (<?php echo getConfig($config,'database','db_name','blog'); ?>)</div>
                        <div><strong><?php echo escape($lang['Timezone']); ?>:</strong><br><?php echo date_default_timezone_get(); ?></div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="admin-panel">
                <div class="panel-header"><h2><?php echo escape($lang['Quick Access']); ?></h2></div>
                <div class="panel-body">
                    <div class="quick-actions">
                        <a href="../#new-post" class="quick-action-card"><div class="qa-icon">✏️</div><div class="qa-label"><?php echo escape($lang['New Post']); ?></div></a>
                        <a href="backups.php" class="quick-action-card"><div class="qa-icon">💾</div><div class="qa-label"><?php echo escape($lang['Backups']); ?></div></a>
                        <a href="comments.php" class="quick-action-card"><div class="qa-icon">💬</div><div class="qa-label"><?php echo escape($lang['Comments']); ?></div></a>
                        <a href="posts.php" class="quick-action-card"><div class="qa-icon">📝</div><div class="qa-label"><?php echo escape($lang['Manage Posts']); ?></div></a>
                        <a href="media.php" class="quick-action-card"><div class="qa-icon">📁</div><div class="qa-label"><?php echo escape($lang['Files']); ?></div></a>
                        <a href="trash.php" class="quick-action-card"><div class="qa-icon">🗑️</div><div class="qa-label"><?php echo escape($lang['Trash']); ?></div></a>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.tab;
            tabButtons.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('tab-' + tab).classList.add('active');
        });
    });
    </script>
</body>
</html>