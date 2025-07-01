<?php

// Define BASE_PATH
define('BASE_PATH', dirname(__DIR__));

// --- Environment Variable Loading (Conceptual without a library) ---
// In a real HostGator environment, you'd set these in cPanel or similar.
// For local dev, you might use a .env loader library or set them in your web server config.
// This is a simplified approach to demonstrate usage.

// Function to get environment variables
// Attempts to fetch from actual environment, then falls back to a simplified .env file parser.
// NOTE: The .env parsing is basic and not suitable for complex .env files or production.
// For production, environment variables should be set directly in the server environment (e.g., cPanel on HostGator).
// For robust local .env handling, consider a library like `vlucas/phpdotenv`.
function get_env($key, $default = null) {
    $value = getenv($key); // Check actual environment variables first
    if ($value === false) {
        // Fallback to simplified .env file parsing if actual env var not found
        $env_file_path = BASE_PATH . '/.env';
        if (file_exists($env_file_path)) {
            // Read .env file, skipping empty lines and comments
            $envLines = file($env_file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($envLines as $line) {
                if (strpos(trim($line), '#') === 0) continue; // Skip comments
                list($envKey, $envValue) = explode('=', $line, 2);
                $envKey = trim($envKey);
                $envValue = trim($envValue);
                if ($envKey === $key) {
                    // Remove surrounding quotes if any
                    if (substr($envValue, 0, 1) == '"' && substr($envValue, -1) == '"') {
                        $envValue = substr($envValue, 1, -1);
                    }
                    return $envValue;
                }
            }
        }
        return $default;
    }
    return $value;
}

// --- CORS Handling ---
// Define allowed origins from .env or default to wildcard (less secure, tighten in production)
$allowed_origins_str = get_env('ALLOWED_ORIGINS', '*');
$allowed_origins = array_map('trim', explode(',', $allowed_origins_str));

if (isset($_SERVER['HTTP_ORIGIN'])) {
    if ($allowed_origins[0] === '*' || in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400'); // cache for 1 day
    }
}

// Access-Control-Allow-Methods
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    exit(0);
}

// --- Basic Configuration ---
define('JWT_SECRET', get_env('JWT_SECRET', 'your_super_secret_jwt_key_here'));
define('ENCRYPTION_KEY', get_env('ENCRYPTION_KEY', 'your_strong_encryption_key_for_api_keys'));
define('API_BASE_URL', get_env('API_BASE_URL', '/api'));

// --- Error Reporting ---
if (get_env('APP_ENV', 'production') === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
    // Consider setting up a proper logging mechanism for production errors
}

// --- Global Headers ---
header('Content-Type: application/json'); // Default content type for API responses

?>
