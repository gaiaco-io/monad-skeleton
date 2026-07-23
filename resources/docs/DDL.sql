-- DDL.sql — Database Source of Truth
-- Target database: MySQL 8.x
-- Primary key convention: UUID unless explicitly stated otherwise.
-- Claude must not invent tables or columns not defined in this file.
-- If schema changes are required, update this file and the corresponding migration together.

-- Example structure. Replace with actual project schema.

-- CREATE TABLE example_entities (
--     id CHAR(36) NOT NULL PRIMARY KEY,
--     name VARCHAR(255) NOT NULL,
--     status VARCHAR(50) NOT NULL DEFAULT 'active',
--     created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
--     updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
--     INDEX idx_example_entities_status (status)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
