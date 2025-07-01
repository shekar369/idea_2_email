<?php

require_once __DIR__ . '/../config/core.php'; // Load core configurations, env variables, CORS

<?php
// public/index.php - Main Entry Point & Router for the Backend API

// Load core configurations, environment variables, CORS handling, and global headers
require_once __DIR__ . '/../config/core.php';

// --- Simple Request Router ---
$request_uri = $_SERVER['REQUEST_URI'];    // Full URI, e.g., /api/test?param=1
$request_method = $_SERVER['REQUEST_METHOD']; // E.g., GET, POST

// Remove query string from URI
$request_path = strtok($request_uri, '?');

// Remove base API path if it's part of the request path (e.g., /api)
$api_base_url_config = API_BASE_URL; // From core.php, e.g., "/api"
if (strpos($request_path, $api_base_url_config) === 0) {
    $effective_request_path = substr($request_path, strlen($api_base_url_config));
} else {
    $effective_request_path = $request_path; // Or handle as an error if base path is mandatory
}
// Ensure it starts with a slash and handle empty path for base API URL
$effective_request_path = '/' . ltrim($effective_request_path, '/');


// --- Route Definitions ---
// The $routes array maps HTTP methods and URL patterns to their handlers.
//
// Structure for each route:
//   'METHOD /path/pattern' => [
//       string $controller_file,   // Filename of the controller (e.g., 'AuthController.php')
//       string $function_name,     // Function name within the controller to call
//       bool $is_protected (opt)   // Optional. If true, AuthMiddleware::require_auth() is called. Defaults to false.
//   ]
// Or for closure-based handlers:
//   'METHOD /path/pattern' => [
//       Closure $handler_function, // An anonymous function to handle the request
//       bool $is_protected (opt)   // Optional. If true, AuthMiddleware::require_auth() is called. Defaults to false.
//   ]
//
// Path patterns can include placeholders like '/users/{id}', which will be converted to regex
// and the matched values passed as parameters to the handler function.

$routes = [
    // Test route to check if API is working
    'GET /test' => [function() {
        echo json_encode([
            'status' => 'success',
            'message' => 'API is working!',
            'timestamp' => date('c'),
            'base_url_config' => API_BASE_URL,
            'request_path' => $_SERVER['REQUEST_URI'],
            'effective_path' => $GLOBALS['effective_request_path_debug'] ?? 'N/A'
        ]);
    }, false],

    // --- Authentication Routes ---
    'POST /auth/register'   => ['AuthController.php', 'register', false],      // User registration
    'POST /auth/login'      => ['AuthController.php', 'login', false],         // User login
    'POST /auth/sso/google' => ['AuthController.php', 'sso_google', false],    // Placeholder for Google SSO
    'POST /auth/logout'     => ['AuthController.php', 'logout', true],         // User logout (protected)

    // --- User Routes ---
    'GET /user/me'          => ['UserController.php', 'getCurrentUser', true], // Get current user details (protected)

    // --- Settings Routes ---
    'GET /settings/llm'     => ['SettingsController.php', 'getLlmSettings', true],    // Get LLM settings (protected)
    'POST /settings/llm'    => ['SettingsController.php', 'updateLlmSettings', true], // Update LLM settings (protected)

    // --- Email Generation Routes ---
    'POST /email/generate'  => ['EmailController.php', 'generateEmail', true],  // Generate email (protected)
];

// Store effective_request_path for debugging the test route (used by GET /test)
$GLOBALS['effective_request_path_debug'] = $effective_request_path;


// --- Route Matching and Dispatching ---
$route_found = false;
foreach ($routes as $route_pattern => $handler_config) {
    // Extract method and path pattern (e.g., "GET /test")
    list($method, $path_pattern) = explode(' ', $route_pattern, 2);

    $handler_action = $handler_config[0];
    $controller_function = ''; // For string handlers
    if (is_string($handler_action)) { // e.g. 'ControllerFile.php'
         $controller_function = $handler_config[1];
    }
    $is_protected = $handler_config[is_string($handler_action) ? 2 : 1] ?? false;


    // Convert path pattern to regex (e.g., /users/{id} -> /users/([^/]+))
    // Ensure path_pattern starts with a slash if not empty
    if ($path_pattern !== '' && $path_pattern[0] !== '/') {
        $path_pattern = '/' . $path_pattern;
    }

    $regex_pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $path_pattern);
    $regex_pattern = '#^' . $regex_pattern . '$#';


    if ($request_method === $method && preg_match($regex_pattern, $effective_request_path, $matches)) {
        array_shift($matches); // Remove the full match
        $params = $matches;
        $route_found = true;

        if ($is_protected) {
            require_once BASE_PATH . '/middleware/AuthMiddleware.php';
            require_auth(); // This will exit if not authenticated
        }

        if (is_callable($handler_action)) { // Closure
            call_user_func_array($handler_action, $params);
        } elseif (is_string($handler_action)) { // 'ControllerFile.php'
            $controller_file = $handler_action;
            // $function_name is $controller_function defined above
            $controller_path = BASE_PATH . '/controllers/' . $controller_file;

            if (file_exists($controller_path)) {
                require_once $controller_path;
                if (function_exists($controller_function)) {
                    call_user_func_array($controller_function, $params);
                } else {
                    http_response_code(500);
                    error_log("Function {$controller_function} not found in {$controller_file} for route {$method} {$path_pattern}");
                    echo json_encode(['error' => "Server error: Controller function not found."]);
                }
            } else {
                http_response_code(500);
                error_log("Controller file {$controller_file} not found for route {$method} {$path_pattern}");
                echo json_encode(['error' => "Server error: Controller file not found."]);
            }
        }
        break;
    }
}

if (!$route_found) {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found', 'requested_path' => $effective_request_path]);
}

?>
