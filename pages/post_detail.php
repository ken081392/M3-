<?php
session_start();
include '../includes/db_connection.php';
$conn = OpenCon();

// 檢查文章 ID 是否有效
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);

    // 查詢文章資訊
    $query = "SELECT posts.*, users.username, users.avatar, users.level, users.gp 
              FROM posts 
              JOIN users ON posts.author_id = users.id 
              WHERE posts.id = ?";
    $stmt = $conn->prepare($query);

    if ($stmt === false) {
        die("SQL 錯誤: " . $conn->error);
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();

    if ($post) {
        // 確定是否為作者
        $is_author = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['author_id'];
    } else {
        die("找不到文章。");
    }
} else {
    die("無效的文章 ID。");
}

// 處理留言提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    // 確認用戶是否登入
    if (!isset($_SESSION['user_id'])) {
        echo "<p style='color: red;'>請先登入後才能留言。</p>";
        exit();
    }

    // 接收表單資料
    $comment_content = trim($_POST['comment_content']);
    $user_id = $_SESSION['user_id'];

    // 驗證留言內容
    if (!empty($comment_content)) {
        // 插入留言到資料庫
        $insert_query = "INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())";
        $insert_stmt = $conn->prepare($insert_query);

        if ($insert_stmt === false) {
            die("SQL 錯誤: " . $conn->error);
        }

        $insert_stmt->bind_param("iis", $id, $user_id, $comment_content);
        $insert_stmt->execute();

        if ($insert_stmt->affected_rows > 0) {
            // 成功後重新導向避免重複提交
            header("Location: post_detail.php?id=$id");
            exit();
        } else {
            echo "<p style='color: red;'>留言失敗，請重試。</p>";
        }

        $insert_stmt->close();
    } else {
        echo "<p style='color: red;'>留言內容不能為空。</p>";
    }
}

?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文章內容</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }

        /* 外框 - Flexbox */
        .container {
            display: flex;
            margin: 20px auto;
            width: 80%;
            max-width: 1200px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* 左側使用者資訊 */
        .user-sidebar {
            width: 25%;
            background-color: #e6f7ff;
            text-align: center;
            padding: 20px;
            box-shadow: 1px 0 3px rgba(0, 0, 0, 0.1);
        }

        .user-sidebar img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 10px;
        }

        .user-info {
            font-size: 14px;
            color: #333;
        }

        .level, .gp {
            margin: 5px 0;
            color: #0073e6;
            font-weight: bold;
        }

        /* 右側主要內容 */
        .content-section {
            flex: 1;
            padding: 20px;
        }

        .post-content {
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 20px;
        }

        .post-content h2 {
            margin-bottom: 10px;
            color: #333;
        }

        .post-header {
            font-size: 14px;
            color: #666;
        }

        /* 留言表單 */
        .comment-form {
            width: 100%; /* 留言表單的寬度可根據需求調整 */
            max-width: 600px; /* 可設置最大寬度 */
            margin-bottom: 20px;
        }

        .comment-form textarea {
            width: 100%; /* 讓文本框的寬度自適應表單 */
            height: 40px; /* 固定高度 */
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            resize: none; /* 禁止用戶調整大小 */
        }

        .comment-form button {
            padding: 10px 20px;
            background-color: #0073e6;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .comment-form button:hover {
            background-color: #005bb5;
        }

        /* 留言列表 */
        .comments-section {
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }

        .comment {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .comment strong {
            color: #0073e6;
        }

        .comment small {
            color: #999;
        }

        /* 選單容器樣式 */
        .dropdown-menu-container {
            position: relative;
            display: inline-block;
        }

        /* 觸發按鈕樣式 */
        .dropdown-button {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
        }

        /* 選單內容樣式 */
        .dropdown-menu {
            display: none; /* 預設隱藏 */
            position: absolute;
            right: 0; /* 選單靠右對齊 */
            top: 30px; /* 與按鈕的間隔 */
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            width: 150px;
            z-index: 100;
            padding: 10px 0;
        }

        /* 選單項目樣式 */
        .dropdown-menu a {
            display: block;
            padding: 10px 20px;
            text-decoration: none;
            color: #333;
            font-size: 14px;
        }

        .dropdown-menu a:hover {
            background-color: #f0f0f0;
        }

        /* 分隔線樣式 */
        .dropdown-menu hr {
            margin: 5px 0;
            border: none;
            border-top: 1px solid #ddd;
        }

        /* 滑鼠懸停時顯示選單 */
        .dropdown-menu-container:hover .dropdown-menu {
            display: block; /* 顯示選單 */
        }

    </style>

</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <!-- 左側使用者資訊 -->
        <div class="user-sidebar">
            <img src="../images/<?php echo htmlspecialchars($post['avatar'] ?? 'default_avatar.png'); ?>" alt="使用者頭像">
            <div class="user-info">
                <div class="level">LV. <?php echo htmlspecialchars($post['level'] ?? 1); ?></div>
                <div class="gp">GP <?php echo htmlspecialchars($post['gp'] ?? 0); ?></div>
                <p><?php echo htmlspecialchars($post['username'] ?? ''); ?></p>
            </div>
        </div>

        <!-- 右側內容區域 -->
        <div class="content-section">
            <div class="post-content">
                <h2><?php echo htmlspecialchars($post['title']); ?></h2>
                <div class="post-header">
                    由 <strong><?php echo htmlspecialchars($post['username']); ?></strong> 發布於 <?php echo $post['created_at']; ?>
                </div>
                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>

                <!-- 下拉選單 -->
                <div class="dropdown-menu-container">
                    <button class="dropdown-button">⋮</button>
                    <div class="dropdown-menu">
                        <a href="#">檢舉文章</a>
                        
                        <?php if ($is_author): ?>
                            <hr>
                            <a href="edit_post.php?id=<?php echo $post['id']; ?>">編輯文章</a>
                            <a href="delete_post.php?id=<?php echo $post['id']; ?>" onclick="return confirm('確定要刪除這篇文章嗎？');">刪除文章</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
                <!-- 留言表單 -->
                <div class="comment-form">
                    <h3>發表留言</h3>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form method="POST" action="">
                            <textarea name="comment_content" placeholder="輸入你的留言..." required></textarea>
                            <button type="submit" name="submit_comment">送出留言</button>
                        </form>
                    <?php else: ?>
                        <p style="color: red;">請先 <a href="../pages/login.php">登入</a> 才能留言。</p>
                    <?php endif; ?>
                </div>

                <!-- 留言列表 -->
                <div class="comments-section">
                    <h3>留言列表</h3>
                    <?php
                    $comment_query = "SELECT comments.content, comments.created_at, users.username
                                    FROM comments
                                    JOIN users ON comments.user_id = users.id
                                    WHERE comments.post_id = ?
                                    ORDER BY comments.created_at DESC";
                    $comment_stmt = $conn->prepare($comment_query);
                    $comment_stmt->bind_param("i", $id);
                    $comment_stmt->execute();
                    $comments_result = $comment_stmt->get_result();

                    if ($comments_result->num_rows > 0): ?>
                        <?php while ($comment = $comments_result->fetch_assoc()): ?>
                            <div class="comment">
                                <strong><?php echo htmlspecialchars($comment['username']); ?>：</strong>
                                <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                <small><?php echo $comment['created_at']; ?></small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>目前沒有留言，成為第一個留言的人吧！</p>
                    <?php endif;

                    $comment_stmt->close();
                    ?>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>

<?php CloseCon($conn); ?>
