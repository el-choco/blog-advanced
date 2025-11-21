<?php
defined('PROJECT_PATH') OR exit('No direct script access allowed');

class Email {
    
    // Send email notification when new comment is posted
    public static function notify_new_comment($comment_id) {
        $db = DB::get_instance();
        
        // Get comment details
        $comment = $db->query("
            SELECT c.*, p.plain_text as post_excerpt
            FROM comments c
            LEFT JOIN posts p ON c.post_id = p.id
            WHERE c.id = ?
        ", $comment_id)->first();
        
        if (!$comment) return false;
        
        // Get admin email
        $admin_email = Config::get_safe('admin_email', Config::get_safe('email', null));
        
        if (!$admin_email) return false;
        
        $site_name = Config::get('title');
        $site_url = Config::get_safe('site_url', 'http://' . $_SERVER['HTTP_HOST']);
        
        $subject = "[$site_name] Neuer Kommentar wartet auf Freigabe";
        
        $message = "Hallo!\n\n";
        $message .= "Ein neuer Kommentar wurde auf deinem Blog gepostet und wartet auf Freigabe.\n\n";
        $message .= "Autor: " . $comment['author_name'] . "\n";
        if ($comment['author_email']) {
            $message .= "E-Mail: " . $comment['author_email'] . "\n";
        }
        $message .= "Beitrag: " . substr($comment['post_excerpt'], 0, 50) . "...\n\n";
        $message .= "Kommentar:\n" . $comment['content'] . "\n\n";
        $message .= "Freigeben: " . $site_url . "/admin/comments.php?status=pending\n\n";
        $message .= "---\n";
        $message .= $site_name;
        
        $headers = "From: " . $site_name . " <noreply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
        $headers .= "Reply-To: " . ($comment['author_email'] ?: 'noreply@' . $_SERVER['HTTP_HOST']) . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        return mail($admin_email, $subject, $message, $headers);
    }
    
    // Send notification when comment is approved
    public static function notify_comment_approved($comment_id) {
        $db = DB::get_instance();
        
        $comment = $db->query("
            SELECT c.*, p.plain_text as post_excerpt
            FROM comments c
            LEFT JOIN posts p ON c.post_id = p.id
            WHERE c.id = ?
        ", $comment_id)->first();
        
        if (!$comment || !$comment['author_email']) return false;
        
        $site_name = Config::get('title');
        $site_url = Config::get_safe('site_url', 'http://' . $_SERVER['HTTP_HOST']);
        
        $subject = "[$site_name] Dein Kommentar wurde genehmigt";
        
        $message = "Hallo " . $comment['author_name'] . "!\n\n";
        $message .= "Dein Kommentar auf \"" . substr($comment['post_excerpt'], 0, 50) . "...\" wurde genehmigt und ist jetzt sichtbar.\n\n";
        $message .= "Zum Beitrag: " . $site_url . "/#id=" . $comment['post_id'] . "\n\n";
        $message .= "Vielen Dank für deinen Kommentar!\n\n";
        $message .= "---\n";
        $message .= $site_name;
        
        $headers = "From: " . $site_name . " <noreply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        return mail($comment['author_email'], $subject, $message, $headers);
    }
}
