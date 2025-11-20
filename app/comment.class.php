<?php
defined('PROJECT_PATH') OR exit('No direct script access allowed');

class Comment {
    
    // Add new comment
    public static function comment_add($r) {
        // Honeypot spam protection
        if (!empty($r['website_check'])) {
            throw new Exception("Spam detected!");
        }
        
        // Validate required fields
        if (empty($r['post_id']) || empty($r['author_name']) || empty($r['content'])) {
            throw new Exception("Missing required fields!");
        }
        
        // Sanitize input
        $data = [
            'post_id' => (int)$r['post_id'],
            'parent_id' => !empty($r['parent_id']) ? (int)$r['parent_id'] : null,
            'author_name' => htmlspecialchars(trim($r['author_name']), ENT_QUOTES, 'UTF-8'),
            'author_email' => !empty($r['author_email']) ? filter_var($r['author_email'], FILTER_VALIDATE_EMAIL) : null,
            'author_website' => !empty($r['author_website']) ? filter_var($r['author_website'], FILTER_VALIDATE_URL) : null,
            'author_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'content' => htmlspecialchars(trim($r['content']), ENT_QUOTES, 'UTF-8'),
            'status' => Config::get_safe('comments_auto_approve', false) ? 'approved' : 'pending',
            'created_at' => 'NOW()'
        ];
        
        // Insert into database
        $id = DB::get_instance()->insert('comments', $data)->last_id();
        
        return [
            'success' => true,
            'comment_id' => $id,
            'status' => $data['status'],
            'message' => $data['status'] === 'approved' 
                ? 'Comment posted successfully!' 
                : 'Comment is awaiting moderation.'
        ];
    }
    
    // Get comments for a post
    public static function comment_get($r) {
        $post_id = (int)$r['post_id'];
        $include_pending = User::is_logged_in();
        
        $status_filter = $include_pending 
            ? "status IN ('approved', 'pending')" 
            : "status = 'approved'";
        
        $comments = DB::get_instance()->query("
            SELECT 
                id,
                post_id,
                parent_id,
                author_name,
                content,
                status,
                created_at
            FROM comments
            WHERE post_id = ? AND $status_filter
            ORDER BY created_at ASC
        ", $post_id)->all();
        
        return ['comments' => $comments, 'count' => count($comments)];
    }
    
    // Approve comment (admin only)
    public static function comment_approve($r) {
        User::login_protected();
        
        DB::get_instance()->query("
            UPDATE comments 
            SET status = 'approved'
            WHERE id = ?
        ", $r['id']);
        
        return ['success' => true];
    }
    
    // Mark as spam (admin only)
    public static function comment_spam($r) {
        User::login_protected();
        
        DB::get_instance()->query("
            UPDATE comments 
            SET status = 'spam'
            WHERE id = ?
        ", $r['id']);
        
        return ['success' => true];
    }
    
    // Delete comment (admin only)
    public static function comment_delete($r) {
        User::login_protected();
        
        DB::get_instance()->query("
            UPDATE comments 
            SET status = 'trash'
            WHERE id = ?
        ", $r['id']);
        
        return ['success' => true];
    }
    
    // Get pending comments count (admin)
    public static function comment_pending_count($r = []) {
        User::login_protected();
        
        $count = DB::get_instance()->query("
            SELECT COUNT(*) as count
            FROM comments
            WHERE status = 'pending'
        ")->first('count');
        
        return ['count' => $count];
    }
    
    // Get all comments (admin)
    public static function comment_get_all($r) {
        User::login_protected();
        
        $status = $r['status'] ?? 'all';
        $limit = (int)($r['limit'] ?? 50);
        $offset = (int)($r['offset'] ?? 0);
        
        if ($status === 'all') {
            $comments = DB::get_instance()->query("
                SELECT 
                    c.id,
                    c.post_id,
                    c.author_name,
                    c.author_email,
                    c.content,
                    c.status,
                    c.created_at,
                    LEFT(p.plain_text, 50) as post_title
                FROM comments c
                LEFT JOIN posts p ON c.post_id = p.id
                WHERE c.status IN ('approved', 'pending', 'spam')
                ORDER BY c.created_at DESC
                LIMIT ? OFFSET ?
            ", $limit, $offset)->all();
        } else {
            $comments = DB::get_instance()->query("
                SELECT 
                    c.id,
                    c.post_id,
                    c.author_name,
                    c.author_email,
                    c.content,
                    c.status,
                    c.created_at,
                    LEFT(p.plain_text, 50) as post_title
                FROM comments c
                LEFT JOIN posts p ON c.post_id = p.id
                WHERE c.status = ?
                ORDER BY c.created_at DESC
                LIMIT ? OFFSET ?
            ", $status, $limit, $offset)->all();
        }
        
        return ['comments' => $comments, 'count' => count($comments)];
    }
}
