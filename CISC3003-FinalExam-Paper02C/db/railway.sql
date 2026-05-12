-- Railway import file for the existing Railway MySQL database.
-- Import this file into the Railway-provided database; it does not create or switch databases.

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  account_activation_hash CHAR(64) DEFAULT NULL,
  reset_token_hash CHAR(64) DEFAULT NULL,
  reset_token_expires_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (email),
  INDEX (account_activation_hash),
  INDEX (reset_token_hash)
);

INSERT INTO users (full_name, email, password_hash, account_activation_hash)
VALUES ('Activated Demo User', 'activated@example.com', '$2y$10$7qst8m3r9xEnVJdXyvSaeedHRu1pWUXVtr26JxZRYo08LKTQnWgI.', NULL);

