-- Migration: Create distributions table
-- Run this on your production MySQL server (wmahubco_hub database)

CREATE TABLE IF NOT EXISTS `distributions` (
    `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`      VARCHAR(255) NOT NULL,
    `artist`     VARCHAR(255) NOT NULL,
    `image_url`  VARCHAR(500) NOT NULL DEFAULT '',
    `link`       VARCHAR(500) NOT NULL DEFAULT '',
    `status`     ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
