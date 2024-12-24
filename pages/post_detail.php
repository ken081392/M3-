<?php
session_start();
include '../includes/db_connection.php';
$conn = OpenCon();

// 初始化文章資料
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "SELECT posts.*, users.username 
              FROM posts 
              JOIN users ON posts.author_id = users.id 
              WHERE posts.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();

    if (!$post) {
        echo json_encode(["success" => false, "message" => "找不到文章內容！"]);
        exit();
    }
} else {
    echo json_encode(["success" => false, "message" => "無效的文章 ID！"]);
    exit();
}

// 處理文章編輯請求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_post'])) {
    $post_id = intval($_POST['post_id']);
    
    // 檢查是否為作者
    $check_author_query = "SELECT author_id FROM posts WHERE id = ?";
    $stmt = $conn->prepare($check_author_query);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();

    if ($post && $post['author_id'] == $_SESSION['user_id']) {
        header("Location: edit_post.php?id=$post_id");
        exit();
    } else {
        echo json_encode(["success" => false, "message" => "權限不足，無法編輯文章。"]);
    }
}

// 處理留言提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    if (!isset($_SESSION['user_id'])) {
        echo "請先登入後才能留言。";
        exit();
    }

    $comment_content = trim($_POST['comment_content']);
    $user_id = $_SESSION['user_id'];

    if (!empty($comment_content)) {
        $insert_query = "INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())";
        $insert_stmt = $conn->prepare($insert_query);

        if ($insert_stmt === false) {
            die("SQL 錯誤: " . $conn->error);
        }

        $insert_stmt->bind_param("iis", $id, $user_id, $comment_content);
        $insert_stmt->execute();

        if ($insert_stmt->affected_rows > 0) {
            // 留言成功，重定向到文章詳細頁
            header("Location: post_detail.php?id=$id");
            exit();
        } else {
            // 留言失敗
            echo "留言失敗，請重試。";
            exit();
        }

        $insert_stmt->close();
    } else {
        // 留言內容為空
        echo "留言內容不能為空。";
        exit();
    }
}

// 處理留言刪除
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
    $comment_id = intval($_POST['comment_id']);

    if ($comment_id <= 0) {
        echo json_encode(["success" => false, "message" => "無效的留言 ID"]);
        exit();
    }

    $check_author_query = "SELECT user_id FROM comments WHERE id = ?";
    $stmt = $conn->prepare($check_author_query);
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $comment = $result->fetch_assoc();

    if (!$comment) {
        echo json_encode(["success" => false, "message" => "留言不存在"]);
        exit();
    }

    if ($comment['user_id'] != $_SESSION['user_id']) {
        echo json_encode(["success" => false, "message" => "權限不足"]);
        exit();
    }

    $delete_query = "DELETE FROM comments WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $comment_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "留言刪除成功"]);
    } else {
        echo json_encode(["success" => false, "message" => "刪除失敗，請稍後再試"]);
    }
    exit();
}

// 處理留言通報
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_comment'])) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["success" => false, "message" => "請先登入後才能通報"]);
        exit();
    }

    $comment_id = intval($_POST['comment_id']);
    $reason = trim($_POST['reason']);
    $reporter_id = $_SESSION['user_id'];

    if (empty($reason)) {
        echo json_encode(["success" => false, "message" => "請填寫通報原因"]);
        exit();
    }

    // 新增調試資訊記錄
    error_log("準備插入通報資料：comment_id={$comment_id}, reporter_id={$reporter_id}, reason={$reason}");

    // 插入通報資料
    $insert_query = "INSERT INTO reports_comment (comment_id, user_id, reason, reported_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($insert_query);

    if (!$stmt) {
        error_log("SQL 準備失敗：" . $conn->error);
        echo json_encode(["success" => false, "message" => "系統錯誤，請稍後再試"]);
        exit();
    }

    $stmt->bind_param("iis", $comment_id, $reporter_id, $reason);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "留言通報已提交"]);
    } else {
        // 新增錯誤日誌
        error_log("通報資料插入失敗：" . $stmt->error);
        echo json_encode(["success" => false, "message" => "提交失敗，請稍後再試"]);
    }
    $stmt->close();
    exit();
}

