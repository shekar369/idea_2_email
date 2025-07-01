<?php

require_once __DIR__ . '/../middleware/AuthMiddleware.php'; // For require_auth and get_current_user

/**
 * Gets the details of the currently authenticated user.
 * This endpoint is protected by AuthMiddleware, which is called by the router.
 * The middleware makes user data available via `get_current_user()`.
 *
 * Method: GET
 * Path: /api/user/me
 * Protected: Yes
 * Responds with current user's details (excluding sensitive information like password hash).
 */
function getCurrentUser() {
    // AuthMiddleware's require_auth() is expected to have been called by the router
    // before this function is invoked for protected routes.
    // get_current_user() retrieves the user data populated by the middleware.
    $user = get_current_user();

    if (!$user) {
        // This state should ideally not be reached if AuthMiddleware is working correctly,
        // as it would exit with a 401 response if authentication fails.
        // This check acts as a final safeguard.
        http_response_code(401);
        echo json_encode(['error' => 'Authentication failed or user data is unavailable.']);
        return;
    }

    // User data from get_current_user() (sourced from User::findById)
    // is designed to exclude sensitive information like the password_hash.
    // Double-check User::findById if this behavior needs to be confirmed.
    // Example: unset($user['password_hash']); // Defensive coding, if necessary.

    http_response_code(200); // OK
    echo json_encode([
        'message' => 'User details retrieved successfully.',
        'user' => $user // Contains id, email, sso_provider, sso_id, created_at, updated_at
    ]);
}

?>
