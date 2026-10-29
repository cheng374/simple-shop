CREATE DATABASE IF NOT EXISTS simple_shop
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE simple_shop;

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

INSERT INTO products (name, description, category, price, emoji, accent, stock)
SELECT * FROM (
  SELECT '晨光陶杯', '每天早晨，從一杯溫暖的咖啡開始。', '生活選物', 420, '☕', '#f5c7a9', 30 UNION ALL
  SELECT '雲朵抱枕', '柔軟親膚的日常抱枕，讓房間多一點療癒感。', '居家好物', 680, '☁️', '#cddcf6', 24 UNION ALL
  SELECT '山系帆布袋', '耐用、輕盈，裝下每天出門需要的小物。', '日常配件', 520, '👜', '#c7dfc5', 18 UNION ALL
  SELECT '木質香氛蠟燭', '淡淡木質調，為夜晚留一段安靜時間。', '生活選物', 760, '🕯️', '#f0d9ad', 16 UNION ALL
  SELECT '手工陶盤', '手作釉色的獨特紋理，適合盛裝日常料理。', '居家好物', 580, '🍽️', '#f2c9c9', 20 UNION ALL
  SELECT '城市散步帽', '簡約剪裁與柔和色系，陪你走過每個午後。', '日常配件', 890, '🧢', '#d4c7ed', 12
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM products);

