# G03-Press-on-Nail-Salon
 113-1 DBMS G03 Press-on Nail Salon
 ```
project/
├── .git/                      # Git 版本控制資料夾（clone 後會自動生成）
├── .gitignore                 # 忽略不需要同步的檔案（例如 .env 和 log）
├── README.md                  # 專案說明文件
├── sql/                       # 資料庫 SQL 檔案目錄
│   ├── create_tables.sql      # 建立資料表的 SQL 腳本
│   ├── seed_data.sql          # 初始化數據的 SQL 腳本
│   └── update_schema.sql      # 資料庫結構更新的腳本
├── src/                       # PHP 程式碼主目錄
│   ├── index.php              # 專案入口檔案
│   ├── config/                # 配置相關檔案
│   │   └── db.php             # 資料庫連線設定
│   ├── controllers/           # 處理邏輯的控制器
│   │   └── userController.php # 範例控制器
│   ├── models/                # 資料庫操作的模型
│   │   └── User.php           # 範例模型
│   ├── views/                 # 前端視圖檔案
│   │   ├── templates/         # HTML 模板檔案
│   │   │   └── header.php     # 頁首模板
│   │   └── pages/             # 不同頁面的 PHP 檔案
│   │       ├── home.php       # 首頁
│   │       └── login.php      # 登入頁
├── assets/                    # 靜態資源
│   ├── css/                   # 樣式表
│   │   └── style.css          # 範例樣式
│   ├── js/                    # 前端 JavaScript
│   │   └── app.js             # 範例腳本
│   └── images/                # 圖片和媒體檔案
│       └── logo.png           # 範例圖片
└── logs/                      # 日誌檔案（加入 .gitignore）
    └── error.log              # 錯誤日誌
```