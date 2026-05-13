-- KAZ-SIGN System Database Schema
-- MySQL 5.7+ / MariaDB 10.3+

CREATE DATABASE IF NOT EXISTS a200368
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE a200368;

-- --------------------------------------------------------
-- Table: users
-- Stores registered users with their RSA public keys
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    username    VARCHAR(80)     NOT NULL,
    email       VARCHAR(180)    NOT NULL,
    password    VARCHAR(255)    NOT NULL,         -- bcrypt hash
    public_key  TEXT            NOT NULL,         -- PEM-encoded RSA public key
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email    (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: documents
-- Tracks uploaded documents, their SHA-256 hash, and RSA signature
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS documents (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    NOT NULL,
    file_name   VARCHAR(255)    NOT NULL,         -- original filename
    file_hash   VARCHAR(64)     NOT NULL,         -- SHA-256 hex digest of file contents
    signature   TEXT            NOT NULL,         -- base64-encoded RSA-SHA256 signature
    status      ENUM('pending','signed','verified','rejected') NOT NULL DEFAULT 'pending',
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    CONSTRAINT fk_documents_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    INDEX idx_documents_user_id (user_id),
    INDEX idx_documents_status  (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
