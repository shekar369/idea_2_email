<?php

require_once __DIR__ . '/../middleware/AuthMiddleware.php'; // For require_auth and get_current_user
require_once __DIR__ . '/../models/UserSettings.php';     // For UserSettings model

/**
 * Retrieves the LLM settings for the currently authenticated user.
 *
 * Method: GET
 * Path: /api/settings/llm
 * Protected: Yes
 * Responds with user's LLM settings (e.g., preferred_llm, ollama_endpoint, and flags indicating if API keys are set).
 * Actual API keys are NOT sent to the client for security reasons.
 */
function getLlmSettings() {
    $current_user = get_current_user(); // Relies on require_auth() being called by the router
    if (!$current_user) {
        // This should ideally be caught by require_auth() middleware.
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required. User not found.']);
        return;
    }

    $user_id = (int)$current_user['id'];
    $settings = UserSettings::getByUserId($user_id); // Fetches and decrypts keys internally if needed by backend

    if ($settings) {
        // Prepare a client-safe version of settings.
        // API keys themselves are not sent; only indicators of whether they are set.
        $client_safe_settings = [
            'id' => $settings['id'], // Setting ID
            'user_id' => $settings['user_id'],
            'preferred_llm' => $settings['preferred_llm'],
            'ollama_endpoint' => $settings['ollama_endpoint'],
            'updated_at' => $settings['updated_at'],
            // Indicate if keys are set without exposing them
            'openai_api_key_set' => !empty($settings['openai_api_key']),
            'claude_api_key_set' => !empty($settings['claude_api_key']),
            'gemini_api_key_set' => !empty($settings['gemini_api_key']),
            'groq_api_key_set' => !empty($settings['groq_api_key']),
            'cohere_api_key_set' => !empty($settings['cohere_api_key']),
        ];


        http_response_code(200);
        echo json_encode([
            'message' => 'LLM settings retrieved successfully.',
            'settings' => $client_safe_settings
        ]);
    } else {
        // This might happen if UserSettings::createDefaults failed or was missed during registration.
        // Or if getByUserId returns null due to a decryption error of a critical field.
        // Consider creating default settings here if they are missing.
        error_log("SettingsController::getLlmSettings - No settings found for user ID: " . $user_id . ". Attempting to create defaults.");
        if (UserSettings::createDefaults($user_id)) {
            $settings = UserSettings::getByUserId($user_id);
            if ($settings) {
                 $client_safe_settings_after_create = [ /* Re-populate as above */
                    'id' => $settings['id'], 'user_id' => $settings['user_id'],
                    'preferred_llm' => $settings['preferred_llm'], 'ollama_endpoint' => $settings['ollama_endpoint'],
                    'updated_at' => $settings['updated_at'],
                    'openai_api_key_set' => !empty($settings['openai_api_key']),
                    'claude_api_key_set' => !empty($settings['claude_api_key']),
                    'gemini_api_key_set' => !empty($settings['gemini_api_key']),
                    'groq_api_key_set' => !empty($settings['groq_api_key']),
                    'cohere_api_key_set' => !empty($settings['cohere_api_key']),
                ];
                http_response_code(200);
                echo json_encode([
                    'message' => 'Default LLM settings created and retrieved successfully.',
                    'settings' => $client_safe_settings_after_create
                ]);
                return;
            }
        }
        http_response_code(404);
        echo json_encode(['error' => 'LLM settings not found for this user.']);
    }
}

/**
 * Updates the LLM settings for the currently authenticated user.
 *
 * Method: POST
 * Path: /api/settings/llm
 * Protected: Yes
 * Expects JSON input with fields like 'preferred_llm', 'ollama_endpoint',
 * and any API keys ('openai_api_key', 'claude_api_key', etc.) the user wishes to update.
 * API keys are encrypted by the UserSettings model before saving.
 * Responds with updated client-safe settings on success.
 */
function updateLlmSettings() {
    $current_user = get_current_user(); // Relies on require_auth() being called by the router
    if (!$current_user) {
        // This should ideally be caught by require_auth() middleware.
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required. User not found.']);
        return;
    }

    $user_id = (int)$current_user['id'];
    // Get raw POST data
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Invalid input data: No JSON payload received.']);
        return;
    }

    // Validate 'preferred_llm' - it's crucial for functionality
    if (!isset($input['preferred_llm']) || empty(trim($input['preferred_llm']))) {
        http_response_code(400);
        echo json_encode(['error' => "'preferred_llm' is a required field and cannot be empty."]);
        return;
    }

    // Define allowed LLM providers for validation
    $allowed_llm_providers = ['ollama', 'openai', 'claude', 'gemini', 'groq', 'cohere'];
    if (!in_array($input['preferred_llm'], $allowed_llm_providers)) {
        http_response_code(400);
        echo json_encode(['error' => "Invalid 'preferred_llm' value. Allowed values are: " . implode(', ', $allowed_llm_providers) . "."]);
        return;
    }

    // Prepare data for UserSettings::save. It handles encryption internally.
    // Only pass fields that are actually submitted by the client to allow partial updates.
    $settings_data = [];
    $updatable_fields = [
        'preferred_llm', 'ollama_endpoint',
        'openai_api_key', 'claude_api_key', 'gemini_api_key', 'groq_api_key', 'cohere_api_key'
    ];

    foreach ($updatable_fields as $field) {
        if (array_key_exists($field, $input)) { // Use array_key_exists to allow empty strings or null for clearing API keys
            $settings_data[$field] = ($input[$field] === '') ? null : trim($input[$field]);
        }
    }

    // If specific LLM is chosen and its API key field is present but empty,
    // it implies user wants to use it but hasn't provided the key.
    // This logic is more for the frontend to manage. Backend saves what's given.
    // Example: if preferred_llm is 'openai' and openai_api_key is empty/null, the EmailController will later fail if it tries to use it.

    if (empty($settings_data)) {
        http_response_code(400);
        echo json_encode(['error' => 'No settings data provided to update.']);
        return;
    }


    if (UserSettings::save($user_id, $settings_data)) {
        // Fetch the updated settings to return (client-safe version)
        $updated_settings = UserSettings::getByUserId($user_id);
        $client_safe_updated_settings = [
            'preferred_llm' => $updated_settings['preferred_llm'] ?? null,
            'ollama_endpoint' => $updated_settings['ollama_endpoint'] ?? null,
            'updated_at' => $updated_settings['updated_at'] ?? null,
            'openai_api_key_set' => !empty($updated_settings['openai_api_key']),
            'claude_api_key_set' => !empty($updated_settings['claude_api_key']),
            'gemini_api_key_set' => !empty($updated_settings['gemini_api_key']),
            'groq_api_key_set' => !empty($updated_settings['groq_api_key']),
            'cohere_api_key_set' => !empty($updated_settings['cohere_api_key']),
        ];

        http_response_code(200);
        echo json_encode([
            'message' => 'LLM settings updated successfully.',
            'settings' => $client_safe_updated_settings
        ]);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Failed to update LLM settings. Please try again.']);
    }
}

?>
