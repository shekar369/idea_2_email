<?php

require_once __DIR__ . '/../middleware/AuthMiddleware.php'; // For require_auth and get_current_user
require_once __DIR__ . '/../models/UserSettings.php';     // For UserSettings model
require_once __DIR__ . '/../models/EmailHistory.php';    // For EmailHistory model
require_once __DIR__ . '/../utils/api_helper.php';       // For make_curl_request

/**
 * Generates an email based on user input and their LLM settings.
 * This endpoint is protected by AuthMiddleware.
 *
 * Method: POST
 * Path: /api/email/generate
 * Protected: Yes
 * Expects JSON input:
 * {
 *   "rawThoughts": "User's notes...",
 *   "tone": "professional",
 *   "contextEmail": "Optional previous email text..."
 * }
 * Responds with the generated email body or an error message.
 * Saves generation attempt to history.
 */
function generateEmail() {
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

    // Validate required inputs
    if (!$input || !isset($input['rawThoughts']) || !isset($input['tone'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Missing required fields: rawThoughts and tone are required.']);
        return;
    }

    $raw_thoughts = trim($input['rawThoughts']);
    $tone = trim($input['tone']);
    $context_email = isset($input['contextEmail']) ? trim($input['contextEmail']) : ''; // Optional

    if (empty($raw_thoughts)) {
        http_response_code(400);
        echo json_encode(['error' => 'Raw thoughts input cannot be empty.']);
        return;
    }
    if (empty($tone)) { // Basic validation for tone
        http_response_code(400);
        echo json_encode(['error' => 'Tone input cannot be empty.']);
        return;
    }


    // Fetch user's LLM settings
    $settings = UserSettings::getByUserId($user_id); // Retrieves decrypted keys if necessary for backend use
    if (!$settings) {
        http_response_code(500); // Settings should exist for an authenticated user (created on registration)
        echo json_encode(['error' => 'Could not retrieve LLM settings for this user. Please try re-saving your settings or contact support.']);
        error_log("EmailController::generateEmail - Critical: User settings not found for user_id: {$user_id}. Defaults might have failed on registration.");
        return;
    }

    $preferred_llm = $settings['preferred_llm'];
    $generated_email_body = null;
    $llm_error = null;

    // Construct the prompt (similar to frontend logic)
    $context_part = !empty($context_email)
        ? "\n\nContext - I am responding to this email:\n\"" . $context_email . "\"\n\n"
        : '';
    $prompt = "You are an expert email writer. Transform the following raw thoughts into a well-crafted email with a {$tone} tone.\n\nRaw thoughts: \"{$raw_thoughts}\"{$context_part}\n\nInstructions:\n- Write a complete, professional email body\n- Use a {$tone} tone throughout\n- Make it clear, engaging, and well-structured\n- Ensure proper email etiquette\n- Do not include a subject line\n\nRespond with ONLY the email body content. Do not include any explanations or additional text outside of the email.";

    $llm_service_used = $preferred_llm; // Base name, might append model later

    try {
        switch ($preferred_llm) {
            case 'ollama':
                $ollama_endpoint = $settings['ollama_endpoint'];
                if (empty($ollama_endpoint)) {
                    throw new Exception("Ollama endpoint is not configured.");
                }
                // Ollama typically uses /api/generate or /api/chat endpoint
                // For simplicity, using a structure similar to OpenAI's chat completions
                // This might need adjustment based on specific Ollama setup (e.g. which model to call)
                // Let's assume a model is specified in the endpoint or a default one is used by Ollama.
                $ollama_model_name = get_env('OLLAMA_DEFAULT_MODEL', 'llama3'); // Example, can be made configurable
                $llm_service_used = "ollama_{$ollama_model_name}";

                $payload = [
                    'model' => $ollama_model_name, // Or another model if specified/configured
                    'prompt' => $prompt,
                    'stream' => false, // We want the full response
                    // 'system' => "You are an expert email writer." // Alternative way to set system prompt
                ];
                $headers = ['Content-Type: application/json'];
                // Ensure Ollama endpoint has /api/generate or similar path
                $ollama_api_url = rtrim($ollama_endpoint, '/') . '/api/generate';

                $response = make_curl_request('POST', $ollama_api_url, json_encode($payload), $headers, 60); // 60s timeout

                if ($response['status_code'] === 200 && isset($response['body']['response'])) {
                    $generated_email_body = trim($response['body']['response']);
                } else {
                    $error_detail = isset($response['body']['error']) ? $response['body']['error'] : json_encode($response['body']);
                    throw new Exception("Ollama API error (HTTP {$response['status_code']}): " . $error_detail);
                }
                break;

            case 'openai':
                $api_key = $settings['openai_api_key'];
                if (empty($api_key)) {
                    throw new Exception("OpenAI API key is not configured.");
                }
                $openai_model = get_env('OPENAI_DEFAULT_MODEL', 'gpt-3.5-turbo'); // Configurable via .env
                $llm_service_used = "openai_{$openai_model}";
                $payload = [
                    'model' => $openai_model,
                    'messages' => [
                        // System message can be set here if preferred over embedding in user prompt
                        // ['role' => 'system', 'content' => 'You are an expert email writer.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.7, // Adjust as needed
                ];
                $headers = [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $api_key
                ];
                $response = make_curl_request('POST', 'https://api.openai.com/v1/chat/completions', json_encode($payload), $headers, 60);

                if ($response['status_code'] === 200 && isset($response['body']['choices'][0]['message']['content'])) {
                    $generated_email_body = trim($response['body']['choices'][0]['message']['content']);
                } else {
                     $error_detail = isset($response['body']['error']['message']) ? $response['body']['error']['message'] : json_encode($response['body']);
                    throw new Exception("OpenAI API error (HTTP {$response['status_code']}): " . $error_detail);
                }
                break;

            // --- Placeholder for other LLMs ---
            // case 'claude':
            // case 'gemini':
            // case 'groq':
            // case 'cohere':
            //     $api_key = $settings[$preferred_llm . '_api_key'];
            //     if (empty($api_key)) {
            //         throw new Exception(ucfirst($preferred_llm) . " API key is not configured.");
            //     }
            //     // ... specific API call logic for this LLM ...
            //     $llm_service_used = $preferred_llm . "_model_name"; // Replace with actual model
            //     throw new Exception(ucfirst($preferred_llm) . " integration is not yet fully implemented.");
            //     break;

            default:
                throw new Exception("Unsupported LLM provider: {$preferred_llm}. Or provider not fully implemented.");
        }
    } catch (Exception $e) {
        $llm_error = $e->getMessage();
        error_log("EmailController::generateEmail - LLM Error for user_id {$user_id}, provider {$preferred_llm}: " . $llm_error);
    }

    // Save to history regardless of LLM success/failure to log the attempt
    EmailHistory::save(
        $user_id,
        $raw_thoughts,
        $tone,
        $context_email,
        $generated_email_body, // This will be null if LLM failed
        $llm_error ? "{$llm_service_used}_error" : $llm_service_used
    );

    if ($llm_error) {
        http_response_code(502); // Bad Gateway (indicates error from upstream LLM)
        echo json_encode(['error' => "Failed to generate email using {$preferred_llm}: {$llm_error}"]);
    } elseif ($generated_email_body !== null) {
        http_response_code(200);
        echo json_encode([
            'message' => 'Email generated successfully.',
            'generatedEmail' => $generated_email_body
        ]);
    } else {
        // Should have been caught by $llm_error, but as a fallback
        http_response_code(500);
        echo json_encode(['error' => 'An unexpected error occurred during email generation.']);
    }
}
?>
