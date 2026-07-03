<?php
session_start();
$PASSWORD = 'xxxxxxxx'; // 改成你自己的登录密码

// ===== 登录处理 =====
if (isset($_POST['login'])) {
    if ($_POST['password'] === $PASSWORD) {
        $_SESSION['logged_in'] = true;
    } else {
        $error = "密码错误";
    }
}
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// ===== 未登录则显示登录页 =====
if (empty($_SESSION['logged_in'])) {
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <title>后台登录</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <div class="container" style="max-width: 400px; padding-top: 100px;">
            <h1>后台登录</h1>
            <?php if(isset($error)) echo '<p style="color:red;">'.$error.'</p>'; ?>
            <form method="post" class="gb-form" style="border:none; padding:0;">
                <input type="password" name="password" placeholder="输入密码" required>
                <button type="submit" name="login" style="width:100%;">登录</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ===== 发布文章逻辑 =====
$msg = '';
if (isset($_POST['publish'])) {
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']); // 英文文件名，如 my-new-post
    $desc = trim($_POST['desc']);
    $content = $_POST['content'];
    
    if ($title && $slug && $content) {
        $filename = $slug . '.md';
        $filepath = __DIR__ . '/posts/' . $filename;
        
        // 1. 保存 Markdown 文件
        file_put_contents($filepath, $content);
        
        // 2. 更新 blog.html 列表
        $blog_html_path = __DIR__ . '/blog.html';
        $blog_html = file_get_contents($blog_html_path);
        
        $date = date('Y / m / d');
        $new_card = '
<article class="card">
    <div class="meta">' . $date . ' · POST</div>
    <h3><a href="blog-post.html?file=posts/' . $filename . '">' . htmlspecialchars($title) . '</a></h3>
    <p>' . htmlspecialchars($desc) . '</p>
</article>';
        
        // 在锚点之间插入新卡片
        $blog_html = preg_replace(
            '/(<!-- BLOG_LIST_START -->)(.*?)(<!-- BLOG_LIST_END -->)/s',
            '$1' . $new_card . '$2',
            $blog_html
        );
        file_put_contents($blog_html_path, $blog_html);
        
        // 3. 自动推送给百度
        $api = 'http://data.zz.baidu.com/urls?site=https://www.stellaric.site&token=4dbTytHYWwc4Zhx1';
        $url_to_push = 'https://www.stellaric.site/blog-post.html?file=posts/' . $filename;
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $api,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $url_to_push . "\n" . 'https://www.stellaric.site/blog.html',
            CURLOPT_HTTPHEADER => ['Content-Type: text/plain'],
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $push_result = curl_exec($ch);
        //curl_close($ch);
        
        $msg = "发布成功！文件已生成，列表已更新，百度推送返回：" . $push_result;
    } else {
        $msg = "请填写所有必填字段！";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>撰写新博客</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        .admin-container { display: flex; gap: 1rem; height: 60vh; }
        .admin-input { width: 50%; padding: 1rem; border: 1px solid var(--line); font-family: var(--font-mono); resize: none; outline: none; }
        .admin-preview { width: 50%; padding: 1rem; border: 1px solid var(--line); overflow-y: auto; background: var(--bg-soft); }
        .admin-meta { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
        .admin-meta input { padding: 0.5rem; border: 1px solid var(--line); }
        @media (max-width: 768px) { .admin-container { flex-direction: column; height: auto; } .admin-input, .admin-preview { width: 100%; height: 300px; } }
    </style>
</head>
<body>
    <div class="container">
        <h1>撰写新博客</h1>
        <a href="admin.php?logout=1" style="float:right; font-size:0.9rem;">退出登录</a>
        
        <?php if($msg): ?>
            <p style="background: #f0f0f0; padding: 1rem; border-left: 3px solid var(--fg);"><?php echo $msg; ?></p>
        <?php endif; ?>

        <form method="post">
            <div class="admin-meta">
                <input type="text" name="title" placeholder="文章标题" required>
                <input type="text" name="slug" placeholder="英文文件名(如: my-post)" required>
                <input type="text" name="desc" placeholder="一句话简介" required>
            </div>
            
            <div class="admin-container">
                <textarea class="admin-input" name="content" id="md-input" placeholder="在这里输入 Markdown 正文..." oninput="renderPreview()"></textarea>
                <div class="admin-preview" id="md-preview"></div>
            </div>
            <br>
            <button type="submit" name="publish" style="background: var(--fg); color: var(--bg); border:none; padding: 0.8rem 2rem; cursor:pointer; width: 100%; font-size: 1rem;">一键发布并推送</button>
        </form>
    </div>
    <script>
        function renderPreview() {
            document.getElementById('md-preview').innerHTML = marked.parse(document.getElementById('md-input').value);
        }
    </script>
</body>
</html>
