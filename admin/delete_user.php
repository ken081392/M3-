<?php
// 連接資料庫
include 'db_connection.php';

// 檢查是否有傳遞用戶 ID
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // 刪除用戶
    $delete_query = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        echo "用戶已成功刪除！";
        header("Location: user_list.php"); // 返回用戶列表頁
        exit();
    } else {
        echo "刪除失敗：" . $conn->error;
    }
} else {
    echo "無效的用戶 ID！";
    exit();
}
?>