?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文章內容</title>
    <link rel="stylesheet" href="../css/style.css">
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // 下拉選單邏輯
            const dropdowns = document.querySelectorAll(".dropdown-menu-container");
            dropdowns.forEach((dropdown) => {
                const button = dropdown.querySelector(".dropdown-button");
                button.addEventListener("click", function (e) {
                    e.stopPropagation();
                    dropdown.classList.toggle("active");
                });
            });

            document.addEventListener("click", function () {
                dropdowns.forEach((dropdown) => dropdown.classList.remove("active"));
            });

            // 文章編輯邏輯
            document.querySelectorAll(".edit-post").forEach(button => {
                button.addEventListener("click", function () {
                    console.log("跳轉到 edit_post.php");
                    // 允許默認超鏈接行為，不阻止跳轉
                });
            });

            // 留言編輯邏輯
            document.querySelectorAll(".edit-comment").forEach(button => {
                button.addEventListener("click", function (event) {
                    event.preventDefault();
                    const commentId = this.getAttribute("data-comment-id");
                    const newContent = prompt("請輸入新的留言內容：");
                    if (newContent) {
                        fetch("edit_comment.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json"
                            },
                            body: JSON.stringify({ id: commentId, content: newContent })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert("留言已更新！");
                                location.reload();
                            } else {
                                alert("留言更新失敗：" + data.error);
                            }
                        });
                    }
                });
            });

            // 留言通報邏輯
            document.addEventListener("DOMContentLoaded", function () {
            // 留言通報邏輯
            document.querySelectorAll(".report-comment-button").forEach(button => {
                button.addEventListener("click", function () {
                    const commentId = this.getAttribute("data-comment-id");
                    const reason = prompt("請輸入通報原因：");

                    if (reason) {
                        fetch("report_comments.php", {  // 發送請求到 report_comments.php
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json"
                            },
                            body: JSON.stringify({
                                report_comment: true,
                                comment_id: commentId,
                                reason: reason
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert("通報成功！");
                            } else {
                                alert("通報失敗：" + data.message);
                            }
                        })
                        .catch(error => console.error("通報請求失敗：", error));
                    }
                });
            });
        });
        });
    </script>

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
            height: 350px;
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

        textarea {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            resize: none;
            margin-top: 5px;
        }

        button {
            padding: 5px 10px;
            background-color: #0073e6;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 5px;
        }

        button:hover {
            background-color: #005bb5;
        }

        /* 外框保持原位 */
        .dropdown-menu-container {
            position: relative;
            display: inline-block;
            overflow: visible; /* 確保子元素不被裁剪 */
        }

        /* 按鈕樣式 */
        .dropdown-button {
            display: inline-block;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #333; /* 預設按鈕顏色 */
        }

        .dropdown-button:hover {
            color: #0073e6; /* 滑鼠懸停效果 */
        }

        /* 下拉選單隱藏 */
        .dropdown-menu {
            display: none; /* 預設隱藏 */
            position: absolute;
            right: 0;
            top: 30px;
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            width: 150px;
            z-index: 150; /* 層級控制 */
            padding: 10px 0;
        }

        /* 當容器有 active 類名時顯示選單 */
        .dropdown-menu-container.active .dropdown-menu {
            display: block; /* 顯示選單 */
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
            background-color: #f0f0f0; /* 滑鼠懸停背景色 */
        }

        /* 分隔線樣式 */
        .dropdown-menu hr {
            margin: 5px 0;
            border: none;
            border-top: 1px solid #ddd;
        }
    </style>

</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="user-sidebar">
            <img src="../images/<?php echo htmlspecialchars($post['avatar'] ?? 'default_avatar.png'); ?>" alt="使用者頭像">
            <div class="user-info">
                <div class="level">LV. <?php echo htmlspecialchars($post['level'] ?? 1); ?></div>
                <div class="gp">GP <?php echo htmlspecialchars($post['gp'] ?? 0); ?></div>
                <p><?php echo htmlspecialchars($post['username'] ?? ''); ?></p>
            </div>
        </div>

        <div class="content-section">
            <div class="post-content">
                <h2><?php echo htmlspecialchars($post['title']); ?></h2>
                <div class="post-header">
                    由 <strong><?php echo htmlspecialchars($post['username']); ?></strong> 發布於 <?php echo $post['created_at']; ?>
                </div>
                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="dropdown-menu-container">
                        <button class="dropdown-button">⋮</button>
                        <div class="dropdown-menu">
                            <?php if ($_SESSION['user_id'] == $post['author_id']): ?>
                                <a href="edit_post.php?id=<?php echo $post['id']; ?>" class="edit-post">編輯文章</a>
                                <a href="delete_post.php?id=<?php echo $post['id']; ?>" onclick="return confirm('確定要刪除此文章嗎？');">刪除文章</a>
                            <?php endif; ?>
                            <a href="report_post.php?id=<?php echo $post['id']; ?>" onclick="return confirm('確定要通報此文章嗎？');">通報文章</a>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
            <div class="comment-form">
                <h3>發表留言</h3>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form method="POST" action="">
                        <textarea name="comment_content" placeholder="輸入你的留言..." required></textarea>
                        <button type="submit" name="submit_comment">送出留言</button>
                    </form>
                <?php else: ?>
                    <p style="color: red;">請先登入後才能留言。</p>
                <?php endif; ?>
            </div>
            <div class="comments-section">
                <h3>留言列表</h3>
                <?php
                $comment_query = "SELECT comments.id, comments.content, comments.created_at, users.username, comments.user_id
                                FROM comments
                                JOIN users ON comments.user_id = users.id
                                WHERE comments.post_id = ?
                                ORDER BY comments.created_at DESC";
                $comment_stmt = $conn->prepare($comment_query);
                $comment_stmt->bind_param("i", $id);
                $comment_stmt->execute();
                $comments_result = $comment_stmt->get_result();

                if ($comments_result->num_rows > 0):
                    while ($comment = $comments_result->fetch_assoc()): ?>
                        <div class="comment" id="comment-<?php echo $comment['id']; ?>">
                            <strong><?php echo htmlspecialchars($comment['username']); ?>：</strong>
                            <p id="content-<?php echo $comment['id']; ?>"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                            <small><?php echo $comment['created_at']; ?></small>

                            <?php if (isset($_SESSION['user_id'])): ?>
                                <div class="dropdown-menu-container">
                                    <button class="dropdown-button">⋮</button>
                                    <div class="dropdown-menu">
                                        <!-- 通報留言：登入用戶可見 -->
                                        <a href="#" class="report-comment" data-comment-id="<?php echo $comment['id']; ?>" data-post-id="<?php echo $post['id']; ?>">通報留言</a>

                                        <!-- 編輯與刪除留言：僅限留言作者可見 -->
                                        <?php if ($_SESSION['user_id'] == $comment['user_id']): ?>
                                            <br>
                                            <a href="#" class="edit-comment" data-comment-id="<?php echo $comment['id']; ?>">編輯留言</a>
                                            <a href="#" class="delete-comment" data-comment-id="<?php echo $comment['id']; ?>" data-post-id="<?php echo $post['id']; ?>">刪除留言</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>
                    <?php endwhile;
                else: ?>
                    <p>目前沒有留言，成為第一個留言的人吧！</p>
                <?php endif;

                $comment_stmt->close();
                ?>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
