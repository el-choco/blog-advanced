<?php
defined('PROJECT_PATH') OR exit('No direct script access allowed');

class Backup
{
    private static $backup_dir = null;
    
    // Tables to include in backup (whitelist for security)
    private static $tables = ['posts', 'comments', 'categories', 'images', 'users'];
    
    // Valid column names pattern (alphanumeric and underscore only)
    private static $valid_identifier_pattern = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';
    
    // Maximum file size for ZIP inclusion (100MB)
    private const MAX_FILE_SIZE = 104857600; // 100 * 1024 * 1024
    
    // Media directories to include in full backup ZIP
    private static $media_dirs = ['static/images/', 'data/', 'uploads/files/'];
    
    private static function get_backup_dir() {
        if (self::$backup_dir === null) {
            self::$backup_dir = PROJECT_PATH . 'data/backups/';
            
            if (!is_dir(self::$backup_dir)) {
                mkdir(self::$backup_dir, 0755, true);
            }
        }
        return self::$backup_dir;
    }
    
    /**
     * Validate that a table name is in the allowed whitelist
     */
    private static function isValidTable($table) {
        return in_array($table, self::$tables, true);
    }
    
    /**
     * Validate that a column name is safe (alphanumeric and underscore only)
     */
    private static function isValidColumnName($column) {
        return preg_match(self::$valid_identifier_pattern, $column) === 1;
    }
    
    /**
     * Get all table data from database
     * @return array Associative array with table names as keys
     */
    private static function getAllTableData() {
        $db = DB::get_instance();
        $data = [];
        
        foreach (self::$tables as $table) {
            // Table name is from our whitelist, so it's safe
            try {
                $rows = $db->query("SELECT * FROM `$table`")->all();
                $data[$table] = $rows;
            } catch (Exception $e) {
                // Table might not exist, skip it
                $data[$table] = [];
            }
        }
        
        return $data;
    }
    
