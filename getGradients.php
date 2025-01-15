<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$gradientDir = __DIR__ . '/img';
$gradients = [];

// 获取当前域名
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domain = $protocol . $_SERVER['HTTP_HOST'];

if (is_dir($gradientDir)) {
    $files = glob($gradientDir . '/*.png');
    foreach ($files as $filePath) {
        $fileName = basename($filePath);
        $gradients[] = "$domain/img/$fileName";
    }
}

echo json_encode($gradients);
?>