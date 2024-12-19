<?php
session_start(); // 啟動 Session

// 包含資料庫連接檔案
include '../includes/db_connection.php';

// 使用 OpenCon 函數連接資料庫
$conn = OpenCon();

// 檢查用戶是否已登入
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 檢查表單是否被提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = htmlspecialchars($_POST['title'], ENT_QUOTES, 'UTF-8');
    $content = htmlspecialchars($_POST['content'], ENT_QUOTES, 'UTF-8');
    $category = intval($_POST['category']); // 獲取發文類別
    $question_type = htmlspecialchars($_POST['question-type'], ENT_QUOTES, 'UTF-8');
    $author_id = $_SESSION['user_id']; // 使用當前登入用戶的 ID 作為作者

    // 插入文章到資料庫
    $stmt = $conn->prepare("INSERT INTO posts (title, content, category, question_type, author_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $title, $content, $category, $question_type, $author_id);

    if ($stmt->execute()) {
        // 發佈成功後重定向到首頁
        header("Location: /pages/index.php");
        exit();
    } else {
        echo "<p>發佈文章時發生錯誤。</p>";
    }
    $stmt->close();
}

$sql = "SELECT id, name FROM categories";
$result = $conn->query($sql);

// 關閉資料庫連接
CloseCon($conn);
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
    const titleInput = document.getElementById('title');
    const contentInput = document.getElementById('content');
    const submitBtn = document.querySelector('.submit-btn');
    const errorMessage = document.getElementById('error-message');

    if (titleInput && contentInput && submitBtn && errorMessage) {
        function checkFields() {
            if (titleInput.value.trim() === '' || contentInput.value.trim() === '') {
                submitBtn.disabled = true;
                errorMessage.style.display = 'block';
            } else {
                submitBtn.disabled = false;
                errorMessage.style.display = 'none';
            }
        }

        titleInput.addEventListener('input', checkFields);
        contentInput.addEventListener('input', checkFields);
        checkFields();
    } else {
        console.error('Some form elements are missing. Please check your HTML structure.');
    }
});
    </script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="main-content">
        <div class="left-section">
            <!-- 左側內容，例如圖片或文字 -->
            <img src="path/to/image1.jpg" alt="左側圖片" style="max-width: 100%;">
            <p>左側展示內容</p>
        </div>
        
        <div class="container">
    <form action="create_post.php" method="POST">
        <h2>發佈文章</h2>
        <!-- 動態生成分類選單 -->
        <select name="category" class="category-select" required>
            <option value="">選擇發文類別</option>
            <?php
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . '</option>';
                }
            } else {
                echo '<option value="">無分類可供選擇</option>';
            }
            ?>
        </select>

        <!-- 問題類型選單 -->
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

        <!-- 標題與內容 -->
        <input type="text" name="title" id="title" class="form-input" placeholder="輸入文章標題" required>
        <textarea name="content" id="content" class="form-textarea" placeholder="輸入文章內容" required></textarea>

        <!-- 提交按鈕 -->
        <div class="form-actions">
            <button type="submit" class="submit-btn">提交文章</button>
            <button type="button" class="btn-template" onclick="window.history.back();">取消</button>
        </div>
    </form>
</div>

        
        <div class="right-section">
            <!-- 右側內容，例如圖片或文字 -->
            <img src="path/to/image2.jpg" alt="右側圖片" style="max-width: 100%;">
            <p>右側展示內容</p>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

</body>
</html>
