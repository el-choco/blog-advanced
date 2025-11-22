<?php
declare(strict_types=1);

defined('PROJECT_PATH') OR exit('No direct script access allowed');

class Lang
{
    private const PATH = 'lang/';
    
    private static ?array $_dictionary = null;
    
    public static function load(string $lang = 'en'): void
    {
        $lang_file = APP_PATH . self::PATH . $lang . '.ini';
        
        if (preg_match('/^[a-z]+$/', $lang) && is_readable($lang_file)) {
            $parsed = parse_ini_file($lang_file);
            
            if (is_array($parsed)) {
                self::$_dictionary = $parsed;
            }
        }
    }
    
    public static function get(string $key): string
    {
        if (self::$_dictionary === null || !array_key_exists($key, self::$_dictionary)) {
            return $key;
        }
        
        return (string)self::$_dictionary[$key];
    }
    
    public static function get_safe(string $key, string $default = ''): string
    {
        if (self::$_dictionary === null || !array_key_exists($key, self::$_dictionary)) {
            return $default;
        }
        
        return (string)self::$_dictionary[$key];
    }
    
    public static function get_all(): array
    {
        return self::$_dictionary ?? [];
    }
}

/**
 * Shorthand function for Lang::get()
 */
function __(string $key): string
{
    return Lang::get($key);
}