    /**
     * Export all tables to JSON format
     * @return array Contains 'data' (JSON string) and 'filename'
     */
    public static function exportToJson() {
        try {
            $data = self::getAllTableData();
            
            $export = [
                'version' => Config::get_safe('version', '1.0'),
                'exported_at' => date('Y-m-d H:i:s'),
                'db_type' => DB::connection(),
                'tables' => $data
            ];
            
            $json = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            if ($json === false) {
                throw new Exception('Failed to encode data to JSON');
            }
            
            $filename = 'export_' . date('Y-m-d_H-i-s') . '.json';
            
            return [
                'data' => $json,
                'filename' => $filename,
                'size' => strlen($json)
            ];
        } catch (Exception $e) {
            throw new Exception('JSON export failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Export all tables to CSV format (creates a ZIP with multiple CSV files)
     * @return array Contains 'filepath' and 'filename'
     */
    public static function exportToCsv() {
        try {
            $data = self::getAllTableData();
            $backup_dir = self::get_backup_dir();
            $timestamp = date('Y-m-d_H-i-s');
            $zipFilename = "export_csv_$timestamp.zip";
            $zipPath = $backup_dir . $zipFilename;
            
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception('Could not create ZIP archive');
            }
            
            foreach ($data as $table => $rows) {
                if (empty($rows)) {
                    continue;
                }
                
                // Create CSV content
                $csvContent = '';
                
                // Header row
                $headers = array_keys($rows[0]);
                $csvContent .= self::arrayToCsvLine($headers);
                
                // Data rows
                foreach ($rows as $row) {
                    $csvContent .= self::arrayToCsvLine(array_values($row));
                }
                
                $zip->addFromString("$table.csv", $csvContent);
            }
            
            // Add metadata file
            $meta = [
                'version' => Config::get_safe('version', '1.0'),
                'exported_at' => date('Y-m-d H:i:s'),
                'db_type' => DB::connection(),
                'tables' => array_keys($data)
            ];
            $zip->addFromString('_metadata.json', json_encode($meta, JSON_PRETTY_PRINT));
            
            $zip->close();
            
            return [
                'filepath' => $zipPath,
                'filename' => $zipFilename,
                'size' => filesize($zipPath)
            ];
        } catch (Exception $e) {
            throw new Exception('CSV export failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Convert array to CSV line
     */
    private static function arrayToCsvLine($data) {
        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, $data);
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);
        return $csv;
    }
    
    /**
     * Export full backup to ZIP (includes DB data as JSON/CSV and media files)
     * @param bool $includeMedia Whether to include media directories
     * @return array Contains 'filepath' and 'filename'
     */
    public static function exportToZip($includeMedia = true) {
        try {
            $data = self::getAllTableData();
            $backup_dir = self::get_backup_dir();
            $timestamp = date('Y-m-d_H-i-s');
            $zipFilename = "full_backup_$timestamp.zip";
            $zipPath = $backup_dir . $zipFilename;
            
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception('Could not create ZIP archive');
            }
            
            // Add database data as JSON
            $dbExport = [
                'version' => Config::get_safe('version', '1.0'),
                'exported_at' => date('Y-m-d H:i:s'),
                'db_type' => DB::connection(),
                'tables' => $data
            ];
            $zip->addFromString('database/data.json', json_encode($dbExport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            // Add database data as CSV files
            foreach ($data as $table => $rows) {
                if (empty($rows)) {
                    continue;
                }
                
                $csvContent = '';
                $headers = array_keys($rows[0]);
                $csvContent .= self::arrayToCsvLine($headers);
                
                foreach ($rows as $row) {
                    $csvContent .= self::arrayToCsvLine(array_values($row));
                }
                
                $zip->addFromString("database/$table.csv", $csvContent);
            }
            
            // Add media files if requested
            if ($includeMedia) {
                foreach (self::$media_dirs as $mediaDir) {
                    $fullPath = PROJECT_PATH . $mediaDir;
                    if (is_dir($fullPath)) {
                        self::addDirectoryToZip($zip, $fullPath, 'media/' . $mediaDir);
                    }
                }
            }
            
            // Add backup metadata
            $meta = [
                'version' => Config::get_safe('version', '1.0'),
                'exported_at' => date('Y-m-d H:i:s'),
                'db_type' => DB::connection(),
                'include_media' => $includeMedia,
                'media_dirs' => self::$media_dirs,
                'tables' => array_keys($data)
            ];
            $zip->addFromString('_backup_meta.json', json_encode($meta, JSON_PRETTY_PRINT));
            
            $zip->close();
            
            return [
                'filepath' => $zipPath,
                'filename' => $zipFilename,
                'size' => filesize($zipPath)
            ];
        } catch (Exception $e) {
            throw new Exception('Full backup export failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Recursively add directory contents to ZIP
     */
    private static function addDirectoryToZip($zip, $dirPath, $zipPath) {
        $dirPath = rtrim($dirPath, '/') . '/';
        $zipPath = rtrim($zipPath, '/') . '/';
        
        if (!is_dir($dirPath)) {
            return;
        }
        
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($dirPath));
            
            if ($file->isDir()) {
                $zip->addEmptyDir($zipPath . $relativePath);
            } else {
                // Skip .gitkeep files and very large files
                if (basename($filePath) === '.gitkeep') {
                    continue;
                }
                if (filesize($filePath) > self::MAX_FILE_SIZE) {
                    continue; // Skip files larger than MAX_FILE_SIZE
                }
                $zip->addFile($filePath, $zipPath . $relativePath);
            }
        }
    }
    
    /**
     * Import data from JSON file
     * @param string $jsonData JSON string containing backup data
     * @return array Import result with status
     */
    public static function importFromJson($jsonData) {
        try {
            $import = json_decode($jsonData, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON data: ' . json_last_error_msg());
            }
            
            if (!isset($import['tables']) || !is_array($import['tables'])) {
                throw new Exception('Invalid backup format: missing tables data');
            }
            
            $db = DB::get_instance();
            $imported = [];
            
            foreach ($import['tables'] as $table => $rows) {
                // Validate table name against whitelist
                if (!self::isValidTable($table)) {
                    continue; // Skip unknown tables
                }
                
                if (empty($rows)) {
                    $imported[$table] = 0;
                    continue;
                }
                
                $count = 0;
                $errors = 0;
                foreach ($rows as $row) {
                    try {
                        $columns = array_keys($row);
                        
                        // Validate all column names for safety
                        $validColumns = true;
                        foreach ($columns as $col) {
                            if (!self::isValidColumnName($col)) {
                                $validColumns = false;
                                break;
                            }
                        }
                        
                        if (!$validColumns) {
                            $errors++;
                            continue; // Skip rows with invalid column names
                        }
                        
                        $placeholders = array_fill(0, count($columns), '?');
                        $values = array_values($row);
                        
                        $connection = DB::connection();
                        if ($connection === 'sqlite') {
                            $sql = "INSERT OR REPLACE INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
                        } elseif ($connection === 'postgres') {
                            // PostgreSQL uses ON CONFLICT - check if table has 'id' column
                            if (in_array('id', $columns)) {
                                $sql = "INSERT INTO \"$table\" (\"" . implode('", "', $columns) . "\") VALUES (" . implode(', ', $placeholders) . ") ON CONFLICT (id) DO UPDATE SET " . 
                                       implode(', ', array_map(function($col) { return "\"$col\" = EXCLUDED.\"$col\""; }, $columns));
                            } else {
                                // No id column, just insert
                                $sql = "INSERT INTO \"$table\" (\"" . implode('", "', $columns) . "\") VALUES (" . implode(', ', $placeholders) . ")";
                            }
                        } else {
                            // MySQL
                            $updateParts = array_map(function($col) { return "`$col` = VALUES(`$col`)"; }, $columns);
                            $sql = "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ") ON DUPLICATE KEY UPDATE " . implode(', ', $updateParts);
                        }
                        
                        $db->query($sql, ...$values);
                        $count++;
                    } catch (Exception $e) {
                        // Log error without sensitive data
                        error_log("Import error for table $table: database operation failed");
                        $errors++;
                    }
                }
                $imported[$table] = $count;
            }
            
            return [
                'success' => true,
                'message' => 'Import completed',
                'imported' => $imported,
                'source_version' => $import['version'] ?? 'unknown',
                'source_date' => $import['exported_at'] ?? 'unknown'
            ];
        } catch (Exception $e) {
            throw new Exception('JSON import failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Import full backup from ZIP file
     * @param string $zipPath Path to the uploaded ZIP file
     * @return array Import result with status
     */
    public static function importFromZip($zipPath) {
        try {
            if (!file_exists($zipPath)) {
                throw new Exception('ZIP file not found');
            }
            
            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new Exception('Could not open ZIP file');
            }
            
            $result = [
                'success' => true,
                'db_imported' => false,
                'media_restored' => false,
                'details' => []
            ];
            
            // Check for metadata
            $metaContent = $zip->getFromName('_backup_meta.json');
            $meta = $metaContent ? json_decode($metaContent, true) : [];
            
            // Import database from JSON
            $jsonContent = $zip->getFromName('database/data.json');
            if ($jsonContent) {
                $importResult = self::importFromJson($jsonContent);
                $result['db_imported'] = true;
                $result['details']['database'] = $importResult;
            }
            
            // Restore media files
            $mediaRestored = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                
                // Only process media files
                if (strpos($filename, 'media/') !== 0) {
                    continue;
                }
                
                // Get relative path without 'media/' prefix
                $relativePath = substr($filename, 6); // Remove 'media/'
                
                if (empty($relativePath)) {
                    continue;
                }
                
                $targetPath = PROJECT_PATH . $relativePath;
                
                // Skip directories (they end with /)
                if (substr($filename, -1) === '/') {
                    if (!is_dir($targetPath)) {
                        mkdir($targetPath, 0755, true);
                    }
                    continue;
                }
                
                // Ensure parent directory exists
                $parentDir = dirname($targetPath);
                if (!is_dir($parentDir)) {
                    mkdir($parentDir, 0755, true);
                }
                
                // Extract file
                $content = $zip->getFromIndex($i);
                if ($content !== false) {
                    if (file_put_contents($targetPath, $content) !== false) {
                        $mediaRestored++;
                    }
                }
            }
            
            if ($mediaRestored > 0) {
                $result['media_restored'] = true;
                $result['details']['media_files'] = $mediaRestored;
            }
            
            $zip->close();
            
            $result['message'] = 'Full backup restored successfully';
            
            return $result;
        } catch (Exception $e) {
            throw new Exception('ZIP import failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a new backup
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
            throw new Exception('Backup fehlgeschlagen: ' . implode("\n", $output));
        }
        
        if (!file_exists($filepath) || filesize($filepath) === 0) {
            throw new Exception('Backup-Datei wurde nicht erstellt oder ist leer');
        }
        
        return [
            'filename' => $filename,
            'size' => filesize($filepath),
            'created' => time()
        ];
    }
    
    /**
     * Get list of all backups (SQL, JSON, and ZIP files)
     */
    public static function get_list() {
        $backup_dir = self::get_backup_dir();
        $backups = [];
        
        if (!is_dir($backup_dir)) {
            return $backups;
        }
        
        // Get all backup file types
        $patterns = ['*.sql', '*.json', '*.zip'];
        $files = [];
        foreach ($patterns as $pattern) {
            $files = array_merge($files, glob($backup_dir . $pattern));
        }
        
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'filepath' => $file,
                'size' => filesize($file),
                'created' => filemtime($file),
                'type' => pathinfo($file, PATHINFO_EXTENSION)
            ];
        }
        
        usort($backups, function($a, $b) {
            return $b['created'] - $a['created'];
        });
        
        return $backups;
    }
    
    /**
     * Restore a backup
     */
    public static function restore($filename) {
        $backup_dir = self::get_backup_dir();
        $filepath = $backup_dir . $filename;
        
        if (!file_exists($filepath) || strpos(realpath($filepath), realpath($backup_dir)) !== 0) {
            throw new Exception('Backup-Datei nicht gefunden');
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
            throw new Exception('Wiederherstellung fehlgeschlagen: ' . implode("\n", $output));
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
            throw new Exception('Backup-Datei nicht gefunden');
        }
        
        if (!unlink($filepath)) {
            throw new Exception('Backup konnte nicht gelöscht werden');
        }
        
        return true;
    }
}
