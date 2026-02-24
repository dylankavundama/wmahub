<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'artiste') {
    header('Location: ../../auth/login.php');
    exit;
}
?>
