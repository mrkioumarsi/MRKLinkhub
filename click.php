<?php
declare(strict_types=1);
const UP = '';
require __DIR__ . '/inc/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit;
}
track_click((string) ($_POST['k'] ?? ''), (int) ($_POST['id'] ?? 0));
header('Content-Type: text/plain; charset=utf-8');
echo 'ok';
