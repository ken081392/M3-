<?php
session_start();

// 如果未登入，跳轉到登入頁面
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../pages/login.php");
    exit;
}

// 設定資料庫連接資訊
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "forum";

// 建立資料庫連接
$conn = new mysqli($servername, $username, $password, $dbname);

// 檢查資料庫連接是否成功
if ($conn->connect_error) {
    die("資料庫連接失敗: " . $conn->connect_error);
}

// 查詢所有用戶
$query_users = "SELECT * FROM users";
$result_users = mysqli_query($conn, $query_users);

if (!$result_users) {
    die("查詢失敗: " . mysqli_error($conn));  // 如果查詢失敗，顯示錯誤訊息
}

// 查詢所有帖子
$query_posts = "SELECT * FROM posts";
$result_posts = mysqli_query($conn, $query_posts);

if (!$result_posts) {
    die("查詢失敗: " . mysqli_error($conn));  // 如果查詢失敗，顯示錯誤訊息
}

$query_posts = "
    SELECT 
        p.id,
        p.title,
        p.content,
        u.username AS author_name,  -- 將 author_id 轉換為使用者名稱
        p.created_at
    FROM 
        posts p
    LEFT JOIN 
        users u ON p.author_id = u.id  -- 根據 author_id 與 users 資料表聯接
";
$result_posts = mysqli_query($conn, $query_posts);

if (!$result_posts) {
    die("查詢失敗: " . mysqli_error($conn));  // 如果查詢失敗，顯示錯誤訊息
}

// 查詢所有文章通報，並將 post_id 轉換為文章標題，並顯示通報者名稱
$query_post_reports = "
    SELECT 
        pr.id,
        pr.report_message,
        pr.status,
        pr.created_at,
        p.title AS post_title,  -- 將 post_id 轉換為文章標題
        u.username AS reported_by  -- 轉換 user_id 為用戶名
    FROM 
        post_reports pr
    LEFT JOIN 
        posts p ON pr.post_id = p.id
    LEFT JOIN 
        users u ON pr.user_id = u.id  -- 將 user_id 轉換為用戶名
";
$result_post_reports = mysqli_query($conn, $query_post_reports);

if (!$result_post_reports) {
    die("查詢失敗: " . mysqli_error($conn));  // 如果查詢失敗，顯示錯誤訊息
}

// 查詢所有評論通報，並將 comment_id 轉換為評論內容，並顯示通報者名稱
$query_comment_reports = "
    SELECT 
        rc.id,
        rc.report_message,
        rc.status,
        rc.created_at,
        c.content AS comment_content,  -- 將 comment_id 轉換為評論內容
        u.username AS reported_by  -- 轉換 user_id 為用戶名
    FROM 
        reports_comment rc
    LEFT JOIN 
        comments c ON rc.comment_id = c.id
    LEFT JOIN 
        users u ON rc.user_id = u.id  -- 將 user_id 轉換為用戶名
";
$result_comment_reports = mysqli_query($conn, $query_comment_reports);

if (!$result_comment_reports) {
    die("查詢失敗: " . mysqli_error($conn));  // 如果查詢失敗，顯示錯誤訊息
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理員控制面板</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* 整體頁面樣式 */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 0;
}

/* 容器 */
.container {
    width: 90%;
    max-width: 1200px;
    margin: 20px auto;
    background-color: #fff;
    padding: 20px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}


/* 主要標題 */
h1 {
    font-size: 2em;
    color: #333;
    text-align: center;
    margin-bottom: 20px;
}

/* 表格樣式 */
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

table th, table td {
    padding: 10px;
    text-align: left;
    border: 1px solid #ddd;
}

table th {
    background-color: #4CAF50;
    color: white;
}

table td {
    background-color: #f9f9f9;
}

table tr:nth-child(even) td {
    background-color: #f1f1f1;
}

table tr:hover td {
    background-color: #f1f1f1;
}

/* 按鈕樣式 */
.container a {
    text-decoration: none;
    padding: 8px 16px;
    margin: 0 5px;
    color: white;
    background-color: #4CAF50;
    border-radius: 4px;
    transition: background-color 0.3s ease;
}

.container a:hover {
    background-color: #45a049;
}

/* 刪除按鈕樣式 */
.delete {
    background-color: #f44336;
}

.delete:hover {
    background-color: #e53935;
}

/* 文章和評論通報表格的樣式 */
section h2 {
    font-size: 1.8em;
    color: #333;
    margin-bottom: 15px;
    border-bottom: 2px solid #4CAF50;
    padding-bottom: 5px;
}

