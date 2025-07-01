<?php

require_once __DIR__ . '/../config/database.php';

class EmailHistory {

    /**
     * Saves a record of a generated email to the history.
     *
     * @param int $user_id The ID of the user who generated the email.
     * @param string|null $raw_thoughts The raw thoughts input by the user.
     * @param string|null $tone The tone selected for the email.
     * @param string|null $context_email The context email provided by the user.
     * @param string|null $generated_email The AI-generated email body.
     * @param string|null $llm_used The LLM service that was used (e.g., 'ollama', 'openai_gpt-3.5-turbo').
     * @return int|false The ID of the newly created history record, or false on failure.
     */
    public static function save(
        int $user_id,
        ?string $raw_thoughts,
        ?string $tone,
        ?string $context_email,
        ?string $generated_email,
        ?string $llm_used
    ): int|false {
        $db = get_db_connection();
        if (!$db) {
            error_log("EmailHistory::save - Database connection failed.");
            return false;
        }

        $sql = "INSERT INTO user_emails_history
                    (user_id, raw_thoughts, tone, context_email, generated_email, llm_used, created_at)
                VALUES
                    (:user_id, :raw_thoughts, :tone, :context_email, :generated_email, :llm_used, NOW())";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':raw_thoughts', $raw_thoughts);
            $stmt->bindParam(':tone', $tone);
            $stmt->bindParam(':context_email', $context_email);
            $stmt->bindParam(':generated_email', $generated_email);
            $stmt->bindParam(':llm_used', $llm_used);

            $stmt->execute();
            return (int)$db->lastInsertId();
        } catch (PDOException $e) {
            error_log("EmailHistory::save - PDOException for user ID " . $user_id . ": " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves email generation history for a given user, optionally paginated.
     *
     * @param int $user_id The ID of the user.
     * @param int $limit Number of records to retrieve.
     * @param int $offset Offset for pagination.
     * @return array An array of history records, or an empty array if none found/error.
     */
    public static function getByUserId(int $user_id, int $limit = 20, int $offset = 0): array {
        $db = get_db_connection();
        if (!$db) {
            error_log("EmailHistory::getByUserId - Database connection failed for user ID: " . $user_id);
            return [];
        }

        $sql = "SELECT * FROM user_emails_history
                WHERE user_id = :user_id
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("EmailHistory::getByUserId - PDOException for user ID " . $user_id . ": " . $e->getMessage());
            return [];
        }
    }

    /**
     * Counts the total number of history records for a user.
     *
     * @param int $user_id The ID of the user.
     * @return int The total count of history records.
     */
    public static function countByUserId(int $user_id): int {
        $db = get_db_connection();
        if (!$db) {
            error_log("EmailHistory::countByUserId - Database connection failed for user ID: " . $user_id);
            return 0;
        }

        $sql = "SELECT COUNT(*) FROM user_emails_history WHERE user_id = :user_id";
        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("EmailHistory::countByUserId - PDOException for user ID " . $user_id . ": " . $e->getMessage());
            return 0;
        }
    }
}

