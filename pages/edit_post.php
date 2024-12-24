<?php
session_start();
include '../includes/db_connection.php';
$conn = OpenCon();

// 確認用戶是否登入
if (!isset($_SESSION['user_id'])) {
    die("請先登入後才能編輯文章。");
}

// 確認文章 ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("無效的文章 ID。");
}

$post_id = intval($_GET['id']);

// 獲取文章資訊，並檢查當前用戶是否為作者
$query = "SELECT * FROM posts WHERE id = ? AND author_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $post_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("您無權編輯此文章。");
}

$post = $result->fetch_assoc();

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    // 驗證 CSRF Token
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF 驗證失敗。");
    }

    if (!empty($title) && !empty($content)) {
        $update_query = "UPDATE posts SET title = ?, content = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ssi", $title, $content, $post_id);

        if ($update_stmt->execute()) {
            echo "文章更新成功！";
            header("Location: post_detail.php?id=" . $post_id);
            exit;
        } else {
            echo "文章更新失敗，請重試。";
        }
    } else {
        if (empty($title)) echo "標題不能為空。";
        if (empty($content)) echo "內容不能為空。";
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>編輯文章</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .container {
            width: 90%;
            max-width: 500px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .form-input,
        .form-textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .form-textarea {
            height: 150px;
            resize: none;
        }

        .form-button {
            padding: 10px 20px;
            background-color: #0073e6;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .form-button:hover {
            background-color: #005bb5;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container">
        <h1>編輯文章</h1>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <label for="title">標題</label>
            <input type="text" name="title" id="title" class="form-input" value="<?php echo htmlspecialchars($post['title']); ?>" required>
            
            <label for="content">內容</label>
            <textarea name="content" id="content" class="form-textarea" required><?php echo htmlspecialchars($post['content']); ?></textarea>
            
            <button type="submit" class="form-button">保存修改</button>
        </form>
    </div>
    <?php include '../includes/footer.php'; ?>
</body>
</html>
