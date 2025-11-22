<?php
declare(strict_types=1);

defined('PROJECT_PATH') OR exit('No direct script access allowed');

class Email
{
    private static function is_enabled(): bool
    {
        return Config::get_safe('notifications_enabled', '0') === '1';
    }
    
    public static function notify_new_comment(int $comment_id): bool
    {
        if (!self::is_enabled() || Config::get_safe('notify_admin_new_comment', '0') !== '1') {
            return false;
        }
        
        $db = DB::get_instance();
        
        $comment = $db->query("
            SELECT c.*, LEFT(p.plain_text, 100) as post_excerpt, p.id as post_id
            FROM comments c
            LEFT JOIN posts p ON c.post_id = p.id
            WHERE c.id = ?
        ", $comment_id)->first();
        
        if (!$comment) {
            return false;
        }
        
        $admin_email = Config::get_safe('admin_email', null);
        if (!$admin_email) {
            return false;
        }
        
        $site_name = Config::get_safe('from_name', Config::get('title'));
        $from_email = Config::get_safe('from_email', 'noreply@' . $_SERVER['HTTP_HOST']);
        $site_url = 'http://' . $_SERVER['HTTP_HOST'];
        
        $subject = "[{$site_name}] 💬 Neuer Kommentar wartet auf Freigabe";
        
        $message = "Hallo!\n\n";
        $message .= "Ein neuer Kommentar wurde auf deinem Blog gepostet und wartet auf Freigabe.\n\n";
        $message .= "Autor: {$comment['author_name']}\n";
        
        if (!empty($comment['author_email'])) {
            $message .= "E-Mail: {$comment['author_email']}\n";
        }
        
        $message .= "Beitrag: " . substr($comment['post_excerpt'], 0, 50) . "...\n\n";
        $message .= "Kommentar:\n{$comment['content']}\n\n";
        $message .= "Freigeben: {$site_url}/admin/comments.php?status=pending\n\n";
        $message .= "---\n{$site_name}";
        
        $headers = "From: {$site_name} <{$from_email}>\r\n";
        $headers .= "Reply-To: " . ($comment['author_email'] ?: $from_email) . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        return mail($admin_email, $subject, $message, $headers);
    }
    
    public static function notify_comment_approved(int $comment_id): bool
    {
        if (!self::is_enabled() || Config::get_safe('notify_user_approved', '0') !== '1') {
            return false;
        }
        
        $db = DB::get_instance();
        
        $comment = $db->query("
            SELECT c.*, LEFT(p.plain_text, 100) as post_excerpt, p.id as post_id
            FROM comments c
            LEFT JOIN posts p ON c.post_id = p.id
            WHERE c.id = ?
        ", $comment_id)->first();
        
        if (!$comment || empty($comment['author_email'])) {
            return false;
        }
        
        $site_name = Config::get_safe('from_name', Config::get('title'));
        $from_email = Config::get_safe('from_email', 'noreply@' . $_SERVER['HTTP_HOST']);
        $site_url = 'http://' . $_SERVER['HTTP_HOST'];
        
        $subject = "[{$site_name}] ✅ Dein Kommentar wurde genehmigt";
        
        $message = "Hallo {$comment['author_name']}!\n\n";
        $message .= "Dein Kommentar auf \"" . substr($comment['post_excerpt'], 0, 50) . "...\" wurde genehmigt und ist jetzt sichtbar.\n\n";
        $message .= "Zum Beitrag: {$site_url}/?id={$comment['post_id']}\n\n";
        $message .= "Vielen Dank für deinen Kommentar!\n\n";
        $message .= "---\n{$site_name}";
        
        $headers = "From: {$site_name} <{$from_email}>\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        return mail($comment['author_email'], $subject, $message, $headers);
    }
    
    public static function send_test_email(?string $to_email = null): array
    {
        if (!self::is_enabled()) {
            return [
                'success' => false,
                'message' => 'Email notifications are disabled'
            ];
        }
        
        $to = $to_email ?? Config::get_safe('admin_email', null);
        
        if (!$to) {
            return [
                'success' => false,
                'message' => 'No recipient email configured'
            ];
        }
        
        $site_name = Config::get_safe('from_name', Config::get('title'));
        $from_email = Config::get_safe('from_email', 'noreply@' . $_SERVER['HTTP_HOST']);
        
        $subject = "[{$site_name}] ✅ Test Email";
        
        $message = "Dies ist eine Test-Email vom Blog-System.\n\n";
        $message .= "Wenn du diese Email siehst, funktioniert das Email-System! 🎉\n\n";
        $message .= "Zeit: " . date('d.m.Y H:i:s') . "\n";
        $message .= "---\n{$site_name}";
        
        $headers = "From: {$site_name} <{$from_email}>\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        $result = mail($to, $subject, $message, $headers);
        
        return [
            'success' => $result,
            'message' => $result ? 'Test email sent successfully!' : 'Failed to send test email'
        ];
    }
}
