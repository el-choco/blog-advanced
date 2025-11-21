<?php
defined('PROJECT_PATH') OR exit('No direct script access allowed');

class Backup
{
    private static $backup_dir = null;
    
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
     * Get list of all backups
     */
    public static function get_list() {
        $backup_dir = self::get_backup_dir();
        $backups = [];
        
        if (!is_dir($backup_dir)) {
            return $backups;
        }
        
        $files = glob($backup_dir . '*.sql');
        
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'filepath' => $file,
                'size' => filesize($file),
                'created' => filemtime($file)
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
