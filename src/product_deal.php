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
        $searchCondition = "WHERE name LIKE ?";
        $searchParams = ["%$search%"];
    }

    // 處理刪除操作
    if (isset($_GET['delete_id']) && isset($_GET['csrf_token'])) {
        if (!hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
            throw new Exception('CSRF token validation failed');
        }
        
        $delete_id = filter_var($_GET['delete_id'], FILTER_VALIDATE_INT);
        if ($delete_id) {
            $delete_sql = "DELETE FROM price WHERE product_id = ?";
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
        $original = filter_var($_POST['original'], FILTER_VALIDATE_INT);
        $model = filter_var($_POST['model'], FILTER_VALIDATE_INT);
        $friend = filter_var($_POST['friend'], FILTER_VALIDATE_INT);

        if ($original < 0 || $model<0 || $friend<0) {
            // 返回錯誤，避免處理負值價格
            die("Error: Price cannot be negative.");
        }

        if ($id && $name) {
            $update_sql = "UPDATE price SET name = ?, original = ?, model = ?, friend = ? WHERE product_id = ?";
            $update_stmt = $db->prepare($update_sql);
            if ($update_stmt->execute([$name, $original, $model, $friend, $id])) {
                showMessage("資料更新成功！");
            } else {
                showMessage("資料更新失敗！", "error");
            }
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // 獲取總記錄數
    $count_sql = "SELECT COUNT(*) FROM price $searchCondition";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($searchParams);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $perPage);

    // 獲取產品資料
    $sql = "SELECT * FROM price $searchCondition ORDER BY product_id DESC LIMIT ? OFFSET ?";
    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge($searchParams, [$perPage, $offset]));
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    showMessage("系統錯誤：" . $e->getMessage(), "error");
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>編輯產品</title>
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
        <h1><i class="fas fa-edit"></i> 編輯產品</h1>
        
        <a href="product_list.php" class="back-btn">
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
                <input type="text" name="search" placeholder="搜尋產品名稱..." 
                    value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-search">
                    <i class="fas fa-search"></i> 搜尋
                </button>
            </form>
        </div>

        <table id="productTable">
            <thead>
                <tr>
                    <th>產品編號</th>
                    <th>產品名稱</th>
                    <th>原價</th>
                    <th>模特價</th>
                    <th>親友價</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">沒有找到符合的資料</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <tr id="row-<?php echo $product['product_id']; ?>" data-id="<?php echo $product['product_id']; ?>">
                            <td><?php echo htmlspecialchars($product['product_id']); ?></td>
                            <td data-field="name"><?php echo htmlspecialchars($product['name']); ?></td>
                            <td data-field="original"><?php echo htmlspecialchars($product['original'] ?? '-'); ?></td>
                            <td data-field="model"><?php echo htmlspecialchars($product['model'] ?? '-'); ?></td>
                            <td data-field="friend"><?php echo htmlspecialchars($product['friend'] ?? '-'); ?></td>
                            <td>
                                <i class="fas fa-trash icon-btn delete-btn" 
                                   onclick="deleteProduct(<?php echo $product['product_id']; ?>)"></i>
                                <i class="fas fa-edit icon-btn edit-btn" 
                                   onclick="startEdit(<?php echo $product['product_id']; ?>)"></i>
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

            const row = document.getElementById(`row-${id}`);
            currentEditingId = id;
            row.classList.add('edit-mode');

            // 產品名稱
            const nameCell = row.cells[1];
            const currentName = nameCell.textContent;
            nameCell.innerHTML = `<input type="text" value="${currentName}" required>`;

            // 原價
            const originalCell = row.cells[2];
            const currentOriginal = originalCell.textContent !== '-' ? originalCell.textContent : '';
            originalCell.innerHTML = `<input type="number" value="${currentOriginal}">`;

            // 模特價
            const modelCell = row.cells[3];
            const currentModel = modelCell.textContent !== '-' ? modelCell.textContent : '';
            modelCell.innerHTML = `<input type="number" value="${currentModel}">`;

            // 親友價
            const friendCell = row.cells[4];
            const currentFriend = friendCell.textContent !== '-' ? friendCell.textContent : '';
            friendCell.innerHTML = `<input type="number" value="${currentFriend}">`;

            // 更改按鈕
            const actionCell = row.cells[5];
            actionCell.innerHTML = `
                <i class="fas fa-check icon-btn save-btn" onclick="confirmSave(${id})"></i>
                <i class="fas fa-times icon-btn cancel-btn" onclick="cancelEdit(${id})"></i>
                <i class="fas fa-trash icon-btn delete-btn" onclick="deleteProduct(${id})"></i>
            `;
        }

        function confirmSave(id) {
            if (confirm('確定要儲存修改嗎？')) {
                saveEdit(id);
            }
        }

        function saveEdit(id) {
            const row = document.getElementById(`row-${id}`);
            const formData = new FormData();
            
            formData.append('id', id);
            formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
            formData.append('name', row.cells[1].querySelector('input').value);
            formData.append('original', row.cells[2].querySelector('input').value || null);
            formData.append('model', row.cells[3].querySelector('input').value || null);
            formData.append('friend', row.cells[4].querySelector('input').value || null);

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

        function deleteProduct(id) {
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