<?php
session_start();
session_destroy(); // 銷毀所有 Session 資料
header("Location: index.php"); // 重定向到登入頁面
// 在 logout.php 中
exit;
?>
