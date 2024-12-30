<?php
include('condb.php');

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $leftsize = $_POST['leftsize'] ?? '';
    $rightsize = $_POST['rightsize'] ?? '';

    if (empty($name)) {
        $name = '修改顧客名稱';
    }

    $leftsize = json_encode(json_decode($leftsize, true));
    $rightsize = json_encode(json_decode($rightsize, true));

    $sql = "INSERT INTO customer (name, phone, leftsize, rightsize) VALUES (?, ?, ?, ?)";
    if ($stmt = $db->prepare($sql)) {
        $success = $stmt->execute([$name, $phone, $leftsize, $rightsize]);

        if (!$success) {
            $message = "儲存失敗! " . implode(" ", $stmt->errorInfo());
        } else {
            $message = "新增成功，客戶編號：" . $db->lastInsertId();
        }
    } else {
        $message = "SQL 準備失敗: " . implode(" ", $db->errorInfo());
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新增顧客</title>
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
        <h1><i class="fas fa-user-plus"></i> 新增顧客</h1>
        
        <a href="customer_list.php" class="back-btn">
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
                        <i class="fas fa-user"></i> 客戶名稱
                    </div>
                    <div class="form-field">
                        <input type="text" name="name" required />
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">
                        <i class="fas fa-phone"></i> 聯絡電話
                    </div>
                    <div class="form-field">
                        <input type="text" name="phone" required />
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">
                        <i class="fas fa-hand-point-left"></i> 左手尺寸
                    </div>
                    <div class="form-field">
                        <input type="text" name="leftsize" placeholder='例如: [12,14,16,14,16]' required />
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">
                        <i class="fas fa-hand-point-right"></i> 右手尺寸
                    </div>
                    <div class="form-field">
                        <input type="text" name="rightsize" placeholder='例如: [12,14,16,14,16]' required />
                    </div>
                </div>

                <div class="submit-container">
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i>
                        新增客戶資料
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>