/* 操作列樣式 */
section td a {
    margin: 0 5px;
}

/* 居中樣式 */
.center {
    text-align: center;
}

/* 表格外框樣式 */
table {
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}


/* 響應式設計，當螢幕小於768px時，讓頁面更簡潔 */
@media (max-width: 768px) {
    .container {
        width: 95%;
    }

    h1 {
        font-size: 1.8em;
    }

    table th, table td {
        padding: 8px;
        font-size: 0.9em;
    }

    section h2 {
        font-size: 1.5em;
    }
}

    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container">
        <h1>管理員控制面板</h1>

        <!-- 使用者管理 -->
        <section>
            <h2>使用者管理</h2>
            <table>
                <tr>
                    <th>用戶名</th>
                    <th>電子郵件</th>
                    <th>角色</th>
                    <th>操作</th>
                </tr>
                <?php while ($row = mysqli_fetch_assoc($result_users)) { ?>
                    <tr>
                        <td><?php echo $row['username']; ?></td>
                        <td><?php echo $row['email']; ?></td>
                        <td><?php echo $row['role']; ?></td>
                        <td>
                            <a href="edit_user.php?id=<?php echo $row['id']; ?>">編輯</a>
                            <a href="delete_user.php?id=<?php echo $row['id']; ?>" class="delete">刪除</a>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </section>

        <!-- 帖子管理 -->
        <section>
            <h2>帖子管理</h2>
            <table>
                <tr>
                    <th>標題</th>
                    <th>作者</th>  <!-- 顯示作者名稱 -->
                    <th>創建日期</th>
                    <th>操作</th>
                </tr>
                <?php while ($row = mysqli_fetch_assoc($result_posts)) { ?>
                    <tr>
                        <td><?php echo $row['title']; ?></td>
                        <td><?php echo $row['author_name']; ?></td>  <!-- 顯示作者的使用者名稱 -->
                        <td><?php echo date('Y-m-d H:i:s', strtotime($row['created_at'])); ?></td>
                        <td>
                            <a href="edit_post.php?id=<?php echo $row['id']; ?>">編輯</a>
                            <a href="delete_post.php?id=<?php echo $row['id']; ?>" class="delete">刪除</a>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </section>

        <!-- 文章通報管理 -->
        <section>
            <h2>文章通報管理</h2>
            <table>
                <tr>
                    <th>文章標題</th>
                    <th>通報內容</th>
                    <th>狀態</th>
                    <th>創建日期</th>
                    <th>通報者</th>  <!-- 顯示通報者 -->
                    <th>操作</th>
                </tr>
                <?php while ($report = mysqli_fetch_assoc($result_post_reports)) { ?>
                    <tr>
                        <td><?php echo $report['post_title']; ?></td>
                        <td><?php echo $report['report_message']; ?></td>
                        <td><?php echo $report['status']; ?></td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($report['created_at'])); ?></td>
                        <td><?php echo $report['reported_by']; ?></td>  <!-- 顯示通報者的用戶名 -->
                        <td>
                            <a href="resolve_post_report.php?id=<?php echo $report['id']; ?>">標記為已處理</a>
                            <a href="delete_post_report.php?id=<?php echo $report['id']; ?>" class="delete">刪除</a>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </section>

        <!-- 評論通報管理 -->
        <section>
            <h2>評論通報管理</h2>
            <table>
                <tr>
                    <th>評論內容</th>  <!-- 顯示評論內容，而不是 comment_id -->
                    <th>通報內容</th>
                    <th>狀態</th>
                    <th>創建日期</th>
                    <th>通報者</th>  <!-- 顯示通報者 -->
                    <th>操作</th>
                </tr>
                <?php while ($report = mysqli_fetch_assoc($result_comment_reports)) { ?>
                    <tr>
                        <td><?php echo $report['comment_content']; ?></td>  <!-- 顯示評論內容 -->
                        <td><?php echo $report['report_message']; ?></td>
                        <td><?php echo $report['status']; ?></td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($report['created_at'])); ?></td>
                        <td><?php echo $report['reported_by']; ?></td>  <!-- 顯示通報者的用戶名 -->
                        <td>
                            <a href="resolve_comment_report.php?id=<?php echo $report['id']; ?>">標記為已處理</a>
                            <a href="delete_comment_report.php?id=<?php echo $report['id']; ?>" class="delete">刪除</a>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </section>
    </div>
</body>
</html>
