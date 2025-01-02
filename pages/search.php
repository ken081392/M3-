<?php
session_start();
include '../includes/db_connection.php'; // 引入資料庫連接文件

$conn = OpenCon(); // 初始化資料庫連接

function fetchSearchResults($query, $conn) {
    // 防止 XSS 攻擊
    $query = htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); 

    // 使用 JOIN 獲取文章與用戶名
    $sql = "SELECT posts.id, posts.title, posts.content, posts.created_at, users.username, posts.category, posts.question_type 
            FROM posts 
            INNER JOIN users ON posts.author_id = users.id 
            WHERE posts.title LIKE ? OR posts.content LIKE ? 
            LIMIT 50";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return ["error" => "SQL 查詢準備失敗：" . $conn->error];
    }

    $searchTerm = "%$query%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);

    if (!$stmt->execute()) {
        return ["error" => "SQL 查詢執行失敗：" . $stmt->error];
    }

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        return $result->fetch_all(MYSQLI_ASSOC);
    } else {
        return [];
    }
}

if (isset($_GET['query']) && !empty($_GET['query'])) {
    $searchResults = fetchSearchResults($_GET['query'], $conn);
} else {
    $searchResults = null;
}
CloseCon($conn); // 關閉資料庫連接
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
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
        }

        main {
            flex: 1;
        }

        .search-results-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
            background-color: #f5f5f5;
        }

        .search-results {
            width: 100%;
            max-width: 800px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .post-item {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .post-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .post-content {
            font-size: 14px;
            margin-bottom: 10px;
            color: #555;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
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
    <?php include '../includes/header.php'; ?>

    <main class="search-results-container">
        <div class="search-results">
            <h1>搜尋結果：</h1>
            <?php if ($searchResults === null): ?>
                <p>請輸入搜尋關鍵字。</p>
            <?php elseif (isset($searchResults["error"])): ?>
                <p><?php echo htmlspecialchars($searchResults["error"], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php elseif (empty($searchResults)): ?>
                <p>沒有符合的結果。</p>
            <?php else: ?>
                <div class='post-list'>
                    <?php foreach ($searchResults as $row): ?>
                        <div class='post-item'>
                            <?php
                            // 根據 question_type 顯示相應的文字
                            $question_type_text = match ($row['question_type']) {
                                'type0' => '[問題]',
                                'type1' => '[情報]',
                                'type2' => '[心得]',
                                'type3' => '[討論]',
                                'type4' => '[攻略]',
                                'type5' => '[密技]',
                                'type6' => '[閒聊]',
                                'type7' => '[其他]',
                                default => '[未知類型]',
                            };
                            ?>
                            <h2 class='post-title'><?php echo htmlspecialchars($question_type_text); ?> <?php echo htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                            <p class='post-content'><?php echo htmlspecialchars(mb_substr(strip_tags(htmlspecialchars_decode($row['content'], ENT_QUOTES)), 0, 100), ENT_QUOTES, 'UTF-8'); ?>...</p>
                            <p class='post-meta'>由 <?php echo htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8'); ?> 發布於 <?php echo htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <a href='../pages/post_detail.php?id=<?php echo $row['id']; ?>' class='read-more'>閱讀更多</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
