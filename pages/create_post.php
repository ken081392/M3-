<?php
session_start();
include '../includes/db_connection.php';

$conn = OpenCon();

// 確保用戶已登入
if (!isset($_SESSION['user_id'])) {
    die("<script>alert('請先登入後再發佈文章。'); window.location.href = 'login.php';</script>");
}

// 敏感詞檢測函數
function checkToxicity($content) {
    $api_url = "http://localhost:5000/analyze"; // Flask API URL
    $data = json_encode(["text" => $content]);

    // 初始化 cURL
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    // 執行 cURL 並獲取回應
    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 確保 API 回應正常
    if ($http_status !== 200 || !$response) {
        throw new Exception("敏感詞檢測服務連接失敗。");
    }

    $result = json_decode($response, true);

    // 如果 API 回傳敏感詞，返回它們
    if ($result['status'] === 'toxic') {
        return $result['found_words']; // 返回找到的敏感詞
    }

    return []; // 沒有敏感詞
}

// 僅在表單提交後處理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = htmlspecialchars(trim($_POST['title']), ENT_QUOTES, 'UTF-8');
    $content = $_POST['content']; // 保留 HTML 格式
    $category = isset($_POST['category']) ? intval($_POST['category']) : 0;
    $question_type = htmlspecialchars(trim($_POST['question-type']), ENT_QUOTES, 'UTF-8');
    $author_id = $_SESSION['user_id'];

    // 處理圖片上傳
    $imagePath = '';
    if (!empty($_FILES['image']['name'])) {
        $imageDir = '../uploads/images/';
        if (!is_dir($imageDir)) {
            mkdir($imageDir, 0777, true);
        }
        $imagePath = $imageDir . basename($_FILES['image']['name']);
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
            echo "<script>alert('圖片上傳失敗。'); window.history.back();</script>";
            exit();
        }
    }

    // 處理影片上傳
    $videoPath = '';
    if (!empty($_FILES['video']['name'])) {
        $videoDir = '../uploads/videos/';
        if (!is_dir($videoDir)) {
            mkdir($videoDir, 0777, true);
        }
        $videoPath = $videoDir . basename($_FILES['video']['name']);
        if (!move_uploaded_file($_FILES['video']['tmp_name'], $videoPath)) {
            echo "<script>alert('影片上傳失敗。'); window.history.back();</script>";
            exit();
        }
    }

    if (empty($title) || empty($content) || $category === 0 || empty($question_type)) {
        echo "<script>alert('所有欄位均為必填，請重新填寫！'); window.history.back();</script>";
        exit();
    }

    // 檢查分類是否存在
    $category_check_query = "SELECT id FROM categories WHERE id = ?";
    $stmt = $conn->prepare($category_check_query);
    $stmt->bind_param("i", $category);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "<script>alert('無效的分類 ID，請選擇有效的分類！'); window.history.back();</script>";
        exit();
    }

    // 調用敏感詞檢測 API
    try {
        $found_words = checkToxicity($content);
        if (!empty($found_words)) {
            echo "<script>alert('文章內容包含以下敏感詞語：" . implode(", ", $found_words) . "。請重新修改！'); window.history.back();</script>";
            exit();
        }
    } catch (Exception $e) {
        echo "<script>alert('檢測內容時出現錯誤：" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "'); window.history.back();</script>";
        exit();
    }

    // 插入文章到資料庫
    $post_query = "INSERT INTO posts (title, content, category, author_id, question_type, image_path, video_path, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($post_query);
    $stmt->bind_param("ssissss", $title, $content, $category, $author_id, $question_type, $imagePath, $videoPath);

    if ($stmt->execute()) {
        echo "<script>alert('文章發佈成功！'); window.location.href = 'index.php';</script>";
        exit();
    } else {
        echo "<script>alert('發佈文章失敗：" . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8') . "'); window.history.back();</script>";
        exit();
    }
}

// 查詢所有分類
$category_query = "SELECT id, name FROM categories";
$category_result = $conn->query($category_query);
$categories_available = $category_result && $category_result->num_rows > 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>發佈文章</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/post.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/form.css">
    <link rel="stylesheet" href="../css/buttons.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script src="https://cdn.tiny.cloud/1/c55c79oki26ahno32p6sdgiv7j4fzqt7s9c8gjipfl03mgk5/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
       tinymce.init({
           selector: '#content',
           plugins: 'advlist autolink lists link image charmap preview anchor code',
           toolbar: 'undo redo | formatselect | bold italic underline | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat',
           height: 400,
           menubar: false
       });
    </script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="main-content">
        <div class="container">
            <form action="create_post.php" method="POST" enctype="multipart/form-data">
                <h2>發佈文章</h2>
                
                <!-- 錯誤訊息 -->
                <div id="error-message" style="display:none; color:red;">
                    所有欄位均為必填，請重新填寫！
                </div>
                
                <!-- 動態生成分類選單 -->
                <select name="category" class="category-select" required>
                    <option value="">選擇發文類別</option>
                    <?php
                    if ($categories_available) {
                        while ($row = $category_result->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') . '">' .
                                htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . '</option>';
                        }
                    } else {
                        echo '<option value="">無分類可供選擇</option>';
                    }
                    ?>
                </select>

                <!-- 問題類型選單 -->
                <select name="question-type" class="question-select">
                    <option value="type0">問題</option>
                    <option value="type1">情報</option>
                    <option value="type2">心得</option>
                    <option value="type3">討論</option>
                    <option value="type4">攻略</option>
                    <option value="type5">密技</option>
                    <option value="type6">閒聊</option>
                    <option value="type7">其他</option>
                </select>

                <!-- 標題與內容 -->
                <input type="text" name="title" id="title" class="form-input" placeholder="輸入文章標題" required>
                <textarea name="content" id="content" class="form-textarea"></textarea>

                <!-- 圖片上傳 -->
                <label for="image">上傳圖片：</label>
                <input type="file" name="image" id="image" accept="image/*">

                <!-- 影片上傳 -->
                <label for="video">上傳影片：</label>
                <input type="file" name="video" id="video" accept="video/*">

                <!-- 提交按鈕 -->
                <div class="form-actions">
                    <button type="submit" class="submit-btn">提交文章</button>
                    <button type="button" class="btn-template" onclick="window.history.back();">取消</button>
                </div>
            </form>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
</body>
</html>
