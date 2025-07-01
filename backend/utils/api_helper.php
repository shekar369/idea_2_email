<?php

/**
 * Makes an HTTP request using cURL.
 *
 * @param string $method The HTTP method (GET, POST, PUT, DELETE).
 * @param string $url The URL to request.
 * @param array|string|null $data The data to send with the request (for POST, PUT).
 * @param array $headers An array of HTTP headers.
 * @param int $timeout Timeout in seconds.
 * @return array An array containing 'status_code', 'headers', and 'body'. 'body' is decoded if JSON.
 */
function make_curl_request(string $method, string $url, $data = null, array $headers = [], int $timeout = 30): array {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);        // Limit redirects
    curl_setopt($ch, CURLOPT_HEADER, true);        // Include header in the output

    // Set method-specific options
    switch (strtoupper($method)) {
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
            break;
        case 'PUT':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
            break;
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;
        case 'GET':
            // Default, no specific options needed for GET
            break;
        default:
            // Potentially throw an error for unsupported methods
            curl_close($ch);
            return [
                'status_code' => 0, // Indicate an internal error
                'headers' => [],
                'body' => json_encode(['error' => 'Unsupported HTTP method: ' . $method]),
                'error' => 'Unsupported HTTP method: ' . $method
            ];
    }

    // Add headers
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $response = curl_exec($ch);
    $curl_errno = curl_errno($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

    curl_close($ch);

    if ($curl_errno > 0) {
        // cURL error (network issue, etc.)
        return [
            'status_code' => 0, // Or use $http_code if available but might be misleading
            'headers' => [],
            'body' => json_encode(['error' => 'cURL Error: ' . $curl_error, 'errno' => $curl_errno]),
            'error' => 'cURL Error: ' . $curl_error
        ];
    }

    $response_headers_str = substr($response, 0, $header_size);
    $response_body_str = substr($response, $header_size);

    // Parse headers
    $response_headers_arr = [];
    $header_lines = explode("\r\n", trim($response_headers_str));
    foreach ($header_lines as $line) {
        if (strpos($line, ':') !== false) {
            list($key, $value) = explode(':', $line, 2);
            $response_headers_arr[trim($key)] = trim($value);
        }
    }

    // Attempt to decode JSON body if Content-Type suggests it
    $decoded_body = $response_body_str;
    if (isset($response_headers_arr['Content-Type']) && stripos($response_headers_arr['Content-Type'], 'application/json') !== false) {
        $json_decoded = json_decode($response_body_str, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $decoded_body = $json_decoded;
        }
    }

    return [
        'status_code' => $http_code,
        'headers' => $response_headers_arr,
        'body' => $decoded_body,
        'error' => null // No cURL transport error
    ];
}

// --- Example Usage (for testing) ---
/*
if (php_sapi_name() === 'cli') {
    echo "API Helper Test - cURL Request\n";

    // Test GET request
    echo "\nTesting GET request to jsonplaceholder.typicode.com/todos/1...\n";
    $get_response = make_curl_request('GET', 'https://jsonplaceholder.typicode.com/todos/1');
    echo "Status Code: " . $get_response['status_code'] . "\n";
    echo "Response Body:\n";
    print_r($get_response['body']);
    if ($get_response['error']) {
        echo "Error: " . $get_response['error'] . "\n";
    }

    // Test POST request
    echo "\nTesting POST request to jsonplaceholder.typicode.com/posts...\n";
    $post_data = json_encode(['title' => 'foo', 'body' => 'bar', 'userId' => 1]);
    $post_headers = ['Content-Type: application/json; charset=UTF-8'];
    $post_response = make_curl_request('POST', 'https://jsonplaceholder.typicode.com/posts', $post_data, $post_headers);
    echo "Status Code: " . $post_response['status_code'] . "\n"; // Should be 201
    echo "Response Body:\n";
    print_r($post_response['body']);
     if ($post_response['error']) {
        echo "Error: " . $post_response['error'] . "\n";
    }

    // Test request to a non-existent domain
    echo "\nTesting request to a non-existent domain...\n";
    $error_response = make_curl_request('GET', 'http://thisshouldnotexist12345abc.com');
    echo "Status Code: " . $error_response['status_code'] . "\n";
    echo "Response Body/Error:\n";
    print_r($error_response['body']); // Will contain the cURL error message
    if ($error_response['error']) {
        echo "Error: " . $error_response['error'] . "\n";
    }
}
*/
?>
