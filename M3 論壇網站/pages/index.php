<?php
session_start();
// 連接資料庫
include '../includes/db_connection.php'; // 包含資料庫連接檔案
$conn = OpenCon(); // 開啟資料庫連接

// 查詢最新的文章
$query = "SELECT * FROM posts ORDER BY created_at DESC LIMIT 10"; // 獲取最新的10篇文章
$result = $conn->query($query);

if ($result->num_rows > 0) {
    echo "<div class='posts-section'>";
    echo "<h2>最新文章</h2>";
    while ($row = $result->fetch_assoc()) {
        echo "<div class='post'>";
        echo "<h3>" . htmlspecialchars($row['title']) . "</h3>";
        echo "<p>" . htmlspecialchars($row['content']) . "</p>";
        echo "</div>";
    }
    echo "</div>";
} else {
    echo "";
}
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
<body>
    <?php include '../includes/header.php'; ?>    
    <div class="main-content">
        <div class="category-section">
            <h2>分類</h2>
            <ul>
                <li><a href="#">性別平等</a></li>
                <li><a href="#">遊戲專區</a></li>
            </ul>
        </div>

        <div class="posts-section">
            <h2>發佈的文章</h2>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="post">
                    <a href="post_detail.php?id=<?php echo $row['id']; ?>" class="post-link">
                        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                        <p><?php echo mb_substr(htmlspecialchars($row['content']), 0, 100); ?>...</p>
                    </a>
                    <span>由 <?php echo htmlspecialchars($row['author_id']); ?> 發布於 <?php echo $row['created_at']; ?></span>
                </div>
            <?php endwhile; ?>
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
    <a href="../pages/create_post.php">發布</a>
    <?php include '../includes/footer.php'; ?>
    <?php
    CloseCon($conn); // 關閉資料庫連接
    ?>
</body>
</html>
