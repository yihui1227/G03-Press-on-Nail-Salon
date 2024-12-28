<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="/assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <nav class="sidebar">
            <div class="logo">
                <h2>Admin Panel</h2>
            </div>
            <ul>
                <li class="active">
                    <a href="#dashboard"><i class="fas fa-home"></i> Dashboard</a>
                </li>
                <li>
                    <a href="#reservations"><i class="fas fa-calendar-alt"></i> 預約管理</a>
                </li>
                <li>
                    <a href="#services"><i class="fas fa-concierge-bell"></i> 服務項目</a>
                </li>
                <li>
                    <a href="#users"><i class="fas fa-users"></i> 會員管理</a>
                </li>
                <li>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> 登出</a>
                </li>
            </ul>
        </nav>
        <main class="content">
            <header>
                <div class="header-content">
                    <h1>Welcome, Admin</h1>
                    <div class="user-info">
                        <span><?php echo $_SESSION['admin_username']; ?></span>
                        <img src="admin-avatar.png" alt="Admin">
                    </div>
                </div>
            </header>
            <div class="dashboard-stats">
                <div class="stat-card">
                    <i class="fas fa-calendar-check"></i>
                    <div class="stat-info">
                        <h3>今日預約</h3>
                        <p>15</p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <div class="stat-info">
                        <h3>總會員數</h3>
                        <p>256</p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-chart-line"></i>
                    <div class="stat-info">
                        <h3>本月營收</h3>
                        <p>$52,680</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>