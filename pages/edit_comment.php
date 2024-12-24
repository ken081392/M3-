<?php
session_start();
include '../includes/db_connection.php';

$conn = OpenCon();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // 調試輸出接收的原始數據
    file_put_contents("log.txt", print_r($data, true), FILE_APPEND);

    if (!is_array($data) || !isset($data['id'], $data['content']) || empty(trim($data['content']))) {
        echo json_encode(["success" => false, "error" => "請求資料無效"]);
        file_put_contents("log.txt", "請求資料無效\n", FILE_APPEND);
        exit();
    }

    $commentId = intval($data['id']);
    $content = htmlspecialchars(trim($data['content']), ENT_QUOTES, 'UTF-8');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["success" => false, "error" => "用戶未登入"]);
        file_put_contents("log.txt", "用戶未登入\n", FILE_APPEND);
        exit();
    }

    $userId = $_SESSION['user_id'];

    $checkQuery = "SELECT id FROM comments WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($checkQuery);

    if ($stmt === false) {
        echo json_encode(["success" => false, "error" => $conn->error]);
        file_put_contents("log.txt", "檢查權限時出錯：" . $conn->error . "\n", FILE_APPEND);
        exit();
    }

    $stmt->bind_param("ii", $commentId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "error" => "無權修改或留言不存在"]);
        file_put_contents("log.txt", "無權修改或留言不存在\n", FILE_APPEND);
        exit();
    }

    $updateQuery = "UPDATE comments SET content = ?, updated_at = NOW() WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);

    if ($updateStmt === false) {
        echo json_encode(["success" => false, "error" => $conn->error]);
        file_put_contents("log.txt", "更新留言時出錯：" . $conn->error . "\n", FILE_APPEND);
        exit();
    }

    $updateStmt->bind_param("si", $content, $commentId);

    if ($updateStmt->execute()) {
        echo json_encode(["success" => true]);
        file_put_contents("log.txt", "留言更新成功\n", FILE_APPEND);
    } else {
        echo json_encode(["success" => false, "error" => "留言更新失敗"]);
        file_put_contents("log.txt", "留言更新失敗：" . $updateStmt->error . "\n", FILE_APPEND);
    }

    $stmt->close();
    $updateStmt->close();
} else {
    echo json_encode(["success" => false, "error" => "無效的請求方法"]);
    file_put_contents("log.txt", "無效的請求方法\n", FILE_APPEND);
}

CloseCon($conn);
?>
