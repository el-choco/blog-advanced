<?php
// Simple Backup Script
require_once 'common.php';

if (!User::is_logged_in()) {
    die('Unauthorized');
}

$backup_dir = PROJECT_PATH . 'backups/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

$timestamp = date('Y-m-d_H-i-s');
$backup_file = $backup_dir . 'backup_' . $timestamp . '.sql';

// MySQL dump
$db_config = Config::get('db');
$cmd = sprintf(
    'mysqldump -h %s -u %s -p%s %s > %s',
    escapeshellarg($db_config['host']),
    escapeshellarg($db_config['user']),
    escapeshellarg($db_config['password']),
    escapeshellarg($db_config['database']),
    escapeshellarg($backup_file)
);

exec($cmd, $output, $return);

if ($return === 0) {
    // Compress
    exec('gzip ' . escapeshellarg($backup_file));
    echo json_encode(['success' => true, 'file' => basename($backup_file . '.gz')]);
} else {
    echo json_encode(['success' => false, 'error' => 'Backup failed']);
}
