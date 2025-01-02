<?php
session_start();

// 檢查是否已經登入，並且角色是否存在
if (!isset($_SESSION['role'])) {
    // 如果未登入，則重定向到登入頁面
    header("Location: login.php");
    exit();
}

// 根據角色重定向到不同頁面
if ($_SESSION['role'] == 'super_admin') {
    // 允許超級管理員進入管理界面
    header("Location: super_admin_dashboard.php");
} elseif ($_SESSION['role'] == 'admin') {
    // 允許管理員進入管理界面，但只能訪問文章管理
    header("Location: admin_post_dashboard.php");
} else {
    // 普通使用者不允許進入管理頁面
    header("Location: user_dashboard.php");
}
exit();
