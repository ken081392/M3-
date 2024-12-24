<?php
function OpenCon() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "forum";

    try {
        // 建立資料庫連接
        $conn = new mysqli($servername, $username, $password, $dbname);

        // 檢查連接
        if ($conn->connect_error) {
            throw new Exception("資料庫連接失敗: " . $conn->connect_error);
        }

        // 設置編碼
        if (!$conn->set_charset("utf8mb4")) {
            throw new Exception("無法設定資料庫編碼: " . $conn->error);
        }

    } catch (Exception $e) {
        // 將錯誤記錄到日誌檔案
        error_log($e->getMessage(), 3, "../logs/db_errors.log");
        die("資料庫連接發生問題，請稍後再試。"); // 顯示更安全的錯誤訊息
    }

    return $conn;
}

function CloseCon($conn) {
    if ($conn) {
        $conn->close();
    }
}
?>
