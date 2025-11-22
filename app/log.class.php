<?php
declare(strict_types=1);

defined('PROJECT_PATH') OR exit('No direct script access allowed');

class Log
{
    private static array $_files = [
        "ajax_access",
        "ajax_errors",
        "login_fails",
        "visitors"
    ];
    
    public static function put(string $_file, ?string $_text = null): void
    {
        if (!Config::get_safe("logs", false) || !in_array($_file, self::$_files, true)) {
            return;
        }
        
        $_logs_path = Config::get('logs_path');
        
        if (!is_dir($_logs_path) && !mkdir($_logs_path, 0755, true)) {
            die("Logs directory could not be created.");
        }
        
        $result = file_put_contents(
            $_logs_path . $_file . ".log",
            self::line($_text),
            FILE_APPEND
        );
        
        if ($result === false && Config::get_safe('debug', false)) {
            die(sprintf("Can't write to %s.log file.", $_file));
        }
    }
    
    private static function escape(?string $_text = null): string
    {
        return preg_replace("/[\n\r\t]/", "-", $_text ?? '') ?? '';
    }
    
    private static function line(?string $_text = null): string
    {
        return trim(
            date('Y-m-d H:i:s') . "\t" .
            self::escape($_SERVER["REMOTE_ADDR"] ?? '') . "\t" .
            self::escape($_SERVER["HTTP_USER_AGENT"] ?? '') . "\t" .
            self::escape($_text)
        ) . PHP_EOL;
    }
    
    // ✨ NEW: Convenience methods for better logging
    
    public static function info(string $message): void
    {
        self::put('ajax_access', "[INFO] {$message}");
    }
    
    public static function error(string $message): void
    {
        self::put('ajax_errors', "[ERROR] {$message}");
    }
    
    public static function login_fail(string $message): void
    {
        self::put('login_fails', $message);
    }
    
    public static function visitor(string $message): void
    {
        self::put('visitors', $message);
    }
}
