<?php
defined('PROJECT_PATH') or exit('No direct script access allowed');

class Categories
{
    private static function db() {
        return DB::get_instance();
    }

    private static function slugify(string $name): string {
        $s = strtolower(trim($name));
        $s = preg_replace('/[^a-z0-9]+/i', '-', $s);
        $s = trim($s, '-');
        return $s === '' ? 'cat' . substr(md5($name), 0, 6) : $s;
    }

    public static function all(): array {
        $db = self::db();
        $rows = $db->query("SELECT id, name, slug, created_at FROM categories ORDER BY name ASC")->all();
        return $rows ?: [];
    }

    public static function withCounts(): array {
        $db = self::db();
        $sql = "
            SELECT c.id, c.name, c.slug, c.created_at,
                   COUNT(p.id) AS post_count
            FROM categories c
            LEFT JOIN posts p ON p.category_id = c.id
            GROUP BY c.id, c.name, c.slug, c.created_at
            ORDER BY c.name ASC
        ";
        $rows = $db->query($sql)->all();
        return $rows ?: [];
    }

    public static function create(string $name): int {
        $name = trim($name);
        if ($name === '') {
            throw new Exception('Category name required');
        }
        $slug = self::slugify($name);
        $db = self::db();
        $db->query("INSERT INTO categories (name, slug) VALUES (?, ?)", [$name, $slug]);
        $row = $db->query("SELECT id FROM categories WHERE slug = ?", [$slug])->first();
        return (int)($row['id'] ?? 0);
    }

    public static function rename(int $id, string $name): bool {
        $name = trim($name);
        if ($name === '') {
            throw new Exception('Category name required');
        }
        $slug = self::slugify($name);
        $db = self::db();
        $db->query("UPDATE categories SET name = ?, slug = ? WHERE id = ?", [$name, $slug, $id]);
        return true;
    }

    public static function delete(int $id): bool {
        $db = self::db();
        // Detach posts first (works for all DBs; SQLite braucht das)
        $db->query("UPDATE posts SET category_id = NULL WHERE category_id = ?", [$id]);
        $db->query("DELETE FROM categories WHERE id = ?", [$id]);
        return true;
    }

    public static function assignPost(int $postId, ?int $categoryId): bool {
        $db = self::db();
        if ($categoryId === null) {
            $db->query("UPDATE posts SET category_id = NULL WHERE id = ?", [$postId]);
        } else {
            $db->query("UPDATE posts SET category_id = ? WHERE id = ?", [$categoryId, $postId]);
        }
        return true;
    }
}