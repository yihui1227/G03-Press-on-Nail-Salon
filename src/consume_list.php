<?php
session_start();
include("condb.php");

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
    <title>消費資料查詢</title>
    <link rel="stylesheet" href="/assets/css/sub.css">
    <link rel="stylesheet" href="/assets/css/admin_nav.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="top-nav">
        <div class="nav-links">
            <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="consume_list.php"><i class="fa-solid fa-receipt"></i> 消費管理</a>
            <a href="customer_list.php"><i class="fa-solid fa-user-tag"></i> 客戶管理</a>
            <a href="product_list.php"><i class="fa-solid fa-hand-sparkles"></i> 產品管理</a>
        </div>
    </div>    
    <div class="container">
        <h1><i class="fas fa-list"></i> 消費資料查詢</h1>
        
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
                <a href="consume_add.php" class="btn btn-add">
                    <i class="fas fa-plus"></i> 新增
                </a>
                <a href="consume_deal.php" class="btn btn-edit">
                    <i class="fas fa-edit"></i> 編輯
                </a>
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
    </div>
</body>
</html>