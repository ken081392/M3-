<?php
session_start();
include '../includes/db_connection.php';

$conn = OpenCon();

// 確認用戶是否登入
if (!isset($_SESSION['user_id'])) {
    die("請先登入後再執行通報操作。");
}

// 驗證文章 ID
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $post_id = intval($_GET['id']);
} else {
    die("無效的文章 ID。");
}

// 檢查是否已通報過
$check_query = "SELECT * FROM post_reports WHERE post_id = ? AND user_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $post_id, $_SESSION['user_id']);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    echo "您已經通報過此文章！";
    exit();
}

// 插入新的通報記錄
$report_query = "INSERT INTO post_reports (post_id, user_id, reported_at) VALUES (?, ?, NOW())";
$stmt = $conn->prepare($report_query);
$stmt->bind_param("ii", $post_id, $_SESSION['user_id']);

if ($stmt->execute()) {
    echo "文章已成功通報！";
    header("Location: post_detail.php?id=$post_id");
    exit();
} else {
    die("通報失敗：" . $conn->error);
}
?>
