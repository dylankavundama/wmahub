<?php
// includes/header.php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1200">
    <title><?= $pageTitle ?? 'WMA Hub' ?></title>
    <link rel="icon" type="image/png" sizes="48x48" href="/asset/favicon-48x48.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/asset/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/asset/favicon-192x192.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/asset/apple-touch-icon.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/theme-harmony.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8fafc; }
        .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="min-h-screen">
