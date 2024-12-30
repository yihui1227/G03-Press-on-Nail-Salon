<?php
include('condb.php');

$message = "";

// 首先檢查並創建trigger
$checkTriggerSQL = "SHOW TRIGGERS LIKE 'calculate_prices_before_insert'";
$result = $db->query($checkTriggerSQL);

if ($result->rowCount() == 0) {
    // Trigger 不存在，創建它
    $createTriggerSQL = "
        CREATE TRIGGER calculate_prices_before_insert 
        BEFORE INSERT ON price
        FOR EACH ROW
        BEGIN
            IF NEW.original IS NOT NULL AND NEW.original != '' THEN
                -- 設定模特價為原價的0.5倍
                IF NEW.model IS NULL OR NEW.model = '' THEN
                    SET NEW.model = NEW.original * 0.5;
                END IF;
                
                -- 設定親友價為原價的0.9倍
                IF NEW.friend IS NULL OR NEW.friend = '' THEN
                    SET NEW.friend = NEW.original * 0.9;
                END IF;
            END IF;
        END;
    ";
    
    try {
        $db->exec($createTriggerSQL);
    } catch (PDOException $e) {
        error_log("Create trigger failed: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $original = $_POST['original'] ?? '';
    $model = $_POST['model'] ?? '';
    $friend = $_POST['friend'] ?? '';

    if (empty($name)) {
        $name = '修改產品名稱';
    }

    $sql = "INSERT INTO price (name, original, model, friend) VALUES (?, ?, ?, ?)";
    if ($stmt = $db->prepare($sql)) {
        $success = $stmt->execute([$name, $original, $model, $friend]);

        if (!$success) {
            $message = "儲存失敗! " . implode(" ", $stmt->errorInfo());
        } else {
            $message = "新增成功，產品編號：" . $db->lastInsertId();
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
    <title>新增產品</title>
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
        <h1><i class="fas fa-box-open"></i> 新增產品</h1>
        
        <a href="product_list.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> 返回列表
        </a>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, '成功') !== false ? '' : 'error'; ?>">
                <i class="<?php echo strpos($message, '成功') !== false ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form id="productForm" method="post" action="">
                <div class="form-row">
                    <div class="form-label">
                        <i class="fas fa-tag"></i> 產品名稱
                    </div>
                    <div class="form-field">
                        <input type="text" name="name" required />
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">
                        <i class="fas fa-dollar-sign"></i> 原價
                    </div>
                    <div class="form-field">
                        <input type="text" name="original" required />
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">
                        <i class="fas fa-user-tie"></i> 模特價
                    </div>
                    <div class="form-field">
                        <input type="text" name="model" readonly />
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-label">
                        <i class="fas fa-users"></i> 親友價
                    </div>
                    <div class="form-field">
                        <input type="text" name="friend" readonly />
                    </div>
                </div>

                <div class="submit-container">
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i>
                        新增產品資料
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const originalInput = document.querySelector('input[name="original"]');
            const modelInput = document.querySelector('input[name="model"]');
            const friendInput = document.querySelector('input[name="friend"]');

            function calculatePrices() {
                const originalPrice = parseFloat(originalInput.value) || 0;
                modelInput.value = (originalPrice * 0.5).toFixed(0);
                friendInput.value = (originalPrice * 0.9).toFixed(0);
            }

            originalInput.addEventListener('input', calculatePrices);
            
            if (originalInput.value) {
                calculatePrices();
            }

            document.getElementById('productForm').addEventListener('submit', function(e) {
                const original = parseFloat(originalInput.value);
                if (!original || original <= 0) {
                    alert('請輸入有效的原價！');
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
</body>
</html>