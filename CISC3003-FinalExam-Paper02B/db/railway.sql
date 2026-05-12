-- Railway import file for the existing Railway MySQL database.
-- Import this file into the Railway-provided database; it does not create or switch databases.

CREATE TABLE IF NOT EXISTS contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  subject VARCHAR(160) NOT NULL,
  message TEXT NOT NULL,
  mail_status VARCHAR(30) NOT NULL,
  debug_message TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO contact_messages (name, email, subject, message, mail_status, debug_message)
VALUES ('Kris Wu Zexian', 'dc228114@example.com', 'Sample Contact', 'Sample INSERT INTO contact message.', 'demo', 'Inserted by database.sql');

CREATE TABLE IF NOT EXISTS demo_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

