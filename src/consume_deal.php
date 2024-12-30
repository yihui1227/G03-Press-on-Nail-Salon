<?php
session_start();
include('condb.php');

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
    <title>編輯消費</title>
    <link rel="stylesheet" href="/assets/css/sub.css">
    <link rel="stylesheet" href="/assets/css/admin_nav.css">
    <link rel="stylesheet" href="/assets/css/edit.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="top-nav">
        <div class="nav-links">
            <a href="dashboard.php" class="menu-item"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="consume_list.php" class="menu-item"><i class="fa-solid fa-receipt"></i> 消費管理</a>
            <a href="customer_list.php" class="menu-item"><i class="fa-solid fa-user-tag"></i> 客戶管理</a>
            <a href="product_list.php" class="menu-item"><i class="fa-solid fa-hand-sparkles"></i> 產品管理</a>
        </div>
    </div>

    <div class="container">
        <h1><i class="fas fa-edit"></i> 編輯消費</h1>
        
        <a href="consume_list.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> 返回列表
        </a>

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
                <button type="submit" class="btn btn-search">
                    <i class="fas fa-search"></i> 搜尋
                </button>
            </form>
        </div>

        <table id="transactionTable">
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
                        <td colspan="8" style="text-align: center;">沒有找到符合的資料</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr id="row-<?php echo $transaction['transaction_id']; ?>" data-id="<?php echo $transaction['transaction_id']; ?>">
                            <td><?php echo htmlspecialchars($transaction['transaction_id']); ?></td>
                            <td data-field="customer_id"><?php echo htmlspecialchars($transaction['customer_name']); ?></td>
                            <td data-field="state"><?php echo htmlspecialchars($transaction['state']); ?></td>
                            <td data-field="date"><?php echo htmlspecialchars($transaction['date']); ?></td>
                            <td data-field="product_id"><?php echo htmlspecialchars($transaction['product_name']); ?></td>
                            <td data-field="amount"><?php echo htmlspecialchars($transaction['amount']); ?></td>
                            <td data-field="payment"><?php echo htmlspecialchars($transaction['payment']) ?: '-'; ?></td>
                            <td>
                                <i class="fas fa-trash icon-btn delete-btn" 
                                onclick="deleteTransaction(<?php echo $transaction['transaction_id']; ?>)"></i>
                                <i class="fas fa-edit icon-btn edit-btn" 
                                onclick="startEdit(<?php echo $transaction['transaction_id']; ?>)"></i>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

                    <!--頁數-->
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                class="<?php echo ($page == $i) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>

    

    <script>
        // 儲存所有客戶和產品資料
        const customers = <?php echo json_encode($customers); ?>;
        const products = <?php echo json_encode($products); ?>;
        let currentEditingId = null;

        function startEdit(id) {
            if (currentEditingId && currentEditingId !== id) {
                if (confirm('有未儲存的修改，是否要儲存？')) {
                    saveEdit(currentEditingId);
                } else {
                    cancelEdit(currentEditingId);
                }
            }

            const row = document.getElementById(`row-${id}`);
            currentEditingId = id;
            row.classList.add('edit-mode');

            // 替換每個可編輯欄位為輸入元素
            const cells = row.cells;
            
            // 客戶名稱下拉選單
            const customerCell = cells[1];
            const currentCustomerId = customerCell.getAttribute('data-customer-id');
            customerCell.innerHTML = createCustomerSelect(currentCustomerId);

            // 狀態下拉選單
            const stateCell = cells[2];
            const currentState = stateCell.textContent;
            stateCell.innerHTML = createStateSelect(currentState);

            // 日期輸入
            const dateCell = cells[3];
            const currentDate = dateCell.textContent;
            dateCell.innerHTML = `<input type="date" value="${currentDate}">`;

            // 產品名稱下拉選單
            const productCell = cells[4];
            const currentProductId = productCell.getAttribute('data-product-id');
            productCell.innerHTML = createProductSelect(currentProductId);

            // 價格下拉選單
            const amountCell = cells[5];
            const currentAmount = amountCell.textContent;
            amountCell.innerHTML = createAmountSelect(currentProductId, currentAmount);

            // 付款方式下拉選單
            const paymentCell = cells[6];
            const currentPayment = paymentCell.textContent;
            paymentCell.innerHTML = createPaymentSelect(currentPayment);

            // 更改按鈕
            const actionCell = cells[7];
            actionCell.innerHTML = `
                <i class="fas fa-check icon-btn save-btn" onclick="confirmSave(${id})"></i>
                <i class="fas fa-times icon-btn cancel-btn" onclick="cancelEdit(${id})"></i>
                <i class="fas fa-trash icon-btn delete-btn" onclick="deleteTransaction(${id})"></i>
            `;

            // 綁定狀態變更事件
            const stateSelect = row.querySelector('select[name="state"]');
            stateSelect.addEventListener('change', function() {
                togglePaymentField(row);
            });

            // 綁定產品變更事件
            const productSelect = row.querySelector('select[name="product_id"]');
            productSelect.addEventListener('change', function() {
                updatePriceOptions(row);
            });
        }

        function confirmSave(id) {
            if (confirm('確定要儲存修改嗎？')) {
                saveEdit(id);
            }
        }

        function saveEdit(id) {
            const row = document.getElementById(`row-${id}`);
            const formData = new FormData();
            
            formData.append('id', id);
            formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
            formData.append('customer_id', row.querySelector('select[name="customer_id"]').value);
            formData.append('state', row.querySelector('select[name="state"]').value);
            formData.append('date', row.querySelector('input[type="date"]').value);
            formData.append('product_id', row.querySelector('select[name="product_id"]').value);
            formData.append('amount', row.querySelector('select[name="amount"]').value);
            formData.append('payment', row.querySelector('select[name="payment"]').value);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('儲存失敗，請稍後再試。');
            });
        }

        function cancelEdit(id) {
            const row = document.getElementById(`row-${id}`);
            window.location.reload();
        }

        function deleteTransaction(id) {
            if (currentEditingId && currentEditingId !== id) {
                if (confirm('有未儲存的修改，是否要儲存？')) {
                    saveEdit(currentEditingId);
                    return;
                }
                cancelEdit(currentEditingId);
            }
            
            if (confirm('確定要刪除此筆資料嗎？')) {
                window.location.href = `?delete_id=${id}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`;
            }
        }

        function createCustomerSelect(currentId) {
            let html = '<select name="customer_id" required>';
            customers.forEach(customer => {
                html += `<option value="${customer.customer_id}" 
                        ${customer.customer_id == currentId ? 'selected' : ''}>
                        ${customer.name}</option>`;
            });
            html += '</select>';
            return html;
        }

        function createStateSelect(currentState) {
            return `
                <select name="state" required>
                    <option value="已付" ${currentState === '已付' ? 'selected' : ''}>已付</option>
                    <option value="未付" ${currentState === '未付' ? 'selected' : ''}>未付</option>
                </select>
            `;
        }

        function createProductSelect(currentId) {
            let html = '<select name="product_id" required>';
            products.forEach(product => {
                html += `<option value="${product.product_id}" 
                        ${product.product_id == currentId ? 'selected' : ''}>
                        ${product.name}</option>`;
            });
            html += '</select>';
            return html;
        }

        function createAmountSelect(productId, currentAmount) {
            let html = '<select name="amount" required>';
            const product = products.find(p => p.product_id == productId);
            
            if (product) {
                if (product.original) {
                    html += `<option value="${product.original}" 
                            ${product.original == currentAmount ? 'selected' : ''}>
                            原價 NT$${product.original}</option>`;
                }
                if (product.model) {
                    html += `<option value="${product.model}" 
                            ${product.model == currentAmount ? 'selected' : ''}>
                            模特價 NT$${product.model}</option>`;
                }
                if (product.friend) {
                    html += `<option value="${product.friend}" 
                            ${product.friend == currentAmount ? 'selected' : ''}>
                            親友價 NT$${product.friend}</option>`;
                }
            }
            
            html += '</select>';
            return html;
        }

        function createPaymentSelect(currentPayment) {
            return `
                <select name="payment">
                    <option value="" ${!currentPayment ? 'selected' : ''}>請選擇付款方式</option>
                    <option value="現金" ${currentPayment === '現金' ? 'selected' : ''}>現金</option>
                    <option value="信用卡" ${currentPayment === '信用卡' ? 'selected' : ''}>信用卡</option>
                    <option value="LINE PAY" ${currentPayment === 'LINE PAY' ? 'selected' : ''}>LINE PAY</option>
                    <option value="APPLE PAY" ${currentPayment === 'APPLE PAY' ? 'selected' : ''}>APPLE PAY</option>
                </select>
            `;
        }

        function togglePaymentField(row) {
            const state = row.querySelector('select[name="state"]').value;
            const paymentSelect = row.querySelector('select[name="payment"]');
            
            if (state === '已付') {
                paymentSelect.style.display = 'block';
                paymentSelect.required = true;
            } else {
                paymentSelect.style.display = 'none';
                paymentSelect.required = false;
                paymentSelect.value = '';
            }
        }

        function updatePriceOptions(row) {
            const productId = row.querySelector('select[name="product_id"]').value;
            const amountCell = row.querySelector('td[data-field="amount"]');
            amountCell.innerHTML = createAmountSelect(productId, '');
        }

        // 在頁面載入時為所有行添加必要的數據屬性
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('#transactionTable tbody tr');
            rows.forEach(row => {
                const customerName = row.cells[1].textContent;
                const productName = row.cells[4].textContent;
                
                const customer = customers.find(c => c.name === customerName.trim());
                const product = products.find(p => p.name === productName.trim());
                
                if (customer) {
                    row.cells[1].setAttribute('data-customer-id', customer.customer_id);
                }
                if (product) {
                    row.cells[4].setAttribute('data-product-id', product.product_id);
                }
            });
        });

        // 攔截所有可能導致離開編輯狀態的操作
        window.addEventListener('beforeunload', function(e) {
            if (currentEditingId) {
                e.preventDefault();
                e.returnValue = '有未儲存的修改，確定要離開嗎？';
            }
        });

        // 攔截分頁和搜尋操作
        document.querySelectorAll('.pagination a, .search-box form').forEach(element => {
            element.addEventListener('click', function(e) {
                if (currentEditingId) {
                    if (confirm('有未儲存的修改，是否要儲存？')) {
                        e.preventDefault();
                        saveEdit(currentEditingId);
                    } else {
                        cancelEdit(currentEditingId);
                    }
                }
            });
        });
    </script>
    </body>
</html>