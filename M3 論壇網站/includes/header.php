<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/header.css">
    <title>My Forum</title>
</head>
<body>
    <header class="header">
        <div class="logo-title">
            <a href="index.php"><img src="../assets/img/logo.png" alt="Logo"></a>
            <h1><a href="index.php">M3論壇網站</a></h1>
        </div>
        <div class="center-search">
            <form action="search.php" method="GET" class="search-form">
                <input type="text" name="query" class="search-input" placeholder="搜尋文章...">
                <button type="submit" class="search-button">搜尋</button>
            </form>
        </div>
        <div class="user-menu">
            <?php if (isset($_SESSION['username'])): ?>
                <span>歡迎，<?php echo $_SESSION['username']; ?></span>
                <a href="profile.php" class="profile-link">個人資料</a>
                <a href="logout.php" class="logout-link">登出</a>
            <?php else: ?>
                <a href="login.php" class="login-link">登入</a>
                <a href="register.php" class="register-link">註冊</a>
            <?php endif; ?>
        </div>
    </header>
