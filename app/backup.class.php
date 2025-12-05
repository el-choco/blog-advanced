<?php
defined('PROJECT_PATH') OR exit('No direct script access allowed');

class Backup
{
    private static $backup_dir = null;
    
    /**
     * Get backup directory path, creating it if needed
     */
    public static function get_backup_dir() {
        if (self::$backup_dir === null) {
            // Check config for custom backup_dir
            $configured_dir = Config::get_safe('backup_dir', '');
            if (!empty($configured_dir)) {
                // If relative path, prepend PROJECT_PATH
                if (strpos($configured_dir, '/') !== 0) {
                    self::$backup_dir = PROJECT_PATH . $configured_dir;
                } else {
                    self::$backup_dir = $configured_dir;
                }
            } else {
                self::$backup_dir = PROJECT_PATH . 'data/backups/';
            }
            
            // Ensure trailing slash
            self::$backup_dir = rtrim(self::$backup_dir, '/') . '/';
            
            if (!is_dir(self::$backup_dir)) {
                mkdir(self::$backup_dir, 0755, true);
            }
        }
        return self::$backup_dir;
    }
    
    /**
     * Create a new SQL backup
     */
    public static function create() {
        $backup_dir = self::get_backup_dir();
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "backup_$timestamp.sql";
        $filepath = $backup_dir . $filename;
        
        // Get DB credentials
        $db_host = Config::get('mysql_host');
        $db_user = Config::get('mysql_user');
        $db_pass = Config::get('mysql_pass');
        $db_name = Config::get('db_name');
        
        // mysqldump mit --skip-ssl Option
        $command = sprintf(
            'mysqldump --skip-ssl -h %s -u %s -p%s %s > %s 2>&1',
            escapeshellarg($db_host),
            escapeshellarg($db_user),
            escapeshellarg($db_pass),
            escapeshellarg($db_name),
            escapeshellarg($filepath)
        );
        
        exec($command, $output, $return_var);
        
        if ($return_var !== 0) {
            throw new Exception(__('Backup failed') . ': ' . implode("\n", $output));
        }
        
        if (!file_exists($filepath) || filesize($filepath) === 0) {
            throw new Exception(__('Backup file was not created or is empty'));
        }
        
        return [
            'filename' => $filename,
            'size' => filesize($filepath),
            'created' => time()
        ];
    }
    
    /**
     * Get list of all backups (SQL, JSON, CSV ZIP, Full Backup ZIP)
     */
    public static function get_list() {
        $backup_dir = self::get_backup_dir();
        $backups = [];
        
        if (!is_dir($backup_dir)) {
            return $backups;
        }
        
        // Get all backup files
        $patterns = ['*.sql', '*.json', '*.zip'];
        $files = [];
        foreach ($patterns as $pattern) {
            $files = array_merge($files, glob($backup_dir . $pattern));
        }
        
        foreach ($files as $file) {
            $filename = basename($file);
            $type = 'sql';
            if (preg_match('/^export_.*\.json$/', $filename)) {
                $type = 'json';
            } elseif (preg_match('/^export_csv_.*\.zip$/', $filename)) {
                $type = 'csv';
            } elseif (preg_match('/^full_backup_.*\.zip$/', $filename)) {
                $type = 'full';
            } elseif (preg_match('/\.zip$/', $filename)) {
                $type = 'zip';
            }
            
            $backups[] = [
                'filename' => $filename,
                'filepath' => $file,
                'size' => filesize($file),
                'created' => filemtime($file),
                'type' => $type
            ];
        }
        
        usort($backups, function($a, $b) {
            return $b['created'] - $a['created'];
        });
        
        return $backups;
    }
    
