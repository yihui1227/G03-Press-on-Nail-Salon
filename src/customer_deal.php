<?php
session_start();
include('condb.php');

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

    // 處理刪除操作
    if (isset($_GET['delete_id']) && isset($_GET['csrf_token'])) {
        if (!hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
            throw new Exception('CSRF token validation failed');
        }
        
        $delete_id = filter_var($_GET['delete_id'], FILTER_VALIDATE_INT);
        if ($delete_id) {
            $delete_sql = "DELETE FROM Customer WHERE customer_id = ?";
            $delete_stmt = $db->prepare($delete_sql);
            if ($delete_stmt->execute([$delete_id])) {
                showMessage("資料刪除成功！");
            } else {
                showMessage("資料刪除失敗！", "error");
            }
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // 處理修改操作
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])) {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception('CSRF token validation failed');
        }

        $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
        $name = filter_var($_POST['name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $phone = filter_var($_POST['phone'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        $rightsize = isset($_POST['rightsize']) ? 
            json_encode(array_map('intval', explode(',', $_POST['rightsize']))) : 
            '[]';
        
        $leftsize = isset($_POST['leftsize']) ? 
            json_encode(array_map('intval', explode(',', $_POST['leftsize']))) : 
            '[]';

        if ($id && $name && $phone) {
            $update_sql = "UPDATE Customer SET name = ?, phone = ?, rightSize = ?, leftSize = ? WHERE customer_id = ?";
            $update_stmt = $db->prepare($update_sql);
            if ($update_stmt->execute([$name, $phone, $rightsize, $leftsize, $id])) {
                showMessage("資料更新成功！");
            } else {
                showMessage("資料更新失敗！", "error");
            }
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
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
    <title>編輯顧客</title>
    <link rel="stylesheet" href="/assets/css/sub.css">
    <link rel="stylesheet" href="/assets/css/admin_nav.css">
    <link rel="stylesheet" href="/assets/css/edit.css">
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
        <h1><i class="fas fa-user-edit"></i> 編輯客戶</h1>
        
        <a href="customer_list.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> 返回列表
        </a>

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
                <input type="text" name="search" placeholder="搜尋客戶名稱或電話..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-search">
                    <i class="fas fa-search"></i> 搜尋
                </button>
            </form>
        </div>

        <table id="customerTable">
            <thead>
                <tr>
                    <th>客戶名稱</th>
                    <th>聯絡電話</th>
                    <th>右手尺寸</th>
                    <th>左手尺寸</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">沒有找到符合的資料</td>
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
                            <td>
                                <i class="fas fa-trash icon-btn delete-btn" 
                                onclick="deleteCustomer(<?php echo $customer['customer_id']; ?>)"></i>
                                <i class="fas fa-edit icon-btn edit-btn" 
                                onclick="startEdit(<?php echo $customer['customer_id']; ?>)"></i>
                            </td>
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

    <script>
        let currentEditingId = null;

        function startEdit(id) {
            if (currentEditingId && currentEditingId !== id) {
                if (confirm('有未儲存的修改，是否要儲存？')) {
                    saveEdit(currentEditingId);
                } else {
                    cancelEdit(currentEditingId);
                }
            }

            const row = document.querySelector(`tr[data-id="${id}"]`);
            currentEditingId = id;
            row.classList.add('edit-mode');

            const cells = row.cells;
            
            // 客戶名稱
            cells[0].innerHTML = `<input type="text" value="${cells[0].textContent.trim()}" name="name" required>`;
            
            // 聯絡電話
            cells[1].innerHTML = `<input type="text" value="${cells[1].textContent.trim()}" name="phone" required>`;
            
            // 右手尺寸
            cells[2].innerHTML = `<input type="text" value="${cells[2].textContent.trim()}" name="rightsize">`;
            
            // 左手尺寸
            cells[3].innerHTML = `<input type="text" value="${cells[3].textContent.trim()}" name="leftsize">`;
            
            // 操作按鈕
            cells[4].innerHTML = `
                <i class="fas fa-check icon-btn save-btn" onclick="confirmSave(${id})"></i>
                <i class="fas fa-times icon-btn cancel-btn" onclick="cancelEdit(${id})"></i>
                <i class="fas fa-trash icon-btn delete-btn" onclick="deleteCustomer(${id})"></i>
            `;
        }

        function confirmSave(id) {
            if (confirm('確定要儲存修改嗎？')) {
                saveEdit(id);
            }
        }

        function saveEdit(id) {
            const row = document.querySelector(`tr[data-id="${id}"]`);
            const formData = new FormData();
            
            formData.append('id', id);
            formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
            formData.append('name', row.querySelector('input[name="name"]').value);
            formData.append('phone', row.querySelector('input[name="phone"]').value);
            formData.append('rightsize', row.querySelector('input[name="rightsize"]').value);
            formData.append('leftsize', row.querySelector('input[name="leftsize"]').value);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('儲存失敗，請稍後再試。');
            });
        }

        function cancelEdit(id) {
            window.location.reload();
        }

        function deleteCustomer(id) {
            if (currentEditingId && currentEditingId !== id) {
                if (confirm('有未儲存的修改，是否要儲存？')) {
                    saveEdit(currentEditingId);
                    return;
                }
                cancelEdit(currentEditingId);
            }
            
            if (confirm('確定要刪除此筆資料嗎？')) {
                window.location.href = `?delete_id=${id}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`;
            }
        }

        // 攔截所有可能導致離開編輯狀態的操作
        window.addEventListener('beforeunload', function(e) {
            if (currentEditingId) {
                e.preventDefault();
                e.returnValue = '有未儲存的修改，確定要離開嗎？';
            }
        });

        // 攔截分頁和搜尋操作
        document.querySelectorAll('.pagination a, .search-box form').forEach(element => {
            element.addEventListener('click', function(e) {
                if (currentEditingId) {
                    if (confirm('有未儲存的修改，是否要儲存？')) {
                        e.preventDefault();
                        saveEdit(currentEditingId);
                    } else {
                        cancelEdit(currentEditingId);
                    }
                }
            });
        });
    </script>
</body>
</html>