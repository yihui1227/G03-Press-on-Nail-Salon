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

<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <title>新增客戶</title>
  <style>
    body {
      margin: 0px;
    }
    a {
      text-decoration: none;
      font-family: 微軟正黑體,新細明體,標楷體;
      font-weight: bold;
      font-size: 17px;
    }
    .menu {
      position: fixed;
      width: 100%;
      height: 40px;
      background-color: dimgrey;
      z-index: 9999999;
    }
    .menu a {
      color: white;
    }
    .content {
      position: relative;
      top: 40px;
      background-color: #f1f1f1;
      padding: 20px;
    }
    table {
      width: 100%;
      background-color: white;
      border-collapse: collapse;
    }
    th, td {
      padding: 10px;
      text-align: left;
    }
    input[type="text"] {
      width: 100%;
      padding: 5px;
    }
    .hidden {
      display: none;
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
    <table>
      <tr>
        <td><a href="../index.php">Home</a></td>
        <td><a href="customer_list.php">客戶列表</a></td>
        <td><a href="customer_add.php">新增客戶</a></td>
      </tr>
    </table>
  </div>
  <div class="content">
    <h2>新增客戶</h2>
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
      <input type="submit" name="submit" value="新增客戶" />
    </form>

    <br />

    <!-- 顯示新增結果 -->
    <h3><?php echo $message; ?></h3>

    <!-- 顯示按鈕和結果表格 -->
    <?php if ($lastId): ?>
      <button onclick="toggleDisplay()">顯示/隱藏新增資料</button>
      <table id="resultTable" class="hidden">
        <tr>
          <th>ID</th>
          <th>名稱</th>
          <th>電話</th>
          <th>左手尺寸</th>
          <th>右手尺寸</th>
        </tr>
        <tr>
          <td><?php echo $lastId; ?></td>
          <td><?php echo htmlspecialchars($name); ?></td>
          <td><?php echo htmlspecialchars($phone); ?></td>
          <td><?php echo htmlspecialchars($leftsize); ?></td>
          <td><?php echo htmlspecialchars($rightsize); ?></td>
        </tr>
      </table>
    <?php endif; ?>
  </div>
</body>
</html>