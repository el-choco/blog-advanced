<?php
require_once 'common.php';

// Handle actions
$message = '';
$message_type = '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Update general settings
    if($action === 'update_general') {
        $config_file = PROJECT_PATH . 'data/config.ini';
        $config = parse_ini_file($config_file, true);
        
        // Update values
        if(isset($_POST['title'])) {
            $config['title'] = $_POST['title'];
        }
        if(isset($_POST['name'])) {
            $config['name'] = $_POST['name'];
        }
        if(isset($_POST['subtitle'])) {
            $config['subtitle'] = $_POST['subtitle'];
        }
        if(isset($_POST['lang'])) {
            $config['lang'] = $_POST['lang'];
        }
        if(isset($_POST['timezone'])) {
            $config['timezone'] = $_POST['timezone'];
        }
        if(isset($_POST['theme'])) {
            $config['theme'] = $_POST['theme'];
        }
        
        // Write config file
        $content = '';
        foreach($config as $key => $value) {
            if(is_array($value)) {
                $content .= "[$key]\n";
                foreach($value as $k => $v) {
                    $content .= "$k = \"$v\"\n";
                }
            } else {
                $content .= "$key = \"$value\"\n";
            }
        }
        
        if(file_put_contents($config_file, $content)) {
            $message = 'Einstellungen wurden gespeichert';
            $message_type = 'success';
        } else {
            $message = 'Fehler beim Speichern der Einstellungen';
            $message_type = 'error';
        }
    }
    
    // Update cover image
    if($action === 'update_cover' && isset($_FILES['cover'])) {
        $file = $_FILES['cover'];
        
        if($file['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if(in_array($mime, $allowed)) {
                $upload_dir = PROJECT_PATH . 'data/images/';
                if(!is_dir($upload_dir)) {
                    @mkdir($upload_dir, 0755, true);
                }
                
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $dest = $upload_dir . 'cover.' . $ext;
                
                if(move_uploaded_file($file['tmp_name'], $dest)) {
                    // Update config
                    $config_file = PROJECT_PATH . 'data/config.ini';
                    $config = parse_ini_file($config_file, true);
                    $config['cover'] = 'data/images/cover.' . $ext;
                    
                    $content = '';
                    foreach($config as $key => $value) {
                        if(is_array($value)) {
                            $content .= "[$key]\n";
                            foreach($value as $k => $v) {
                                $content .= "$k = \"$v\"\n";
                            }
                        } else {
                            $content .= "$key = \"$value\"\n";
                        }
                    }
                    
                    file_put_contents($config_file, $content);
                    
                    $message = 'Cover-Bild wurde hochgeladen';
                    $message_type = 'success';
                } else {
                    $message = 'Fehler beim Hochladen';
                    $message_type = 'error';
                }
            } else {
                $message = 'Nur Bilder erlaubt (JPG, PNG, GIF, WebP)';
                $message_type = 'error';
            }
        }
    }
    
    // Change password
    if($action === 'change_password') {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';
        
        // Verify current password
        $config = parse_ini_file(PROJECT_PATH . 'data/config.ini', true);
        $stored_hash = $config['pass'] ?? '';
        
        if(password_verify($current_pass, $stored_hash)) {
            if($new_pass === $confirm_pass) {
                if(strlen($new_pass) >= 6) {
                    $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                    
                    $config_file = PROJECT_PATH . 'data/config.ini';
                    $config['pass'] = $new_hash;
                    
                    $content = '';
                    foreach($config as $key => $value) {
                        if(is_array($value)) {
                            $content .= "[$key]\n";
                            foreach($value as $k => $v) {
                                $content .= "$k = \"$v\"\n";
                            }
                        } else {
                            $content .= "$key = \"$value\"\n";
                        }
                    }
                    
                    if(file_put_contents($config_file, $content)) {
                        $message = 'Passwort wurde geändert';
                        $message_type = 'success';
                    } else {
                        $message = 'Fehler beim Speichern';
                        $message_type = 'error';
                    }
                } else {
                    $message = 'Passwort muss mindestens 6 Zeichen lang sein';
                    $message_type = 'error';
                }
            } else {
                $message = 'Passwörter stimmen nicht überein';
                $message_type = 'error';
            }
        } else {
            $message = 'Aktuelles Passwort ist falsch';
            $message_type = 'error';
        }
    }
}

// Load current config
$config = parse_ini_file(PROJECT_PATH . 'data/config.ini', true);

// Available themes
$themes = [];
$theme_files = glob(PROJECT_PATH . 'static/styles/theme*.css');
foreach($theme_files as $theme_file) {
    $theme_name = basename($theme_file, '.css');
    $themes[] = $theme_name;
}

