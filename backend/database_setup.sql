-- SQL script for Email Writing Assistant database setup (MySQL/MariaDB)

-- users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    sso_provider VARCHAR(50) NULL,
    sso_id VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
);

-- user_settings table
CREATE TABLE IF NOT EXISTS user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    preferred_llm VARCHAR(50) NOT NULL DEFAULT 'ollama', -- e.g., 'ollama', 'openai', 'gemini'
    ollama_endpoint VARCHAR(255) NULL,
    openai_api_key TEXT NULL, -- Stored encrypted
    claude_api_key TEXT NULL,  -- Stored encrypted
    gemini_api_key TEXT NULL,  -- Stored encrypted
    groq_api_key TEXT NULL,    -- Stored encrypted
    cohere_api_key TEXT NULL,  -- Stored encrypted
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);

-- user_emails_history table
CREATE TABLE IF NOT EXISTS user_emails_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    raw_thoughts TEXT NULL,
    tone VARCHAR(50) NULL,
    context_email TEXT NULL,
    generated_email TEXT NULL,
    llm_used VARCHAR(50) NULL, -- e.g., 'ollama', 'openai_gpt-4', 'claude_sonnet-3.5'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id_created_at (user_id, created_at)
);

-- Note on API Key Encryption:
-- The TEXT type is used for API keys to accommodate potentially long encrypted strings.
-- Encryption/decryption should be handled at the application layer (PHP backend)
-- before inserting into and after fetching from this table.
-- Consider using a dedicated, strong encryption key managed securely (e.g., in .env).

-- Example of how to create a default user setting upon user registration (concept, actual implementation in PHP):
-- INSERT INTO user_settings (user_id, preferred_llm, ollama_endpoint) VALUES (new_user_id, 'ollama', 'http://localhost:11434');

-- End of script
