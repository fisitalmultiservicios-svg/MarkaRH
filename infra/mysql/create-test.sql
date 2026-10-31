-- Dedicated local test database. Never execute this against a production server.
CREATE DATABASE IF NOT EXISTS markarh_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON markarh_test.* TO 'markarh'@'%';
