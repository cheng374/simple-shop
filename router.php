<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (str_contains($path, '..')) {
    http_response_code(404);
    exit;
}

if ($path === '/api' || $path === '/api.php' || str_starts_with($path, '/api/')) {
    require __DIR__ . '/backend/api.php';
    exit;
}

$assetPath = str_starts_with($path, '/public/') ? substr($path, 7) : $path;
$publicPath = __DIR__ . '/public' . ($path === '/' ? '/index.html' : $assetPath);
if (is_file($publicPath)) {
    $mimeTypes = [
        'html' => 'text/html; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
    ];
    $extension = strtolower(pathinfo($publicPath, PATHINFO_EXTENSION));
    header('Content-Type: ' . ($mimeTypes[$extension] ?? 'application/octet-stream'));
    readfile($publicPath);
    exit;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo "Page not found";

