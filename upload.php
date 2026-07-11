<?php
session_start();
// 复用 admin.php 的登录态
if (empty($_SESSION['logged_in'])) {
    http_response_code(403);
    die('未登录');
}

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $f = $_FILES['file'];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        $msg = '上传失败，错误码：' . $f['error'];
    } else {
        // 只取文件名，丢弃任何路径
        $safeName = basename($f['name']);
        // 简单扩展名白名单（按需增删）
        $ext = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
        $allowed = ['zip','rar','7z','pdf','doc','docx','xls','xlsx','ppt','pptx',
                    'txt','md','jpg','jpeg','png','gif','mp3','mp4','exe','apk'];
        if (!in_array($ext, $allowed)) {
            $msg = '不允许的文件类型：' . $ext;
        } else {
            $target = $uploadDir . $safeName;
            // 同名加序号
            if (file_exists($target)) {
                $i = 1;
                while (file_exists($uploadDir . pathinfo($safeName, PATHINFO_FILENAME) . "_$i." . $ext)) $i++;
                $safeName = pathinfo($safeName, PATHINFO_FILENAME) . "_$i." . $ext;
                $target = $uploadDir . $safeName;
            }
            if (move_uploaded_file($f['tmp_name'], $target)) {
                $msg = '上传成功：' . $safeName;
            } else {
                $msg = '保存失败，检查目录权限';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>上传文件 - 个人云盘</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>上传文件</h1>
    <a href="admin.php" style="font-size:0.9rem;">← 返回后台</a>
    <?php if($msg): ?>
        <p style="background:var(--bg-soft); padding:1rem; border-left:2px solid var(--fg); margin:1rem 0;"><?php echo htmlspecialchars($msg); ?></p>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="gb-form" style="border:none;padding:0;">
        <input type="file" name="file" required style="margin-bottom:1rem;">
        <button type="submit" id="submit-btn" style="width:100%;">上传到云盘</button>
    </form>
    <p style="margin-top:1rem;font-size:0.85rem;color:var(--fg-4);">上传后的下载页：<a href="/projects">/projects</a></p>
</div>
</body>
</html>
