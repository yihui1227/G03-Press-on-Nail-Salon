<?php
include("../condb.php");

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
            $lastId = null;
        } else {
            $message = "新增成功，ID：" . $db->lastInsertId();
            $lastId = $db->lastInsertId();
        }
    } else {
        $message = "SQL 準備失敗: " . implode(" ", $db->errorInfo());
        $lastId = null;
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新增產品</title>
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
        .menu a {
            color: white;
            text-decoration: none;
            margin-right: 10px;
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
        input[type="text"] {
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
        .hidden {
            display: none;
        }
        button {
            padding: 10px 20px;
            background-color: dimgrey;
            color: white;
            border: none;
            cursor: pointer;
            margin: 10px 0;
        }
    </style>
    <script>
        function toggleDisplay() {
            const resultTable = document.getElementById("resultTable");
            if (resultTable.classList.contains("hidden")) {
                resultTable.classList.remove("hidden");
            } else {
                resultTable.classList.add("hidden");
            }
        }

        // 價格自動計算功能
        document.addEventListener('DOMContentLoaded', function() {
            const originalInput = document.querySelector('input[name="original"]');
            const modelInput = document.querySelector('input[name="model"]');
            const friendInput = document.querySelector('input[name="friend"]');

            function calculatePrices() {
                const originalPrice = parseFloat(originalInput.value) || 0;
                modelInput.value = (originalPrice * 0.5).toFixed(0);
                friendInput.value = (originalPrice * 0.9).toFixed(0);
            }

            // 在輸入過程中即時計算價格
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
</head>
<body>
    <div class="menu">
        <a href="../index.php">Home</a> |
        <a href="customer_add.php">新增客戶</a> |
        <a href="consume_add.php">新增交易</a>
    </div>
    <div class="content">
        <h2>新增產品</h2>
        <?php if (!empty($message)): ?>
            <p class="message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <form id="productForm" method="post" action="">
            <table>
                <tr>
                    <td>產品名稱：</td>
                    <td><input type="text" name="name" required /></td>
                </tr>
                <tr>
                    <td>原價：</td>
                    <td><input type="text" name="original" required /></td>
                </tr>
                <tr>
                    <td>模特價：</td>
                    <td><input type="text" name="model" readonly /></td>
                </tr>
                <tr>
                    <td>親友價：</td>
                    <td><input type="text" name="friend" readonly /></td>
                </tr>
            </table>
            <br />
            <input type="submit" value="新增產品" />
        </form>
    </div>
</body>
</html>