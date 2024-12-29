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
        $searchCondition = "WHERE c.name LIKE ? OR p.name LIKE ? OR t.state LIKE ?";
        $searchParams = array_fill(0, 3, "%$search%");
    }

    // 處理刪除操作
    if (isset($_GET['delete_id']) && isset($_GET['csrf_token'])) {
        if (!hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
            throw new Exception('CSRF token validation failed');
        }
        
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
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception('CSRF token validation failed');
        }

        $customer_id = filter_var($_POST['customer_id'], FILTER_VALIDATE_INT);
        $state = filter_var($_POST['state'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $date = filter_var($_POST['date'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);
        $amount = filter_var($_POST['amount'], FILTER_VALIDATE_INT);
        $payment = filter_var($_POST['payment'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

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

    // 獲取編輯資料
    $edit_transaction = null;
    if (isset($_GET['edit_id'])) {
        $edit_id = filter_var($_GET['edit_id'], FILTER_VALIDATE_INT);
        $edit_sql = "SELECT t.*, c.name as customer_name, p.name as product_name 
                     FROM transaction t 
                     INNER JOIN customer c ON t.customer_id = c.customer_id 
                     INNER JOIN price p ON t.product_id = p.product_id 
                     WHERE t.transaction_id = ?";
        $edit_stmt = $db->prepare($edit_sql);
        $edit_stmt->execute([$edit_id]);
        $edit_transaction = $edit_stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 獲取交易資料總數
    $count_sql = "SELECT COUNT(*) FROM transaction t 
                  INNER JOIN customer c ON t.customer_id = c.customer_id 
                  INNER JOIN price p ON t.product_id = p.product_id 
                  $searchCondition";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($searchParams);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $perPage);

    // 獲取交易資料
    $transactions_sql = "SELECT t.*, c.name as customer_name, p.name as product_name 
                    FROM transaction t 
                    INNER JOIN customer c ON t.customer_id = c.customer_id 
                    INNER JOIN price p ON t.product_id = p.product_id 
                    $searchCondition
                    ORDER BY t.transaction_id ASC 
                    LIMIT ? OFFSET ?";
    $transactions_stmt = $db->prepare($transactions_sql);
    $transactions_stmt->execute(array_merge($searchParams, [$perPage, $offset]));
    $transactions = $transactions_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 獲取客戶和產品資料
    $customers_sql = "SELECT customer_id, name FROM customer ORDER BY name";
    $customers_stmt = $db->query($customers_sql);
    $customers = $customers_stmt->fetchAll(PDO::FETCH_ASSOC);

    $products_sql = "SELECT product_id, name, original, model, friend FROM price ORDER BY name";
    $products_stmt = $db->query($products_sql);
    $products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    showMessage("系統錯誤：" . $e->getMessage(), "error");
}
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
        .form-group input, .form-group select {
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
        .export-btn {
            float: right;
            margin-bottom: 10px;
            background-color: #4CAF50;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
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

    <div class="search-box">
        <form method="get" action="">
            <input type="text" name="search" placeholder="搜尋客戶名稱、產品名稱或狀態..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">搜尋</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>交易編號</th>
                <th>客戶名稱</th>
                <th>狀態</th>
                <th>日期</th>
                <th>產品名稱</th>
                <th>價格</th>
                <th>付款方式</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="7" style="text-align: center;">沒有找到符合的資料</td>
                </tr>
            <?php else: ?>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($transaction['transaction_id']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['state']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['date']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['amount']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['payment']) ?: '-'; ?></td>
                        <td>
                            <a href="?delete_id=<?php echo $transaction['transaction_id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" 
                               onclick="return confirm('確定要刪除此筆資料嗎？')" 
                               class="btn btn-delete">刪除</a>
                            <a href="?edit_id=<?php echo $transaction['transaction_id']; ?>" 
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

    <?php if (isset($_GET['edit_id']) && $edit_transaction): ?>
        <div class="edit-form">
            <h2>修改交易資料</h2>
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="id" value="<?php echo $edit_transaction['transaction_id']; ?>">
                
                <div class="form-group">
                    <label>交易編號：</label>
                    <input type="text" value="<?php echo htmlspecialchars($edit_transaction['transaction_id']); ?>" readonly 
                    class="form-control" style="background-color: #f0f0f0;">
                </div>

                <div class="form-group">
                    <label for="customer_id">客戶：</label>
                    <select name="customer_id" id="customer_id" required>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?php echo $customer['customer_id']; ?>"
                                <?php echo ($customer['customer_id'] == $edit_transaction['customer_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($customer['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="state">狀態：</label>
                    <select name="state" id="state" required onchange="togglePaymentField()">
                        <option value="已付" <?php echo ($edit_transaction['state'] == '已付') ? 'selected' : ''; ?>>已付</option>
                        <option value="未付" <?php echo ($edit_transaction['state'] == '未付') ? 'selected' : ''; ?>>未付</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="date">日期：</label>
                    <input type="date" name="date" id="date" 
                           value="<?php echo htmlspecialchars($edit_transaction['date']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="product_id">產品：</label>
                    <select name="product_id" id="product_id" required onchange="updateAmount()">
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['product_id']; ?>"
                                <?php echo ($product['product_id'] == $edit_transaction['product_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($product['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="amount">價格：</label>
                    <select name="amount" id="amount" required>
                    <option value="">選擇價格</option>
                    </select>
                </div>

                <div class="form-group" id="payment_group">
                    <label for="payment">付款方式：</label>
                    <select name="payment" id="payment">
                        <option value="">請選擇付款方式</option>
                        <option value="現金" <?php echo ($edit_transaction['payment'] == '現金') ? 'selected' : ''; ?>>現金</option>
                        <option value="信用卡" <?php echo ($edit_transaction['payment'] == '信用卡') ? 'selected' : ''; ?>>信用卡</option>
                        <option value="LINE PAY" <?php echo ($edit_transaction['payment'] == 'LINE PAY') ? 'selected' : ''; ?>>LINE PAY</option>  
                        <option value="APPLE PAY" <?php echo ($edit_transaction['payment'] == 'APPLE PAY') ? 'selected' : ''; ?>>APPLE PAY</option>
                    </select>
                </div>
                
                <input type="submit" value="更新資料" class="form-submit">
            </form>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // 初始化表單
                if (document.getElementById('product_id')) {
                    updatePrice();
                    togglePaymentField();
                }
                
                // 綁定事件監聽器
                const productSelect = document.getElementById('product_id');
                const stateSelect = document.querySelector('select[name="state"]');
                
                if (productSelect) {
                    productSelect.addEventListener('change', updatePrice);
                }
                
                if (stateSelect) {
                    stateSelect.addEventListener('change', togglePaymentField);
                }
            });

            function updatePrice() {
                try {
                    const productId = document.getElementById('product_id').value;
                    const priceSelect = document.getElementById('amount');
                    const products = <?php echo json_encode($products ?? []); ?>;
                    
                    priceSelect.innerHTML = '<option value="">選擇價格</option>';
                    
                    if (!productId) return;
                    
                    const selectedProduct = products.find(product => product.product_id == productId);
                    if (!selectedProduct) return;
                    
                    const prices = [
                        { label: '原價 NT$', value: selectedProduct.original },
                        { label: '模特價 NT$', value: selectedProduct.model },
                        { label: '親友價 NT$', value: selectedProduct.friend }
                    ];
                    
                    prices.forEach(price => {
                        if (price.value) {
                            const option = document.createElement('option');
                            option.value = price.value;
                            option.text = price.label + price.value;
                            priceSelect.appendChild(option);
                        }
                    });
                } catch (error) {
                    console.error('更新價格時發生錯誤:', error);
                }
            }

            function togglePaymentField() {
                try {
                    const state = document.querySelector('select[name="state"]').value;
                    const paymentGroup = document.getElementById('payment_group');
                    const paymentSelect = document.querySelector('select[name="payment"]');
                    
                    if (!paymentGroup || !paymentSelect) return;
                    
                    if (state === '已付') {
                        paymentGroup.style.display = 'block';
                        paymentSelect.required = true;
                    } else {
                        paymentGroup.style.display = 'none';
                        paymentSelect.required = false;
                        paymentSelect.value = '';
                    }
                } catch (error) {
                    console.error('切換付款欄位時發生錯誤:', error);
                }
            }
        </script>

    <?php endif; ?>

</body>
</html>