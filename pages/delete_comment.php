<?php
session_start();
include '../includes/db_connection.php';

$conn = OpenCon();

// 確認用戶是否登入
if (!isset($_SESSION['user_id'])) {
    die("請先登入後再執行刪除操作。");
}

// 驗證是否有正確的留言 ID
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $comment_id = intval($_GET['id']);
} else {
    die("無效的留言 ID。");
}

// 驗證是否為留言作者
$check_author_query = "SELECT user_id FROM comments WHERE id = ?";
$stmt = $conn->prepare($check_author_query);
$stmt->bind_param("i", $comment_id);
$stmt->execute();
$result = $stmt->get_result();
$comment = $result->fetch_assoc();

if (!$comment) {
    die("找不到該留言。");
}

if ($comment['user_id'] != $_SESSION['user_id']) {
    die("您沒有權限刪除此留言。");
}

// 刪除留言
$delete_query = "DELETE FROM comments WHERE id = ?";
$stmt = $conn->prepare($delete_query);
$stmt->bind_param("i", $comment_id);

if ($stmt->execute()) {
    echo "留言已成功刪除！";
    // 返回到文章詳細頁面
    header("Location: post_detail.php?id=" . intval($_GET['post_id']));
    exit();
} else {
    echo "<script>alert('刪除失敗，文章可能已不存在或權限不足。'); window.location.href='index.php';</script>";
}
?>
