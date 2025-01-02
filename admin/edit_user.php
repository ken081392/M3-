<?php
// 連接資料庫
include 'db_connection.php';
session_start();

// 確保只有已登入使用者才能訪問
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'super_admin' && $_SESSION['user_role'] !== 'admin')) {
    echo "你沒有權限訪問此頁面。";
    exit();
}

// 檢查是否有傳遞用戶 ID
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // 查詢當前用戶資料
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        echo "用戶不存在！";
        exit();
    }
} else {
    echo "無效的用戶 ID！";
    exit();
}

// 更新用戶資料
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role']; // 新增的角色欄位

    // 確保只有 super_admin 可以修改角色
    if ($_SESSION['user_role'] === 'super_admin') {
        $update_query = "UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssi", $username, $email, $role, $user_id);
    } else {
        // 如果是 admin，禁止修改角色
        $update_query = "UPDATE users SET username = ?, email = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssi", $username, $email, $user_id);
    }

    if ($stmt->execute()) {
        echo "用戶資料已更新！";
        header("Location: user_list.php"); // 返回用戶列表頁
        exit();
    } else {
        echo "更新失敗：" . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>編輯用戶</title>
</head>
<body>
    <h1>編輯用戶</h1>
    <form action="" method="POST">
        <label for="username">用戶名：</label>
        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required><br><br>

        <label for="email">電子郵件：</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required><br><br>

        <?php if ($_SESSION['user_role'] === 'super_admin') { ?>
        <label for="role">角色：</label>
        <select id="role" name="role">
            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>一般使用者</option>
            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>管理員</option>
            <option value="super_admin" <?php echo $user['role'] === 'super_admin' ? 'selected' : ''; ?>>超級管理員</option>
        </select><br><br>
        <?php } ?>

        <button type="submit">保存更改</button>
    </form>
    <a href="user_list.php">返回用戶列表</a>
</body>
</html>
