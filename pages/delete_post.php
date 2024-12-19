<?php
session_start();
include '../includes/db_connection.php';
$conn = OpenCon();

if (!isset($_SESSION['user_id'])) {
    die("請先登入才能刪除文章。");
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("無效的文章 ID。");
}

$post_id = intval($_GET['id']);

// 確認用戶是否為文章作者
$query = "SELECT * FROM posts WHERE id = ? AND author_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $post_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("您無權刪除此文章。");
}

// 刪除文章
$delete_query = "DELETE FROM posts WHERE id = ?";
$delete_stmt = $conn->prepare($delete_query);
$delete_stmt->bind_param("i", $post_id);
$delete_stmt->execute();

if ($delete_stmt->affected_rows > 0) {
    echo "文章刪除成功！";
    header("Location: index.php"); // 回到首頁或其他列表頁
    exit;
} else {
    echo "刪除失敗，請重試。";
}
?>
