<?php
declare(strict_types=1);

return [
    'host' => getenv('SHOP_DB_HOST') ?: '127.0.0.1',
    'port' => getenv('SHOP_DB_PORT') ?: '3306',
    'database' => getenv('SHOP_DB_NAME') ?: 'simple_shop',
    'username' => getenv('SHOP_DB_USER') ?: 'your_mysql_user',
    'password' => getenv('SHOP_DB_PASSWORD') ?: 'your_mysql_password',
];

