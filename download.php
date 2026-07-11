<?php
// download.php — 公开云盘下载接口（供 projects.html 调用）
// 参数 ?f=<文件名>，严格防路径穿越。

$raw = isset($_GET['f']) ? $_GET['f'] : '';
$name = basename($raw);

// 防路径穿越：basename 后仍不得包含分隔符或 ..
if ($name === '' || $name === '.' || $name === '..'
    || strpos($name, '/') !== false
    || strpos($name, '\\') !== false
    || strpos($name, '..') !== false) {
    http_response_code(404);
    exit('文件不存在');
}

$path = __DIR__ . '/uploads/' . $name;

if (!is_file($path)) {
    http_response_code(404);
    exit('文件不存在');
}

// 文件名编码处理（兼容中文文件名下载）
$encoded = rawurlencode($name);

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $name . '"; filename*=UTF-8\'\'' . $encoded);
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . filesize($path));
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Expires: 0');

// 清空输出缓冲，避免文件内容前出现杂字符
while (ob_get_level()) ob_end_clean();
readfile($path);
exit;
