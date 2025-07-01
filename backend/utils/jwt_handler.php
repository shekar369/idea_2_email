<?php

require_once __DIR__ . '/../config/core.php'; // For JWT_SECRET and JWT_EXPIRATION_TIME_SECONDS

/**
 * Generates a JWT token.
 *
 * @param array $payload The payload to include in the token (e.g., user ID).
 * @return string The generated JWT.
 */
function generate_jwt(array $payload): string {
    $secret_key = JWT_SECRET;
    $expiration_time = time() + (int)get_env('JWT_EXPIRATION_TIME_SECONDS', 3600);

    // Standard JWT header
    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];
    $header_encoded = base64url_encode(json_encode($header));

    // Add 'exp' (expiration time) and 'iat' (issued at) to payload
    $payload['exp'] = $expiration_time;
    $payload['iat'] = time();
    $payload_encoded = base64url_encode(json_encode($payload));

    // Create signature
    $signature_input = $header_encoded . '.' . $payload_encoded;
    $signature = hash_hmac('sha256', $signature_input, $secret_key, true);
    $signature_encoded = base64url_encode($signature);

    return $header_encoded . '.' . $payload_encoded . '.' . $signature_encoded;
}

/**
 * Validates a JWT token and returns its payload.
 *
 * @param string $jwt The JWT to validate.
 * @return array|null The decoded payload if the token is valid and not expired, otherwise null.
 */
function validate_jwt(string $jwt): ?array {
    $secret_key = JWT_SECRET;
    $parts = explode('.', $jwt);

    if (count($parts) !== 3) {
        return null; // Invalid token structure
    }

    list($header_encoded, $payload_encoded, $signature_encoded) = $parts;

    // Verify signature
    $signature_input = $header_encoded . '.' . $payload_encoded;
    $expected_signature = hash_hmac('sha256', $signature_input, $secret_key, true);
    $expected_signature_encoded = base64url_encode($expected_signature);

    if (!hash_equals($expected_signature_encoded, $signature_encoded)) {
        error_log("JWT Validation Failed: Signature mismatch.");
        return null; // Signature verification failed
    }

    $payload = json_decode(base64url_decode($payload_encoded), true);

    if ($payload === null) {
        error_log("JWT Validation Failed: Payload is not valid JSON.");
        return null; // Payload is not valid JSON
    }

    // Check for expiration
    if (isset($payload['exp']) && time() > $payload['exp']) {
        error_log("JWT Validation Failed: Token expired at " . date('Y-m-d H:i:s', $payload['exp']));
        return null; // Token expired
    }

    // Check 'iat' if it exists and is in the future (optional, but good practice)
    if (isset($payload['iat']) && $payload['iat'] > time()) {
        error_log("JWT Validation Failed: Issued at time is in the future.");
        return null; // Issued at time is in the future
    }

    return $payload;
}

/**
 * Base64 URL encodes a string.
 *
 * @param string $data The string to encode.
 * @return string The Base64 URL encoded string.
 */
function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Base64 URL decodes a string.
 *
 * @param string $data The string to decode.
 * @return string|false The decoded string, or false on failure.
 */
function base64url_decode(string $data) {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
}

/**
 * Extracts the JWT token from the Authorization header.
 *
 * @return string|null The token if found, otherwise null.
 */
function get_bearer_token(): ?string {
    $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    if ($auth_header) {
        if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

// --- Example Usage (for testing) ---
/*
if (php_sapi_name() === 'cli') {
    echo "JWT Handler Test\n";
    $user_payload = ['user_id' => 123, 'username' => 'testuser'];

    // Generate token
    $token = generate_jwt($user_payload);
    echo "Generated Token: " . $token . "\n\n";

    // Validate token (immediately)
    echo "Validating token...\n";
    $decoded_payload = validate_jwt($token);
    if ($decoded_payload) {
        echo "Token is valid. Payload:\n";
        print_r($decoded_payload);
    } else {
        echo "Token is invalid.\n";
    }

    // Simulate an expired token
    echo "\nSimulating expired token...\n";
    // Manually create a token with past expiration
    $header_expired = ['alg' => 'HS256', 'typ' => 'JWT'];
    $payload_expired = ['user_id' => 456, 'exp' => time() - 3600, 'iat' => time() - 3700]; // Expired 1 hour ago
    $header_encoded_expired = base64url_encode(json_encode($header_expired));
    $payload_encoded_expired = base64url_encode(json_encode($payload_expired));
    $signature_input_expired = $header_encoded_expired . '.' . $payload_encoded_expired;
    $signature_expired = hash_hmac('sha256', $signature_input_expired, JWT_SECRET, true);
    $signature_encoded_expired = base64url_encode($signature_expired);
    $expired_token = $header_encoded_expired . '.' . $payload_encoded_expired . '.' . $signature_encoded_expired;

    echo "Expired Token: " . $expired_token . "\n";
    $decoded_expired = validate_jwt($expired_token);
    if ($decoded_expired) {
        echo "Expired token validated (SHOULD NOT HAPPEN). Payload:\n";
        print_r($decoded_expired);
    } else {
        echo "Expired token validation failed (Correct).\n";
    }

    // Simulate a tampered token
    echo "\nSimulating tampered token...\n";
    $parts = explode('.', $token);
    $tampered_payload = base64url_encode(json_encode(['user_id' => 789, 'exp' => time() + 3600, 'iat' => time()]));
    $tampered_token = $parts[0] . '.' . $tampered_payload . '.' . $parts[2];
    echo "Tampered Token: " . $tampered_token . "\n";
    $decoded_tampered = validate_jwt($tampered_token);
    if ($decoded_tampered) {
        echo "Tampered token validated (SHOULD NOT HAPPEN). Payload:\n";
        print_r($decoded_tampered);
    } else {
        echo "Tampered token validation failed (Correct).\n";
    }
}
*/
?>
