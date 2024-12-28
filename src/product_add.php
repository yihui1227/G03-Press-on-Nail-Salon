<?php
include("../condb.php");

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

<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <title>新增產品</title>
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
    <h2>新增產品</h2>
    <form id="customerForm" method="post" action="">
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
          <td><input type="text" name="model" required /></td>
        </tr>
        <tr>
          <td>親友價：</td>
          <td><input type="text" name="friend" required /></td>
        </tr>
      </table>
      <br />
      <input type="submit" name="submit" value="新增產品" />
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
          <th>原價</th>
          <th>模特價</th>
          <th>親友價</th>
        </tr>
        <tr>
          <td><?php echo $lastId; ?></td>
          <td><?php echo htmlspecialchars($name); ?></td>
          <td><?php echo htmlspecialchars($original); ?></td>
          <td><?php echo htmlspecialchars($model); ?></td>
          <td><?php echo htmlspecialchars($friend); ?></td>
        </tr>
      </table>
    <?php endif; ?>
  </div>
</body>
</html>
