<?php
session_start();
include("condb.php");

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
    <title>顧客列表</title>
    <link rel="stylesheet" href="/assets/css/sub.css">
    <link rel="stylesheet" href="/assets/css/admin_nav.css">
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
        <h1><i class="fas fa-users"></i> 顧客列表</h1>
        
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
                <a href="customer_add.php" class="btn btn-add">
                    <i class="fas fa-plus"></i> 新增
                </a>
                <a href="customer_deal.php" class="btn btn-edit">
                    <i class="fas fa-edit"></i> 編輯
                </a>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>客戶名稱</th>
                    <th>聯絡電話</th>
                    <th>右手尺寸</th>
                    <th>左手尺寸</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">沒有找到符合的資料</td>
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