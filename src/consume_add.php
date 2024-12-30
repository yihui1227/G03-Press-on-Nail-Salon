<?php
include('condb.php'); 

$message = ""; 

// 讀取 customer 表格中的客戶資訊
$customer_sql = "SELECT customer_id, name FROM customer"; 
$customer_stmt = $db->query($customer_sql);
$customers = $customer_stmt->fetchAll(PDO::FETCH_ASSOC);

// 讀取 price 表格中的產品資訊
$product_sql = "SELECT product_id, name, original, model, friend FROM price"; 
$product_stmt = $db->query($product_sql);
$products = $product_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 接收表單資料
    $customer_id = $_POST["customer_id"] ?? null;
    $product_id = $_POST["product_id"] ?? null;
    $amount = $_POST["amount"] ?? null;
    $payment = $_POST["payment"] ?? null;
    $state = $_POST["state"] ?? null;
    $date = $_POST["date"] ?? null;

    if ($state === "未付") {
        $payment = null;
    }

    if (!empty($customer_id) && !empty($product_id) && !empty($amount) && !empty($state) && !empty($date)) {
        try {
            $sql = "INSERT INTO transaction (customer_id, product_id, amount, payment, state, date) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $success = $stmt->execute([$customer_id, $product_id, $amount, $payment, $state, $date]);

            if ($success) {
                $message = "新增成功！消費編號：" . $db->lastInsertId();
            } else {
                $message = "新增失敗：" . implode(" ", $stmt->errorInfo());
            }
        } catch (PDOException $e) {
            $message = "資料庫錯誤：" . $e->getMessage();
        }
    } else {
        $message = "表單資料不完整，請重新檢查！";
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新增消費</title>
    <link rel="stylesheet" href="/assets/css/sub.css">
    <link rel="stylesheet" href="/assets/css/admin_nav.css">
    <link rel="stylesheet" href="/assets/css/add.css">
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
        <h1><i class="fas fa-plus-circle"></i> 新增消費</h1>
        
        <a href="consume_list.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> 返回列表
        </a>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, '成功') !== false ? '' : 'error'; ?>">
                <i class="<?php echo strpos($message, '成功') !== false ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="post" action="">
                <div class="form-row">
                    <div class="form-label">
                        <i class="fas fa-user"></i> 客戶
                    </div>
                    <div class="form-field">
                        <select name="customer_id" required>
                            <option value="">選擇客戶</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['customer_id']; ?>">
                                    <?php echo htmlspecialchars($customer['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">
                        <i class="fas fa-box"></i> 產品名稱
                    </div>
                    <div class="form-field">
                        <select name="product_id" id="product_id" onchange="updatePrice()" required>
                            <option value="">選擇產品</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['product_id']; ?>">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">
                        <i class="fas fa-dollar-sign"></i> 價格
                    </div>
                    <div class="form-field">
                        <select name="amount" id="amount" required>
                            <option value="">選擇價格</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">
                        <i class="fas fa-clipboard-check"></i> 狀態
                    </div>
                    <div class="form-field">
                        <select name="state" required onchange="togglePaymentField()">
                            <option value="已付">已付</option>
                            <option value="未付">未付</option>
                        </select>
                    </div>
                </div>

                <div class="form-row" id="paymentField">
                    <div class="form-label">
                        <i class="fas fa-credit-card"></i> 付款方式
                    </div>
                    <div class="form-field">
                        <select name="payment">
                            <option value="">請選擇付款方式</option>
                            <option value="現金">現金</option>
                            <option value="信用卡">信用卡</option>
                            <option value="LINE PAY">LINE PAY</option>
                            <option value="APPLE PAY">APPLE PAY</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">
                        <i class="fas fa-calendar"></i> 日期
                    </div>
                    <div class="form-field">
                        <input type="date" name="date" required />
                    </div>
                </div>

                <div class="submit-container">
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i>
                        新增消費資料
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updatePrice() {
            try {
                const productId = document.getElementById('product_id').value;
                const priceSelect = document.getElementById('amount');
                const products = <?php echo json_encode($products); ?>;
                
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
            const state = document.querySelector('select[name="state"]').value;
            const paymentField = document.getElementById('paymentField');
            const paymentSelect = document.querySelector('select[name="payment"]');
            
            if (state === '已付') {
                paymentField.style.display = 'flex';
                paymentSelect.required = true;
            } else {
                paymentField.style.display = 'none';
                paymentSelect.required = false;
                paymentSelect.value = '';
            }
        }

        // 初始化付款方式欄位顯示狀態
        document.addEventListener('DOMContentLoaded', function() {
            togglePaymentField();
        });
    </script>
</body>
</html>