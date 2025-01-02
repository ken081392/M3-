<?php
session_start(); // 開啟會話

// 生成唯一令牌（若尚未存在）
if (empty($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32)); // 32 字節的隨機字串
}

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
    // 驗證表單令牌
    if (!isset($_POST['form_token']) || $_POST['form_token'] !== $_SESSION['form_token']) {
        die("非法表單提交。");
    }
    // 清除令牌以防止重複提交
    unset($_SESSION['form_token']);

    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['password']); // 刪除特殊符號
    $confirm_password = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['confirm_password']); // 同步刪除特殊符號
    $birthdate = $_POST['birthdate'];
    $gender = $_POST['gender']; // 性別欄位

    // 密碼強度檢查
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) { // 不再檢查特殊符號
        $error_message = "密碼必須至少包含8個字符，且包含大寫字母、小寫字母和數字。";
    } elseif ($password !== $confirm_password) {
        $error_message = "密碼和確認密碼不一致。";
    } else {
        $hashed_password = password_hash($password, PASSWORD_ARGON2ID); // 使用更安全的 Argon2 加密

        // 檢查用戶名或 Email 是否已經存在
        $check_user = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        if ($check_user) {
            $check_user->bind_param("ss", $username, $email);
            $check_user->execute();
            $check_user->store_result();
            
            if ($check_user->num_rows > 0) {
                $error_message = "用戶名或 Email 已經被註冊。";
            } else {
                // 插入新用戶，包括性別欄位
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, birthdate, gender) VALUES (?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("sssss", $username, $email, $hashed_password, $birthdate, $gender);

                    if ($stmt->execute()) {
                        echo "<script>
                            alert('註冊成功！按下確認將跳轉到登入頁面。');
                            window.location.href = '/pages/login.php';
                        </script>";
                        exit;
                    } else {
                        $error_message = "註冊失敗，請重試。";
                    }
                    $stmt->close();
                } else {
                    $error_message = "資料庫錯誤：無法準備插入語句。";
                }
            }
            $check_user->close();
        } else {
            $error_message = "資料庫錯誤：無法準備檢查語句。";
        }
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

    <style>
        /* 性別圖片樣式 */
        .gender-photo {
            text-align: center;
            margin-top: 10px;
        }

        .gender-photo img {
            display: block;
            margin: 0 auto;
            border-radius: 50%; /* 圓形圖片 */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100px;
            height: 100px;
            border: 1px solid #ddd;
        }
    </style>

    <script>
        // 更新性別圖片的函式
        function updateGenderImage() {
            var gender = document.getElementById("gender").value;
            var image = document.getElementById("gender-image");

            if (gender === "male") {
                image.src = "../assets/img/male.png"; // 男性圖片
            } else if (gender === "female") {
                image.src = "../assets/img/female.png"; // 女性圖片
            } else {
                image.src = "images/default.png"; // 其他性別圖片
            }
        }

        // 動態刪除密碼中的特殊符號
        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById("password").addEventListener("input", function () {
                this.value = this.value.replace(/[^a-zA-Z0-9]/g, ""); // 僅保留字母和數字
            });

            document.getElementById("confirm_password").addEventListener("input", function () {
                this.value = this.value.replace(/[^a-zA-Z0-9]/g, ""); // 同步刪除特殊符號
            });
        });

        // 驗證表單的函式
        function validateForm() {
            var password = document.getElementById("password").value;
            var confirmPassword = document.getElementById("confirm_password").value;
            var passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;

            if (!passwordPattern.test(password)) {
                alert("密碼必須至少包含8個字符，且包含大寫字母、小寫字母和數字。");
                return false;
            }
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
            <div class="error" style="color: red; font-weight: bold;">
                <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" onsubmit="return validateForm();">
            <!-- 隱藏的唯一令牌 -->
            <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">

            <label for="username">用戶名:</label>
            <input type="text" id="username" name="username" required><br>

            <div class="form-group">
                <label for="gender" class="form-label">性別:</label>
                <div class="custom-select">
                    <select id="gender" name="gender" required onchange="updateGenderImage()">
                        <option value="male" selected>男</option>
                        <option value="female">女</option>
                        <option value="other">其他</option>
                    </select>
                </div>
                <div class="gender-photo">
                    <img id="gender-image" src="../assets/img/male.png" alt="性別圖片">
                </div>
            </div>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required><br>

            <label for="password">密碼:</label>
            <input type="password" id="password" name="password" required>
            <p style="font-size: 0.9em; color: gray;">密碼必須至少包含8個字符，包括大寫字母、小寫字母和數字。</p><br>

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

