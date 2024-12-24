<?php
session_start();
include '../includes/db_connection.php';
$conn = OpenCon();

$category = null;
$postsResult = null;

if (isset($_GET['type']) && !empty($_GET['type'])) {
    $slug = htmlspecialchars($_GET['type'], ENT_QUOTES, 'UTF-8');

    // 查詢分類
    $sql = "SELECT id, name, description, image_path FROM categories WHERE slug = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("SQL Error: " . $conn->error);
    }

    $stmt->bind_param("s", $slug);
    if (!$stmt->execute()) {
        die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $category = $result->fetch_assoc();

        // 查詢該分類中的文章
        $postQuery = "SELECT posts.*, users.username 
                      FROM posts 
                      INNER JOIN users ON posts.author_id = users.id 
                      WHERE posts.category = ?";
        $postStmt = $conn->prepare($postQuery);

        if ($postStmt === false) {
            die("SQL Error: " . $conn->error);
        }

        $postStmt->bind_param("i", $category['id']);
        if (!$postStmt->execute()) {
            die("Execute failed: (" . $postStmt->errno . ") " . $postStmt->error);
        }

        $postsResult = $postStmt->get_result();
    } else {
        echo "分類不存在。";
    }
} else {
    echo "分類類型未指定或無效。";
}

CloseCon($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['name'] ?? '分類頁面'); ?> - 分類頁面</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/post.css">
    <style>
        .main-content {
            display: block; /* 移除 flex 布局，改回區塊排列 */
            padding: 0; /* 移除 padding，確保圖片無邊距 */
            background-color: #f5f5f5;
        }

        .category-image-banner {
            width: 100%; /* 寬度填滿父容器 */
            height: 400px; /* 設定固定高度 */
            overflow: hidden; /* 隱藏超出容器的內容 */
            display: flex; /* 使用 flex 水平與垂直置中 */
            justify-content: center;
            align-items: center;
            margin: 0; /* 移除多餘的外邊距 */
            padding: 0; /* 移除內邊距 */
        }

        .category-image-banner img {
            width: auto; /* 根據原始比例顯示圖片 */
            height: 100%; /* 高度填滿容器 */
            object-fit: contain; /* 完整顯示圖片，不會裁剪 */
            display: block; /* 移除圖片底部的空白 */
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="main-content">
        <?php if ($category): ?>
            <!-- 顯示分類圖片，像橫幅一樣放在文章列表上方 -->
            <?php if (!empty($category['image_path'])): ?>
                <div class="category-image-banner">
                    <img src="../images/<?php echo htmlspecialchars($category['image_path']); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" class="category-image">
                </div>
            <?php endif; ?>

            <!-- 顯示文章列表 -->
            <div class="posts-section">
                <div class="header-posts">
                    <h2>文章</h2>
                    <div class="create">
                        <a href="../pages/create_post.php">發布</a>
                    </div>
                </div>
                <?php if ($postsResult && $postsResult->num_rows > 0): ?>
                    <?php while ($post = $postsResult->fetch_assoc()): ?>
                        <div class="post">
                            <a href="post_detail.php?id=<?php echo $post['id']; ?>" class="post-link">
                                <?php
                                // 根據 question_type 顯示相應的文字
                                switch ($post['question_type']) {
                                    case 'type0':
                                        $question_type_text = '[問題]';
                                        break;
                                    case 'type1':
                                        $question_type_text = '[情報]';
                                        break;
                                    case 'type2':
                                        $question_type_text = '[心得]';
                                        break;
                                    case 'type3':
                                        $question_type_text = '[討論]';
                                        break;
                                    case 'type4':
                                        $question_type_text = '[攻略]';
                                        break;
                                    case 'type5':
                                        $question_type_text = '[密技]';
                                        break;
                                    case 'type6':
                                        $question_type_text = '[閒聊]';
                                        break;
                                    case 'type7':
                                        $question_type_text = '[其他]';
                                        break;
                                    default:
                                        $question_type_text = '[未知類型]';
                                }
                                ?>
                                <h3><?php echo htmlspecialchars($question_type_text); ?></h3>
                                <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                                <p><?php echo mb_substr(htmlspecialchars($post['content']), 0, 100); ?>...</p>
                            </a>
                            <span>由 <?php echo htmlspecialchars($post['username']); ?> 發布於 <?php echo $post['created_at']; ?></span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>此分類暫無文章。</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>分類不存在或未指定。</p>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
