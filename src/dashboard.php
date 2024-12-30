<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../assets/login.html');
    exit();
}

include('condb.php');

// 獲取統計數據的函數
function getDashboardStats($db) {
    $stats = array();
    
    // 獲取顧客總數
    $stmt = $db->query("SELECT COUNT(*) as total FROM customer");
    $stats['total_customers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 獲取本月訂單數
    $stmt = $db->query("SELECT COUNT(*) as total FROM transaction WHERE MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())");
    $stats['monthly_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 獲取本月營收
    $stmt = $db->query("SELECT SUM(amount) as total FROM transaction WHERE MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())");
    $stats['monthly_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    return $stats;
}

// 獲取產品銷售數據
function getProductSales($db) {
    $stmt = $db->query("
        SELECT 
            p.name AS product_name,
            COUNT(*) AS total_orders,
            SUM(t.amount) AS total_amount
        FROM transaction t
        JOIN price p ON t.product_id = p.product_id
        WHERE t.date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        GROUP BY p.product_id, p.name
        ORDER BY total_orders DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 獲取統計數據
$stats = getDashboardStats($db);
$salesData = getProductSales($db);

?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="icon" 
      href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'><path d='M0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm320 96c0-26.9-16.5-49.9-40-59.3L280 88c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 204.7c-23.5 9.5-40 32.5-40 59.3c0 35.3 28.7 64 64 64s64-28.7 64-64zM144 176a32 32 0 1 0 0-64 32 32 0 1 0 0 64zm-16 80a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm288 32a32 32 0 1 0 0-64 32 32 0 1 0 0 64zM400 144a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z'/></svg>">
    <script src="https://unpkg.com/react@17/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom@17/umd/react-dom.production.min.js"></script>
</head>
<body>
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="dashboard-container">
        <!-- 側邊欄 -->
        <div class="sidebar" id="sidebar">
            <button class="close-menu" id="closeMenu">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="sidebar-header">
                <h1><i class="fa-solid fa-database"></i> Dashboard</h1>
                <div class="admin-info">
                    <i class="fas fa-user-circle"></i> 歡迎回來！
                </div>
            </div>

            <!-- 消費管理 -->
            <div class="menu-section">
                <h2><i class="fa-solid fa-receipt"></i> 消費管理</h2>
                <a href="consume_list.php" class="menu-item">
                    <i class="fas fa-list"></i> 消費列表
                </a>
                <a href="consume_add.php" class="menu-item">
                    <i class="fas fa-plus-circle"></i> 新增消費
                </a>
                <a href="consume_deal.php" class="menu-item">
                    <i class="fas fa-edit"></i> 編輯消費
                </a>
            </div>

            <!-- 顧客管理 -->
            <div class="menu-section">
                <h2><i class="fa-solid fa-user-tag"></i> 顧客管理</h2>
                <a href="customer_list.php" class="menu-item">
                    <i class="fas fa-users"></i>顧客列表
                </a>
                <a href="customer_add.php" class="menu-item">
                    <i class="fas fa-user-plus"></i>新增顧客
                </a>
                <a href="customer_deal.php" class="menu-item">
                    <i class="fa-solid fa-user-pen"></i>編輯顧客
                </a>
            </div>

            <!-- 產品管理 -->
            <div class="menu-section">
                <h2><i class="fa-solid fa-hand-sparkles"></i> 產品管理</h2>
                <a href="product_list.php" class="menu-item">
                    <i class="fas fa-boxes"></i>產品列表
                </a>
                <a href="product_add.php" class="menu-item">
                    <i class="fas fa-box-open"></i>新增產品
                </a>
                <a href="product_deal.php" class="menu-item">
                    <i class="fa-solid fa-cube"></i>產品管理
                </a>
            </div>

            <a href="/assets/home.html" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> exit
            </a>
        </div>

        <!-- 主要內容區域 -->
        <div class="main-content">
            <div class="welcome-section">
                <h1><i class="fa-solid fa-business-time"></i> 歡迎使用管理系統 <i class="fa-solid fa-hands-clapping"></i></h1>
                <p>今天是個美好的一天！讓我們開始工作吧！</p>
            </div>

            <!--統計資料-->
            <div class="stats-container">
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <h3>顧客總數</h3>
                    <p><?php echo $stats['total_customers']; ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-shopping-bag"></i>
                    <h3>本月訂單</h3>
                    <p><?php echo $stats['monthly_orders']; ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-dollar-sign"></i>
                    <h3>本月營收</h3>
                    <p>$<?php echo number_format($stats['monthly_revenue']); ?></p>
                </div>
            </div>

                <!-- 銷售分析圖表 -->
            <div class="analysis-section">
                <h2>
                    <i class="fas fa-chart-bar"></i> 產品銷售分析</h2>
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
   
    <script>
        const menuToggle = document.getElementById('menuToggle');
        const closeMenu = document.getElementById('closeMenu');
        const sidebar = document.getElementById('sidebar');

        menuToggle.addEventListener('click', () => {
            sidebar.classList.add('mobile-active');
        });

        closeMenu.addEventListener('click', () => {
            sidebar.classList.remove('mobile-active');
        });

        // 點擊選單外的區域時關閉選單
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                    sidebar.classList.remove('mobile-active');
                }
            }
        });

        // 視窗大小改變時重置選單狀態
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('mobile-active');
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // 獲取銷售數據
    const salesData = <?php echo json_encode($salesData); ?>;

    // 準備圖表數據
    const productNames = salesData.map(item => item.product_name);
    const orderCounts = salesData.map(item => parseInt(item.total_orders));
    const totalOrders = orderCounts.reduce((a, b) => a + b, 0);

    // 計算百分比
    const percentages = orderCounts.map(count => ((count / totalOrders) * 100).toFixed(1));

    // 創建圖表
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: productNames.map((name, index) => `${name} (${percentages[index]}%)`),
            datasets: [{
                data: orderCounts,
                backgroundColor: [
                    'rgba(243, 207, 109, 0.8)',  // 金黃色
                    'rgba(241, 166, 67, 0.8)',   // 橘色
                    'rgba(236, 127, 46, 0.8)',   // 深橘色
                    'rgba(234, 95, 30, 0.8)',    // 橘紅色
                    'rgba(230, 57, 17, 0.8)',    // 紅色
                    'rgba(186, 84, 16, 0.8)',    // 褐色
                ],
                borderColor: [
                    'rgba(243, 207, 109, 1)',
                    'rgba(241, 166, 67, 1)',
                    'rgba(236, 127, 46, 1)',
                    'rgba(234, 95, 30, 1)',
                    'rgba(230, 57, 17, 1)',
                    'rgba(186, 84, 16, 1)',
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        padding: 20,
                        font: {
                            size: 12
                        }
                    }
                },
                title: {
                    display: true,
                    text: '產品訂單數量占比',
                    font: {
                        size: 16,
                        weight: 'bold'
                    },
                    padding: {
                        top: 10,
                        bottom: 30
                    }
                }
            }
        }
    });
    </script>
</body>
</html>