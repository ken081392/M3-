<?php

use function PHPSTORM_META\type;

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
$postsResult = $conn->query($query);  // 使用不同變數儲存文章查詢結果

// 查詢最新文章標題
$latestPostsQuery = "
    SELECT id, title 
    FROM posts 
    ORDER BY created_at DESC 
    LIMIT 3
";
$latestPostsResult = $conn->query($latestPostsQuery); // 查詢最新文章標題

// 查詢分類
$sql = "SELECT name, slug, description FROM categories ORDER BY id ASC";
$categoriesResult = $conn->query($sql);  // 使用不同變數儲存分類查詢結果
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
    <link rel="stylesheet" href="../css/test.css">
    <script>
        function navigateToCategory(category) {
            if (category) {
                window.location.href = category;
            }
        }
    </script>

</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="main-content">
        <!-- 分類區塊 -->
        <div class="category-dropdown">
            <h2>分類</h2>
            <select name="category" id="category" onchange="navigateToCategory(this.value)">
                <option value="">SDGS分類</option>
                <?php while ($row = $categoriesResult->fetch_assoc()): ?>
                    <option value="category.php?type=<?php echo $row['slug']; ?>">
                        <?php echo $row['name']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- 發佈的文章區塊 -->
        <div class="posts-section">
            <div class="header-posts">
                <h2>發佈的文章</h2>
                <div class="create">
                    <a href="../pages/create_post.php">發布</a>
                </div>
            </div>
            
            <?php
            if ($postsResult->num_rows > 0):
                while ($row = $postsResult->fetch_assoc()):
            ?>
                <div class="post">
                    <a href="post_detail.php?id=<?php echo $row['id']; ?>" class="post-link">
                        <?php
                        // 根據 question_type 顯示相應的文字
                        switch ($row['question_type']) {
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
                        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                        <p>
                            <?php 
                            // 解碼 HTML 並限制內容長度
                            $decoded_content = strip_tags(htmlspecialchars_decode($row['content'], ENT_QUOTES));
                            echo mb_substr($decoded_content, 0, 100); 
                            ?>...
                        </p>
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

        <!-- 最新文章區塊 -->
        <div class="latest-posts-section">
            <h2>最新文章</h2>
            <ul class="latest-posts-list">
                <?php
                if ($latestPostsResult->num_rows > 0):
                    while ($row = $latestPostsResult->fetch_assoc()): ?>
                        <li class="latest-post-item">
                            <a href="post_detail.php?id=<?php echo $row['id']; ?>" class="latest-post-link">
                                <?php echo htmlspecialchars($row['title']); ?>
                            </a>
                        </li>
                <?php
                    endwhile;
                else:
                    echo "<li>目前沒有最新文章。</li>";
                endif;
                ?>
            </ul>
        </div>

    </div>

    <?php include '../includes/footer.php'; ?>
    <?php
    CloseCon($conn); // 關閉資料庫連接
    ?>
</body>
</html>
