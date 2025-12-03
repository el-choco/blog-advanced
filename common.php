<?php

// Define PROJECT PATH only if not already defined
if (!defined('PROJECT_PATH')) {
    define('PROJECT_PATH', dirname(__FILE__) . '/');
}
if (!defined('APP_PATH')) {
    define('APP_PATH', PROJECT_PATH . 'app/');
}

// Load Autoloader
require APP_PATH . "splclassloader.class.php";
$classLoader = new SplClassLoader(null, APP_PATH);
$classLoader->setFileExtension('.class.php');
$classLoader->register();

// In debug mode, display errors (but avoid breaking JSON responses)
if (Config::get_safe('debug', false)) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // Check extensions
    $required = ['curl', 'PDO', 'pdo_mysql', 'gd', 'exif'];
    $loaded = get_loaded_extensions();
    if ($missing = array_diff($required, $loaded)) {
        // Log instead of die(), to avoid output that breaks AJAX responses
        error_log("Missing extensions: " . implode(", ", $missing));
    }
} else {
    // In non-debug mode, keep output clean
    ini_set('display_errors', 0);
    error_reporting(0);
}

// Language
Lang::load(empty($_GET["hl"]) ? Config::get("lang") : $_GET["hl"]);

// Timezone
if (false !== ($TZ = Config::get_safe('timezone', getenv('TZ')))) {
    date_default_timezone_set($TZ);
    ini_set('date.timezone', $TZ);
}

// Start session before any output
ini_set('session.cookie_httponly', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('escape')) {
    function escape($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}