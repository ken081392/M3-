<?php
session_start(); // 開啟 Session

// 連接資料庫
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "forum";

$conn = new mysqli($servername, $username, $password, $dbname);

// 檢查連接是否成功
if ($conn->connect_error) {
    die("資料庫連接失敗: " . $conn->connect_error);
}

$error_message = ''; // 儲存錯誤訊息的變數

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username']; // 獲取用戶名
    $password = $_POST['password']; // 獲取密碼

    // 查詢資料庫中的用戶資料
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    // 將用戶名綁定到 SQL 查詢語句中
    $stmt->bind_param("s", $username);
    $stmt->execute(); // 執行查詢
    $stmt->store_result(); // 儲存查詢結果
    
    if ($stmt->num_rows > 0) {
        // 如果查詢有結果，取出資料庫中的密碼
        $stmt->bind_result($id, $hashed_password);
        $stmt->fetch();

        // 驗證用戶輸入的密碼是否與資料庫中的加密密碼匹配
        if (password_verify($password, $hashed_password)) {
            // 如果密碼正確，開始 Session 並保存用戶狀態
            $_SESSION['user_id'] = $id; // 儲存用戶ID到 Session 中
            $_SESSION['username'] = $username; // 儲存用戶名到 Session 中
            // 這是假設用戶從資料庫中讀取了 role 值
            $_SESSION['role'] = $role; // 儲存用戶角色到 Session 中


            // 登入成功後重定向到用戶個人頁面
            header("Location: profile.php");
            exit;
        } else {
            // 如果密碼錯誤，儲存錯誤訊息
            $error_message = "密碼錯誤！";
        }
    } else {
        // 如果用戶名不存在，儲存錯誤訊息
        $error_message = "用戶不存在！";
    }
    
    $stmt->close();
}



$conn->close(); // 關閉資料庫連接
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登入</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/form.css">

</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h2>會員登入</h2>

        <!-- 顯示錯誤訊息 -->
        <?php if (!empty($error_message)): ?>
            <div class="error-message" style="color:red;">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- 登入表單 -->
        <form action="login.php" method="POST">
            <!-- 用戶名輸入框 -->
            <label for="username">用戶名:</label>
            <input type="text" id="username" name="username" required><br>

            <!-- 密碼輸入框 -->
            <label for="password">密碼:</label>
            <input type="password" id="password" name="password" required><br>

            <!-- 提交按鈕 -->
            <input type="submit" value="登入">
            
        </form>
        <a href="/pages/register.php" class="return-link">尚未註冊嗎？</a>
    </div>
    <?php include '../includes/footer.php'; ?>
</body>
</html>