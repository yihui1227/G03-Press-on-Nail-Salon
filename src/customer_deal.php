<?php
session_start();
include("../condb.php");

// 顯示訊息的函數
function showMessage($message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

// CSRF Token 生成
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    // 處理分頁
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;

    // 處理搜尋
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $searchCondition = '';
    $searchParams = [];
    if (!empty($search)) {
        $searchCondition = "WHERE name LIKE ? OR phone LIKE ?";
        $searchParams = array_fill(0, 2, "%$search%");
    }

    // 處理刪除操作
    if (isset($_GET['delete_id']) && isset($_GET['csrf_token'])) {
        if (!hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
            throw new Exception('CSRF token validation failed');
        }
        
        $delete_id = filter_var($_GET['delete_id'], FILTER_VALIDATE_INT);
        if ($delete_id) {
            $delete_sql = "DELETE FROM Customer WHERE customer_id = ?";
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
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception('CSRF token validation failed');
        }

        $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
        $name = filter_var($_POST['name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $phone = filter_var($_POST['phone'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        $rightsize = isset($_POST['rightsize']) ? 
            json_encode(array_map('intval', explode(',', $_POST['rightsize']))) : 
            '[]';
        
        $leftsize = isset($_POST['leftsize']) ? 
            json_encode(array_map('intval', explode(',', $_POST['leftsize']))) : 
            '[]';

        if ($id && $name && $phone) {
            $update_sql = "UPDATE Customer SET name = ?, phone = ?, rightSize = ?, leftSize = ? WHERE customer_id = ?";
            $update_stmt = $db->prepare($update_sql);
            if ($update_stmt->execute([$name, $phone, $rightsize, $leftsize, $id])) {
                showMessage("資料更新成功！");
            } else {
                showMessage("資料更新失敗！", "error");
            }
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // 獲取總記錄數
    $count_sql = "SELECT COUNT(*) FROM Customer $searchCondition";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($searchParams);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $perPage);

    // 查詢客戶資料
    $sql = "SELECT * FROM Customer $searchCondition ORDER BY customer_id DESC LIMIT ? OFFSET ?";
    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge($searchParams, [$perPage, $offset]));
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    showMessage("系統錯誤：" . $e->getMessage(), "error");
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>客戶資料管理系統</title>
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
        .search-box {
            margin-bottom: 20px;
            padding: 15px;
            background-color: white;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .search-box input[type="text"] {
            width: 300px;
            padding: 8px;
            margin-right: 10px;
        }
        .search-box button {
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .pagination {
            margin: 20px 0;
            text-align: center;
        }
        .pagination a {
            display: inline-block;
            padding: 8px 16px;
            text-decoration: none;
            color: #4CAF50;
            border: 1px solid #4CAF50;
            margin: 0 4px;
            border-radius: 4px;
        }
        .pagination a.active {
            background-color: #4CAF50;
            color: white;
        }
        .pagination a:hover:not(.active) {
            background-color: #ddd;
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
    <h1>客戶資料管理系統</h1>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="message <?php echo $_SESSION['message_type']; ?>">
            <?php 
            echo $_SESSION['message'];
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        </div>
    <?php endif; ?>

    <div class="search-box">
        <form method="get" action="">
            <input type="text" name="search" placeholder="搜尋客戶名稱或電話..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">搜尋</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>客戶名稱</th>
                <th>聯絡電話</th>
                <th>右手尺寸</th>
                <th>左手尺寸</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="5" style="text-align: center;">沒有找到符合的資料</td>
                </tr>
            <?php else: ?>
                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($customer['name']); ?></td>
                        <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                        <td><?php 
                            $rightSize = json_decode($customer['rightsize']);
                            echo htmlspecialchars(implode(', ', $rightSize ?? []));
                        ?></td>
                        <td><?php 
                            $leftSize = json_decode($customer['leftsize']);
                            echo htmlspecialchars(implode(', ', $leftSize ?? []));
                        ?></td>
                        <td>
                            <a href="?delete_id=<?php echo $customer['customer_id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                               onclick="return confirm('確定要刪除此筆資料嗎？')" 
                               class="btn btn-delete">刪除</a>
                            <a href="?edit_id=<?php echo $customer['customer_id']; ?>" 
                               class="btn btn-edit">修改</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
               class="<?php echo ($page == $i) ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>

    <?php if (isset($_GET['edit_id'])): ?>
        <?php 
        $edit_id = filter_var($_GET['edit_id'], FILTER_VALIDATE_INT);
        $edit_sql = "SELECT * FROM Customer WHERE customer_id = ?";
        $edit_stmt = $db->prepare($edit_sql);
        $edit_stmt->execute([$edit_id]);
        $edit_customer = $edit_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($edit_customer):
            $rightSize = json_decode($edit_customer['rightsize']);
            $leftSize = json_decode($edit_customer['leftsize']);
        ?>
            <div class="edit-form">
                <h2>修改客戶資料</h2>
                <form method="post" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="id" value="<?php echo $edit_customer['customer_id']; ?>">
                    
                    <div class="form-group">
                        <label for="name">客戶名稱：</label>
                        <input type="text" name="name" id="name" 
                               value="<?php echo htmlspecialchars($edit_customer['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">聯絡電話：</label>
                        <input type="text" name="phone" id="phone" 
                               value="<?php echo htmlspecialchars($edit_customer['phone']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="rightsize">右手尺寸（用逗號分隔）：</label>
                        <input type="text" name="rightsize" id="rightsize" 
                               value="<?php echo htmlspecialchars(implode(',', $rightSize ?? [])); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="leftsize">左手尺寸（用逗號分隔）：</label>
                        <input type="text" name="leftsize" id="leftsize" 
                               value="<?php echo htmlspecialchars(implode(',', $leftSize ?? [])); ?>">
                    </div>
                    
                    <input type="submit" value="更新資料" class="form-submit">
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</body>
</html>