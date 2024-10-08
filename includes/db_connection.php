<?php
function OpenCon() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "forum";

    // 建立資料庫連接
    $conn = new mysqli($servername, $username, $password, $dbname);

    // 檢查連接
    if ($conn->connect_error) {
        die("資料庫連接失敗: " . $conn->connect_error);
    }

    return $conn;
}

function CloseCon($conn) {
    $conn->close();
}
?>