// Available languages
$languages = [
    'en' => 'English',
    'de' => 'Deutsch',
    'es' => 'Español',
    'fr' => 'Français',
    'it' => 'Italiano',
    'pt' => 'Português',
    'ru' => 'Русский',
    'zh' => '中文',
    'ja' => '日本語'
];

// Available timezones (common ones)
$timezones = [
    'UTC' => 'UTC',
    'Europe/Berlin' => 'Europe/Berlin (CET/CEST)',
    'Europe/London' => 'Europe/London (GMT/BST)',
    'Europe/Paris' => 'Europe/Paris (CET/CEST)',
    'Europe/Rome' => 'Europe/Rome (CET/CEST)',
    'Europe/Vienna' => 'Europe/Vienna (CET/CEST)',
    'Europe/Zurich' => 'Europe/Zurich (CET/CEST)',
    'America/New_York' => 'America/New York (EST/EDT)',
    'America/Chicago' => 'America/Chicago (CST/CDT)',
    'America/Denver' => 'America/Denver (MST/MDT)',
    'America/Los_Angeles' => 'America/Los Angeles (PST/PDT)',
    'Asia/Tokyo' => 'Asia/Tokyo (JST)',
    'Asia/Shanghai' => 'Asia/Shanghai (CST)',
    'Australia/Sydney' => 'Australia/Sydney (AEDT/AEST)'
];

