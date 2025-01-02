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
$stmt = $conn->prepare("SELECT username, email, join_date, bio, role FROM users WHERE id = ?");
if (!$stmt) {
    die("SQL 語句準備失敗: " . $conn->error); // 顯示具體的 SQL 錯誤
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $email, $join_date, $bio, $role);
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
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        /* 為外層容器設定樣式 */
.user-info-container {
    width: 90%;
    max-width: 600px;
    margin: 20px auto;
    padding: 20px;
    background-color: #f9f9f9; /* 淺灰背景 */
    border: 1px solid #d3d3d3;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    font-family: Arial, sans-serif;
    color: #333; /* 字體顏色 */
}

/* 標題樣式 */
.user-info-container h2 {
    text-align: center;
    color: #0056b3; /* 深藍色 */
    font-size: 1.8rem;
    margin-bottom: 20px;
}

/* 單項資訊的樣式 */
.user-info-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #e6e6e6;
}

.user-info-item:last-child {
    border-bottom: none; /* 移除最後一項的底線 */
}

.user-info-item label {
    font-weight: bold;
    color: #0056b3; /* 深藍色 */
}

.user-info-item span {
    color: #555; /* 灰色文字 */
}

/* 編輯按鈕樣式 */
.edit-btn {
    display: block;
    margin: 20px auto;
    padding: 10px 20px;
    text-align: center;
    background-color: #007bff; /* 藍色背景 */
    color: #fff;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
    transition: background-color 0.3s ease;
}

.edit-btn:hover {
    background-color: #0056b3; /* 深藍色背景 */
}

/* 特殊連結樣式 */
.special-link {
    display: block;
    margin: 10px auto;
    text-align: center;
    color: #dc3545; /* 紅色 */
    font-weight: bold;
    text-decoration: none;
    padding: 10px 0;
    transition: color 0.3s ease;
}

.special-link:hover {
    color: #a71d2a; /* 深紅色 */
}

    </style>
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

        <!-- 只有管理員和超級管理員才能看到的超連結 -->
        <?php if ($role == 'admin' || $role == 'super_admin'): ?>
            <a href="../admin/admin_dashboard.php" class="special-link">管理員專用連結</a>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
