<?php
// 引入資料庫連接設定
require_once '../condb.php';

// 從資料庫讀取產品並設定顏色
function getProductColors($db) {
    $colors = array(
        '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEEAD', 
        '#FF9F9F', '#74B49B', '#A8D8EA', '#D4A5A5', '#9B786F'
    );
    
    $productColors = array();
    try {
        $stmt = $db->query("SELECT name FROM price ORDER BY product_id");
        $i = 0;
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $productColors[$row['name']] = $colors[$i % count($colors)];
            $i++;
        }
    } catch(PDOException $e) {
        echo "讀取產品失敗：" . $e->getMessage();
    }
    return $productColors;
}

// 建立必要的stored procedures
function setupDatabase($db) {
    try {
        // 刪除現有的procedures和triggers
        $db->exec("DROP PROCEDURE IF EXISTS GetMonthlySales");
        $db->exec("DROP PROCEDURE IF EXISTS GetCustomerPurchaseHistory");
        $db->exec("DROP PROCEDURE IF EXISTS UpdateTransactionAmount");
        $db->exec("DROP TRIGGER IF EXISTS before_transaction_insert");
        $db->exec("DROP TRIGGER IF EXISTS after_transaction_insert");

        // 創建stored procedures
        $db->exec("
            CREATE PROCEDURE GetMonthlySales(IN start_date DATE, IN end_date DATE)
            BEGIN
                SELECT 
                    p.name AS product_name,
                    DATE_FORMAT(t.date, '%Y-%m') AS month,
                    COUNT(*) AS total_orders,
                    SUM(t.amount) AS total_amount
                FROM transaction t
                JOIN price p ON t.product_id = p.product_id
                WHERE t.date BETWEEN start_date AND end_date
                GROUP BY p.product_id, DATE_FORMAT(t.date, '%Y-%m')
                ORDER BY p.product_id, month;
            END
        ");

        $db->exec("
            CREATE PROCEDURE GetCustomerPurchaseHistory(IN customer_id_param INT)
            BEGIN
                SELECT 
                    t.transaction_id,
                    t.date,
                    p.name AS product_name,
                    t.amount,
                    t.payment,
                    t.state
                FROM transaction t
                JOIN price p ON t.product_id = p.product_id
                WHERE t.customer_id = customer_id_param
                ORDER BY t.date DESC;
            END
        ");

        $db->exec("
            CREATE PROCEDURE UpdateTransactionAmount(
                IN transaction_id_param INT,
                IN new_amount DECIMAL(10,2)
            )
            BEGIN
                UPDATE transaction
                SET amount = new_amount
                WHERE transaction_id = transaction_id_param;
            END
        ");

    } catch(PDOException $e) {
        echo "資料庫設置失敗：" . $e->getMessage();
    }
}

// 取得產品銷售數據
function getProductSales($db, $start_date, $end_date) {
    try {
        $stmt = $db->prepare("
            SELECT 
                p.name AS product_name,
                COUNT(*) AS total_orders,
                SUM(t.amount) AS total_amount
            FROM transaction t
            JOIN price p ON t.product_id = p.product_id
            WHERE t.date BETWEEN ? AND ?
            GROUP BY p.product_id, p.name
            ORDER BY p.product_id
        ");
        $stmt->execute([$start_date, $end_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        echo "查詢失敗：" . $e->getMessage();
        return array();
    }
}

// 取得客戶資料
function getCustomerData($db, $customer_id) {
    try {
        $stmt = $db->prepare("CALL GetCustomerPurchaseHistory(?)");
        $stmt->execute([$customer_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        echo "查詢失敗：" . $e->getMessage();
        return array();
    }
}

// 設置資料庫
setupDatabase($db);

// 主程式
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-6 months'));
$customer_id = isset($_GET['customer_id']) ? $_GET['customer_id'] : null;

$productColors = getProductColors($db);
$salesData = getProductSales($db, $start_date, $end_date);

?>

<!DOCTYPE html>
<html>
<head>
    <title>銷售分析系統</title>
    <meta charset="UTF-8">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f0f2f5;
        }
        .chart-container {
            width: 80%;
            margin: 20px auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .date-selector {
            width: 80%;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .statistics {
            width: 80%;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        input[type="date"] {
            padding: 8px;
            margin: 0 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        input[type="submit"] {
            padding: 8px 20px;
            background-color: #4ECDC4;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        input[type="submit"]:hover {
            background-color: #45B7D1;
        }
        .statistics ul {
            list-style: none;
            padding: 0;
        }
        .statistics li {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .statistics li:last-child {
            border-bottom: none;
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="date-selector">
        <form method="GET">
            <label for="start_date">開始日期：</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
            
            <label for="end_date">結束日期：</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
            
            <input type="submit" value="更新圖表">
        </form>
    </div>
    
    <div class="chart-container">
        <canvas id="salesChart"></canvas>
    </div>
    
    <script>
        var ctx = document.getElementById('salesChart').getContext('2d');
        var salesData = <?php echo json_encode($salesData); ?>;
        var productColors = <?php echo json_encode($productColors); ?>;
        
        // 準備圖表數據
        var labels = salesData.map(item => item.product_name);
        var orderData = salesData.map(item => item.total_orders);
        var colors = labels.map(label => productColors[label] || '#000000');
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: '銷售數量',
                    data: orderData,
                    backgroundColor: colors,
                    borderColor: colors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: '產品銷售統計',
                        font: {
                            size: 16
                        }
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: '銷售數量'
                        },
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: '產品名稱'
                        }
                    }
                }
            }
        });
    </script>

    <?php
    // 顯示銷售統計
    echo "<div class='statistics'>";
    echo "<h2>產品銷售統計 (" . date('Y/m/d', strtotime($start_date)) . " ~ " . 
         date('Y/m/d', strtotime($end_date)) . ")</h2>";
    echo "<ul>";
    foreach ($salesData as $row) {
        echo "<li>{$row['product_name']}<br>訂單數: {$row['total_orders']}<br>總收入: " . 
             number_format($row['total_amount'], 0, '.', ',') . " 元</li>";
    }
    echo "</ul>";
    echo "</div>";

    // 如果有指定客戶ID，顯示客戶購買歷史
    if ($customer_id) {
        $customerData = getCustomerData($db, $customer_id);
        if ($customerData) {
            echo "<div class='statistics'>";
            echo "<h2>客戶購買歷史 (客戶ID: $customer_id)</h2>";
            echo "<ul>";
            foreach ($customerData as $row) {
                echo "<li>
                    日期: {$row['date']}<br>
                    產品: {$row['product_name']}<br>
                    金額: " . number_format($row['amount'], 0, '.', ',') . " 元<br>
                    付款方式: {$row['payment']}<br>
                    狀態: {$row['state']}
                    </li>";
            }
            echo "</ul>";
            echo "</div>";
        }
    }
    ?>
</body>
</html>