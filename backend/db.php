<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';
    $database = (string) $config['database'];
    if (!preg_match('/^[A-Za-z0-9_]+$/', $database)) {
        throw new RuntimeException('資料庫名稱格式不正確。');
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $serverDsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $config['host'], $config['port']);
    $server = new PDO($serverDsn, $config['username'], $config['password'], $options);
    $server->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $database);
    $pdo = new PDO($dsn, $config['username'], $config['password'], $options);

    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        CREATE TABLE IF NOT EXISTS admins (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        CREATE TABLE IF NOT EXISTS products (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            description TEXT NOT NULL,
            category VARCHAR(50) NOT NULL,
            price INT UNSIGNED NOT NULL,
            emoji VARCHAR(16) NOT NULL,
            accent VARCHAR(20) NOT NULL,
            stock INT UNSIGNED NOT NULL DEFAULT 20,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        CREATE TABLE IF NOT EXISTS cart_items (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            product_id INT UNSIGNED NOT NULL,
            quantity INT UNSIGNED NOT NULL,
            UNIQUE KEY unique_user_product (user_id, product_id),
            CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        CREATE TABLE IF NOT EXISTS orders (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            recipient VARCHAR(100) NOT NULL,
            address VARCHAR(255) NOT NULL,
            total INT UNSIGNED NOT NULL,
            status VARCHAR(30) NOT NULL DEFAULT '待處理',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        CREATE TABLE IF NOT EXISTS order_items (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            order_id INT UNSIGNED NOT NULL,
            product_id INT UNSIGNED NOT NULL,
            product_name VARCHAR(150) NOT NULL,
            price INT UNSIGNED NOT NULL,
            quantity INT UNSIGNED NOT NULL,
            CONSTRAINT fk_order_item_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    SQL);

    $adminCount = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    $adminEmail = getenv('SHOP_ADMIN_EMAIL') ?: 'admin@simple-shop.local';
    $adminPassword = getenv('SHOP_ADMIN_PASSWORD') ?: '';
    if ($adminCount === 0 && $adminPassword !== '') {
        $admin = $pdo->prepare('INSERT INTO admins (name, email, password_hash) VALUES (?, ?, ?)');
        $admin->execute(['商店管理員', $adminEmail, password_hash($adminPassword, PASSWORD_DEFAULT)]);
    }

    $count = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    if ($count === 0) {
        $seed = $pdo->prepare(
            'INSERT INTO products (name, description, category, price, emoji, accent, stock)
             VALUES (:name, :description, :category, :price, :emoji, :accent, :stock)'
        );
        $products = [
            ['晨光陶杯', '每天早晨，從一杯溫暖的咖啡開始。', '生活選物', 420, '☕', '#f5c7a9', 30],
            ['雲朵抱枕', '柔軟親膚的日常抱枕，讓房間多一點療癒感。', '居家好物', 680, '☁️', '#cddcf6', 24],
            ['山系帆布袋', '耐用、輕盈，裝下每天出門需要的小物。', '日常配件', 520, '👜', '#c7dfc5', 18],
            ['木質香氛蠟燭', '淡淡木質調，為夜晚留一段安靜時間。', '生活選物', 760, '🕯️', '#f0d9ad', 16],
            ['手工陶盤', '手作釉色的獨特紋理，適合盛裝日常料理。', '居家好物', 580, '🍽️', '#f2c9c9', 20],
            ['城市散步帽', '簡約剪裁與柔和色系，陪你走過每個午後。', '日常配件', 890, '🧢', '#d4c7ed', 12],
        ];
        foreach ($products as $product) {
            $seed->execute([
                ':name' => $product[0], ':description' => $product[1], ':category' => $product[2],
                ':price' => $product[3], ':emoji' => $product[4], ':accent' => $product[5], ':stock' => $product[6],
            ]);
        }
    }

    return $pdo;
}

function userPayload(array $user): array
{
    return ['id' => (int) $user['id'], 'name' => $user['name'], 'email' => $user['email']];
}

