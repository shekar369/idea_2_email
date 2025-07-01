<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/encryption_handler.php'; // For encrypt_data and decrypt_data

class UserSettings {

    /**
     * Retrieves settings for a given user.
     * API keys are decrypted before being returned.
     *
     * @param int $user_id The ID of the user.
     * @return array|null User settings as an associative array, or null if not found/error.
     */
    public static function getByUserId(int $user_id): ?array {
        $db = get_db_connection();
        if (!$db) {
            error_log("UserSettings::getByUserId - Database connection failed for user ID: " . $user_id);
            return null;
        }

        $sql = "SELECT * FROM user_settings WHERE user_id = :user_id LIMIT 1";
        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($settings) {
                // Decrypt API keys
                $api_key_fields = ['openai_api_key', 'claude_api_key', 'gemini_api_key', 'groq_api_key', 'cohere_api_key'];
                foreach ($api_key_fields as $field) {
                    if (!empty($settings[$field])) {
                        $decrypted_key = decrypt_data($settings[$field]);
                        if ($decrypted_key === false) {
                            // Log error, decide if you want to return partial data or null
                            error_log("UserSettings::getByUserId - Failed to decrypt {$field} for user_id: {$user_id}. Key may be corrupted or ENCRYPTION_KEY changed.");
                            // For security, might be better to nullify the key or the entire settings
                            $settings[$field] = null; // Or handle error more strictly
                        } else {
                            $settings[$field] = $decrypted_key;
                        }
                    }
                }
                return $settings;
            }
            return null; // No settings found for this user
        } catch (PDOException $e) {
            error_log("UserSettings::getByUserId - PDOException for user ID " . $user_id . ": " . $e->getMessage());
            return null;
        }
    }

    /**
     * Creates or updates user settings.
     * If settings for the user_id already exist, they are updated. Otherwise, new settings are created.
     * API keys in the $data array are encrypted before saving.
     *
     * @param int $user_id The user's ID.
     * @param array $data Associative array of settings data. Expected keys:
     *                    'preferred_llm' (string, required),
     *                    'ollama_endpoint' (string|null),
     *                    'openai_api_key' (string|null),
     *                    'claude_api_key' (string|null),
     *                    'gemini_api_key' (string|null),
     *                    'groq_api_key' (string|null),
     *                    'cohere_api_key' (string|null)
     * @return bool True on success, false on failure.
     */
    public static function save(int $user_id, array $data): bool {
        $db = get_db_connection();
        if (!$db) {
            error_log("UserSettings::save - Database connection failed for user ID: " . $user_id);
            return false;
        }

        // Fields that can be saved
        $allowed_fields = [
            'preferred_llm', 'ollama_endpoint',
            'openai_api_key', 'claude_api_key', 'gemini_api_key', 'groq_api_key', 'cohere_api_key'
        ];
        // API key fields that need encryption
        $api_key_fields = ['openai_api_key', 'claude_api_key', 'gemini_api_key', 'groq_api_key', 'cohere_api_key'];

        $params = [':user_id' => $user_id];
        $set_clauses = [];

        // Ensure preferred_llm is present
        if (!isset($data['preferred_llm']) || empty(trim($data['preferred_llm']))) {
            error_log("UserSettings::save - 'preferred_llm' is required but not provided for user ID: " . $user_id);
            return false;
        }

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $value = $data[$field];
                if (in_array($field, $api_key_fields) && !empty($value)) {
                    $encrypted_value = encrypt_data($value);
                    if ($encrypted_value === false) {
                        error_log("UserSettings::save - Failed to encrypt {$field} for user_id: {$user_id}.");
                        // Potentially skip this field or return false depending on desired strictness
                        continue; // Skip this field if encryption fails
                    }
                    $params[":{$field}"] = $encrypted_value;
                } else {
                    // For non-api-key fields or empty api_key fields (to allow clearing them)
                    $params[":{$field}"] = (empty($value) && in_array($field, $api_key_fields)) ? null : $value;
                }
                $set_clauses[] = "{$field} = :{$field}";
            }
        }

        if (empty($set_clauses)) {
            error_log("UserSettings::save - No valid data provided to save for user ID: " . $user_id);
            return false; // Or true if no change is not an error
        }

        // Use INSERT ... ON DUPLICATE KEY UPDATE to handle both create and update
        $sql = "INSERT INTO user_settings (user_id, " . implode(', ', array_map(function($key) { return trim($key, ':'); }, array_keys($params))) . ", updated_at)
                VALUES (:user_id, " . implode(', ', array_filter(array_keys($params), function($k){ return $k !== ':user_id';})) . ", NOW())
                ON DUPLICATE KEY UPDATE " . implode(', ', $set_clauses) . ", updated_at = NOW()";

        // Adjust SQL for ON DUPLICATE KEY UPDATE, parameters need to be available for both parts.
        // This simplified version assumes all provided fields are part of the SET clause for UPDATE.
        $update_clauses_str = implode(', ', $set_clauses);
        $fields_for_insert_names = [];
        $fields_for_insert_values = [];

        // Prepare parameters for INSERT and UPDATE parts
        $insert_params = [':user_id_insert' => $user_id]; // Use different placeholder for user_id in insert part to avoid conflict
        $update_params = [':user_id_update' => $user_id]; // And for update part

        // Populate fields and params for INSERT part
        $fields_for_insert_names[] = 'user_id';
        $fields_for_insert_values[] = ':user_id_insert';

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $value_to_insert = $data[$field];
                if (in_array($field, $api_key_fields) && !empty($value_to_insert)) {
                    $encrypted_value = encrypt_data($value_to_insert);
                    if ($encrypted_value === false) {
                        error_log("UserSettings::save - Failed to encrypt {$field} for user_id: {$user_id} during INSERT prep.");
                        continue;
                    }
                    $insert_params[":{$field}_insert"] = $encrypted_value;
                } else {
                    $insert_params[":{$field}_insert"] = (empty($value_to_insert) && in_array($field, $api_key_fields)) ? null : $value_to_insert;
                }
                $fields_for_insert_names[] = $field;
                $fields_for_insert_values[] = ":{$field}_insert";
            } elseif (array_key_exists($field, $data) && $data[$field] === null) { // Handle explicit nulls to clear fields
                 $insert_params[":{$field}_insert"] = null;
                 $fields_for_insert_names[] = $field;
                 $fields_for_insert_values[] = ":{$field}_insert";
            }
        }

        // Populate params for UPDATE part (already partially done by $set_clauses logic)
        $final_set_clauses_update = [];
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $value_to_update = $data[$field];
                if (in_array($field, $api_key_fields) && !empty($value_to_update)) {
                    $encrypted_value = encrypt_data($value_to_update);
                    if ($encrypted_value === false) {
                        error_log("UserSettings::save - Failed to encrypt {$field} for user_id: {$user_id} during UPDATE prep.");
                        continue;
                    }
                    $update_params[":{$field}_update"] = $encrypted_value;
                } else {
                     $update_params[":{$field}_update"] = (empty($value_to_update) && in_array($field, $api_key_fields)) ? null : $value_to_update;
                }
                $final_set_clauses_update[] = "{$field} = :{$field}_update";
            } elseif (array_key_exists($field, $data) && $data[$field] === null) { // Handle explicit nulls to clear fields
                 $update_params[":{$field}_update"] = null;
                 $final_set_clauses_update[] = "{$field} = :{$field}_update";
            }
        }

        if (empty($final_set_clauses_update) && !in_array('preferred_llm', $fields_for_insert_names)) {
             error_log("UserSettings::save - No valid data provided for user ID: " . $user_id);
             return false;
        }

        $fields_for_insert_names_str = implode(', ', $fields_for_insert_names);
        $fields_for_insert_values_str = implode(', ', $fields_for_insert_values);
        $update_clauses_str_final = implode(', ', $final_set_clauses_update);


        $sql = "INSERT INTO user_settings ({$fields_for_insert_names_str}, updated_at)
                VALUES ({$fields_for_insert_values_str}, NOW())
                ON DUPLICATE KEY UPDATE {$update_clauses_str_final}, updated_at = NOW()";

        $final_params = array_merge($insert_params, $update_params);
        // Remove the user_id_update if it's not needed (user_id is part of unique key, not set)
        unset($final_params[':user_id_update']);


        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($final_params);
            return $stmt->rowCount() >= 0; // rowCount can be 0 if no change, 1 for insert, 2 for update (in some MySQL versions for ON DUPLICATE KEY UPDATE)
        } catch (PDOException $e) {
            error_log("UserSettings::save - PDOException for user ID " . $user_id . ": " . $e->getMessage() . " SQL: " . $sql . " Params: " . json_encode($final_params));
            return false;
        }
    }

    /**
     * Creates default settings for a new user.
     * Typically called after user registration.
     *
     * @param int $user_id The ID of the new user.
     * @return bool True on success, false on failure.
     */
    public static function createDefaults(int $user_id): bool {
        $default_settings = [
            'preferred_llm' => get_env('DEFAULT_LLM_PROVIDER', 'ollama'), // e.g., 'ollama'
            'ollama_endpoint' => get_env('OLLAMA_DEFAULT_ENDPOINT', 'http://localhost:11434'),
            // Other API keys are null by default
            'openai_api_key' => null,
            'claude_api_key' => null,
            'gemini_api_key' => null,
            'groq_api_key' => null,
            'cohere_api_key' => null,
        ];
        return self::save($user_id, $default_settings);
    }
}

