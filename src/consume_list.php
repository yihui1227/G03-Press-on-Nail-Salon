<?php
session_start();
include("../condb.php");

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

} catch (Exception $e) {
    $_SESSION['message'] = "系統錯誤：" . $e->getMessage();
    $_SESSION['message_type'] = "error";
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>交易資料查詢</title>
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
    </style>
</head>
<body>
    <h1>交易資料查詢系統</h1>
    
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

</body>
</html>