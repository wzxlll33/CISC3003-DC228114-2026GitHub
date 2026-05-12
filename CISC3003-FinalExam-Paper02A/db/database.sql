CREATE DATABASE IF NOT EXISTS cisc3003_paper02a
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE cisc3003_paper02a;

CREATE TABLE IF NOT EXISTS service_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  student_email VARCHAR(160) NOT NULL,
  student_id VARCHAR(40) NOT NULL,
  service_type VARCHAR(80) NOT NULL,
  academic_year VARCHAR(20) NOT NULL,
  contact_method VARCHAR(30) NOT NULL,
  interests VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO service_requests
  (full_name, student_email, student_id, service_type, academic_year, contact_method, interests, message)
VALUES
  ('Kris Wu Zexian', 'dc228114@example.com', 'DC228114', 'Academic Advising', 'Year 3', 'Email', 'PHP,MySQL', 'Sample INSERT INTO record for Scenario A.');

CREATE TABLE IF NOT EXISTS demo_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
