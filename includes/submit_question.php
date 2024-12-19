<?php
// 連接到資料庫
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "forum";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 取得選擇的問題類型
$question_type = $_POST['question-type'];  // 這將是 'type0', 'type1', 等等

// 將選擇的類型轉換為資料庫中的數值型
switch ($question_type) {
    case 'type0':
        $db_type = 0;
        break;
    case 'type1':
        $db_type = 1;
        break;
    case 'type2':
        $db_type = 2;
        break;
    case 'type3':
        $db_type = 3;
        break;
    case 'type4':
        $db_type = 4;
        break;
    case 'type5':
        $db_type = 5;
        break;
    case 'type6':
        $db_type = 6;
        break;
    case 'type7':
        $db_type = 7;
        break;
    default:
        $db_type = 0;  // 默認情況
}

// 插入資料庫
$sql = "INSERT INTO your_table_name (question_type) VALUES ($db_type)";

if ($conn->query($sql) === TRUE) {
    echo "新記錄插入成功";
} else {
    echo "錯誤: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