    /**
     * Restore a SQL backup
     */
    public static function restore($filename) {
        $backup_dir = self::get_backup_dir();
        $filepath = $backup_dir . $filename;
        
        if (!file_exists($filepath) || strpos(realpath($filepath), realpath($backup_dir)) !== 0) {
            throw new Exception(__('Backup file not found'));
        }
        
        // Get DB credentials
        $db_host = Config::get('mysql_host');
        $db_user = Config::get('mysql_user');
        $db_pass = Config::get('mysql_pass');
        $db_name = Config::get('db_name');
        
        // mysql mit --skip-ssl Option
        $command = sprintf(
            'mysql --skip-ssl -h %s -u %s -p%s %s < %s 2>&1',
            escapeshellarg($db_host),
            escapeshellarg($db_user),
            escapeshellarg($db_pass),
            escapeshellarg($db_name),
            escapeshellarg($filepath)
        );
        
        exec($command, $output, $return_var);
        
        if ($return_var !== 0) {
            throw new Exception(__('Restore failed') . ': ' . implode("\n", $output));
        }
        
        return true;
    }
    
    /**
     * Delete a backup
     */
    public static function delete($filename) {
        $backup_dir = self::get_backup_dir();
        $filepath = $backup_dir . $filename;
        
        if (!file_exists($filepath) || strpos(realpath($filepath), realpath($backup_dir)) !== 0) {
            throw new Exception(__('Backup file not found'));
        }
        
        if (!unlink($filepath)) {
            throw new Exception(__('Backup could not be deleted'));
        }
        
        return true;
    }
    
    /**
     * Export data to JSON format
     * @return array with filename, filepath and download info
     */
    public static function export_json() {
        $backup_dir = self::get_backup_dir();
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "export_{$timestamp}.json";
        $filepath = $backup_dir . $filename;
        
        $db = DB::get_instance();
        
        // Export all tables
        $data = [
            'export_date' => date('Y-m-d H:i:s'),
            'version' => Config::get_safe('version', '1.0'),
            'categories' => [],
            'posts' => [],
            'comments' => [],
            'posts_categories' => []
        ];
        
        // Export categories
        try {
            $data['categories'] = $db->query("SELECT * FROM categories ORDER BY id")->all();
        } catch (Exception $e) {
            $data['categories'] = [];
        }
        
        // Export posts
        try {
            $data['posts'] = $db->query("SELECT * FROM posts ORDER BY id")->all();
        } catch (Exception $e) {
            $data['posts'] = [];
        }
        
        // Export comments
        try {
            $data['comments'] = $db->query("SELECT * FROM comments ORDER BY id")->all();
        } catch (Exception $e) {
            $data['comments'] = [];
        }
        
        // Export posts_categories junction table if exists
        try {
            $data['posts_categories'] = $db->query("SELECT * FROM posts_categories ORDER BY post_id, category_id")->all();
        } catch (Exception $e) {
            $data['posts_categories'] = [];
        }
        
        // Save JSON file
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (file_put_contents($filepath, $json) === false) {
            throw new Exception(__('Could not save export file'));
        }
        
        return [
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => filesize($filepath),
            'created' => time(),
            'type' => 'json'
        ];
    }
    
