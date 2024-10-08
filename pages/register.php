<?php
// 連接資料庫的基本信息
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "forum";

$conn = new mysqli($servername, $username, $password, $dbname);

// 檢查資料庫連接是否成功
if ($conn->connect_error) {
    die("資料庫連接失敗: " . $conn->connect_error);
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $birthdate = $_POST['birthdate'];

    if ($password !== $confirm_password) {
        $error_message = "密碼和確認密碼不一致。";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $check_user = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check_user->bind_param("ss", $username, $email);
        $check_user->execute();
        $check_user->store_result();
        
        if ($check_user->num_rows > 0) {
            $error_message = "用戶名或Email已經被註冊";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, birthdate) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $hashed_password, $birthdate);

            if ($stmt->execute()) {
                echo "註冊成功！";
            } else {
                $error_message = "註冊失敗，請重試。";
            }
        }

        $check_user->close();
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>註冊</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/form.css">

    <script>
        function validateForm() {
            var password = document.getElementById("password").value;
            var confirmPassword = document.getElementById("confirm_password").value;
            if (password !== confirmPassword) {
                alert("密碼與確認密碼不一致，請重新輸入。");
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container">
        <h2>用戶註冊</h2>
        
        <!-- 顯示錯誤信息 -->
        <?php if (!empty($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST" onsubmit="return validateForm()">
            <label for="username">用戶名:</label>
            <input type="text" id="username" name="username" required><br>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required><br>

            <label for="password">密碼:</label>
            <input type="password" id="password" name="password" required><br>

            <label for="confirm_password">確認密碼:</label>
            <input type="password" id="confirm_password" name="confirm_password" required><br>

            <label for="birthdate">出生年月日:</label>
            <input type="date" id="birthdate" name="birthdate" required><br>

            <input type="submit" value="註冊">
        </form>

        <a href="/pages/login.php" class="return-link">返回登入</a>
    </div>
    <?php include '../includes/footer.php'; ?>
</body>
</html>
