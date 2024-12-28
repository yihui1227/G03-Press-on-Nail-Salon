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
        $delete_sql = "DELETE FROM transaction WHERE transaction_id = ?";
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
    $customer_id = filter_var($_POST['customer_id'], FILTER_VALIDATE_INT);
    $state = filter_var($_POST['state'], FILTER_SANITIZE_STRING);
    $date = filter_var($_POST['date'], FILTER_SANITIZE_STRING);
    $product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);
    $amount = filter_var($_POST['amount'], FILTER_VALIDATE_INT);
    $payment = filter_var($_POST['payment'], FILTER_SANITIZE_STRING);

    if ($customer_id && $product_id) {
        $update_sql = "UPDATE transaction SET customer_id = ?, state = ?, date = ?, product_id = ?, amount = ?, payment = ? WHERE transaction_id = ?";
        $update_stmt = $db->prepare($update_sql);
        if ($update_stmt->execute([$customer_id, $state, $date, $product_id, $amount, $payment, $_POST['id']])) {
            showMessage("資料更新成功！");
        } else {
            showMessage("資料更新失敗！", "error");
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 查詢所有交易資料
$sql = "SELECT * FROM transaction ORDER BY transaction_id DESC";
$stmt = $db->query($sql);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>交易資料管理</title>
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
    <h1>交易資料管理系統</h1>
    
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
                <th>客戶ID</th>
                <th>狀態</th>
                <th>日期</th>
                <th>產品ID</th>
                <th>數量</th>
                <th>付款方式</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td><?php echo htmlspecialchars($transaction['customer_id']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['state']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['date']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['product_id']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['amount']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['payment']); ?></td>
                    <td>
                        <a href="?delete_id=<?php echo $transaction['transaction_id']; ?>" 
                           onclick="return confirm('確定要刪除此筆資料嗎？')" 
                           class="btn btn-delete">刪除</a>
                        <a href="?edit_id=<?php echo $transaction['transaction_id']; ?>" 
                           class="btn btn-edit">修改</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (isset($_GET['edit_id'])): ?>
        <?php 
        $edit_id = filter_var($_GET['edit_id'], FILTER_VALIDATE_INT);
        $edit_sql = "SELECT * FROM transaction WHERE transaction_id = ?";
        $edit_stmt = $db->prepare($edit_sql);
        $edit_stmt->execute([$edit_id]);
        $edit_transaction = $edit_stmt->fetch(PDO::FETCH_ASSOC);
        ?>
            <div class="edit-form">
                <h2>修改交易資料</h2>
                <form method="post" action="">
                    <input type="hidden" name="id" value="<?php echo $edit_transaction['transaction_id']; ?>">
                    
                    <div class="form-group">
                        <label for="customer_id">客戶ID：</label>
                        <input type="number" name="customer_id" id="customer_id" 
                               value="<?php echo htmlspecialchars($edit_transaction['customer_id']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="state">狀態：</label>
                        <input type="text" name="state" id="state" 
                               value="<?php echo htmlspecialchars($edit_transaction['state']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="date">日期：</label>
                        <input type="text" name="date" id="date" 
                               value="<?php echo htmlspecialchars($edit_transaction['date']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="product_id">產品ID：</label>
                        <input type="number" name="product_id" id="product_id" 
                               value="<?php echo htmlspecialchars($edit_transaction['product_id']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="amount">數量：</label>
                        <input type="number" name="amount" id="amount" 
                               value="<?php echo htmlspecialchars($edit_transaction['amount']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="payment">付款方式：</label>
                        <input type="text" name="payment" id="payment" 
                               value="<?php echo htmlspecialchars($edit_transaction['payment']); ?>" required>
                    </div>
                    
                    <input type="submit" value="更新資料" class="form-submit">
                </form>
            </div>
    <?php endif; ?>
</body>
</html>
