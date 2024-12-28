<?php
include("../condb.php");

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
    <title>新增客戶</title>
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
    </script>
</head>
<body>
    <div class="menu">
        <a href="../index.php">Home</a> |
        <a href="consume_add.php">新增交易</a> |
        <a href="product_add.php">新增產品</a>
    </div>
    <div class="content">
        <h2>新增客戶</h2>
        <?php if (!empty($message)): ?>
            <p class="message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <form id="customerForm" method="post" action="">
            <table>
                <tr>
                    <td>客戶名稱：</td>
                    <td><input type="text" name="name" required /></td>
                </tr>
                <tr>
                    <td>聯絡電話：</td>
                    <td><input type="text" name="phone" required /></td>
                </tr>
                <tr>
                    <td>左手尺寸：</td>
                    <td><input type="text" name="leftsize" placeholder='例如: [12,14,16,14,16]' required /></td>
                </tr>
                <tr>
                    <td>右手尺寸：</td>
                    <td><input type="text" name="rightsize" placeholder='例如: [12,14,16,14,16]' required /></td>
                </tr>
            </table>
            <br />
            <input type="submit" value="新增客戶" />
        </form>
        
    </div>
</body>
</html>