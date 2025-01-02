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
    echo json_encode(["success" => false, "message" => "請先登入後才能通報"]);
    exit();
}

// 驗證通報資料
$comment_id = intval($_POST['comment_id'] ?? 0);
$reason = trim($_POST['reason'] ?? "");

if ($comment_id <= 0 || empty($reason)) {
    echo json_encode(["success" => false, "message" => "通報資料不完整"]);
    exit();
}

// 插入通報資料到 reports_comment 表
try {
    $insert_query = "INSERT INTO reports_comment (comment_id, user_id, reason, reported_at, status, created_at) 
                     VALUES (?, ?, ?, NOW(), 'pending', NOW())";
    $stmt = $conn->prepare($insert_query);

    if (!$stmt) {
        throw new Exception("SQL 準備失敗：" . $conn->error);
    }

    $stmt->bind_param("iis", $comment_id, $_SESSION['user_id'], $reason);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "留言通報成功"]);
    } else {
        // 檢查是否是唯一性違反錯誤
        if ($conn->errno === 1062) {
            echo json_encode(["success" => false, "message" => "您已經通報過此留言"]);
        } else {
            throw new Exception("SQL 執行失敗：" . $stmt->error);
        }
    }

    $stmt->close();
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(["success" => false, "message" => "系統錯誤，請稍後再試"]);
}

$conn->close();
exit();
?>
