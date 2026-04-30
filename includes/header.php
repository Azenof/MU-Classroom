<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MU-Classroom Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-toggle" id="sidebar-toggle">
            <span>‹</span>
        </div>
        <div class="sidebar-header">
            <a href="dashboard.php" class="logo-link">
                <img src="assets/images/logo.png" alt="MU-Logo">
            </a>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <span class="nav-icon">📊</span> <span class="nav-text">Dashboard</span>
            </a>
            <a href="notes.php" class="<?php echo ($current_page == 'notes.php') ? 'active' : ''; ?>">
                <span class="nav-icon">📝</span> <span class="nav-text">Notes</span>
            </a>
            <a href="materials.php" class="<?php echo ($current_page == 'materials.php') ? 'active' : ''; ?>">
                <span class="nav-icon">📚</span> <span class="nav-text">Materials</span>
            </a>
            <a href="past_questions.php" class="<?php echo ($current_page == 'past_questions.php') ? 'active' : ''; ?>">
                <span class="nav-icon">📸</span> <span class="nav-text">Archive</span>
            </a>
            <a href="calendar.php" class="<?php echo ($current_page == 'calendar.php') ? 'active' : ''; ?>">
                <span class="nav-icon">⏳</span> <span class="nav-text">Due Dates</span>
            </a>
            <a href="cgpa.php" class="<?php echo ($current_page == 'cgpa.php') ? 'active' : ''; ?>">
                <span class="nav-icon">📈</span> <span class="nav-text">CGPA Calc</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="logout.php" class="logout">
                    <span class="nav-icon">🚪</span> <span class="nav-text">Logout</span>
                </a>
            <?php else: ?>
                <a href="index.php" class="logout" style="color: #28a745 !important; background: rgba(40, 167, 69, 0.1);">
                    <span class="nav-icon">🔑</span> <span class="nav-text">Login</span>
                </a>
            <?php endif; ?>
        </div>
    </aside>
    <main class="main-content">
        <div class="container">
