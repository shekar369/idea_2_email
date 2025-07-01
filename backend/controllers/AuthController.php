<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/UserSettings.php';
require_once __DIR__ . '/../utils/jwt_handler.php';
require_once __DIR__ . '/../config/core.php'; // For basic config if any, though not strictly needed here

/**
 * Handles user registration.
 *
 * Method: POST
 * Path: /api/auth/register
 * Expects JSON input: {"email": "user@example.com", "password": "securepassword123"}
 * Responds with JWT and user data on success, or error message on failure.
 */
function register() {
    // Get raw POST data
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['email']) || !isset($input['password'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Email and password are required.']);
        return;
    }

    $email = filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL);
    $password = trim($input['password']);

    if (!$email) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format.']);
        return;
    }

    if (strlen($password) < 8) { // Basic password length validation
        http_response_code(400);
        echo json_encode(['error' => 'Password must be at least 8 characters long.']);
        return;
    }

    // Check if user already exists
    if (User::findByEmail($email)) {
        http_response_code(409); // Conflict
        echo json_encode(['error' => 'User with this email already exists.']);
        return;
    }

    $user_id = User::create($email, $password);

    if ($user_id) {
        // Create default settings for the new user
        if (!UserSettings::createDefaults($user_id)) {
            // Log this issue, but don't fail registration if user creation was successful
            error_log("AuthController::register - Failed to create default settings for user ID: " . $user_id);
        }

        // Generate JWT token
        $token = generate_jwt(['user_id' => $user_id]);

        // Get user details to return (excluding password hash)
        $user_data = User::findById($user_id);
        unset($user_data['password_hash']); // Ensure hash is not returned

        http_response_code(201); // Created
        echo json_encode([
            'message' => 'User registered successfully.',
            'token' => $token,
            'user' => $user_data
        ]);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Failed to register user. Please try again.']);
    }
}

/**
 * Handles user login.
 *
 * Method: POST
 * Path: /api/auth/login
 * Expects JSON input: {"email": "user@example.com", "password": "securepassword123"}
 * Responds with JWT and user data on success, or error message on failure.
 */
function login() {
    // Get raw POST data
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['email']) || !isset($input['password'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Email and password are required.']);
        return;
    }

    $email = filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL);
    $password = trim($input['password']);

    if (!$email) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format.']);
        return;
    }

    $user = User::verifyPassword($email, $password);

    if ($user) {
        // User verified, generate JWT
        $token = generate_jwt(['user_id' => $user['id']]);

        // User data already has password_hash removed by verifyPassword/findById logic
        http_response_code(200); // OK
        echo json_encode([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $user
        ]);
    } else {
        http_response_code(401); // Unauthorized
        echo json_encode(['error' => 'Invalid email or password.']);
    }
}

/**
 * Handles SSO login/registration (Conceptual).
 * This is a placeholder and would require significant implementation specific to the SSO provider.
 * For example, with Google, you'd typically:
 * 1. Frontend: User clicks "Login with Google", gets redirected to Google, authorizes.
 * 2. Frontend: Receives an authorization code or ID token from Google.
 * 3. Frontend: Sends this code/token to this backend endpoint.
 * 4. Backend: Verifies the code/token with Google.
 * 5. Backend: Gets user info (email, name, Google ID) from Google.
 * 6. Backend: Checks if user with this Google ID or email exists.
 *    - If exists: Log them in (generate JWT).
 *    - If not exists: Create a new user (potentially with a placeholder or no password), then log them in.
 * 7. Backend: Returns JWT to frontend.
 *
 * Method: POST
 * Path: /api/auth/sso/google
 * (Conceptual - Not fully implemented)
 */
function sso_google() {
    // $input = json_decode(file_get_contents('php://input'), true);
    // $id_token = $input['id_token'] ?? null; // Or authorization code

    // if (!$id_token) {
    //     http_response_code(400);
    //     echo json_encode(['error' => 'Google ID token not provided.']);
    //     return;
    // }

    // // --- Placeholder for Google Token Verification & User Handling ---
    // // 1. Verify $id_token with Google API Client Library or manually via HTTP request
    // //    (e.g., https://oauth2.googleapis.com/tokeninfo?id_token=YOUR_TOKEN)
    // // 2. Extract user's Google ID, email, name, etc.
    // // 3. $google_user_id = ...; $email = ...;
    // // 4. $user = User::findBySsoId('google', $google_user_id);
    // // 5. if (!$user) $user = User::findByEmail($email); // Link by email if SSO ID not found
    // // 6. if ($user) { /* Update SSO ID if necessary, login */ }
    // //    else { /* Create new user: User::create($email, uniqid(), 'google', $google_user_id); */ }
    // // 7. Generate JWT for $user

    http_response_code(501); // Not Implemented
    echo json_encode(['message' => 'SSO with Google is not yet implemented.']);
}


/**
 * Handles user logout (Conceptual for JWT).
 * Since JWTs are stateless, true server-side logout involves token blocklisting,
 * which adds complexity. For simpler apps, client-side token deletion is often sufficient.
 * This endpoint can be a no-op or implement a blocklist if required.
 * It is marked as 'protected' in the router, meaning a valid JWT is expected,
 * which might be used if implementing a server-side blocklist (to identify the token to block).
 *
 * Method: POST
 * Path: /api/auth/logout
 * Protected: Yes
 */
function logout() {
    // Option 1: No-op (client discards token)
    // http_response_code(200);
    // echo json_encode(['message' => 'Logged out successfully. Please discard your token.']);

    // Option 2: Implement token blocklisting (more complex)
    // - Get token from Authorization header.
    // - Add token's JTI (JWT ID) or signature to a blocklist (e.g., in Redis, DB) with its expiry time.
    // - `validate_jwt` would then need to check this blocklist.

    http_response_code(200); // OK
    echo json_encode(['message' => 'Logout processed. Client should clear token.']);
}

?>
