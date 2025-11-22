<?php
require_once 'common.php';

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'update_general':
                $values = [];
                if (isset($_POST['title'])) $values['title'] = $_POST['title'];
                if (isset($_POST['name'])) $values['name'] = $_POST['name'];
                if (isset($_POST['subtitle'])) $values['subtitle'] = $_POST['subtitle'];
                if (isset($_POST['lang'])) $values['lang'] = $_POST['lang'];
                if (isset($_POST['timezone'])) $values['timezone'] = $_POST['timezone'];
                if (isset($_POST['theme'])) $values['theme'] = $_POST['theme'];
                
                Config::save($values);
                $message = 'Allgemeine Einstellungen gespeichert';
                $message_type = 'success';
                break;
                
            case 'update_email':
                $values = [];
                $values['notifications_enabled'] = isset($_POST['notifications_enabled']) ? '1' : '0';
                $values['admin_email'] = $_POST['admin_email'] ?? '';
                $values['notify_admin_new_comment'] = isset($_POST['notify_admin_new_comment']) ? '1' : '0';
                $values['notify_user_approved'] = isset($_POST['notify_user_approved']) ? '1' : '0';
                $values['from_email'] = $_POST['from_email'] ?? '';
                $values['from_name'] = $_POST['from_name'] ?? '';
                
                Config::save($values);
                $message = 'Email-Einstellungen gespeichert';
                $message_type = 'success';
                break;
                
            case 'update_system':
                $values = [];
                $values['debug'] = isset($_POST['debug']) ? 'true' : 'false';
                $values['logs'] = isset($_POST['logs']) ? 'true' : 'false';
                $values['SOFT_DELETE'] = isset($_POST['SOFT_DELETE']) ? 'true' : 'false';
                $values['HARD_DELETE_FILES'] = isset($_POST['HARD_DELETE_FILES']) ? 'true' : 'false';
                $values['AUTO_CLEANUP_IMAGES'] = isset($_POST['AUTO_CLEANUP_IMAGES']) ? 'true' : 'false';
                
                Config::save($values);
                $message = 'System-Einstellungen gespeichert';
                $message_type = 'success';
                break;
                
            case 'update_visitor':
                $values = [];
                $values['visitor_enabled'] = isset($_POST['visitor_enabled']) ? '1' : '0';
                $values['visitor_title'] = $_POST['visitor_title'] ?? '';
                $values['visitor_name'] = $_POST['visitor_name'] ?? '';
                $values['visitor_subtitle'] = $_POST['visitor_subtitle'] ?? '';
                
                Config::save($values);
                $message = 'Besucher-Einstellungen gespeichert';
                $message_type = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = 'Fehler: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Load current config
$config = Config::get_all();

