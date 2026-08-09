-- DDL.sql — Database Source of Truth
-- Target database: MySQL 8.x
-- Primary key convention: UUID unless explicitly stated otherwise.
-- Claude must not invent tables or columns not defined in this file.
-- If schema changes are required, update this file and the corresponding migration together.

-- ============================================================================
-- users — app-owned example table (database/migrations/20260101000000_create_users_table.php)
-- Not one of Clarity's two setup-owned tables (sessions/caches — see
-- monad/clarity's own DDL.sql); this one belongs to the application. Column
-- shape matches what Middlewares\Authentication's findByCredential/findById
-- resolvers are expected to return: {id, passwordHash, locked, emailVerifiedAt}.
-- ============================================================================
CREATE TABLE `users` (
    `id` char(36) NOT NULL,
    `email` varchar(255) NOT NULL,
    `password_hash` varchar(255) NOT NULL,
    `full_name` varchar(255) NULL,
    `role` varchar(50) NOT NULL DEFAULT 'member',
    `locked` tinyint(1) NOT NULL DEFAULT 0,
    `email_verified_at` datetime NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Example structure for a project-specific table. Replace with real schema as
-- the application grows — this file must stay the actual source of truth, not
-- fall behind the real migrations the way it did for `users` above.
-- ----------------------------------------------------------------------------

-- CREATE TABLE example_entities (
--     id CHAR(36) NOT NULL PRIMARY KEY,
--     name VARCHAR(255) NOT NULL,
--     status VARCHAR(50) NOT NULL DEFAULT 'active',
--     created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
--     updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
--     INDEX idx_example_entities_status (status)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
