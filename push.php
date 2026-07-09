<?php
// push.php — 百度推送统一工具
// 定义 push_to_baidu() 供 admin.php 复用；直接访问本文件时提供手动推送 UI。

if (session_status() === PHP_SESSION_NONE) session_start();

define('BAIDU_SITE', 'https://www.stellaric.site');
define('BAIDU_TOKEN', '4dbTytHYWwc4Zhx1');
define('BAIDU_PUSH_API', 'http://data.zz.baidu.com/urls?site=' . BAIDU_SITE . '&token=' . BAIDU_TOKEN);

/**
 * 推送 URL 列表到百度站长平台。
 * @param string|array $urls
 * @return string 百度返回内容或错误信息
 */
function push_to_baidu($urls) {
    if (!function_exists('curl_init')) return 'curl 不可用';
    if (!is_array($urls)) $urls = [$urls];
    $urls = array_filter(array_map('trim', $urls), 'strlen');
    if (!$urls) return '无 URL 可推送';
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => BAIDU_PUSH_API,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => implode("\n", $urls),
        CURLOPT_HTTPHEADER => ['Content-Type: text/plain'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    return $err ? ('curl error: ' . $err) : $res;
}

// ===== 仅在直接访问本文件时运行 UI 与推送动作（被 require 时不执行） =====
if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'push.php') return;

// 登录校验（与 admin.php 共用 session）
if (empty($_SESSION['logged_in'])) {
    header('Location: admin.php');
    exit;
}

$push_msg = '';
if (isset($_GET['file'])) {
    $slug = basename($_GET['file']);
    if (preg_match('/^[a-zA-Z0-9_-]+$/', $slug)) {
        $url = BAIDU_SITE . '/blog-post.html?file=posts/' . $slug . '.md';
        $push_msg = '推送 ' . $slug . '：' . push_to_baidu([$url]);
    } else {
        $push_msg = '无效的文件名';
    }
} elseif (isset($_GET['all'])) {
    $urls = [];
    foreach (glob(__DIR__ . '/posts/*.md') as $p) {
        $urls[] = BAIDU_SITE . '/blog-post.html?file=posts/' . basename($p);
    }
    $urls[] = BAIDU_SITE . '/blog.html';
    $push_msg = '全部推送：' . push_to_baidu($urls);
}

$posts = [];
foreach (glob(__DIR__ . '/posts/*.md') as $p) {
    $posts[] = basename($p, '.md');
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>百度推送 | 后台</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .push-msg { background: #f0f0f0; padding: 1rem; border-left: 3px solid var(--fg); font-family: var(--font-mono); font-size: 0.82rem; word-break: break-all; margin-bottom: 1.5rem; }
        .push-all-btn { display:inline-block; background: var(--fg); color: var(--bg); padding: 0.6rem 1.2rem; font-size:0.85rem; margin: 1rem 0 2rem; }
        .push-link { color: var(--fg); border-bottom: 1px solid var(--fg); }
    </style>
</head>
<body>
    <div class="container">
        <p style="font-family:var(--font-mono);font-size:0.85rem;margin-bottom:1rem;"><a href="admin.php" class="push-link">&larr; 返回后台</a></p>
        <h1>百度推送</h1>
        <p class="subtitle" style="color:var(--fg-3);">手动把博客文章推送给百度收录。</p>

        <?php if ($push_msg): ?>
            <div class="push-msg"><?php echo htmlspecialchars($push_msg); ?></div>
        <?php endif; ?>

        <a href="push.php?all=1" class="push-all-btn">一键推送全部文章</a>

        <ul class="list-line">
            <?php if (!$posts): ?>
                <li><span class="li-label">空</span><span>暂无文章</span></li>
            <?php endif; ?>
            <?php foreach ($posts as $slug): ?>
                <li>
                    <span class="li-label"><?php echo htmlspecialchars($slug); ?></span>
                    <span><a href="push.php?file=<?php echo urlencode($slug); ?>" class="push-link">推送</a></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>
