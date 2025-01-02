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
    echo json_encode(["success" => false, "message" => "請先登入後再編輯留言"]);
    exit();
}

// 驗證留言資料
$comment_id = intval($_POST['comment_id'] ?? 0);
$new_content = trim($_POST['content'] ?? "");
$user_id = $_SESSION['user_id'];

if ($comment_id <= 0 || empty($new_content)) {
    echo json_encode(["success" => false, "message" => "留言資料不完整"]);
    exit();
}

// 不雅詞語檢測（調用 NLP 服務）
function contains_prohibited_words($content) {
    $url = "http://192.168.0.44:5000/analyze"; // NLP API 的地址
    $data = json_encode(["text" => $content]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $result = json_decode($response, true);
        if (isset($result['status']) && $result['status'] === 'toxic') {
            return $result['found_words']; // 返回找到的不雅字詞
        }
    }
    return false;
}

// 檢查留言是否包含不雅字
$prohibited_words = contains_prohibited_words($new_content);
if ($prohibited_words) {
    $words = implode(", ", $prohibited_words);
    echo json_encode(["success" => false, "message" => "留言內容包含不雅字詞: {$words}"]);
    exit();
}

// 檢查是否為留言的作者
$check_query = "SELECT id FROM comments WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $comment_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "您無權編輯此留言"]);
    exit();
}

// 更新留言內容
$update_query = "UPDATE comments SET content = ?, updated_at = NOW() WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("si", $new_content, $comment_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "留言已成功編輯"]);
} else {
    echo json_encode(["success" => false, "message" => "編輯失敗，請稍後再試"]);
}
$stmt->close();
?>
