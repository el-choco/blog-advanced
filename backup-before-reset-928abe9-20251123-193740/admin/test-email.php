<?php
require_once 'common.php';

// Force login check
if (!User::is_logged_in()) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>📧 Email-System Test</title>
    <link href="../static/styles/admin.css" rel="stylesheet" type="text/css" />
    <style>
        body { padding: 40px; font-family: Arial, sans-serif; }
        .test-box { background: #f8f9fa; border: 1px solid #dee2e6; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        pre { background: white; padding: 15px; border-radius: 3px; overflow-x: auto; }
        h1 { color: #007bff; }
        h2 { color: #495057; margin-top: 30px; }
    </style>
</head>
<body>
    <h1>📧 Email-System Test</h1>
    <p><a href="index.php">← Zurück zum Dashboard</a></p>

    <h2>1. Konfiguration prüfen</h2>
    <div class="test-box">
        <pre><?php
echo "Email Notifications Enabled: " . (Config::get_safe('notifications_enabled', '0') === '1' ? '✅ JA' : '❌ NEIN') . "\n";
echo "Admin Email: " . Config::get_safe('admin_email', '❌ NICHT GESETZT') . "\n";
echo "Notify Admin (new comment): " . (Config::get_safe('notify_admin_new_comment', '0') === '1' ? '✅ JA' : '❌ NEIN') . "\n";
echo "Notify User (approved): " . (Config::get_safe('notify_user_approved', '0') === '1' ? '✅ JA' : '❌ NEIN') . "\n";
echo "From Email: " . Config::get_safe('from_email', '❌ NICHT GESETZT') . "\n";
echo "From Name: " . Config::get_safe('from_name', '❌ NICHT GESETZT') . "\n";
        ?></pre>
    </div>

    <h2>2. Test-Email senden</h2>
    <?php
    if (isset($_GET['send_test'])) {
        $result = Email::send_test_email();
        $class = $result['success'] ? 'success' : 'error';
        echo '<div class="test-box ' . $class . '">';
        echo '<strong>' . ($result['success'] ? '✅ ERFOLG!' : '❌ FEHLER!') . '</strong><br>';
        echo $result['message'];
        echo '</div>';
    }
    ?>
    <div class="test-box">
        <p><strong>Test-Email senden an:</strong> <?php echo Config::get_safe('admin_email', 'KEINE EMAIL GESETZT'); ?></p>
        <p><a href="?send_test=1" style="display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">📧 Test-Email jetzt senden</a></p>
    </div>

    <h2>3. PHP mail() Funktion prüfen</h2>
    <div class="test-box">
        <pre><?php
echo "PHP Version: " . phpversion() . "\n";
echo "mail() function: " . (function_exists('mail') ? '✅ Verfügbar' : '❌ NICHT verfügbar') . "\n";
echo "sendmail_path: " . ini_get('sendmail_path') . "\n";
echo "SMTP: " . ini_get('SMTP') . "\n";
echo "smtp_port: " . ini_get('smtp_port') . "\n";
        ?></pre>
    </div>

    <h2>4. Server Informationen</h2>
    <div class="test-box">
        <pre><?php
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Host: " . $_SERVER['HTTP_HOST'] . "\n";
echo "Remote Addr: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "\n";
        ?></pre>
    </div>

</body>
</html>
