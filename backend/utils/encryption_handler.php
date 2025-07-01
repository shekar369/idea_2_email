<?php

require_once __DIR__ . '/../config/core.php'; // For ENCRYPTION_KEY

define('ENCRYPTION_METHOD', 'aes-256-cbc'); // AES 256-bit encryption in CBC mode

/**
 * Encrypts a string.
 *
 * @param string $string The string to encrypt.
 * @return string|false The encrypted string (hex encoded) or false on failure.
 */
function encrypt_data(string $string) {
    $key = ENCRYPTION_KEY;
    if (empty($key)) {
        error_log("Encryption failed: ENCRYPTION_KEY is not set.");
        return false;
    }
    // Key must be exactly 32 bytes for AES-256
    $secret_key = hash('sha256', $key, true);

    // Generate an initialization vector (IV)
    $iv_length = openssl_cipher_iv_length(ENCRYPTION_METHOD);
    if ($iv_length === false) {
        error_log("Encryption failed: Could not get IV length for " . ENCRYPTION_METHOD);
        return false;
    }
    $iv = openssl_random_pseudo_bytes($iv_length);

    $encrypted = openssl_encrypt($string, ENCRYPTION_METHOD, $secret_key, OPENSSL_RAW_DATA, $iv);

    if ($encrypted === false) {
        error_log("Encryption failed: openssl_encrypt returned false. " . openssl_error_string());
        return false;
    }

    // Prepend the IV to the encrypted string (hex encoded for safe storage)
    // IV is not secret and is required for decryption
    return bin2hex($iv . $encrypted);
}

/**
 * Decrypts a string.
 *
 * @param string $encrypted_string_hex The hex encoded encrypted string (IV prepended).
 * @return string|false The decrypted string or false on failure.
 */
function decrypt_data(string $encrypted_string_hex) {
    $key = ENCRYPTION_KEY;
    if (empty($key)) {
        error_log("Decryption failed: ENCRYPTION_KEY is not set.");
        return false;
    }
    // Key must be exactly 32 bytes for AES-256
    $secret_key = hash('sha256', $key, true);

    $combined_data = hex2bin($encrypted_string_hex);
    if ($combined_data === false) {
        error_log("Decryption failed: Input data is not valid hex.");
        return false;
    }

    $iv_length = openssl_cipher_iv_length(ENCRYPTION_METHOD);
    if ($iv_length === false) {
        error_log("Decryption failed: Could not get IV length for " . ENCRYPTION_METHOD);
        return false;
    }

    if (strlen($combined_data) < $iv_length) {
        error_log("Decryption failed: Encrypted data is too short to contain IV.");
        return false;
    }

    // Extract the IV from the beginning of the combined data
    $iv = substr($combined_data, 0, $iv_length);
    $encrypted_string = substr($combined_data, $iv_length);

    if (empty($encrypted_string)) {
        error_log("Decryption failed: Encrypted part is empty after extracting IV.");
        return false;
    }

    $decrypted = openssl_decrypt($encrypted_string, ENCRYPTION_METHOD, $secret_key, OPENSSL_RAW_DATA, $iv);

    if ($decrypted === false) {
        // Log OpenSSL errors for debugging
        $errors = [];
        while ($msg = openssl_error_string()) {
            $errors[] = $msg;
        }
        error_log("Decryption failed: openssl_decrypt returned false. Errors: " . implode(", ", $errors));
        return false;
    }

    return $decrypted;
}


// --- Example Usage (for testing) ---
/*
if (php_sapi_name() === 'cli') {
    echo "Encryption Handler Test\n";

    if (empty(ENCRYPTION_KEY) || ENCRYPTION_KEY === 'your_strong_encryption_key_for_api_keys') {
        echo "WARNING: ENCRYPTION_KEY is not set or is using the default placeholder. Please set a strong key in your .env file for proper testing.\n";
    }

    $original_string = "This is a super secret API key: sk-1234567890abcdefghijklmnopqrstuvwxyz";
    echo "Original String: " . $original_string . "\n";

    $encrypted = encrypt_data($original_string);
    if ($encrypted) {
        echo "Encrypted (hex): " . $encrypted . "\n";

        $decrypted = decrypt_data($encrypted);
        if ($decrypted !== false) {
            echo "Decrypted String: " . $decrypted . "\n";
            if ($decrypted === $original_string) {
                echo "SUCCESS: Decrypted string matches original.\n";
            } else {
                echo "ERROR: Decrypted string does NOT match original.\n";
            }
        } else {
            echo "ERROR: Failed to decrypt the string.\n";
        }
    } else {
        echo "ERROR: Failed to encrypt the string.\n";
    }

    // Test with empty string
    echo "\nTesting with empty string:\n";
    $empty_string = "";
    $encrypted_empty = encrypt_data($empty_string);
    if ($encrypted_empty) {
        echo "Encrypted empty (hex): " . $encrypted_empty . "\n";
        $decrypted_empty = decrypt_data($encrypted_empty);
        if ($decrypted_empty !== false && $decrypted_empty === $empty_string) {
            echo "SUCCESS: Decrypted empty string matches original.\n";
        } else {
            echo "ERROR: Failed to decrypt empty string or mismatch.\n";
        }
    } else {
        echo "ERROR: Failed to encrypt empty string.\n";
    }

    // Test decryption failure with bad data
    echo "\nTesting decryption failure with bad data:\n";
    $bad_data = "notarealhexstring";
    $decrypted_bad = decrypt_data($bad_data);
    if ($decrypted_bad === false) {
        echo "SUCCESS: Decryption failed for bad data as expected.\n";
    } else {
        echo "ERROR: Decryption succeeded for bad data (should not happen).\n";
    }

    $short_hex_data = "aabbcc"; // Too short to be valid
    $decrypted_short = decrypt_data($short_hex_data);
    if ($decrypted_short === false) {
        echo "SUCCESS: Decryption failed for short hex data as expected.\n";
    } else {
        echo "ERROR: Decryption succeeded for short hex data (should not happen).\n";
    }
}
*/
?>
