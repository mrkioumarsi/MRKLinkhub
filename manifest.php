<?php
declare(strict_types=1);
const UP = '';
require __DIR__ . '/inc/bootstrap.php';

$s = db()->query('SELECT k, v FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
$v = $s['icon'] ?? '';
$icons = ($v !== '' && is_file(__DIR__ . '/uploads/icon-512.png'))
    ? [
        ['src' => "uploads/icon-192.png?v=$v", 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => "uploads/icon-512.png?v=$v", 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
    ]
    : [['src' => 'assets/icon.svg', 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'any']];
$color = $s['theme_color'] ?? '#6c5ce7';

header('Content-Type: application/manifest+json; charset=utf-8');
echo json_encode([
    'name' => ($s['app_name'] ?? '') ?: 'My Links',
    'short_name' => ($s['short_name'] ?? '') ?: 'Links',
    'start_url' => './',
    'scope' => './',
    'display' => 'standalone',
    'background_color' => $color,
    'theme_color' => $color,
    'icons' => $icons,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
