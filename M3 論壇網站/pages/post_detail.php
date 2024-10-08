<?php
include '../includes/db_connection.php'; // 包含資料庫連接檔案
$conn = OpenCon(); // 開啟資料庫連接

// 確認是否有文章 ID
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "SELECT * FROM posts WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();

    if ($post):
        ?>
        <h2><?php echo htmlspecialchars($post['title']); ?></h2>
        <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
        <span>由 <?php echo htmlspecialchars($post['author_id']); ?> 發布於 <?php echo $post['created_at']; ?></span>
        <?php
    else:
        echo "找不到這篇文章";
    endif;

    $stmt->close();
} else {
    echo "沒有指定文章";
}

CloseCon($conn); // 關閉資料庫連接
?>

<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/header.css">
<link rel="stylesheet" href="../css/post.css">
<link rel="stylesheet" href="../css/layout.css">
<link rel="stylesheet" href="../css/form.css">