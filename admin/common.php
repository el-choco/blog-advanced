<?php
// Admin Common Functions
define('ADMIN_PATH', dirname(__FILE__));

// Include main common.php FIRST (defines PROJECT_PATH and loads DB)
require_once dirname(ADMIN_PATH) . '/common.php';

// Load language file for admin
$user_lang = Config::get('lang', 'de');
$lang_file = PROJECT_PATH . 'app/lang/' . $user_lang . '.ini';

if (file_exists($lang_file)) {
    $lang = parse_ini_file($lang_file);
} else {
    // Fallback to English
    $lang_file = PROJECT_PATH . 'app/lang/en.ini';
    if (file_exists($lang_file)) {
        $lang = parse_ini_file($lang_file);
    } else {
        $lang = [];
    }
}

// Helper function for HTML escaping
if (!function_exists('escape')) {
    function escape($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Check if user is logged in - redirect to main page if not
if (!User::is_logged_in()) {
    header('Location: ' . Config::get_safe('url', '/'));
    exit;
}

// Admin helper functions
class AdminHelper {

    // Get post statistics
    public static function getStats() {
        $stats = [
            'total_posts'   => 0,
            'public_posts'  => 0,
            'hidden_posts'  => 0,
            'sticky_posts'  => 0,
            'trash_posts'   => 0,
            'total_images'  => 0,
            'total_files'   => 0
        ];

        $db = DB::get_instance();

        $result = $db->query("SELECT COUNT(*) as count FROM posts WHERE status <> 5")->first();
        $stats['total_posts'] = $result['count'];

        $result = $db->query("SELECT COUNT(*) as count FROM posts WHERE status = 1")->first();
        $stats['public_posts'] = $result['count'];

        $result = $db->query("SELECT COUNT(*) as count FROM posts WHERE status = 4")->first();
        $stats['hidden_posts'] = $result['count'];

        $result = $db->query("SELECT COUNT(*) as count FROM posts WHERE is_sticky = 1 AND status <> 5")->first();
        $stats['sticky_posts'] = $result['count'];

        $result = $db->query("SELECT COUNT(*) as count FROM posts WHERE status = 5")->first();
        $stats['trash_posts'] = $result['count'];

        $result = $db->query("SELECT COUNT(*) as count FROM posts WHERE content_type IN ('image', 'images') AND status <> 5")->first();
        $stats['total_images'] = $result['count'];

        if (is_dir(PROJECT_PATH . 'uploads/files/')) {
            $files = glob(PROJECT_PATH . 'uploads/files/*');
            $stats['total_files'] = count(array_filter($files, 'is_file'));
        }

        return $stats;
    }

    /**
     * Get all posts with details
     * Supports (optional) filter, sort, search for admin table
     * filter: all|published|hidden|sticky and category (slug or id) via $category param
     * sort: newest|oldest
     */
    public static function getAllPosts($include_trash = false, $filter = 'all', $sort = 'newest', $search = '', $category = '') {
        $posts = [];
        $db = DB::get_instance();

        $where = [];
        $params = [];

        if ($include_trash) {
            $where[] = "status = 5";
        } else {
            $where[] = "status <> 5";
        }

        if ($filter === 'published') {
            $where[] = "status = 1";
        } else if ($filter === 'hidden') {
            $where[] = "status = 4";
        } else if ($filter === 'sticky') {
            $where[] = "is_sticky = 1 AND status <> 5";
        }

        if (!empty($search)) {
            $where[] = "plain_text LIKE " . DB::concat("'%'", "?", "'%'");
            $params[] = $search;
        }

        // Kategorie-Filter
        $join = "";
        if (!empty($category)) {
            if (is_numeric($category)) {
                $where[] = "posts.category_id = ?";
                $params[] = intval($category);
            } else {
                $join = "LEFT JOIN categories c ON c.id = posts.category_id";
                $where[] = "c.slug = ?";
                $params[] = $category;
            }
        }

        $order = ($sort === 'oldest') ? "ASC" : "DESC";

        $sql = "
            SELECT posts.*
            FROM posts
            $join
            WHERE " . implode(" AND ", $where) . "
            ORDER BY datetime $order
        ";

        $result = $db->query($sql, ...$params)->all();

        foreach ($result as $row) {
            $posts[] = [
                'id'           => $row['id'],
                'time'         => strtotime($row['datetime']),
                'text'         => $row['text'] ?? '',
                'privacy'      => $row['privacy'] ?? 'public',
                'hidden'       => ($row['status'] == 4),
                'sticky'       => (bool)($row['is_sticky'] ?? false),
                'deleted'      => ($row['status'] == 5),
                'has_images'   => in_array($row['content_type'], ['image', 'images']),
                'image_count'  => (in_array($row['content_type'], ['image', 'images']) && !empty($row['content']))
                    ? (is_array($c = json_decode($row['content'], true)) ? count($c) : 1)
                    : 0,
                'content_type' => $row['content_type'] ?? '',
                'content'      => $row['content'] ?? '',
                'category_id'  => $row['category_id'] ?? null
            ];
        }

        return $posts;
    }

    public static function formatDate($timestamp) {
        return date('d.m.Y H:i', $timestamp);
    }

    public static function getStatusBadge($post) {
        if ($post['deleted']) {
            return '<span class="badge badge-danger">🗑️ Trash</span>';
        }
        if ($post['sticky']) {
            return '<span class="badge badge-warning">📌 Sticky</span>';
        }
        if ($post['hidden']) {
            return '<span class="badge badge-secondary">👁️‍🗨️ Hidden</span>';
        }
        if ($post['privacy'] === 'private') {
            return '<span class="badge badge-info">🔒 Private</span>';
        }
        return '<span class="badge badge-success">✅ Public</span>';
    }

    public static function getExcerpt($text, $length = 100) {
        $text = strip_tags($text);
        if (mb_strlen($text) > $length) {
            return mb_substr($text, 0, $length) . '...';
        }
        return $text;
    }

    public static function updatePost($post_id, $data) {
        $db = DB::get_instance();

        $updates = [];
        $params = [$post_id];

        if (isset($data['sticky'])) {
            $updates[] = "is_sticky = ?";
            $params[] = $data['sticky'] ? 1 : 0;
        }

        if (isset($data['hidden'])) {
            $updates[] = "status = ?";
            $params[] = $data['hidden'] ? 4 : 1;
        }

        if (isset($data['deleted'])) {
            $updates[] = "status = ?";
            $params[] = $data['deleted'] ? 5 : 1;
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE posts SET " . implode(', ', $updates) . " WHERE id = ?";

        $id = array_shift($params);
        $params[] = $id;

        $db->query($sql, ...$params);

        return true;
    }

    public static function permanentDelete($post_id) {
        $db = DB::get_instance();

        $post = $db->query("SELECT * FROM posts WHERE id = ?", $post_id)->first();

        if (!$post) {
            return false;
        }

        if (in_array($post['content_type'], ['image', 'images']) && !empty($post['content'])) {
            $content = json_decode($post['content'], true);
            if (is_array($content)) {
                foreach ($content as $image) {
                    if (isset($image['path']) && file_exists($image['path'])) {
                        @unlink($image['path']);
                    }
                    if (isset($image['thumb']) && file_exists($image['thumb'])) {
                        @unlink($image['thumb']);
                    }
                }
            }
        }

        $db->query("DELETE FROM posts WHERE id = ?", $post_id);

        return true;
    }

    public static function getPostStats() {
        $db = DB::get_instance();

        $stats = [
            'total'     => 0,
            'published' => 0,
            'hidden'    => 0,
            'sticky'    => 0,
            'deleted'   => 0
        ];

        $result = $db->query("SELECT COUNT(*) as count FROM posts WHERE status != 5")->first();
        $stats['total'] = $result['count'] ?? 0;

        $result = $db->query("SELECT COUNT(*) as count FROM posts WHERE status = 1")->first();
        $stats['published'] = $result['count'] ?? 0;

        $result = $db->query("SELECT COUNT(*) as count FROM posts WHERE status = 4")->first();
        $stats['hidden'] = $result['count'] ?? 0;

        $result = $db->query("SELECT COUNT(*) as count FROM posts WHERE is_sticky = 1 AND status != 5")->first();
        $stats['sticky'] = $result['count'] ?? 0;

        $result = $db->query("SELECT COUNT(*) as count FROM posts WHERE status = 5")->first();
        $stats['deleted'] = $result['count'] ?? 0;

        return $stats;
    }

    public static function getTrashCount() {
        $db = DB::get_instance();
        $result = $db->query("SELECT COUNT(*) as count FROM posts WHERE status = 5")->first();
        return $result['count'] ?? 0;
    }
}
?>