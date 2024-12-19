<?php
session_start();
include '../includes/db_connection.php'; // 引入資料庫連接文件

$conn = OpenCon(); // 初始化資料庫連接
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M3論壇網站 - 搜尋結果</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/post.css">
    <style>
        /* 搜尋結果容器 */
        .search-results-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh; /* 占滿視窗高度 */
            background-color: #f5f5f5; /* 背景色，可根據需求調整 */
            padding: 20px;
            box-sizing: border-box;
        }

        /* 搜尋結果內容 */
        .search-results {
            width: 80%; /* 調整結果區域的寬度 */
            max-width: 800px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
        }

        /* 單篇文章卡片 */
        .post-item {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: left;
        }

        .post-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .post-content {
            font-size: 14px;
            margin-bottom: 10px;
            color: #555;
        }

        .post-meta {
            font-size: 12px;
            color: #888;
            margin-bottom: 10px;
        }

        .read-more {
            display: inline-block;
            margin-top: 10px;
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }

        .read-more:hover {
            text-decoration: underline;
        }


    </style>
</head>
<body>
    <?php include '../includes/header.php'; // 頁首 ?>

    <main class="search-results-container">
    <div class="search-results">
        <h1>搜尋結果：</h1>
        <?php
if (isset($_GET['query']) && !empty($_GET['query'])) {
    $query = trim($_GET['query']);
    $query = htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); // 防止 XSS 攻擊

    // 使用 JOIN 獲取文章與用戶名
    $sql = "SELECT posts.id, posts.title, posts.content, posts.created_at, users.username, posts.category 
            FROM posts 
            INNER JOIN users ON posts.author_id = users.id 
            WHERE posts.title LIKE ? OR posts.content LIKE ? 
            LIMIT 50";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo "<p>SQL 查詢準備失敗：" . $conn->error . "</p>";
        exit;
    }

    $searchTerm = "%$query%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);

    if (!$stmt->execute()) {
        echo "<p>SQL 查詢執行失敗：" . $stmt->error . "</p>";
        exit;
    }

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        echo "<div class='post-list'>";
        while ($row = $result->fetch_assoc()) {
            $author = isset($row['username']) ? htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8') : '匿名';
            $category = isset($row['category']) ? htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8') : '未分類';
            $created_at = isset($row['created_at']) ? htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') : '未知時間';

            echo "<div class='post-item'>";
            echo "<h2 class='post-title'>[$category] " . htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') . "</h2>";
            echo "<p class='post-content'>" . htmlspecialchars(mb_substr($row['content'], 0, 50), ENT_QUOTES, 'UTF-8') . "...</p>";
            echo "<p class='post-meta'>由 $author 發布於 $created_at</p>";
            echo "<a href='../pages/post_detail.php?id=" . $row['id'] . "' class='read-more'>閱讀更多</a>";
            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "<p>沒有符合的結果。</p>";
    }
} else {
    echo "<p>請輸入搜尋關鍵字。</p>";
}
?>
    </div>
</main>

    <?php include '../includes/footer.php'; // 頁尾 ?>
</body>
</html>

<?php
CloseCon($conn); // 關閉資料庫連接
?>
