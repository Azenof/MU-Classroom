<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MU-Classroom Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container">
    <div class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="notes.php">Notes</a>
        <a href="materials.php">Materials</a>
        <a href="calendar.php">Due Dates</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="logout.php" style="float: right;">Logout (<?php echo htmlspecialchars($_SESSION['user_name']); ?>)</a>
        <?php else: ?>
            <a href="index.php" style="float: right;">Login</a>
        <?php endif; ?>
    </div>