    /**
     * Export data to CSV format in a ZIP archive
     * @return array with filename, filepath and download info
     */
    public static function export_csv() {
        $backup_dir = self::get_backup_dir();
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "export_csv_{$timestamp}.zip";
        $filepath = $backup_dir . $filename;
        
        $db = DB::get_instance();
        
        // Create ZIP archive
        $zip = new ZipArchive();
        $result = $zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($result !== true) {
            throw new Exception(__('Could not create ZIP archive') . " (Error: $result)");
        }
        
        // Helper function to convert array to CSV string
        $arrayToCsv = function($data, $headers = null) {
            if (empty($data)) {
                return $headers ? implode(',', $headers) . "\n" : '';
            }
            
            $output = fopen('php://temp', 'r+');
            
            // Write headers
            if ($headers === null && !empty($data[0])) {
                $headers = array_keys($data[0]);
            }
            if ($headers) {
                fputcsv($output, $headers);
            }
            
            // Write data rows
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
            
            rewind($output);
            $csv = stream_get_contents($output);
            fclose($output);
            
            return $csv;
        };
        
        // Export categories
        try {
            $categories = $db->query("SELECT * FROM categories ORDER BY id")->all();
            $zip->addFromString('categories.csv', $arrayToCsv($categories, ['id', 'name', 'slug', 'description', 'created_at', 'updated_at']));
        } catch (Exception $e) {
            $zip->addFromString('categories.csv', "id,name,slug,description,created_at,updated_at\n");
        }
        
        // Export posts
        try {
            $posts = $db->query("SELECT * FROM posts ORDER BY id")->all();
            $zip->addFromString('posts.csv', $arrayToCsv($posts));
        } catch (Exception $e) {
            $zip->addFromString('posts.csv', '');
        }
        
        // Export comments
        try {
            $comments = $db->query("SELECT * FROM comments ORDER BY id")->all();
            $zip->addFromString('comments.csv', $arrayToCsv($comments));
        } catch (Exception $e) {
            $zip->addFromString('comments.csv', '');
        }
        
        // Export posts_categories junction table if exists
        try {
            $posts_categories = $db->query("SELECT * FROM posts_categories ORDER BY post_id, category_id")->all();
            $zip->addFromString('posts_categories.csv', $arrayToCsv($posts_categories, ['post_id', 'category_id']));
        } catch (Exception $e) {
            $zip->addFromString('posts_categories.csv', "post_id,category_id\n");
        }
        
        // Close ZIP file properly
        if (!$zip->close()) {
            throw new Exception(__('Could not finalize ZIP archive'));
        }
        
        if (!file_exists($filepath) || filesize($filepath) === 0) {
            throw new Exception(__('ZIP archive is empty or was not created'));
        }
        
        return [
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => filesize($filepath),
            'created' => time(),
            'type' => 'csv'
        ];
    }
    
