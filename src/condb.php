<?php
    $user = 'root'; //資料庫使用者名稱
    $password = 'yihui888'; //資料庫的密碼
    try {
        $db = new PDO ('mysql:host=localhost;dbname=g03;charset=utf8', $user, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        //echo "資料庫連線成功！";
    } catch(PDOException $e) {
        echo "資料庫連線失敗：" . $e->getMessage();
    }
?>