// Available themes
$themes = ['theme01', 'theme02'];
$languages = ['de' => 'Deutsch', 'en' => 'English'];
$timezones = ['Europe/Berlin', 'Europe/Vienna', 'Europe/Zurich', 'UTC', 'America/New_York'];

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einstellungen - Admin</title>
    <link href="../static/styles/main.css" rel="stylesheet">
    <style>
        .settings-container { max-width: 800px; margin: 20px auto; padding: 20px; }
        .settings-section { background: #f9f9f9; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .settings-section h2 { margin-top: 0; color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .form-group { margin: 15px 0; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #555; }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .form-group input[type="checkbox"] { margin-right: 8px; }
        .checkbox-label { display: inline; font-weight: normal; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .message { padding: 15px; margin: 20px 0; border-radius: 4px; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info-box { background: #e7f3ff; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0; }
        .nav-tabs { display: flex; border-bottom: 2px solid #ddd; margin-bottom: 20px; }
        .nav-tabs button { padding: 10px 20px; background: none; border: none; cursor: pointer; font-size: 16px; }
        .nav-tabs button.active { border-bottom: 3px solid #007bff; font-weight: bold; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>
    <div class="settings-container">
        <h1>⚙️ Einstellungen</h1>
        
        <?php if ($message): ?>
            <div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <div class="nav-tabs">
            <button onclick="switchTab('general')" class="tab-btn active" data-tab="general">Allgemein</button>
            <button onclick="switchTab('email')" class="tab-btn" data-tab="email">Email</button>
            <button onclick="switchTab('system')" class="tab-btn" data-tab="system">System</button>
            <button onclick="switchTab('visitor')" class="tab-btn" data-tab="visitor">Besucher</button>
            <button onclick="switchTab('database')" class="tab-btn" data-tab="database">Datenbank</button>
        </div>
        
        <!-- General Settings -->
        <div id="tab-general" class="tab-content active">
            <div class="settings-section">
                <h2>📝 Allgemeine Einstellungen</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="update_general">
                    
                    <div class="form-group">
                        <label>Blog-Titel:</label>
                        <input type="text" name="title" value="<?= htmlspecialchars($config['title'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Dein Name:</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($config['name'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Untertitel:</label>
                        <input type="text" name="subtitle" value="<?= htmlspecialchars($config['subtitle'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Sprache:</label>
                        <select name="lang">
                            <?php foreach ($languages as $code => $name): ?>
                                <option value="<?= $code ?>" <?= ($config['lang'] ?? 'de') === $code ? 'selected' : '' ?>>
                                    <?= $name ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Zeitzone:</label>
                        <select name="timezone">
                            <?php foreach ($timezones as $tz): ?>
                                <option value="<?= $tz ?>" <?= ($config['timezone'] ?? 'Europe/Berlin') === $tz ? 'selected' : '' ?>>
                                    <?= $tz ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Theme:</label>
                        <select name="theme">
                            <?php foreach ($themes as $theme): ?>
                                <option value="<?= $theme ?>" <?= ($config['theme'] ?? 'theme01') === $theme ? 'selected' : '' ?>>
                                    <?= $theme ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn">💾 Speichern</button>
                </form>
            </div>
        </div>
        
        <!-- Email Settings -->
        <div id="tab-email" class="tab-content">
            <div class="settings-section">
                <h2>📧 Email-Einstellungen</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="update_email">
                    
                    <div class="form-group">
                        <input type="checkbox" name="notifications_enabled" id="notifications_enabled" 
                               <?= ($config['notifications_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <label for="notifications_enabled" class="checkbox-label">Email-Benachrichtigungen aktivieren</label>
                    </div>
                    
                    <div class="form-group">
                        <label>Admin-Email:</label>
                        <input type="email" name="admin_email" value="<?= htmlspecialchars($config['admin_email'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <input type="checkbox" name="notify_admin_new_comment" id="notify_admin_new_comment"
                               <?= ($config['notify_admin_new_comment'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <label for="notify_admin_new_comment" class="checkbox-label">Bei neuem Kommentar benachrichtigen</label>
                    </div>
                    
                    <div class="form-group">
                        <input type="checkbox" name="notify_user_approved" id="notify_user_approved"
                               <?= ($config['notify_user_approved'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <label for="notify_user_approved" class="checkbox-label">Benutzer bei Freigabe benachrichtigen</label>
                    </div>
                    
                    <div class="form-group">
                        <label>Absender-Email:</label>
                        <input type="email" name="from_email" value="<?= htmlspecialchars($config['from_email'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Absender-Name:</label>
                        <input type="text" name="from_name" value="<?= htmlspecialchars($config['from_name'] ?? '') ?>">
                    </div>
                    
                    <button type="submit" class="btn">💾 Speichern</button>
                </form>
            </div>
        </div>
        
        <!-- System Settings -->
        <div id="tab-system" class="tab-content">
            <div class="settings-section">
                <h2>🔧 System-Einstellungen</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="update_system">
                    
                    <div class="form-group">
                        <input type="checkbox" name="debug" id="debug"
                               <?= ($config['debug'] ?? 'false') === 'true' || ($config['debug'] ?? false) === true ? 'checked' : '' ?>>
                        <label for="debug" class="checkbox-label">Debug-Modus aktivieren</label>
                    </div>
                    
                    <div class="form-group">
                        <input type="checkbox" name="logs" id="logs"
                               <?= ($config['logs'] ?? 'false') === 'true' || ($config['logs'] ?? false) === true ? 'checked' : '' ?>>
                        <label for="logs" class="checkbox-label">Logging aktivieren</label>
                    </div>
                    
                    <div class="info-box">
                        <strong>⚠️ Soft-Delete Features:</strong>
                        <p>Soft-Delete verschiebt gelöschte Posts in den Papierkorb statt sie sofort zu löschen.</p>
                    </div>
                    
                    <div class="form-group">
                        <input type="checkbox" name="SOFT_DELETE" id="SOFT_DELETE"
                               <?= ($config['SOFT_DELETE'] ?? 'false') === 'true' || ($config['SOFT_DELETE'] ?? false) === true ? 'checked' : '' ?>>
                        <label for="SOFT_DELETE" class="checkbox-label">Soft-Delete aktivieren</label>
                    </div>
                    
                    <div class="form-group">
                        <input type="checkbox" name="HARD_DELETE_FILES" id="HARD_DELETE_FILES"
                               <?= ($config['HARD_DELETE_FILES'] ?? 'false') === 'true' || ($config['HARD_DELETE_FILES'] ?? false) === true ? 'checked' : '' ?>>
                        <label for="HARD_DELETE_FILES" class="checkbox-label">Dateien bei Hard-Delete permanent löschen</label>
                    </div>
                    
                    <div class="form-group">
                        <input type="checkbox" name="AUTO_CLEANUP_IMAGES" id="AUTO_CLEANUP_IMAGES"
                               <?= ($config['AUTO_CLEANUP_IMAGES'] ?? 'false') === 'true' || ($config['AUTO_CLEANUP_IMAGES'] ?? false) === true ? 'checked' : '' ?>>
                        <label for="AUTO_CLEANUP_IMAGES" class="checkbox-label">Automatisches Cleanup von Bildern</label>
                    </div>
                    
                    <button type="submit" class="btn">💾 Speichern</button>
                </form>
            </div>
        </div>
        
        <!-- Visitor Settings -->
        <div id="tab-visitor" class="tab-content">
            <div class="settings-section">
                <h2>👤 Besucher-Ansicht</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="update_visitor">
                    
                    <div class="form-group">
                        <input type="checkbox" name="visitor_enabled" id="visitor_enabled"
                               <?= ($config['visitor_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <label for="visitor_enabled" class="checkbox-label">Besucher-Modus aktivieren</label>
                    </div>
                    
                    <div class="form-group">
                        <label>Besucher-Titel:</label>
                        <input type="text" name="visitor_title" value="<?= htmlspecialchars($config['visitor_title'] ?? $config['title'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Besucher-Name:</label>
                        <input type="text" name="visitor_name" value="<?= htmlspecialchars($config['visitor_name'] ?? $config['name'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Besucher-Untertitel:</label>
                        <input type="text" name="visitor_subtitle" value="<?= htmlspecialchars($config['visitor_subtitle'] ?? '') ?>">
                    </div>
                    
                    <button type="submit" class="btn">💾 Speichern</button>
                </form>
            </div>
        </div>
        
        <!-- Database Info (Read-only) -->
        <div id="tab-database" class="tab-content">
            <div class="settings-section">
                <h2>🗄️ Datenbank-Informationen</h2>
                <div class="info-box">
                    <strong>ℹ️ Nur-Lese-Ansicht:</strong>
                    <p>Datenbank-Einstellungen können nur in der config.ini geändert werden.</p>
                </div>
                
                <div class="form-group">
                    <label>Verbindungstyp:</label>
                    <input type="text" value="<?= htmlspecialchars($config['db_connection'] ?? 'nicht gesetzt') ?>" disabled>
                </div>
                
                <?php if (($config['db_connection'] ?? '') === 'mysql'): ?>
                    <div class="form-group">
                        <label>MySQL Host:</label>
                        <input type="text" value="<?= htmlspecialchars($config['mysql_host'] ?? '') ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label>MySQL User:</label>
                        <input type="text" value="<?= htmlspecialchars($config['mysql_user'] ?? '') ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label>Datenbank-Name:</label>
                        <input type="text" value="<?= htmlspecialchars($config['db_name'] ?? '') ?>" disabled>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <p style="text-align: center; margin-top: 30px;">
            <a href="index.php">← Zurück zum Dashboard</a>
        </p>
    </div>
    
    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById('tab-' + tabName).classList.add('active');
            
            // Add active to selected button
            document.querySelector('[data-tab="' + tabName + '"]').classList.add('active');
        }
    </script>
</body>
</html>