// --- Example Usage (CLI) ---
/*
if (php_sapi_name() === 'cli') {
    echo "UserSettings Model Test\n\n";
    require_once __DIR__ . '/User.php'; // For creating a test user

    // Ensure ENCRYPTION_KEY is set in .env
    if (empty(ENCRYPTION_KEY) || ENCRYPTION_KEY === 'your_strong_encryption_key_for_api_keys') {
        echo "WARNING: ENCRYPTION_KEY is not set or is using the default placeholder. Please set a strong key in your .env file for proper testing.\n";
        // exit(1); // Optional: exit if key is not set
    }

    // 1. Create a test user
    $test_email = "settingsuser_" . uniqid() . "@example.com";
    $test_user_id = User::create($test_email, "securepass123");

    if (!$test_user_id) {
        echo "Failed to create test user. Aborting UserSettings test.\n";
        exit(1);
    }
    echo "Test user created with ID: " . $test_user_id . "\n";

    // 2. Create Default Settings for the new user
    echo "\nAttempting to create default settings for user ID: " . $test_user_id . "\n";
    if (UserSettings::createDefaults($test_user_id)) {
        echo "Default settings created successfully.\n";
        $settings = UserSettings::getByUserId($test_user_id);
        echo "Retrieved default settings:\n";
        print_r($settings);
    } else {
        echo "ERROR: Failed to create default settings.\n";
    }

    // 3. Save (Update) some settings
    echo "\nAttempting to update settings for user ID: " . $test_user_id . "\n";
    $new_settings_data = [
        'preferred_llm' => 'openai',
        'ollama_endpoint' => 'http://customhost:12345', // Should still be saved even if openai is preferred
        'openai_api_key' => 'sk-testopenaikey12345',
        'claude_api_key' => null, // Explicitly set to null to clear it
        'gemini_api_key' => 'gm-testgeminikey67890'
    ];
    if (UserSettings::save($test_user_id, $new_settings_data)) {
        echo "Settings updated successfully.\n";
        $updated_settings = UserSettings::getByUserId($test_user_id);
        echo "Retrieved updated settings:\n";
        print_r($updated_settings);

        // Verify encryption/decryption
        if ($updated_settings && $updated_settings['openai_api_key'] === 'sk-testopenaikey12345') {
            echo "SUCCESS: OpenAI API key decrypted correctly.\n";
        } else {
            echo "ERROR: OpenAI API key decryption failed or mismatch.\n";
        }
        if ($updated_settings && $updated_settings['gemini_api_key'] === 'gm-testgeminikey67890') {
            echo "SUCCESS: Gemini API key decrypted correctly.\n";
        } else {
            echo "ERROR: Gemini API key decryption failed or mismatch.\n";
        }
         if ($updated_settings && $updated_settings['claude_api_key'] === null) {
            echo "SUCCESS: Claude API key correctly set to null.\n";
        } else {
            echo "ERROR: Claude API key was not set to null.\n";
        }
    } else {
        echo "ERROR: Failed to update settings.\n";
    }

    // 4. Test saving settings for a non-existent user_id (should ideally not happen if foreign key constraint is active)
    // The save method uses ON DUPLICATE KEY UPDATE, which relies on user_id existing or being inserted.
    // If user_id doesn't exist and the INSERT part fails due to FK constraint, it should fail.
    // The current DB schema has ON DELETE CASCADE for user_settings, but not for INSERT if user doesn't exist.
    // Let's assume the user_id must exist. UserSettings::createDefaults would typically be called during user creation.

    // 5. Test retrieving settings for a user with no settings (should return null)
    $non_existent_settings_user_id = $test_user_id + 999; // Unlikely to exist
    echo "\nAttempting to get settings for non-existent user ID: " . $non_existent_settings_user_id . "\n";
    $no_settings = UserSettings::getByUserId($non_existent_settings_user_id);
    if ($no_settings === null) {
        echo "SUCCESS: Correctly returned null for user with no settings.\n";
    } else {
        echo "ERROR: Expected null for user with no settings, but got data:\n";
        print_r($no_settings);
    }

    // 6. Test saving with only preferred_llm (should work)
    $minimal_settings_user_email = "minimalsettingsuser_" . uniqid() . "@example.com";
    $minimal_settings_user_id = User::create($minimal_settings_user_email, "securepass123");
     if ($minimal_settings_user_id) {
        echo "\nAttempting to save minimal settings (only preferred_llm) for user ID: " . $minimal_settings_user_id . "\n";
        $minimal_data = ['preferred_llm' => 'claude'];
        if (UserSettings::save($minimal_settings_user_id, $minimal_data)) {
            echo "Minimal settings saved successfully.\n";
            $retrieved_minimal = UserSettings::getByUserId($minimal_settings_user_id);
            echo "Retrieved minimal settings:\n";
            print_r($retrieved_minimal);
            if ($retrieved_minimal && $retrieved_minimal['preferred_llm'] === 'claude') {
                echo "SUCCESS: preferred_llm correctly saved.\n";
            } else {
                echo "ERROR: preferred_llm mismatch or not saved.\n";
            }
        } else {
            echo "ERROR: Failed to save minimal settings.\n";
        }
    } else {
        echo "Failed to create user for minimal settings test.\n";
    }
}
*/
?>
