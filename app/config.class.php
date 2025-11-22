<?php
declare(strict_types=1);

defined('PROJECT_PATH') OR exit('No direct script access allowed');

class Config
{
    private static ?array $config = null;
    private const ENV_PREFIX = 'BLOG_';
    
    private static function init(): void
    {
        if (self::$config !== null) {
            return;
        }
        
        $config_file = rtrim(PROJECT_PATH, '/') . '/config.ini';
        
        if (!file_exists($config_file)) {
            throw new Exception("Config file not found: {$config_file}");
        }
        
        // Parse INI file with sections
        $custom_settings = parse_ini_file($config_file, true);
        
        if ($custom_settings === false) {
            throw new Exception("Cannot parse config.ini file");
        }
        
        // Flatten sections into single array
        $flattened = [];
        foreach ($custom_settings as $section => $values) {
            if (is_array($values)) {
                foreach ($values as $key => $value) {
                    $flattened[$key] = $value;
                }
            }
        }
        
        $default_settings = [
            'title' => 'M1K1O Blog',
            'theme' => 'theme01',
            'lang' => 'en',
            'timezone' => 'UTC',
            'debug' => false
        ];
        
        self::$config = array_merge($default_settings, $flattened);
        
        // Environment variables override
        $envs = getenv();
        $env_prefix_len = strlen(self::ENV_PREFIX);
        
        foreach ($envs as $key => $value) {
            if (str_starts_with($key, self::ENV_PREFIX)) {
                $config_key = strtolower(substr($key, $env_prefix_len));
                self::$config[$config_key] = $value;
            }
        }
        
        // Backward compatibility for db_connection
        if (!array_key_exists('db_connection', self::$config) 
            && array_key_exists('mysql_user', self::$config)
            && (array_key_exists('mysql_socket', self::$config) 
                || array_key_exists('mysql_host', self::$config))) {
            self::$config['db_connection'] = 'mysql';
        }
    }
    
    public static function get(string $key)
    {
        self::init();
        
        if (!array_key_exists($key, self::$config)) {
            throw new Exception("Config key not found: {$key}");
        }
        
        return self::$config[$key];
    }
    
    public static function get_safe(string $key, $default)
    {
        self::init();
        return self::$config[$key] ?? $default;
    }
    
    public static function set(string $key, $value): void
    {
        self::init();
        self::$config[$key] = $value;
    }
    
    public static function save(array $values): void
    {
        self::init();
        
        // Update internal config
        foreach ($values as $key => $value) {
            self::$config[$key] = $value;
        }
        
        $config_file = rtrim(PROJECT_PATH, '/') . '/config.ini';
        
        // Read existing INI to preserve structure
        $existing = parse_ini_file($config_file, true);
        
        // Update values in existing structure
        foreach ($values as $key => $value) {
            $found = false;
            foreach ($existing as $section => &$section_values) {
                if (is_array($section_values) && array_key_exists($key, $section_values)) {
                    $section_values[$key] = $value;
                    $found = true;
                    break;
                }
            }
            
            // If not found, add to [system] section
            if (!$found) {
                if (!isset($existing['system'])) {
                    $existing['system'] = [];
                }
                $existing['system'][$key] = $value;
            }
        }
        
        // Write INI file
        $ini_content = '';
        foreach ($existing as $section => $section_values) {
            $ini_content .= "[$section]\n";
            if (is_array($section_values)) {
                foreach ($section_values as $k => $v) {
                    $ini_content .= "$k = " . self::formatIniValue($v) . "\n";
                }
            }
            $ini_content .= "\n";
        }
        
        $result = file_put_contents($config_file, $ini_content);
        
        if ($result === false) {
            throw new Exception("Cannot write config file");
        }
    }
    
    private static function formatIniValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if ($value === 'true' || $value === 'false') {
            return $value;
        }
        if (is_numeric($value)) {
            return (string)$value;
        }
        // Quote strings with special characters
        if (preg_match('/[;\s]/', (string)$value)) {
            return '"' . addslashes((string)$value) . '"';
        }
        return (string)$value;
    }
    
    public static function get_all(): array
    {
        self::init();
        return self::$config;
    }
}
