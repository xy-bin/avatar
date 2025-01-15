<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$uploadDir = __DIR__ . '/uploads';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$response = ['success' => false, 'message' => '', 'url' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $file = $_FILES['image'];

    // 检查上传错误
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = '上传过程中发生错误。';
        echo json_encode($response);
        exit;
    }

    // 验证文件类型
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        $response['message'] = '仅支持JPEG, PNG和GIF格式的图片。';
        echo json_encode($response);
        exit;
    }

    // 验证文件大小
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        $response['message'] = '文件大小不能超过5MB。';
        echo json_encode($response);
        exit;
    }

    // 生成唯一文件名
    $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
    $uniqueName = uniqid('avatar_', true) . '.' . $fileExt;
    $targetPath = $uploadDir . '/' . $uniqueName;

    // 移动上传文件
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // 获取当前域名
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $domain = $protocol . $_SERVER['HTTP_HOST'];

        // 构建图片URL
        $imageUrl = $domain . '/uploads/' . $uniqueName;

        $response['success'] = true;
        $response['message'] = '文件上传成功。';
        $response['url'] = $imageUrl;
    } else {
        $response['message'] = '文件移动失败。';
    }
} else {
    $response['message'] = '无效的请求。';
}

echo json_encode($response);
?>