// --- Example Usage (CLI) ---
/*
if (php_sapi_name() === 'cli') {
    echo "EmailHistory Model Test\n\n";
    require_once __DIR__ . '/User.php'; // For creating a test user

    // 1. Create a test user
    $test_email_history = "historyuser_" . uniqid() . "@example.com";
    $test_user_id_history = User::create($test_email_history, "securepass123");

    if (!$test_user_id_history) {
        echo "Failed to create test user. Aborting EmailHistory test.\n";
        exit(1);
    }
    echo "Test user created with ID: " . $test_user_id_history . "\n";

    // 2. Save some history records
    echo "\nAttempting to save email history records for user ID: " . $test_user_id_history . "\n";
    $history_data_1 = [
        'raw_thoughts' => 'Meeting reminder for tomorrow',
        'tone' => 'professional',
        'context_email' => null,
        'generated_email' => 'This is a reminder about our meeting scheduled for tomorrow...',
        'llm_used' => 'ollama_llama3'
    ];
    $history_id_1 = EmailHistory::save($test_user_id_history, ...array_values($history_data_1));
    if ($history_id_1) echo "Saved history record 1 with ID: " . $history_id_1 . "\n"; else echo "ERROR saving record 1\n";

    sleep(1); // Ensure different created_at for ordering

    $history_data_2 = [
        'raw_thoughts' => 'Thank you for the gift',
        'tone' => 'warm',
        'context_email' => 'Subject: Your lovely present!',
        'generated_email' => 'Thank you so much for the wonderful gift! It was so thoughtful of you.',
        'llm_used' => 'openai_gpt-4'
    ];
    $history_id_2 = EmailHistory::save($test_user_id_history, ...array_values($history_data_2));
    if ($history_id_2) echo "Saved history record 2 with ID: " . $history_id_2 . "\n"; else echo "ERROR saving record 2\n";

    sleep(1);

    $history_data_3 = [
        'raw_thoughts' => 'Inquiry about product X',
        'tone' => 'formal',
        'context_email' => null,
        'generated_email' => 'Dear Sir/Madam, I am writing to inquire about Product X...',
        'llm_used' => 'claude_sonnet'
    ];
    $history_id_3 = EmailHistory::save($test_user_id_history, ...array_values($history_data_3));
    if ($history_id_3) echo "Saved history record 3 with ID: " . $history_id_3 . "\n"; else echo "ERROR saving record 3\n";


    // 3. Retrieve history for the user
    echo "\nRetrieving email history for user ID: " . $test_user_id_history . " (limit 2, offset 0)...\n";
    $history_records_page1 = EmailHistory::getByUserId($test_user_id_history, 2, 0);
    if (!empty($history_records_page1)) {
        echo "Found " . count($history_records_page1) . " records (Page 1):\n";
        print_r($history_records_page1);
        // Check order (should be most recent first: record 3, then record 2)
        if (isset($history_records_page1[0]['raw_thoughts']) && $history_records_page1[0]['raw_thoughts'] == $history_data_3['raw_thoughts']) {
            echo "SUCCESS: Page 1 ordering seems correct.\n";
        } else {
            echo "ERROR: Page 1 ordering incorrect or data mismatch.\n";
        }
    } else {
        echo "ERROR: No history records found for page 1 or error occurred.\n";
    }

    echo "\nRetrieving email history for user ID: " . $test_user_id_history . " (limit 2, offset 2)...\n";
    $history_records_page2 = EmailHistory::getByUserId($test_user_id_history, 2, 2);
     if (!empty($history_records_page2)) {
        echo "Found " . count($history_records_page2) . " records (Page 2):\n";
        print_r($history_records_page2);
        // Check order (should be record 1)
         if (isset($history_records_page2[0]['raw_thoughts']) && $history_records_page2[0]['raw_thoughts'] == $history_data_1['raw_thoughts']) {
            echo "SUCCESS: Page 2 ordering seems correct.\n";
        } else {
            echo "ERROR: Page 2 ordering incorrect or data mismatch.\n";
        }
    } else {
        echo "No history records found for page 2 (this might be correct if total is <=2).\n";
    }


    // 4. Count total history records for the user
    echo "\nCounting total history records for user ID: " . $test_user_id_history . "\n";
    $total_count = EmailHistory::countByUserId($test_user_id_history);
    echo "Total records found: " . $total_count . "\n";
    if ($total_count == 3) { // Assuming all 3 saved successfully
        echo "SUCCESS: Total count matches expected.\n";
    } else {
        echo "ERROR: Total count mismatch. Expected 3, got " . $total_count . "\n";
    }

    // 5. Test with a user ID that has no history
    $no_history_user_id = $test_user_id_history + 999; // Unlikely to have history
    echo "\nRetrieving history for user ID with no history (" . $no_history_user_id . ")...\n";
    $no_history_records = EmailHistory::getByUserId($no_history_user_id);
    if (empty($no_history_records)) {
        echo "SUCCESS: Correctly returned empty array for user with no history.\n";
    } else {
        echo "ERROR: Expected empty array but got records for user with no history.\n";
        print_r($no_history_records);
    }
    $no_history_count = EmailHistory::countByUserId($no_history_user_id);
     if ($no_history_count === 0) {
        echo "SUCCESS: Correctly counted 0 records for user with no history.\n";
    } else {
        echo "ERROR: Expected 0 count but got " . $no_history_count . " for user with no history.\n";
    }
}
*/
?>
