<?php
declare(strict_types=1);

defined('PROJECT_PATH') OR exit('No direct script access allowed');

class Ajax
{
    private ?array $_response = null;
    
    public function __construct()
    {
        ob_start();
    }
    
    public function set_error(?string $msg = null): void
    {
        $this->_response = [
            "error" => true,
            "msg" => $msg
        ];
        
        // Include debug info
        if (ob_get_length() > 0 && Config::get_safe('debug', false)) {
            $this->_response["debug"] = ob_get_clean();
        }
        
        // Log
        Log::put("ajax_errors", $msg ?? 'Unknown error');
    }
    
    public function token(): void
    {
        if (empty($_SESSION['token'])) {
            throw new Exception("Direct access violation.");
        }
        
        if ($_SESSION['token'] !== ($_POST['token'] ?? '')) {
            throw new Exception("Wrong security token.");
        }
    }
    
    public function csrf(): void
    {
        // Get all headers (case-insensitive)
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        
        if (!isset($headers['csrf-token'])) {
            throw new Exception("CSRF token missing.");
        }
        
        if ($headers['csrf-token'] !== $_SESSION['token']) {
            throw new Exception("Wrong CSRF token.");
        }
    }
    
    public function set_response(?array $response = null): void
    {
        $this->_response = $response;
    }
    
    public function json_response(): void
    {
        if (ob_get_length() > 0) {
            ob_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        // IMPORTANT: JSON_UNESCAPED_UNICODE ensures emojis are transmitted correctly
        echo json_encode($this->_response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
