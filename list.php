<?php
// list.php — 公开云盘文件列表接口（供 projects.html 调用）
// 返回 JSON: [{"name","size","time"}, ...]，time 为 Unix 时间戳（秒）。

header('Content-Type: application/json; charset=utf-8');

$dir = __DIR__ . '/uploads/';
$files = [];

if (is_dir($dir)) {
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $path = $dir . $f;
        if (!is_file($path)) continue;
        $files[] = [
            'name' => $f,
            'size' => filesize($path),
            'time' => filemtime($path),
        ];
    }
}

echo json_encode($files);
