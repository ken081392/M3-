<?php
session_start(); // 啟動 Session

// 檢查用戶是否已登入
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // 如果未登入，重定向到登入頁面
    exit;
}

// 連接到資料庫
$conn = new mysqli("localhost", "root", "", "forum");

// 從 Session 取得當前登入的用戶 ID
$user_id = $_SESSION['user_id'];




// 從資料庫中獲取用戶資訊
$stmt = $conn->prepare("SELECT username, email, join_date, bio FROM users WHERE id = ?");
if (!$stmt) {
    die("SQL 語句準備失敗: " . $conn->error); // 顯示具體的 SQL 錯誤
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $email, $join_date, $bio);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $username; ?> 的用戶資訊</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <!-- 用戶資訊容器 -->
    <div class="user-info-container">
        <h2><?php echo $username; ?> 的個人資訊</h2>

        <div class="user-info-item">
            <label>用戶名:</label>
            <span><?php echo $username; ?></span>
        </div>

        <div class="user-info-item">
            <label>Email:</label>
            <span><?php echo $email; ?></span>
        </div>

        <div class="user-info-item">
            <label>註冊日期:</label>
            <span><?php echo $join_date; ?></span>
        </div>

        <div class="user-info-item">
            <label>個人簡介:</label>
            <span><?php echo $bio; ?></span>
        </div>

        <a href="edit-profile.php" class="edit-btn">編輯個人資料</a>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
