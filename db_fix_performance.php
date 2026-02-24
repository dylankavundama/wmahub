<?php
/**
 * WMA HUB - Database Fix Script
 * Resolves missing tables: evaluations, monthly_awards
 * Adds missing columns to tasks: rating, is_archived
 */

require_once __DIR__ . '/includes/config.php';

try {
    $db = getDBConnection();
    echo "<h1>Database Fix - WMA HUB Performance System</h1>";
    echo "<ul style='font-family: sans-serif; line-height: 1.6;'>";

    // 1. Create evaluations table
    echo "<li>Checking <b>evaluations</b> table... ";
    $db->exec("CREATE TABLE IF NOT EXISTS `evaluations` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `admin_id` int(11) NOT NULL,
        `employee_id` int(11) NOT NULL,
        `rating` int(11) NOT NULL,
        `comment` text COLLATE utf8mb4_general_ci DEFAULT NULL,
        `is_archived` tinyint(1) DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `admin_id` (`admin_id`),
        KEY `employee_id` (`employee_id`),
        CONSTRAINT `evaluations_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        CONSTRAINT `evaluations_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    echo "<span style='color: green;'>READY</span></li>";

    // 2. Create monthly_awards table
    echo "<li>Checking <b>monthly_awards</b> table... ";
    $db->exec("CREATE TABLE IF NOT EXISTS `monthly_awards` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `month` varchar(7) NOT NULL,
        `employee_id` int(11) NOT NULL,
        `position` int(11) NOT NULL,
        `score` decimal(3,1) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `employee_id` (`employee_id`),
        CONSTRAINT `monthly_awards_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    echo "<span style='color: green;'>READY</span></li>";

    // 3. Fix tasks table columns
    echo "<li>Checking <b>tasks</b> table columns... ";
    
    // Check 'rating' column
    $res_rating = $db->query("SHOW COLUMNS FROM `tasks` LIKE 'rating'");
    if (!$res_rating->fetch()) {
        $db->exec("ALTER TABLE `tasks` ADD COLUMN `rating` INT DEFAULT 3;");
        echo "<br>&nbsp;&nbsp;&nbsp;- Column <b>rating</b> ADDED.";
    } else {
        echo "<br>&nbsp;&nbsp;&nbsp;- Column <b>rating</b> already exists.";
    }

    // Check 'is_archived' column
    $res_archived = $db->query("SHOW COLUMNS FROM `tasks` LIKE 'is_archived'");
    if (!$res_archived->fetch()) {
        $db->exec("ALTER TABLE `tasks` ADD COLUMN `is_archived` TINYINT(1) DEFAULT 0;");
        echo "<br>&nbsp;&nbsp;&nbsp;- Column <b>is_archived</b> ADDED.";
    } else {
        echo "<br>&nbsp;&nbsp;&nbsp;- Column <b>is_archived</b> already exists.";
    }
    echo "</li>";

    echo "</ul>";
    echo "<div style='background: #e6ffed; border: 1px solid #b7eb8f; padding: 15px; border-radius: 8px; font-family: sans-serif;'>";
    echo "<h3 style='margin-top: 0; color: #52c41a;'>Fix Completed Successfully!</h3>";
    echo "<p>You can now delete this file (<code>db_fix_performance.php</code>) for security and try accessing the dashboard.</p>";
    echo "<a href='dashboards/admin/index.php' style='display: inline-block; background: #1890ff; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none;'>Go to Dashboard</a>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background: #fff1f0; border: 1px solid #ffa39e; padding: 15px; border-radius: 8px; font-family: sans-serif; color: #cf1322;'>";
    echo "<h3>Error during migration:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