function escape($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Einstellungen - <?php echo escape(Config::get("title")); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    
    <link href="../static/styles/main.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/<?php echo rawurlencode(Config::get_safe("theme", "theme01")); ?>.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/custom1.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/admin.css" rel="stylesheet" type="text/css" />
    
    <link href="https://fonts.googleapis.com/css?family=Open+Sans&amp;subset=all" rel="stylesheet">
    
    <style>
    .settings-grid {
        display: grid;
        gap: 20px;
    }
    
    .settings-section {
        background: white;
        border-radius: 8px;
        padding: 25px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .settings-section-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        font-size: 14px;
        color: #333;
    }
    
    .form-input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e5e5e5;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #1877f2;
    }
    
    .form-select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e5e5e5;
        border-radius: 6px;
        font-size: 14px;
        background: white;
        cursor: pointer;
    }
    
    .form-textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e5e5e5;
        border-radius: 6px;
        font-size: 14px;
        min-height: 100px;
        resize: vertical;
    }
    
    .form-help {
        font-size: 12px;
        color: #999;
        margin-top: 5px;
    }
    
    .btn-save {
        background: #1877f2;
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.2s;
    }
    
    .btn-save:hover {
        background: #166fe5;
    }
    
    .current-cover {
        max-width: 300px;
        border-radius: 8px;
        margin-bottom: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
    
    .theme-preview {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 10px;
        margin-top: 10px;
    }
    
    .theme-option {
        position: relative;
        cursor: pointer;
    }
    
    .theme-option input[type="radio"] {
        position: absolute;
        opacity: 0;
    }
    
    .theme-card {
        border: 3px solid #e5e5e5;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        transition: all 0.2s;
    }
    
    .theme-option input[type="radio"]:checked + .theme-card {
        border-color: #1877f2;
        background: #e7f3ff;
    }
    
    .theme-card:hover {
        border-color: #1877f2;
    }
    
    .theme-name {
        font-size: 12px;
        font-weight: 600;
        margin-top: 5px;
    }
    
    .password-strength {
        height: 4px;
        background: #e5e5e5;
        border-radius: 2px;
        margin-top: 8px;
        overflow: hidden;
    }
    
    .password-strength-bar {
        height: 100%;
        width: 0%;
        transition: all 0.3s;
    }
    
    .strength-weak { background: #dc3545; width: 33%; }
    .strength-medium { background: #ffc107; width: 66%; }
    .strength-strong { background: #28a745; width: 100%; }
    </style>
</head>
<body class="admin-body">
    
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="admin-container">
            <h1>⚙️ Einstellungen</h1>
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
                <a href="media.php">📁 Dateien</a>
                <a href="trash.php">🗑️ Papierkorb</a>
                <a href="settings.php" class="active">⚙️ Einstellungen</a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-content">
            
            <?php if($message): ?>
            <div class="message message-<?php echo $message_type; ?>">
                <?php echo escape($message); ?>
            </div>
            <?php endif; ?>
            
            <div class="settings-grid">
                
                <!-- General Settings -->
                <div class="settings-section">
                    <h2 class="settings-section-title">📝 Allgemeine Einstellungen</h2>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_general">
                        
                        <div class="form-group">
                            <label class="form-label">Blog-Titel</label>
                            <input type="text" name="title" class="form-input" value="<?php echo escape($config['title'] ?? ''); ?>" required>
                            <div class="form-help">Der Titel deines Blogs (wird im Browser-Tab angezeigt)</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Dein Name</label>
                            <input type="text" name="name" class="form-input" value="<?php echo escape($config['name'] ?? ''); ?>" required>
                            <div class="form-help">Dein Anzeigename</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Untertitel / Beschreibung</label>
                            <textarea name="subtitle" class="form-textarea"><?php echo escape($config['subtitle'] ?? ''); ?></textarea>
                            <div class="form-help">Eine kurze Beschreibung deines Blogs</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Sprache</label>
                            <select name="lang" class="form-select">
                                <?php foreach($languages as $code => $name): ?>
                                    <option value="<?php echo $code; ?>" <?php echo ($config['lang'] ?? 'en') === $code ? 'selected' : ''; ?>>
                                        <?php echo $name; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Zeitzone</label>
                            <select name="timezone" class="form-select">
                                <?php foreach($timezones as $tz => $label): ?>
                                    <option value="<?php echo $tz; ?>" <?php echo ($config['timezone'] ?? 'UTC') === $tz ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn-save">💾 Speichern</button>
                    </form>
                </div>
                
                <!-- Theme Settings -->
                <div class="settings-section">
                    <h2 class="settings-section-title">🎨 Theme</h2>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_general">
                        
                        <div class="form-group">
                            <label class="form-label">Wähle ein Theme</label>
                            <div class="theme-preview">
                                <?php foreach($themes as $theme): ?>
                                    <label class="theme-option">
                                        <input type="radio" name="theme" value="<?php echo $theme; ?>" <?php echo ($config['theme'] ?? 'theme01') === $theme ? 'checked' : ''; ?>>
                                        <div class="theme-card">
                                            <div style="width: 40px; height: 40px; background: #1877f2; border-radius: 50%; margin: 0 auto;"></div>
                                            <div class="theme-name"><?php echo ucfirst($theme); ?></div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-save">💾 Theme speichern</button>
                    </form>
                </div>
                
                <!-- Cover Image -->
                <div class="settings-section">
                    <h2 class="settings-section-title">🖼️ Cover-Bild</h2>
                    
                    <?php if(!empty($config['cover']) && file_exists(PROJECT_PATH . $config['cover'])): ?>
                        <img src="../<?php echo escape($config['cover']); ?>" alt="Current Cover" class="current-cover">
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_cover">
                        
                        <div class="form-group">
                            <label class="form-label">Neues Cover-Bild hochladen</label>
                            <input type="file" name="cover" class="form-input" accept="image/*">
                            <div class="form-help">Empfohlen: 1200x400px oder größer (JPG, PNG, GIF, WebP)</div>
                        </div>
                        
                        <button type="submit" class="btn-save">📤 Hochladen</button>
                    </form>
                </div>
                
                <!-- Password Change -->
                <div class="settings-section">
                    <h2 class="settings-section-title">🔒 Passwort ändern</h2>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label class="form-label">Aktuelles Passwort</label>
                            <input type="password" name="current_password" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Neues Passwort</label>
                            <input type="password" name="new_password" id="newPassword" class="form-input" required minlength="6">
                            <div class="password-strength">
                                <div class="password-strength-bar" id="strengthBar"></div>
                            </div>
                            <div class="form-help">Mindestens 6 Zeichen</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Passwort bestätigen</label>
                            <input type="password" name="confirm_password" class="form-input" required>
                        </div>
                        
                        <button type="submit" class="btn-save">🔐 Passwort ändern</button>
                    </form>
                </div>
                
                <!-- System Info -->
                <div class="settings-section">
                    <h2 class="settings-section-title">ℹ️ System-Informationen</h2>
                    
                    <div class="form-group">
                        <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?>
                    </div>
                    <div class="form-group">
                        <strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?>
                    </div>
                    <div class="form-group">
                        <strong>Blog-Pfad:</strong> <code><?php echo PROJECT_PATH; ?></code>
                    </div>
                    <div class="form-group">
                        <strong>Datenbank:</strong> <?php echo DB::connection(); ?>
                    </div>
                </div>
                
            </div>
            
        </main>
        
    </div>
    
    <script>
    // Password strength indicator
    const newPassword = document.getElementById('newPassword');
    const strengthBar = document.getElementById('strengthBar');
    
    if(newPassword) {
        newPassword.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if(password.length >= 6) strength++;
            if(password.length >= 10) strength++;
            if(/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if(/[0-9]/.test(password)) strength++;
            if(/[^a-zA-Z0-9]/.test(password)) strength++;
            
            strengthBar.className = 'password-strength-bar';
            
            if(strength <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if(strength <= 3) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        });
    }
    </script>
    
</body>
</html>