<?php
session_start(); // 啟動 Session

// 檢查用戶是否已登入
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // 如果未登入，重定向到登入頁面
    exit;
}

// 連接到資料庫
$conn = new mysqli("localhost", "root", "", "forum");

if ($conn->connect_error) {
    die("資料庫連接失敗: " . $conn->connect_error);
}

// 檢查表單是否被提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $category = $_POST['category']; // 獲取發文類別
    $question_type = $_POST['question-type']; // 獲取問題類型
    $author_id = $_SESSION['user_id']; // 使用當前登入用戶的 ID 作為作者

    // 插入文章到資料庫
    $stmt = $conn->prepare("INSERT INTO posts (title, content, category, question_type, author_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $title, $content, $category, $question_type, $author_id);

    if ($stmt->execute()) {
        // 發佈成功後重定向到首頁
        header("Location: /pages/index.php");
        exit();
    } else {
        echo "發佈文章時發生錯誤。";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>發佈文章</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/post.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/form.css">
    <link rel="stylesheet" href="../css/buttons.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 取得標題、內容輸入框和發佈按鈕
            const titleInput = document.getElementById('title');
            const contentInput = document.getElementById('content');
            const submitBtn = document.querySelector('.submit-btn');
            const errorMessage = document.getElementById('error-message');

            // 定義檢查輸入框是否填寫的函數
            function checkFields() {
                if (titleInput.value.trim() === '' || contentInput.value.trim() === '') {
                    submitBtn.disabled = true;  // 禁用按鈕
                    errorMessage.style.display = 'block';  // 顯示錯誤提示
                } else {
                    submitBtn.disabled = false;  // 啟用按鈕
                    errorMessage.style.display = 'none';  // 隱藏錯誤提示
                }
            }

            // 綁定輸入事件到標題和內容輸入框上
            titleInput.addEventListener('input', checkFields);
            contentInput.addEventListener('input', checkFields);

            // 初始狀態檢查
            checkFields();
        });
    </script>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <form action="create_post.php" method="POST">
        <div class="post-options">
            <select name="category" class="category-select" required>
                <option value="">選擇發文類別</option>
                <option value="1">性別平等</option>
                <option value="2">遊戲專區</option>
            </select>

            <select name="question-type" class="question-select">
                <option value="type0">問題</option>
                <option value="type1">情報</option>
                <option value="type2">心得</option>
                <option value="type3">討論</option>
                <option value="type4">攻略</option>
                <option value="type5">密技</option>
                <option value="type6">閒聊</option>
                <option value="type7">其他</option>
            </select>
        </div>

        <input id="title" type="text" name="title" placeholder="請輸入文章標題..." class="title-input" required>
        <textarea id="content" name="content" placeholder="請輸入文章內容..." class="content-area" required></textarea>

        <p id="error-message" style="color: red; display: none;">請填寫標題和內容！</p>

        <button type="submit" class="submit-btn" disabled>發佈文章</button>
    </form>

    <?php include '../includes/footer.php'; ?>

</body>
</html>
