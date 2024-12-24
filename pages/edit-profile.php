<?php
session_start(); // 啟動 Session

// 包含資料庫連接檔案
include '../includes/db_connection.php';

// 使用 OpenCon 函數連接資料庫
$conn = OpenCon();


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>個人小屋</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>
<body>
<?php include '../includes/header.php'; ?>
<?php include '../includes/footer.php'; ?>
</body>
</html>