<?php
session_start();
include '../includes/db_connection.php';
$conn = OpenCon();

// 檢查是否為 POST 請求並處理通報
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_comment'])) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["success" => false, "message" => "請先登入後才能通報"]);
        exit();
    }

    // 獲取通報所需資料
    $comment_id = intval($_POST['comment_id']);
    $reason = trim($_POST['reason']);
    $reporter_id = $_SESSION['user_id'];

    // 檢查通報原因是否為空
    if (empty($reason)) {
        echo json_encode(["success" => false, "message" => "請填寫通報原因"]);
        exit();
    }

    // 插入通報資料到 `report_comments` 資料表
    $insert_query = "INSERT INTO report_comments (comment_id, user_id, reason, reported_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($insert_query);

    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "系統錯誤，請稍後再試"]);
        exit();
    }

    // 綁定參數並執行查詢
    $stmt->bind_param("iis", $comment_id, $reporter_id, $reason);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "留言通報已提交"]);
    } else {
        echo json_encode(["success" => false, "message" => "提交失敗，請稍後再試"]);
    }

    $stmt->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>通報留言</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h2>通報留言</h2>

        <!-- 通報表單 -->
        <form method="POST" action="report_comments.php">
            <input type="hidden" name="comment_id" value="<?php echo isset($_GET['comment_id']) ? $_GET['comment_id'] : ''; ?>" />
            <textarea name="reason" placeholder="請填寫通報原因" required></textarea><br>
            <button type="submit" name="report_comment">提交通報</button>
        </form>
    </div>
</body>
</html>