    /**
     * Create a full backup including database and media files
     * @return array with filename, filepath and download info
     */
    public static function export_full() {
        $backup_dir = self::get_backup_dir();
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "full_backup_{$timestamp}.zip";
        $filepath = $backup_dir . $filename;
        
        // Create ZIP archive
        $zip = new ZipArchive();
        $result = $zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($result !== true) {
            throw new Exception(__('Could not create ZIP archive') . " (Error: $result)");
        }
        
        // Add JSON export
        $db = DB::get_instance();
        $data = [
            'export_date' => date('Y-m-d H:i:s'),
            'version' => Config::get_safe('version', '1.0'),
            'categories' => [],
            'posts' => [],
            'comments' => [],
            'posts_categories' => []
        ];
        
        try {
            $data['categories'] = $db->query("SELECT * FROM categories ORDER BY id")->all();
        } catch (Exception $e) {}
        
        try {
            $data['posts'] = $db->query("SELECT * FROM posts ORDER BY id")->all();
        } catch (Exception $e) {}
        
        try {
            $data['comments'] = $db->query("SELECT * FROM comments ORDER BY id")->all();
        } catch (Exception $e) {}
        
        try {
            $data['posts_categories'] = $db->query("SELECT * FROM posts_categories ORDER BY post_id, category_id")->all();
        } catch (Exception $e) {}
        
        $zip->addFromString('export.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Add CSV files
        $arrayToCsv = function($data, $headers = null) {
            if (empty($data)) {
                return $headers ? implode(',', $headers) . "\n" : '';
            }
            $output = fopen('php://temp', 'r+');
            if ($headers === null && !empty($data[0])) {
                $headers = array_keys($data[0]);
            }
            if ($headers) {
                fputcsv($output, $headers);
            }
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
            rewind($output);
            $csv = stream_get_contents($output);
            fclose($output);
            return $csv;
        };
        
        $zip->addFromString('csv/categories.csv', $arrayToCsv($data['categories']));
        $zip->addFromString('csv/posts.csv', $arrayToCsv($data['posts']));
        $zip->addFromString('csv/comments.csv', $arrayToCsv($data['comments']));
        $zip->addFromString('csv/posts_categories.csv', $arrayToCsv($data['posts_categories']));
        
        // Add media directories
        $mediaDirs = [
            'static/images/' => 'media/static/images/',
            'data/i/' => 'media/data/i/',
            'data/t/' => 'media/data/t/',
        ];
        
        // Check for uploads/files directory
        if (is_dir(PROJECT_PATH . 'uploads/files/')) {
            $mediaDirs['uploads/files/'] = 'media/uploads/files/';
        }
        
        foreach ($mediaDirs as $srcDir => $destDir) {
            $fullSrcDir = PROJECT_PATH . $srcDir;
            if (is_dir($fullSrcDir)) {
                self::addDirToZip($zip, $fullSrcDir, $destDir);
            }
        }
        
        // Close ZIP file
        if (!$zip->close()) {
            throw new Exception(__('Could not finalize ZIP archive'));
        }
        
        if (!file_exists($filepath) || filesize($filepath) === 0) {
            throw new Exception(__('ZIP archive is empty or was not created'));
        }
        
        return [
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => filesize($filepath),
            'created' => time(),
            'type' => 'full'
        ];
    }
    
    /**
     * Helper: Add directory contents to ZIP archive
     */
    private static function addDirToZip($zip, $srcDir, $destDir) {
        $srcDir = rtrim($srcDir, '/') . '/';
        $destDir = rtrim($destDir, '/') . '/';
        
        if (!is_dir($srcDir)) {
            return;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $relativePath = substr($item->getPathname(), strlen($srcDir));
            $zipPath = $destDir . $relativePath;
            
            if ($item->isDir()) {
                $zip->addEmptyDir($zipPath);
            } else {
                $zip->addFile($item->getPathname(), $zipPath);
            }
        }
    }
    
    /**
     * Import data from JSON file (Replace mode)
     * @param string $filepath Path to JSON file
     * @return array with import statistics
     */
    public static function import_json($filepath) {
        if (!file_exists($filepath)) {
            throw new Exception(__('Import file not found'));
        }
        
        $json = file_get_contents($filepath);
        $data = json_decode($json, true);
        
        if ($data === null) {
            throw new Exception(__('Invalid JSON format'));
        }
        
        return self::importData($data);
    }
    
    /**
     * Import data from ZIP archive (Full backup)
     * @param string $filepath Path to ZIP file
     * @return array with import statistics
     */
    public static function import_full($filepath) {
        if (!file_exists($filepath)) {
            throw new Exception(__('Import file not found'));
        }
        
        $zip = new ZipArchive();
        if ($zip->open($filepath) !== true) {
            throw new Exception(__('Could not open ZIP archive'));
        }
        
        // Extract to temp directory
        $tempDir = sys_get_temp_dir() . '/blog_import_' . uniqid();
        mkdir($tempDir, 0755, true);
        
        try {
            $zip->extractTo($tempDir);
            $zip->close();
            
            // Import database from export.json
            $jsonFile = $tempDir . '/export.json';
            $stats = ['categories' => 0, 'posts' => 0, 'comments' => 0, 'posts_categories' => 0, 'media_files' => 0];
            
            if (file_exists($jsonFile)) {
                $json = file_get_contents($jsonFile);
                $data = json_decode($json, true);
                if ($data !== null) {
                    $stats = self::importData($data);
                }
            }
            
            // Restore media files
            $mediaDir = $tempDir . '/media/';
            if (is_dir($mediaDir)) {
                $mediaStats = self::restoreMediaFiles($mediaDir);
                $stats['media_files'] = $mediaStats;
            }
            
            // Cleanup temp directory
            self::removeDir($tempDir);
            
            return $stats;
            
        } catch (Exception $e) {
            // Cleanup on error
            self::removeDir($tempDir);
            throw $e;
        }
    }
    
    /**
     * Import data array into database (Replace mode)
     * Order: categories → posts → posts_categories → comments
     */
    private static function importData($data) {
        $db = DB::get_instance();
        $stats = ['categories' => 0, 'posts' => 0, 'comments' => 0, 'posts_categories' => 0];
        
        try {
            // Disable foreign key checks for MySQL
            if (DB::connection() === 'mysql') {
                $db->exec('SET FOREIGN_KEY_CHECKS = 0');
            }
            
            // 1. Import categories (truncate first)
            if (!empty($data['categories'])) {
                try {
                    $db->exec('TRUNCATE TABLE categories');
                } catch (Exception $e) {
                    $db->exec('DELETE FROM categories');
                }
                
                foreach ($data['categories'] as $row) {
                    try {
                        $db->insert('categories', $row);
                        $stats['categories']++;
                    } catch (Exception $e) {
                        // Skip on error
                    }
                }
            }
            
            // 2. Import posts (truncate first)
            if (!empty($data['posts'])) {
                try {
                    $db->exec('TRUNCATE TABLE posts');
                } catch (Exception $e) {
                    $db->exec('DELETE FROM posts');
                }
                
                foreach ($data['posts'] as $row) {
                    try {
                        $db->insert('posts', $row);
                        $stats['posts']++;
                    } catch (Exception $e) {
                        // Skip on error
                    }
                }
            }
            
            // 3. Import posts_categories junction table
            if (!empty($data['posts_categories'])) {
                try {
                    $db->exec('TRUNCATE TABLE posts_categories');
                } catch (Exception $e) {
                    try {
                        $db->exec('DELETE FROM posts_categories');
                    } catch (Exception $e2) {
                        // Table might not exist
                    }
                }
                
                foreach ($data['posts_categories'] as $row) {
                    try {
                        $db->insert('posts_categories', $row);
                        $stats['posts_categories']++;
                    } catch (Exception $e) {
                        // Skip on error
                    }
                }
            }
            
            // 4. Import comments (truncate first)
            if (!empty($data['comments'])) {
                try {
                    $db->exec('TRUNCATE TABLE comments');
                } catch (Exception $e) {
                    $db->exec('DELETE FROM comments');
                }
                
                foreach ($data['comments'] as $row) {
                    try {
                        $db->insert('comments', $row);
                        $stats['comments']++;
                    } catch (Exception $e) {
                        // Skip on error
                    }
                }
            }
            
            // Re-enable foreign key checks for MySQL
            if (DB::connection() === 'mysql') {
                $db->exec('SET FOREIGN_KEY_CHECKS = 1');
            }
            
        } catch (Exception $e) {
            // Re-enable foreign key checks on error
            if (DB::connection() === 'mysql') {
                try {
                    $db->exec('SET FOREIGN_KEY_CHECKS = 1');
                } catch (Exception $e2) {}
            }
            throw $e;
        }
        
        return $stats;
    }
    
    /**
     * Restore media files from extracted backup
     */
    private static function restoreMediaFiles($mediaDir) {
        $count = 0;
        
        $mappings = [
            'static/images/' => PROJECT_PATH . 'static/images/',
            'data/i/' => PROJECT_PATH . 'data/i/',
            'data/t/' => PROJECT_PATH . 'data/t/',
            'uploads/files/' => PROJECT_PATH . 'uploads/files/',
        ];
        
        foreach ($mappings as $subDir => $destDir) {
            $srcDir = $mediaDir . $subDir;
            if (!is_dir($srcDir)) {
                continue;
            }
            
            // Create destination directory if needed
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            
            // Copy files
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $item) {
                $relativePath = substr($item->getPathname(), strlen($srcDir));
                $destPath = $destDir . $relativePath;
                
                if ($item->isDir()) {
                    if (!is_dir($destPath)) {
                        mkdir($destPath, 0755, true);
                    }
                } else {
                    $destSubDir = dirname($destPath);
                    if (!is_dir($destSubDir)) {
                        mkdir($destSubDir, 0755, true);
                    }
                    if (copy($item->getPathname(), $destPath)) {
                        $count++;
                    }
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Remove directory recursively
     */
    private static function removeDir($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * Download a backup file
     */
    public static function download($filename) {
        $backup_dir = self::get_backup_dir();
        $filepath = $backup_dir . $filename;
        
        if (!file_exists($filepath)) {
            throw new Exception(__('Backup file not found'));
        }
        
        // Validate file is within backup directory
        if (strpos(realpath($filepath), realpath($backup_dir)) !== 0) {
            throw new Exception(__('Invalid file path'));
        }
        
        return $filepath;
    }
}
