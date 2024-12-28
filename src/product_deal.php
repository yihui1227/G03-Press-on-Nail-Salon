<?php
include("../condb.php");

// 顯示訊息的函數
function showMessage($message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

// 處理刪除操作
if (isset($_GET['delete_id'])) {
    $delete_id = filter_var($_GET['delete_id'], FILTER_VALIDATE_INT);
    if ($delete_id) {
        $delete_sql = "DELETE FROM price WHERE product_id = ?";
        $delete_stmt = $db->prepare($delete_sql);
        if ($delete_stmt->execute([$delete_id])) {
            showMessage("資料刪除成功！");
        } else {
            showMessage("資料刪除失敗！", "error");
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 處理修改操作
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])) {
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $original = filter_var($_POST['original'], FILTER_VALIDATE_INT);
    $model = filter_var($_POST['model'], FILTER_VALIDATE_INT);
    $friend = filter_var($_POST['friend'], FILTER_VALIDATE_INT);

    // 若欄位為空，則設為NULL
    $original = $original !== false ? $original : null;
    $model = $model !== false ? $model : null;
    $friend = $friend !== false ? $friend : null;

    if ($id && $name) {
        $update_sql = "UPDATE price SET name = ?, original = ?, model = ?, friend = ? WHERE product_id = ?";
        $update_stmt = $db->prepare($update_sql);
        if ($update_stmt->execute([$name, $original, $model, $friend, $id])) {
            showMessage("資料更新成功！");
        } else {
            showMessage("資料更新失敗！", "error");
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 查詢所有產品資料
$sql = "SELECT * FROM price ORDER BY product_id DESC";
$stmt = $db->query($sql);
$prices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>產品資料管理</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .btn {
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 3px;
            color: white;
            margin: 0 5px;
        }
        .btn-delete {
            background-color: #ff4444;
        }
        .btn-edit {
            background-color: #33b5e5;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .message.success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        .message.error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }
        .edit-form {
            background-color: white;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-submit {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .form-submit:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <h1>產品資料管理系統</h1>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="message <?php echo $_SESSION['message_type']; ?>">
            <?php 
            echo $_SESSION['message'];
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>產品名稱</th>
                <th>原價</th>
                <th>模特價</th>
                <th>親友價</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($prices as $price): ?>
                <tr>
                    <td><?php echo htmlspecialchars($price['name']); ?></td>
                    <td><?php echo htmlspecialchars($price['original']); ?></td>
                    <td><?php echo htmlspecialchars($price['model']); ?></td>
                    <td><?php echo htmlspecialchars($price['friend']); ?></td>
                    <td>
                        <a href="?delete_id=<?php echo $price['product_id']; ?>" 
                           onclick="return confirm('確定要刪除此筆資料嗎？')" 
                           class="btn btn-delete">刪除</a>
                        <a href="?edit_id=<?php echo $price['product_id']; ?>" 
                           class="btn btn-edit">修改</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (isset($_GET['edit_id'])): ?>
        <?php 
        $edit_id = filter_var($_GET['edit_id'], FILTER_VALIDATE_INT);
        $edit_sql = "SELECT * FROM price WHERE product_id = ?";
        $edit_stmt = $db->prepare($edit_sql);
        $edit_stmt->execute([$edit_id]);
        $edit_price = $edit_stmt->fetch(PDO::FETCH_ASSOC);
        ?>
            <div class="edit-form">
                <h2>修改產品資料</h2>
                <form method="post" action="">
                    <input type="hidden" name="id" value="<?php echo $edit_price['product_id']; ?>">
                    
                    <div class="form-group">
                        <label for="name">產品名稱：</label>
                        <input type="text" name="name" id="name" 
                               value="<?php echo htmlspecialchars($edit_price['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="original">原價：</label>
                        <input type="number" name="original" id="original" 
                               value="<?php echo htmlspecialchars($edit_price['original'] ?? ''); ?>" 
                               placeholder="可留空">
                    </div>

                    <div class="form-group">
                        <label for="model">模特價：</label>
                        <input type="number" name="model" id="model" 
                               value="<?php echo htmlspecialchars($edit_price['model'] ?? ''); ?>" 
                               placeholder="可留空">
                    </div>

                    <div class="form-group">
                        <label for="friend">親友價：</label>
                        <input type="number" name="friend" id="friend" 
                               value="<?php echo htmlspecialchars($edit_price['friend'] ?? ''); ?>" 
                               placeholder="可留空">
                    </div>
                    
                    <input type="submit" value="更新資料" class="form-submit">
                </form>
            </div>
    <?php endif; ?>
</body>
</html>
