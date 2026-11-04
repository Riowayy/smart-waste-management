<?php

// Router khusus proses test; jangan dijalankan sebagai server development.
if (getenv('APP_ENV') !== 'testing' || !getenv('ECOTRACK_TEST_STORAGE')) {
    http_response_code(503);
    exit;
}
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$root = getenv('ECOTRACK_TEST_STORAGE');
config(['filesystems.disks.public.root' => $root, 'filesystems.disks.public.url' => getenv('APP_URL').'/storage']);
// Layani hanya file foto hasil test, tanpa mengeksekusi kontennya.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/storage/waste-reports/([a-zA-Z0-9]+\.(?:png|jpg|jpeg|webp))$#', $path, $match)) {
    $file = $root.'/waste-reports/'.$match[1];
    if (!is_file($file)) { http_response_code(404); exit; }
    header('Content-Type: '.mime_content_type($file));
    readfile($file);
    exit;
}
$app->handleRequest(Illuminate\Http\Request::capture());
