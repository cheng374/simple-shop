<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function input(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $_POST;
}

function reply(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fail(string $message, int $status = 400): never
{
    reply(['ok' => false, 'message' => $message], $status);
}

function loggedInUser(): ?array
{
    if (empty($_SESSION['user_id'])) return null;
    $statement = db()->prepare('SELECT id, name, email FROM users WHERE id = ?');
    $statement->execute([(int) $_SESSION['user_id']]);
    $user = $statement->fetch();
    return $user ?: null;
}

function needUser(): array
{
    $user = loggedInUser();
    if (!$user) fail('請先登入帳號。', 401);
    return $user;
}

function loggedInAdmin(): ?array
{
    if (empty($_SESSION['admin_id'])) return null;
    $statement = db()->prepare('SELECT id, name, email FROM admins WHERE id = ?');
    $statement->execute([(int) $_SESSION['admin_id']]);
    $admin = $statement->fetch();
    return $admin ?: null;
}

function needAdmin(): array
{
    $admin = loggedInAdmin();
    if (!$admin) fail('請先登入商店後台。', 401);
    return $admin;
}

function adminProductRows(): array
{
    $statement = db()->query('SELECT id, name, description, category, price, emoji, accent, stock, created_at FROM products ORDER BY id DESC');
    return array_map(static function (array $row): array {
        $row['id'] = (int) $row['id'];
        $row['price'] = (int) $row['price'];
        $row['stock'] = (int) $row['stock'];
        return $row;
    }, $statement->fetchAll());
}

function adminOrderRows(): array
{
    $statement = db()->query("SELECT o.id, o.recipient, o.address, o.total, o.status, o.created_at, u.name AS customer_name, u.email AS customer_email, GROUP_CONCAT(CONCAT(oi.product_name, ' ×', oi.quantity) SEPARATOR '、') AS items FROM orders o JOIN users u ON u.id = o.user_id LEFT JOIN order_items oi ON oi.order_id = o.id GROUP BY o.id ORDER BY o.id DESC");
    return array_map(static function (array $row): array {
        $row['id'] = (int) $row['id'];
        $row['total'] = (int) $row['total'];
        return $row;
    }, $statement->fetchAll());
}

function productRows(): array
{
    $query = trim((string) ($_GET['q'] ?? ''));
    $category = trim((string) ($_GET['category'] ?? ''));
    $sql = 'SELECT id, name, description, category, price, emoji, accent, stock FROM products WHERE stock > 0';
    $params = [];
    if ($query !== '') { $sql .= ' AND (name LIKE :query OR description LIKE :query)'; $params[':query'] = '%' . $query . '%'; }
    if ($category !== '' && $category !== '全部') { $sql .= ' AND category = :category'; $params[':category'] = $category; }
    $sql .= ' ORDER BY id DESC';
    $statement = db()->prepare($sql);
    $statement->execute($params);
    return array_map(static function (array $row): array {
        $row['id'] = (int) $row['id']; $row['price'] = (int) $row['price']; $row['stock'] = (int) $row['stock']; return $row;
    }, $statement->fetchAll());
}

function cartPayload(int $userId): array
{
    $statement = db()->prepare('SELECT c.id AS item_id, c.quantity, p.id, p.name, p.price, p.emoji, p.accent, p.stock FROM cart_items c JOIN products p ON p.id = c.product_id WHERE c.user_id = ? ORDER BY c.id DESC');
    $statement->execute([$userId]);
    $items = array_map(static function (array $row): array {
        $row['item_id'] = (int) $row['item_id']; $row['id'] = (int) $row['id']; $row['quantity'] = (int) $row['quantity']; $row['price'] = (int) $row['price']; $row['stock'] = (int) $row['stock']; $row['subtotal'] = $row['price'] * $row['quantity']; return $row;
    }, $statement->fetchAll());
    return ['items' => $items, 'count' => array_sum(array_column($items, 'quantity')), 'total' => array_sum(array_column($items, 'subtotal'))];
}

$action = (string) ($_GET['action'] ?? 'products');
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

try {
    switch ($action) {
        case 'products': reply(['ok' => true, 'products' => productRows()]);
        case 'me': $user = loggedInUser(); reply(['ok' => true, 'user' => $user ? userPayload($user) : null]);
        case 'admin_me':
            $admin = loggedInAdmin();
            reply(['ok' => true, 'admin' => $admin]);
        case 'admin_login':
            if ($method !== 'POST') fail('不支援的請求方式。', 405);
            $data = input();
            $email = strtolower(trim((string) ($data['email'] ?? '')));
            $password = (string) ($data['password'] ?? '');
            $statement = db()->prepare('SELECT * FROM admins WHERE email = ?');
            $statement->execute([$email]);
            $admin = $statement->fetch();
            if (!$admin || !password_verify($password, $admin['password_hash'])) fail('後台 Email 或密碼不正確。', 401);
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            reply(['ok' => true, 'message' => '後台登入成功。', 'admin' => ['id' => (int) $admin['id'], 'name' => $admin['name'], 'email' => $admin['email']]]);
        case 'admin_logout':
            unset($_SESSION['admin_id']);
            reply(['ok' => true, 'message' => '已登出後台。']);
        case 'admin_stats':
            needAdmin();
            $pdo = db();
            $stats = [
                'products' => (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
                'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
                'orders' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
                'revenue' => (int) $pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status <> '已取消'")->fetchColumn(),
                'pending' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = '待處理'")->fetchColumn(),
            ];
            reply(['ok' => true, 'stats' => $stats]);
        case 'admin_products':
            needAdmin();
            reply(['ok' => true, 'products' => adminProductRows()]);
        case 'admin_product_save':
            if ($method !== 'POST') fail('不支援的請求方式。', 405);
            needAdmin();
            $data = input();
            $id = (int) ($data['id'] ?? 0);
            $name = trim((string) ($data['name'] ?? ''));
            $description = trim((string) ($data['description'] ?? ''));
            $category = trim((string) ($data['category'] ?? ''));
            $price = max(0, (int) ($data['price'] ?? 0));
            $emoji = trim((string) ($data['emoji'] ?? '🛍️')) ?: '🛍️';
            $accent = trim((string) ($data['accent'] ?? '#dce8dc')) ?: '#dce8dc';
            $stock = max(0, (int) ($data['stock'] ?? 0));
            if ($name === '' || $description === '' || $category === '' || $price <= 0) fail('請完整填寫商品名稱、說明、分類與價格。');
            if ($id > 0) {
                $statement = db()->prepare('UPDATE products SET name = ?, description = ?, category = ?, price = ?, emoji = ?, accent = ?, stock = ? WHERE id = ?');
                $statement->execute([$name, $description, $category, $price, $emoji, $accent, $stock, $id]);
                if ($statement->rowCount() === 0) {
                    $exists = db()->prepare('SELECT id FROM products WHERE id = ?');
                    $exists->execute([$id]);
                    if (!$exists->fetchColumn()) fail('找不到要修改的商品。', 404);
                }
                reply(['ok' => true, 'message' => '商品已更新。']);
            }
            $statement = db()->prepare('INSERT INTO products (name, description, category, price, emoji, accent, stock) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $statement->execute([$name, $description, $category, $price, $emoji, $accent, $stock]);
            reply(['ok' => true, 'message' => '商品已新增。', 'id' => (int) db()->lastInsertId()]);
        case 'admin_product_delete':
            if ($method !== 'POST') fail('不支援的請求方式。', 405);
            needAdmin();
            $id = (int) (input()['id'] ?? 0);
            if ($id <= 0) fail('商品編號不正確。');
            $statement = db()->prepare('DELETE FROM products WHERE id = ?');
            $statement->execute([$id]);
            if ($statement->rowCount() !== 1) fail('找不到要刪除的商品。', 404);
            reply(['ok' => true, 'message' => '商品已刪除。']);
        case 'admin_orders':
            needAdmin();
            reply(['ok' => true, 'orders' => adminOrderRows()]);
        case 'admin_order_status':
            if ($method !== 'POST') fail('不支援的請求方式。', 405);
            needAdmin();
            $data = input();
            $id = (int) ($data['id'] ?? 0);
            $status = trim((string) ($data['status'] ?? ''));
            $allowed = ['待處理', '已確認', '配送中', '已完成', '已取消'];
            if ($id <= 0 || !in_array($status, $allowed, true)) fail('訂單狀態不正確。');
            $statement = db()->prepare('UPDATE orders SET status = ? WHERE id = ?');
            $statement->execute([$status, $id]);
            reply(['ok' => true, 'message' => '訂單狀態已更新。']);
        case 'register':
            if ($method !== 'POST') fail('不支援的請求方式。', 405);
            $data = input(); $name = trim((string) ($data['name'] ?? '')); $email = strtolower(trim((string) ($data['email'] ?? ''))); $password = (string) ($data['password'] ?? '');
            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) fail('請填寫有效姓名、Email，且密碼至少 6 碼。');
            try { $statement = db()->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)'); $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]); }
            catch (PDOException $exception) { if (($exception->errorInfo[0] ?? '') === '23000' || (int) ($exception->errorInfo[1] ?? 0) === 1062) fail('這個 Email 已經註冊過了。'); throw $exception; }
            $_SESSION['user_id'] = (int) db()->lastInsertId(); reply(['ok' => true, 'message' => '註冊成功，歡迎加入。', 'user' => userPayload(loggedInUser())]);
        case 'login':
            if ($method !== 'POST') fail('不支援的請求方式。', 405);
            $data = input(); $email = strtolower(trim((string) ($data['email'] ?? ''))); $password = (string) ($data['password'] ?? '');
            $statement = db()->prepare('SELECT * FROM users WHERE email = ?'); $statement->execute([$email]); $user = $statement->fetch();
            if (!$user || !password_verify($password, $user['password_hash'])) fail('Email 或密碼不正確。', 401);
            $_SESSION['user_id'] = (int) $user['id']; reply(['ok' => true, 'message' => '登入成功。', 'user' => userPayload($user)]);
        case 'logout':
            $_SESSION = []; if (ini_get('session.use_cookies')) { $parameters = session_get_cookie_params(); setcookie(session_name(), '', time() - 42000, $parameters['path'], $parameters['domain'], $parameters['secure'], $parameters['httponly']); } session_destroy(); reply(['ok' => true, 'message' => '已登出。']);
        case 'cart': $user = needUser(); reply(['ok' => true, 'cart' => cartPayload((int) $user['id'])]);
        case 'cart_add':
            if ($method !== 'POST') fail('不支援的請求方式。', 405); $user = needUser(); $data = input(); $productId = (int) ($data['product_id'] ?? 0); $quantity = max(1, (int) ($data['quantity'] ?? 1));
            $productStatement = db()->prepare('SELECT id, stock FROM products WHERE id = ?'); $productStatement->execute([$productId]); $product = $productStatement->fetch(); if (!$product) fail('找不到這項商品。', 404);
            $existingStatement = db()->prepare('SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?'); $existingStatement->execute([(int) $user['id'], $productId]); $existing = $existingStatement->fetch(); $nextQuantity = $quantity + ($existing ? (int) $existing['quantity'] : 0); if ($nextQuantity > (int) $product['stock']) fail('商品庫存不足。');
            if ($existing) { $statement = db()->prepare('UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?'); $statement->execute([$nextQuantity, (int) $user['id'], $productId]); }
            else { $statement = db()->prepare('INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)'); $statement->execute([(int) $user['id'], $productId, $quantity]); }
            reply(['ok' => true, 'message' => '商品已加入購物車。', 'cart' => cartPayload((int) $user['id'])]);
        case 'cart_update':
            if ($method !== 'POST') fail('不支援的請求方式。', 405); $user = needUser(); $data = input(); $itemId = (int) ($data['item_id'] ?? 0); $quantity = (int) ($data['quantity'] ?? 0);
            if ($quantity <= 0) { $statement = db()->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?'); $statement->execute([$itemId, (int) $user['id']]); }
            else { $stockStatement = db()->prepare('SELECT p.stock FROM cart_items c JOIN products p ON p.id = c.product_id WHERE c.id = ? AND c.user_id = ?'); $stockStatement->execute([$itemId, (int) $user['id']]); $stock = $stockStatement->fetchColumn(); if ($stock === false) fail('找不到購物袋商品。', 404); $quantity = min($quantity, (int) $stock); $statement = db()->prepare('UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?'); $statement->execute([$quantity, $itemId, (int) $user['id']]); }
            reply(['ok' => true, 'cart' => cartPayload((int) $user['id'])]);
        case 'checkout':
            if ($method !== 'POST') fail('不支援的請求方式。', 405); $user = needUser(); $data = input(); $recipient = trim((string) ($data['recipient'] ?? '')); $address = trim((string) ($data['address'] ?? '')); if ($recipient === '' || $address === '') fail('請填寫收件人與配送地址。'); $cart = cartPayload((int) $user['id']); if (!count($cart['items'])) fail('購物車目前是空的。');
            $pdo = db(); $pdo->beginTransaction();
            try { $order = $pdo->prepare('INSERT INTO orders (user_id, recipient, address, total) VALUES (?, ?, ?, ?)'); $order->execute([(int) $user['id'], $recipient, $address, $cart['total']]); $orderId = (int) $pdo->lastInsertId(); $item = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, ?)'); $stock = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?'); foreach ($cart['items'] as $cartItem) { $stock->execute([$cartItem['quantity'], $cartItem['id'], $cartItem['quantity']]); if ($stock->rowCount() !== 1) throw new RuntimeException('商品庫存已不足。'); $item->execute([$orderId, $cartItem['id'], $cartItem['name'], $cartItem['price'], $cartItem['quantity']]); } $clear = $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?'); $clear->execute([(int) $user['id']]); $pdo->commit(); }
            catch (Throwable $exception) { $pdo->rollBack(); throw $exception; }
            reply(['ok' => true, 'message' => '訂單已送出，謝謝你的支持！', 'order_id' => $orderId]);
        case 'orders':
            $user = needUser(); $statement = db()->prepare("SELECT o.id, o.recipient, o.address, o.total, o.status, o.created_at, GROUP_CONCAT(CONCAT(oi.product_name, ' ×', oi.quantity) SEPARATOR '、') AS items FROM orders o LEFT JOIN order_items oi ON oi.order_id = o.id WHERE o.user_id = ? GROUP BY o.id ORDER BY o.id DESC"); $statement->execute([(int) $user['id']]); $orders = array_map(static function (array $row): array { $row['id'] = (int) $row['id']; $row['total'] = (int) $row['total']; return $row; }, $statement->fetchAll()); reply(['ok' => true, 'orders' => $orders]);
        default: fail('找不到 API。', 404);
    }
} catch (Throwable $exception) {
    error_log($exception->getMessage()); fail('伺服器發生錯誤，請稍後再試。', 500);
}

