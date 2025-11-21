<?php
/**
 * Email Service für Blog Advanced
 * Sendet Benachrichtigungen bei neuen Kommentaren, Posts, etc.
 */

class EmailService {
    private static $config = null;
    
    /**
     * Initialisiere Email-Konfiguration
     */
    public static function init() {
        if (self::$config === null) {
            self::$config = [
                'admin_email' => Config::get('admin_email', 'admin@' . $_SERVER['HTTP_HOST']),
                'from_email' => Config::get('from_email', 'noreply@' . $_SERVER['HTTP_HOST']),
                'from_name' => Config::get('title', 'Blog Advanced'),
                'enabled' => Config::get('email_notifications', true)
            ];
        }
    }
    
    /**
     * Sende Email
     */
    private static function send($to, $subject, $body, $headers = []) {
        self::init();
        
        if (!self::$config['enabled']) {
            return false;
        }
        
        // Standard Headers
        $default_headers = [
            'From: ' . self::$config['from_name'] . ' <' . self::$config['from_email'] . '>',
            'Reply-To: ' . self::$config['admin_email'],
            'X-Mailer: PHP/' . phpversion(),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8'
        ];
        
        $all_headers = array_merge($default_headers, $headers);
        
        return mail($to, $subject, $body, implode("\r\n", $all_headers));
    }
    
    /**
     * Benachrichtigung bei neuem Kommentar
     */
    public static function notifyNewComment($comment, $post) {
        self::init();
        
        $subject = '💬 Neuer Kommentar: ' . $post['title'];
        
        $body = self::getEmailTemplate([
            'title' => 'Neuer Kommentar erhalten',
            'content' => '
                <p><strong>' . htmlspecialchars($comment['name']) . '</strong> hat einen Kommentar hinterlassen:</p>
                <blockquote style="border-left: 4px solid #007bff; padding-left: 15px; margin: 20px 0; color: #555;">
                    ' . nl2br(htmlspecialchars($comment['text'])) . '
                </blockquote>
                <p><strong>Beitrag:</strong> ' . htmlspecialchars($post['title']) . '</p>
                <p><strong>Status:</strong> Wartend auf Freigabe</p>
            ',
            'action_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/admin/comments.php',
            'action_text' => 'Kommentar moderieren'
        ]);
        
        return self::send(self::$config['admin_email'], $subject, $body);
    }
    
    /**
     * Email Template
     */
    private static function getEmailTemplate($data) {
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 5px 5px; }
        .button { display: inline-block; background: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>' . self::$config['from_name'] . '</h1>
        </div>
        <div class="content">
            <h2>' . $data['title'] . '</h2>
            ' . $data['content'] . '
            ' . (isset($data['action_url']) ? '<p><a href="' . $data['action_url'] . '" class="button">' . $data['action_text'] . '</a></p>' : '') . '
        </div>
        <div class="footer">
            <p>Diese Email wurde automatisch generiert.</p>
            <p>&copy; ' . date('Y') . ' ' . self::$config['from_name'] . '</p>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * Test Email
     */
    public static function sendTestEmail($to) {
        self::init();
        
        $subject = '✅ Test Email - Blog Advanced';
        
        $body = self::getEmailTemplate([
            'title' => 'Email-System funktioniert!',
            'content' => '
                <p>Dies ist eine Test-Email.</p>
                <p>Wenn du diese Email siehst, funktioniert das Email-System korrekt! 🎉</p>
                <p><strong>Zeit:</strong> ' . date('d.m.Y H:i:s') . '</p>
            ',
            'action_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/admin/',
            'action_text' => 'Zum Dashboard'
        ]);
        
        return self::send($to, $subject, $body);
    }
}
