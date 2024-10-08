<?php
session_start();
// 包含資料庫連接檔案
include '../includes/db_connection.php';
$conn = OpenCon(); // 開啟資料庫連接

// 查詢最新的文章
$query = "
    SELECT posts.*, users.username 
    FROM posts 
    INNER JOIN users ON posts.author_id = users.id 
    ORDER BY posts.created_at DESC 
    LIMIT 10
";
$result = $conn->query($query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M3論壇網站</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/post.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>  

    <div class="main-content">
        <!-- 分類區塊 -->
        <div class="category-section">
            <h2>分類</h2>
            <ul>
                <li><a href="#">性別平等</a></li>
                <li><a href="#">遊戲專區</a></li>
            </ul>
        </div>

        <!-- 發佈的文章區塊，合併最新文章 -->
        <div class="posts-section">
            <h2>發佈的文章</h2>
            <?php
            if ($result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
            ?>
                <div class="post">
                    <a href="post_detail.php?id=<?php echo $row['id']; ?>" class="post-link">
                        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                        <p><?php echo mb_substr(htmlspecialchars($row['content']), 0, 100); ?>...</p>
                    </a>
                    <span>由 <?php echo htmlspecialchars($row['username']); ?> 發布於 <?php echo $row['created_at']; ?></span>
                </div>
            <?php
                endwhile;
            else:
                echo "<p>目前還沒有文章。</p>";
            endif;
            ?>
        </div>

        <div class="latest-posts-section">
            <h2>最新文章</h2>
            <ul>
                <li><a href="#">最新文章 1</a></li>
                <li><a href="#">最新文章 2</a></li>
                <li><a href="#">最新文章 3</a></li>
            </ul>
        </div>
    </div>

    <a href="../pages/create_post.php">發布</a> <!-- 發布文章按鈕 -->

    <?php include '../includes/footer.php'; ?>
    <?php
    CloseCon($conn); // 關閉資料庫連接
    ?>
</body>
</html>
