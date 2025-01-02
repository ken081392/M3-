<?php
session_start();
include '../includes/db_connection.php';
$conn = OpenCon();

// 確認請求是否為 POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "無效的請求方式"]);
    exit();
}

// 驗證是否已登入
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "請先登入"]);
    exit();
}

// 驗證留言 ID
$comment_id = intval($_POST['comment_id'] ?? 0);
if ($comment_id <= 0) {
    echo json_encode(["success" => false, "message" => "無效的留言 ID"]);
    exit();
}

// 驗證是否為留言作者
$check_author_query = "SELECT user_id FROM comments WHERE id = ?";
$stmt = $conn->prepare($check_author_query);
$stmt->bind_param("i", $comment_id);
$stmt->execute();
$result = $stmt->get_result();
$comment = $result->fetch_assoc();

if (!$comment) {
    echo json_encode(["success" => false, "message" => "找不到該留言"]);
    exit();
}

if ($comment['user_id'] != $_SESSION['user_id']) {
    echo json_encode(["success" => false, "message" => "您沒有權限刪除此留言"]);
    exit();
}

// 刪除留言
$delete_query = "DELETE FROM comments WHERE id = ?";
$stmt = $conn->prepare($delete_query);
$stmt->bind_param("i", $comment_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "留言刪除成功"]);
} else {
    error_log("刪除留言失敗：" . $stmt->error);
    echo json_encode(["success" => false, "message" => "刪除失敗，請稍後再試"]);
}
exit();
?>