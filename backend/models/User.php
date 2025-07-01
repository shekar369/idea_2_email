<?php

require_once __DIR__ . '/../config/database.php';

class User {

    /**
     * Creates a new user in the database.
     *
     * @param string $email The user's email.
     * @param string $password The user's plain text password.
     * @param string|null $sso_provider SSO provider name (e.g., 'google').
     * @param string|null $sso_id SSO provider's unique ID for the user.
     * @return int|false The ID of the newly created user, or false on failure.
     */
    public static function create(string $email, string $password, ?string $sso_provider = null, ?string $sso_id = null): int|false {
        $db = get_db_connection();
        if (!$db) {
            error_log("User::create - Database connection failed.");
            return false;
        }

        $password_hash = password_hash($password, PASSWORD_ARGON2ID); // Or PASSWORD_DEFAULT
        if ($password_hash === false) {
            error_log("User::create - Password hashing failed.");
            return false;
        }

        $sql = "INSERT INTO users (email, password_hash, sso_provider, sso_id, created_at, updated_at)
                VALUES (:email, :password_hash, :sso_provider, :sso_id, NOW(), NOW())";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password_hash', $password_hash);
            $stmt->bindParam(':sso_provider', $sso_provider);
            $stmt->bindParam(':sso_id', $sso_id);

            $stmt->execute();
            return (int)$db->lastInsertId();
        } catch (PDOException $e) {
            // Check for duplicate email (MySQL error code 1062)
            if ($e->getCode() == 23000 || $e->errorInfo[1] == 1062) {
                error_log("User::create - Attempt to create user with duplicate email: " . $email);
            } else {
                error_log("User::create - PDOException: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Finds a user by their email address.
     *
     * @param string $email The email to search for.
     * @return array|null The user data as an associative array, or null if not found.
     */
    public static function findByEmail(string $email): ?array {
        $db = get_db_connection();
        if (!$db) {
            error_log("User::findByEmail - Database connection failed.");
            return null;
        }

        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (PDOException $e) {
            error_log("User::findByEmail - PDOException: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Finds a user by their ID.
     *
     * @param int $id The user ID.
     * @return array|null The user data as an associative array, or null if not found.
     */
    public static function findById(int $id): ?array {
        $db = get_db_connection();
        if (!$db) {
            error_log("User::findById - Database connection failed.");
            return null;
        }

        $sql = "SELECT id, email, sso_provider, sso_id, created_at, updated_at FROM users WHERE id = :id LIMIT 1"; // Exclude password_hash
        try {
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (PDOException $e) {
            error_log("User::findById - PDOException: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verifies a user's password.
     *
     * @param string $email The user's email.
     * @param string $password The plain text password to verify.
     * @return array|null User data if password is correct, null otherwise.
     */
    public static function verifyPassword(string $email, string $password): ?array {
        $user = self::findByEmail($email); // This fetches the full user record including password_hash
        if ($user && isset($user['password_hash'])) {
            if (password_verify($password, $user['password_hash'])) {
                // Password is correct, remove hash before returning user data
                unset($user['password_hash']);
                return $user;
            }
        }
        return null; // User not found or password incorrect
    }

    /**
     * Updates user details (e.g., email, password).
     * More fields can be added as needed.
     *
     * @param int $user_id The ID of the user to update.
     * @param array $data Associative array of data to update (e.g., ['email' => 'new@example.com', 'password' => 'newpass']).
     * @return bool True on success, false on failure.
     */
    public static function update(int $user_id, array $data): bool {
        $db = get_db_connection();
        if (!$db) {
            error_log("User::update - Database connection failed.");
            return false;
        }

        $fields = [];
        $params = [':user_id' => $user_id];

        if (!empty($data['email'])) {
            $fields[] = "email = :email";
            $params[':email'] = $data['email'];
        }
        if (!empty($data['password'])) {
            $new_password_hash = password_hash($data['password'], PASSWORD_ARGON2ID);
            if ($new_password_hash === false) {
                error_log("User::update - Password hashing failed for user ID: " . $user_id);
                return false;
            }
            $fields[] = "password_hash = :password_hash";
            $params[':password_hash'] = $new_password_hash;
        }
        // Add other updatable fields here (sso_provider, sso_id if they can change post-creation)

        if (empty($fields)) {
            error_log("User::update - No fields to update for user ID: " . $user_id);
            return false; // Or true, if no change is not an error
        }

        $fields[] = "updated_at = NOW()";
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :user_id";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000 || $e->errorInfo[1] == 1062) { // Duplicate email
                error_log("User::update - Attempt to update to a duplicate email for user ID: " . $user_id);
            } else {
                error_log("User::update - PDOException for user ID " . $user_id . ": " . $e->getMessage());
            }
            return false;
        }
    }
}

// --- Example Usage (for testing - typically you wouldn't run this directly in a web request) ---
/*
if (php_sapi_name() === 'cli') {
    echo "User Model Test\n\n";

    // Ensure you have a .env file with DB credentials and run database_setup.sql first.
    // Make sure ENCRYPTION_KEY and JWT_SECRET are also set in .env

    // Test User Creation
    echo "Attempting to create a new user...\n";
    $new_user_email = "testuser" . uniqid() . "@example.com";
    $new_user_id = User::create($new_user_email, "password123");

    if ($new_user_id) {
        echo "User created successfully. ID: " . $new_user_id . ", Email: " . $new_user_email . "\n";

        // Test Find By ID
        echo "\nAttempting to find user by ID (" . $new_user_id . ")...\n";
        $found_user_by_id = User::findById($new_user_id);
        if ($found_user_by_id) {
            echo "User found by ID:\n";
            print_r($found_user_by_id);
        } else {
            echo "ERROR: Could not find user by ID.\n";
        }

        // Test Find By Email
        echo "\nAttempting to find user by email (" . $new_user_email . ")...\n";
        $found_user_by_email = User::findByEmail($new_user_email); // This will include password_hash
        if ($found_user_by_email) {
            echo "User found by email (raw data from DB includes hash):\n";
            print_r($found_user_by_email);
        } else {
            echo "ERROR: Could not find user by email.\n";
        }

        // Test Password Verification (Correct Password)
        echo "\nAttempting to verify password (correct: 'password123')...\n";
        $verified_user = User::verifyPassword($new_user_email, "password123");
        if ($verified_user) {
            echo "Password verification successful. User data (no hash):\n";
            print_r($verified_user);
        } else {
            echo "ERROR: Password verification failed for correct password.\n";
        }

        // Test Password Verification (Incorrect Password)
        echo "\nAttempting to verify password (incorrect: 'wrongpassword')...\n";
        $verified_user_incorrect = User::verifyPassword($new_user_email, "wrongpassword");
        if (!$verified_user_incorrect) {
            echo "Password verification failed for incorrect password (Correct behavior).\n";
        } else {
            echo "ERROR: Password verification succeeded for incorrect password.\n";
        }

        // Test User Update (e.g., change email)
        $updated_email = "updated_" . uniqid() . "@example.com";
        echo "\nAttempting to update user ID " . $new_user_id . " email to " . $updated_email . "...\n";
        if (User::update($new_user_id, ['email' => $updated_email])) {
            echo "User email updated successfully.\n";
            $updated_user_check = User::findById($new_user_id);
            echo "Updated user data:\n";
            print_r($updated_user_check);
        } else {
            echo "ERROR: Failed to update user email.\n";
        }

        // Test duplicate email on creation
        echo "\nAttempting to create user with existing email (" . $updated_email . ")...\n";
        $duplicate_id = User::create($updated_email, "password123");
        if (!$duplicate_id) {
            echo "Failed to create user with duplicate email (Correct behavior).\n";
        } else {
            echo "ERROR: Created user with duplicate email. ID: " . $duplicate_id . "\n";
        }


    } else {
        echo "ERROR: Failed to create user. Check logs and DB connection.\n";
    }

    // Test with non-existent user
    echo "\nAttempting to find non-existent user by email (nonexistent@example.com)...\n";
    $non_existent_user = User::findByEmail("nonexistent@example.com");
    if (!$non_existent_user) {
        echo "User not found (Correct behavior).\n";
    } else {
        echo "ERROR: Found a non-existent user.\n";
    }
}
*/
?>
