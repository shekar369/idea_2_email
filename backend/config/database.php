<?php

// Included by core.php or directly where DB connection is needed.
// Ensure core.php (which defines get_env) is loaded before this if used standalone.
if (!function_exists('get_env')) {
    require_once __DIR__ . '/core.php';
}

/**
 * Establishes a PDO database connection.
 *
 * @return PDO|null Returns a PDO connection object on success, or null on failure.
 */
function get_db_connection() {
    static $pdo = null; // Static variable to hold the connection

    if ($pdo === null) { // Connect only if not already connected
        $host = get_env('DB_HOST', 'localhost');
        $port = get_env('DB_PORT', '3306');
        $db_name = get_env('DB_NAME', 'email_assistant_db');
        $user = get_env('DB_USER', 'root');
        $pass = get_env('DB_PASS', '');
        $charset = 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port};dbname={$db_name};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays by default
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // In a real application, log this error instead of echoing
            error_log("Database Connection Error: " . $e->getMessage());
            // Optionally, you could throw the exception to be caught by a global error handler
            // throw new PDOException($e->getMessage(), (int)$e->getCode());

            // For API responses, it's better to not expose detailed error messages directly
            // This check is for the initial setup, a more robust error handling should be in place
            if (get_env('APP_ENV') === 'development') {
                 // This will be caught by index.php if it's a web request, or die if script execution
                if (php_sapi_name() !== 'cli') {
                    http_response_code(500);
                    echo json_encode([
                        'error' => 'Database connection failed.',
                        'details' => $e->getMessage() // Only in development
                    ]);
                    exit;
                } else {
                    die("Database connection failed: " . $e->getMessage() . "\n");
                }
            } else {
                if (php_sapi_name() !== 'cli') {
                    http_response_code(500);
                    echo json_encode(['error' => 'A server error occurred. Please try again later.']);
                    exit;
                } else {
                    die("Database connection failed. Check logs for details.\n");
                }
            }
            return null; // Indicate failure
        }
    }

    return $pdo;
}

// Example usage (for testing purposes, remove or comment out later):
/*
if (php_sapi_name() === 'cli') { // Check if running from CLI
    echo "Attempting to connect to database...\n";
    $conn = get_db_connection();
    if ($conn) {
        echo "Successfully connected to the database: " . get_env('DB_NAME') . "\n";
        try {
            $stmt = $conn->query("SELECT VERSION() as version");
            $result = $stmt->fetch();
            echo "Database version: " . $result['version'] . "\n";
        } catch (PDOException $e) {
            echo "Error querying version: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Failed to connect to the database.\n";
    }
}
*/
?>
