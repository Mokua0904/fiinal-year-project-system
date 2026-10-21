<?php
// eckose_pos/includes/header.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Get current page name for the title
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? 'User';
$username = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eckose POS - <?= ucfirst(str_replace('.php', '', $current_page)) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        /* Custom Scrollbar for main content */
        .custom-scrollbar::-webkit-scrollbar { width: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #0F131A; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #374151; border-radius: 10px; }
    </style>
</head>
<body class="bg-[#0F131A] text-gray-800 flex h-screen overflow-hidden">