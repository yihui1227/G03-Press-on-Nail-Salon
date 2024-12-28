<?php
include("../condb.php"); // 資料庫連線設定

$message = ""; // 儲存操作結果的訊息

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

    // 如果狀態為 "未付"，將付款方式設為 NULL
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
  <title>新增消費資料</title>
  <style>
    body {
      margin: 0;
      font-family: 微軟正黑體, 新細明體, 標楷體, Arial, sans-serif;
    }
    .menu {
      background-color: dimgrey;
      color: white;
      padding: 10px;
    }
    .content {
      margin: 20px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      padding: 10px;
    }
    input[type="text"], input[type="date"], input[type="number"], select {
      width: 100%;
      padding: 5px;
    }
    input[type="submit"] {
      padding: 10px 20px;
      background-color: dimgrey;
      color: white;
      border: none;
      cursor: pointer;
    }
    .message {
      margin: 10px 0;
      color: green;
      font-weight: bold;
    }
    #paymentField {
      display: none;
    }
  </style>
  <script>
    function updatePrice() {
      // 保持原有的 updatePrice 函數不變
      var productId = document.getElementById('product_id').value;
      var priceSelect = document.getElementById('amount');
      
      priceSelect.innerHTML = '<option value="">選擇價格</option>';

      
      if (productId) {
        var products = <?php echo json_encode($products); ?>;
        const selectedProduct = products.find(product => product.product_id == productId);

        if (selectedProduct) {
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
        }
      }
    }

    function togglePaymentField() {
      var state = document.querySelector('select[name="state"]').value;
      var paymentField = document.getElementById('paymentField');
      var paymentSelect = document.querySelector('select[name="payment"]');
      
      if (state === '已付') {
        paymentField.style.display = 'table-row';
        paymentSelect.required = true;  // 設為必填
      } else {
        paymentField.style.display = 'none';
        paymentSelect.required = false; // 取消必填
        paymentSelect.value = '';       // 清空選擇的值
      }
    }

    // 頁面載入完成後初始化
    document.addEventListener('DOMContentLoaded', function() {
      // 初始化付款欄位顯示狀態
      togglePaymentField();
      
      // 為狀態選擇添加事件監聽
      document.querySelector('select[name="state"]').addEventListener('change', togglePaymentField);
    });
  </script>
</head>
<body>
<div class="menu">
  <a href="../index.php">Home</a> |
  <a href="customer_add.php" style="color:white;">新增客戶</a> |
  <a href="product_add.php" style="color:white;">新增產品</a>
</div>
<div class="content">
  <h2>新增消費資料</h2>
  <?php if (!empty($message)): ?>
    <p class="message"><?php echo htmlspecialchars($message); ?></p>
  <?php endif; ?>
  <form method="post" action="">
    <table>
      <tr>
        <td>客戶：</td>
        <td>
          <select name="customer_id" required>
            <option value="">選擇客戶</option>
            <?php foreach ($customers as $customer): ?>
              <option value="<?php echo $customer['customer_id']; ?>">
                <?php echo htmlspecialchars($customer['name']); ?> 
              </option>
            <?php endforeach; ?>
          </select>
        </td>
      </tr>
      <tr>
        <td>產品名稱：</td>
        <td>
          <select name="product_id" id="product_id" onchange="updatePrice()" required>
            <option value="">選擇產品</option>
            <?php foreach ($products as $product): ?>
              <option value="<?php echo $product['product_id']; ?>">
                <?php echo htmlspecialchars($product['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </td>
      </tr>
      <tr>
        <td>價格：</td>
        <td>
          <select name="amount" id="amount" required>
            <option value="">選擇價格</option>
            <!-- Prices will be populated based on selected product -->
          </select>
        </td>
      </tr>
      <tr>
        <td>狀態：</td>
        <td><select name="state" required>
            <option value="已付">已付</option>
            <option value="未付">未付</option>
          </select></td>
      </tr>
      <tr id="paymentField">
        <td>付款方式：</td>
        <td>
        <select name="payment">
            <option value="">請選擇付款方式</option>
            <option value="現金">現金</option>
            <option value="信用卡">信用卡</option>
            <option value="LINE PAY">LINE PAY</option>
            <option value="APPLE PAY">APPLE PAY</option>
        </select>
        </td>
    </tr>
      <tr>
        <td>日期：</td>
        <td><input type="date" name="date" required /></td>
      </tr>
    </table>
    <br />
    <input type="submit" value="新增消費資料" />
  </form>
</div>
</body>
</html>
