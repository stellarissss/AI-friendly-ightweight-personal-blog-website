<?php
session_start();
require_once __DIR__ . '/push.php';

$PASSWORD  = 'Clt20090912!'; // 改成你自己的登录密码
$POSTS_DIR = __DIR__ . '/posts/';
$UPLOAD_DIR = __DIR__ . '/uploads/';
$BLOG_HTML = __DIR__ . '/blog.html';

// ===== 登录处理 =====
if (isset($_POST['login'])) {
    if (hash_equals($PASSWORD, $_POST['password'] ?? '')) {
        $_SESSION['logged_in'] = true;
        header("Location: admin.php");
        exit;
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
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>后台登录</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <div class="container" style="max-width: 400px; padding-top: 100px;">
            <h1>后台登录</h1>
            <?php if (isset($error)) echo '<p style="color:red;">' . $error . '</p>'; ?>
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

// ===== 辅助函数 =====
function admin_extract_md_title($content) {
    if (preg_match('/^#\s+(.+)$/m', $content, $m)) return trim($m[1]);
    return '';
}

function admin_get_posts_list() {
    global $POSTS_DIR;
    $list = [];
    if (!is_dir($POSTS_DIR)) return $list;
    foreach (glob($POSTS_DIR . '*.md') as $p) {
        $slug = basename($p, '.md');
        $content = file_get_contents($p);
        $title = admin_extract_md_title($content);
        if ($title === '') $title = $slug;
        $list[] = ['slug' => $slug, 'title' => $title, 'mtime' => filemtime($p)];
    }
    usort($list, function ($a, $b) { return $b['mtime'] - $a['mtime']; });
    return $list;
}

function admin_get_card_desc($slug) {
    global $BLOG_HTML;
    if (!is_file($BLOG_HTML)) return '';
    $html = file_get_contents($BLOG_HTML);
    $slugq = preg_quote($slug, '/');
    if (preg_match('/<article class="card">.*?href="blog-post\.html\?file=posts\/' . $slugq . '\.md".*?<p>(.*?)<\/p>\s*<\/article>/s', $html, $m)) {
        return html_entity_decode(trim($m[1]));
    }
    return '';
}

function admin_update_blog_card($slug, $title, $desc) {
    global $BLOG_HTML;
    if (!is_file($BLOG_HTML)) return false;
    $html = file_get_contents($BLOG_HTML);
    $slugq = preg_quote($slug, '/');
    // 捕获：1=前缀(到 meta div 开头), 2=日期, 3=到 h3>a 开头, 4=旧标题, 5=到 p 开头, 6=旧简介, 7=收尾
    $pattern = '/(<article class="card">\s*<div class="meta">)([^<]*)(<\/div>\s*<h3><a href="blog-post\.html\?file=posts\/' . $slugq . '\.md">)([^<]*)(<\/a><\/h3>\s*<p>)([^<]*)(<\/p>\s*<\/article>)/s';
    if (!preg_match($pattern, $html)) return false;
    $newTitle = htmlspecialchars($title);
    $newDesc  = htmlspecialchars($desc);
    $html = preg_replace_callback($pattern, function ($m) use ($newTitle, $newDesc) {
        return $m[1] . $m[2] . $m[3] . $newTitle . $m[5] . $newDesc . $m[7];
    }, $html);
    file_put_contents($BLOG_HTML, $html);
    return true;
}

function admin_remove_blog_card($slug) {
    global $BLOG_HTML;
    if (!is_file($BLOG_HTML)) return false;
    $html = file_get_contents($BLOG_HTML);
    $slugq = preg_quote($slug, '/');
    // 匹配整张卡片及其前导空白（兼容无缩进的新卡片与带缩进的手写卡片），不吞掉卡片后的空白
    $pattern = '/\s*<article class="card">\s*<div class="meta">[^<]*<\/div>\s*<h3><a href="blog-post\.html\?file=posts\/' . $slugq . '\.md">[^<]*<\/a><\/h3>\s*<p>[^<]*<\/p>\s*<\/article>/s';
    $html = preg_replace($pattern, '', $html);
    file_put_contents($BLOG_HTML, $html);
    return true;
}

function admin_get_files_list() {
    global $UPLOAD_DIR;
    $list = [];
    if (!is_dir($UPLOAD_DIR)) return $list;
    foreach (scandir($UPLOAD_DIR) as $f) {
        if ($f === '.' || $f === '..') continue;
        $p = $UPLOAD_DIR . $f;
        if (!is_file($p)) continue;
        $list[] = ['name' => $f, 'size' => filesize($p), 'mtime' => filemtime($p)];
    }
    usort($list, function ($a, $b) { return $b['mtime'] - $a['mtime']; });
    return $list;
}

function admin_fmt_size($b) {
    if ($b < 1024) return $b . ' B';
    if ($b < 1048576) return round($b / 1024, 1) . ' KB';
    if ($b < 1073741824) return round($b / 1048576, 1) . ' MB';
    return round($b / 1073741824, 2) . ' GB';
}

function admin_fmt_date($ts) {
    return date('Y/m/d', $ts);
}

function admin_valid_slug($slug) {
    return is_string($slug) && preg_match('/^[a-zA-Z0-9_-]+$/', $slug);
}

function admin_safe_filename($name) {
    $name = basename($name);
    // 仅保留字母数字、下划线、连字符、点
    $name = preg_replace('/[^\w.\-]/', '_', $name);
    $name = trim($name, '._-');
    return $name;
}

// ===== 处理 POST 动作（PRG：处理完跳转，避免重复提交） =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['login'])) {
    $action = $_POST['action'] ?? '';
    $msg = '';
    $type = 'err';
    $redirect_tab = 'write';

    switch ($action) {
        case 'publish': {
            $title   = trim($_POST['title'] ?? '');
            $slug    = trim($_POST['slug'] ?? '');
            $desc    = trim($_POST['desc'] ?? '');
            $content = $_POST['content'] ?? '';
            if ($title && admin_valid_slug($slug) && $content !== '') {
                $filename = $slug . '.md';
                $filepath = $POSTS_DIR . $filename;
                file_put_contents($filepath, $content);

                $blog_html = file_get_contents($BLOG_HTML);
                $date = date('Y / m / d');
                $new_card = "\n<article class=\"card\">\n    <div class=\"meta\">" . $date . " · POST</div>\n    <h3><a href=\"blog-post.html?file=posts/" . $filename . "\">" . htmlspecialchars($title) . "</a></h3>\n    <p>" . htmlspecialchars($desc) . "</p>\n</article>";
                $blog_html = preg_replace(
                    '/(<!-- BLOG_LIST_START -->)(.*?)(<!-- BLOG_LIST_END -->)/s',
                    '$1' . $new_card . '$2',
                    $blog_html
                );
                file_put_contents($BLOG_HTML, $blog_html);

                $url = 'https://www.stellaric.site/blog-post.html?file=posts/' . $filename;
                $push = push_to_baidu([$url, 'https://www.stellaric.site/blog.html']);

                $msg = "发布成功！文件已生成，列表已更新，百度推送返回：" . $push;
                $type = 'ok';
                $redirect_tab = 'posts';
            } else {
                $msg = "请填写所有必填字段，且文件名仅允许字母数字、下划线和连字符！";
            }
            break;
        }

        case 'edit': {
            $title   = trim($_POST['title'] ?? '');
            $slug    = trim($_POST['slug'] ?? '');
            $desc    = trim($_POST['desc'] ?? '');
            $content = $_POST['content'] ?? '';
            if ($title && admin_valid_slug($slug) && $content !== '') {
                $filepath = $POSTS_DIR . $slug . '.md';
                if (is_file($filepath)) {
                    file_put_contents($filepath, $content);
                    admin_update_blog_card($slug, $title, $desc);
                    $url = 'https://www.stellaric.site/blog-post.html?file=posts/' . $slug . '.md';
                    $push = push_to_baidu([$url]);
                    $msg = "修改成功！文件已更新，列表卡片已同步，百度推送返回：" . $push;
                    $type = 'ok';
                    $redirect_tab = 'posts';
                } else {
                    $msg = "文章不存在！";
                }
            } else {
                $msg = "请填写所有必填字段，且文件名仅允许字母数字、下划线和连字符！";
            }
            break;
        }

        case 'delete_post': {
            $slug = basename($_POST['slug'] ?? '');
            if (admin_valid_slug($slug)) {
                $filepath = $POSTS_DIR . $slug . '.md';
                if (is_file($filepath)) {
                    unlink($filepath);
                    admin_remove_blog_card($slug);
                    $msg = "已删除文章：" . $slug;
                    $type = 'ok';
                    $redirect_tab = 'posts';
                } else {
                    $msg = "文章不存在！";
                }
            } else {
                $msg = "无效的文件名！";
            }
            break;
        }

        case 'upload': {
            if (!is_dir($UPLOAD_DIR)) @mkdir($UPLOAD_DIR, 0775, true);
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $name = admin_safe_filename($_FILES['file']['name']);
                if ($name !== '') {
                    $target = $UPLOAD_DIR . $name;
                    if (is_file($target)) {
                        $pi = pathinfo($name);
                        $name = $pi['filename'] . '_' . time() . (isset($pi['extension']) ? '.' . $pi['extension'] : '');
                        $target = $UPLOAD_DIR . $name;
                    }
                    if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
                        $msg = "上传成功：" . $name;
                        $type = 'ok';
                        $redirect_tab = 'files';
                    } else {
                        $msg = "上传失败，请检查 uploads 目录权限！";
                    }
                } else {
                    $msg = "无效的文件名！";
                }
            } else {
                $msg = "未选择文件或上传出错！";
            }
            break;
        }

        case 'delete_file': {
            $name = basename($_POST['name'] ?? '');
            if ($name && $name !== '.' && $name !== '..'
                && strpos($name, '/') === false
                && strpos($name, '\\') === false
                && strpos($name, '..') === false) {
                $path = $UPLOAD_DIR . $name;
                if (is_file($path)) {
                    unlink($path);
                    $msg = "已删除文件：" . $name;
                    $type = 'ok';
                    $redirect_tab = 'files';
                } else {
                    $msg = "文件不存在！";
                }
            } else {
                $msg = "无效的文件名！";
            }
            break;
        }

        default:
            $msg = "未知操作";
    }

    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type, 'tab' => $redirect_tab];
    header('Location: admin.php');
    exit;
}

// ===== 读取 flash 消息 =====
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$msg = $flash['msg'] ?? '';
$msg_type = $flash['type'] ?? 'info';

// ===== 编辑模式回填 =====
$edit_mode = false;
$edit_slug = $edit_title = $edit_desc = $edit_content = '';
if (isset($_GET['edit'])) {
    $slug = basename($_GET['edit']);
    if (admin_valid_slug($slug)) {
        $path = $POSTS_DIR . $slug . '.md';
        if (is_file($path)) {
            $edit_mode = true;
            $edit_slug = $slug;
            $edit_content = file_get_contents($path);
            $edit_title = admin_extract_md_title($edit_content);
            $edit_desc = admin_get_card_desc($slug);
        }
    }
}

// ===== 当前 Tab =====
$tab = $_GET['tab'] ?? '';
if ($edit_mode) $tab = 'write';
if ($flash && isset($flash['tab'])) $tab = $flash['tab'];
if (!in_array($tab, ['write', 'posts', 'files'], true)) $tab = 'write';

$posts = admin_get_posts_list();
$files = admin_get_files_list();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台管理 | stellaris's BLOG</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        /* ===== Tab 导航 ===== */
        .admin-tabs {
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--line);
            margin-bottom: 2rem;
            gap: 0;
        }
        .admin-tab {
            padding: 0.85rem 1.4rem;
            font-size: 0.9rem;
            color: var(--fg-3);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            transition: all var(--t);
            font-family: var(--font-mono);
            letter-spacing: 0.02em;
            white-space: nowrap;
        }
        .admin-tab:hover { color: var(--fg); }
        .admin-tab.active { color: var(--fg); border-bottom-color: var(--fg); }
        .admin-logout {
            margin-left: auto;
            font-size: 0.82rem;
            color: var(--fg-3);
            font-family: var(--font-mono);
            padding: 0.85rem 0;
        }
        .admin-logout:hover { color: var(--fg); }

        /* ===== 消息条 ===== */
        .admin-msg {
            padding: 0.9rem 1.1rem;
            border-left: 3px solid var(--fg);
            background: var(--bg-soft);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            word-break: break-all;
        }
        .admin-msg-ok { border-left-color: #2a7; }
        .admin-msg-err { border-left-color: #c33; color: var(--fg); }

        /* ===== 撰写表单 ===== */
        .admin-container { display: flex; gap: 1rem; height: 58vh; }
        .admin-input {
            width: 50%; padding: 1rem; border: 1px solid var(--line);
            font-family: var(--font-mono); font-size: 0.92rem; resize: none; outline: none;
            background: var(--bg); color: var(--fg); transition: border-color var(--t);
        }
        .admin-input:focus { border-color: var(--fg); }
        .admin-preview {
            width: 50%; padding: 1rem 1.25rem; border: 1px solid var(--line);
            overflow-y: auto; background: var(--bg-soft);
        }
        .admin-meta { display: grid; grid-template-columns: 1.4fr 1fr 1.6fr; gap: 1rem; margin-bottom: 1rem; }
        .admin-meta input {
            padding: 0.6rem 0.8rem; border: 1px solid var(--line);
            background: var(--bg); color: var(--fg); font-family: inherit; font-size: 0.92rem;
            transition: border-color var(--t);
        }
        .admin-meta input:focus { outline: none; border-color: var(--fg); }
        .admin-meta input[readonly] { background: var(--bg-soft); color: var(--fg-3); }
        .admin-edit-banner {
            background: var(--bg-soft); border-left: 3px solid var(--fg);
            padding: 0.7rem 1rem; margin-bottom: 1rem; font-size: 0.88rem; font-family: var(--font-mono);
        }
        .admin-edit-banner a { border-bottom: 1px solid var(--fg); }

        .admin-actions { display: flex; gap: 0.8rem; margin-top: 1rem; }
        .btn-primary {
            background: var(--fg); color: var(--bg); border: 1px solid var(--fg);
            padding: 0.8rem 2rem; cursor: pointer; font-size: 0.95rem; font-family: inherit;
            transition: all var(--t);
        }
        .btn-primary:hover { background: var(--bg); color: var(--fg); }
        .btn-secondary {
            background: var(--bg); color: var(--fg); border: 1px solid var(--line-2);
            padding: 0.8rem 1.6rem; cursor: pointer; font-size: 0.95rem; font-family: inherit;
            transition: all var(--t);
        }
        .btn-secondary:hover { border-color: var(--fg); }

        /* ===== 表格 ===== */
        .admin-table-wrap { overflow-x: auto; }
        .admin-table { width: 100%; border-collapse: collapse; font-size: 0.92rem; }
        .admin-table th, .admin-table td {
            text-align: left; padding: 0.8rem 0.6rem;
            border-bottom: 1px solid var(--line); vertical-align: middle;
        }
        .admin-table th {
            font-family: var(--font-mono); font-size: 0.76rem; color: var(--fg-4);
            text-transform: uppercase; letter-spacing: 0.08em; font-weight: 500;
            border-bottom: 1px solid var(--line-2);
        }
        .admin-table tr:hover td { background: var(--bg-soft); }
        .admin-table code { font-family: var(--font-mono); font-size: 0.82rem; color: var(--fg-2); }
        .actions-cell { text-align: right; white-space: nowrap; }
        .btn-link {
            background: none; border: 1px solid var(--line-2); color: var(--fg-2);
            padding: 4px 10px; font-size: 0.78rem; font-family: var(--font-mono);
            cursor: pointer; text-decoration: none; display: inline-block;
            transition: all var(--t); margin-left: 4px;
        }
        .btn-link:hover { background: var(--fg); color: var(--bg); border-color: var(--fg); }
        .btn-danger:hover { background: #c33; color: #fff; border-color: #c33; }

        /* ===== 上传表单 ===== */
        .upload-form {
            display: flex; gap: 0.8rem; align-items: center; flex-wrap: wrap;
            background: var(--bg-soft); padding: 1rem 1.25rem; border: 1px solid var(--line);
            margin-bottom: 1.5rem;
        }
        .upload-form input[type=file] { flex: 1; min-width: 200px; font-size: 0.9rem; }
        .upload-form .btn-primary { padding: 0.6rem 1.4rem; }

        .empty-row td { text-align: center; color: var(--fg-4); font-family: var(--font-mono); padding: 2rem; }

        /* ===== 预览弹层 ===== */
        .preview-modal {
            position: fixed; inset: 0; background: rgba(0,0,0,0.55);
            z-index: 1000; display: none; align-items: flex-start; justify-content: center;
            padding: 3rem 1.5rem; overflow-y: auto;
        }
        .preview-modal.show { display: flex; }
        .preview-box {
            background: var(--bg); max-width: 760px; width: 100%;
            padding: 2.5rem 2.5rem 3rem; position: relative; box-shadow: 0 10px 50px rgba(0,0,0,0.2);
        }
        .preview-close {
            position: absolute; top: 1rem; right: 1.2rem; background: none; border: 1px solid var(--line-2);
            width: 32px; height: 32px; font-size: 1.1rem; cursor: pointer; color: var(--fg-2);
            transition: all var(--t);
        }
        .preview-close:hover { background: var(--fg); color: var(--bg); border-color: var(--fg); }

        @media (max-width: 768px) {
            .admin-container { flex-direction: column; height: auto; }
            .admin-input, .admin-preview { width: 100%; height: 320px; }
            .admin-meta { grid-template-columns: 1fr; }
            .admin-tabs { overflow-x: auto; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header fade-in" style="margin-bottom:1.5rem; padding-bottom:1.5rem;">
            <div class="eyebrow">ADMIN / DASHBOARD</div>
            <h1>后台管理</h1>
        </div>

        <!-- Tab 导航 -->
        <div class="admin-tabs">
            <span class="admin-tab <?php echo $tab === 'write' ? 'active' : ''; ?>" data-tab="write">撰写博客</span>
            <span class="admin-tab <?php echo $tab === 'posts' ? 'active' : ''; ?>" data-tab="posts">博客管理</span>
            <span class="admin-tab <?php echo $tab === 'files' ? 'active' : ''; ?>" data-tab="files">文件管理</span>
            <a class="admin-logout" href="admin.php?logout=1">退出登录</a>
        </div>

        <!-- 消息条 -->
        <?php if ($msg): ?>
            <div class="admin-msg admin-msg-<?php echo htmlspecialchars($msg_type); ?>"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <!-- ===== 面板 A：撰写博客 ===== -->
        <section class="admin-panel" id="panel-write" <?php echo $tab !== 'write' ? 'hidden' : ''; ?>>
            <h2 style="margin-top:0;"><?php echo $edit_mode ? '编辑文章' : '撰写新博客'; ?></h2>
            <form method="post" id="write-form">
                <input type="hidden" name="action" value="<?php echo $edit_mode ? 'edit' : 'publish'; ?>">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="slug" value="<?php echo htmlspecialchars($edit_slug); ?>">
                    <div class="admin-edit-banner">
                        正在编辑：<strong><?php echo htmlspecialchars($edit_slug); ?></strong>
                        &middot; <a href="admin.php?tab=write">取消编辑 / 新建文章</a>
                    </div>
                <?php endif; ?>

                <div class="admin-meta">
                    <input type="text" name="title" placeholder="文章标题" required value="<?php echo htmlspecialchars($edit_title); ?>">
                    <?php if ($edit_mode): ?>
                        <input type="text" value="<?php echo htmlspecialchars($edit_slug); ?>" readonly title="文件名不可修改">
                    <?php else: ?>
                        <input type="text" name="slug" placeholder="英文文件名(如: my-post)" required pattern="[a-zA-Z0-9_-]+" title="仅允许字母数字、下划线、连字符">
                    <?php endif; ?>
                    <input type="text" name="desc" placeholder="一句话简介" required value="<?php echo htmlspecialchars($edit_desc); ?>">
                </div>

                <div class="admin-container">
                    <textarea class="admin-input" name="content" id="md-input" placeholder="在这里输入 Markdown 正文..." oninput="renderPreview()"><?php echo htmlspecialchars($edit_content); ?></textarea>
                    <div class="admin-preview" id="md-preview"></div>
                </div>

                <div class="admin-actions">
                    <button type="submit" class="btn-primary"><?php echo $edit_mode ? '保存修改并推送' : '一键发布并推送'; ?></button>
                    <button type="button" class="btn-secondary" id="btn-preview-write">在线预览</button>
                </div>
            </form>
        </section>

        <!-- ===== 面板 B：博客管理 ===== -->
        <section class="admin-panel" id="panel-posts" <?php echo $tab !== 'posts' ? 'hidden' : ''; ?>>
            <h2 style="margin-top:0;">博客管理</h2>
            <p style="color:var(--fg-3); margin-bottom:1.5rem;">共 <?php echo count($posts); ?> 篇文章。</p>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr><th>标题</th><th>文件名</th><th>更新时间</th><th style="text-align:right;">操作</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!$posts): ?>
                            <tr class="empty-row"><td colspan="4">暂无文章，去「撰写博客」发布第一篇吧。</td></tr>
                        <?php endif; ?>
                        <?php foreach ($posts as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['title']); ?></td>
                                <td><code><?php echo htmlspecialchars($p['slug']); ?></code></td>
                                <td style="font-family:var(--font-mono);font-size:0.82rem;color:var(--fg-3);"><?php echo admin_fmt_date($p['mtime']); ?></td>
                                <td class="actions-cell">
                                    <button type="button" class="btn-link btn-preview-post" data-slug="<?php echo htmlspecialchars($p['slug']); ?>">预览</button>
                                    <a class="btn-link" href="admin.php?edit=<?php echo urlencode($p['slug']); ?>">编辑</a>
                                    <form method="post" style="display:inline;margin:0;" onsubmit="return confirm('确认删除文章 [<?php echo htmlspecialchars($p['slug']); ?>]？此操作不可恢复，md 文件与列表卡片都会被移除。');">
                                        <input type="hidden" name="action" value="delete_post">
                                        <input type="hidden" name="slug" value="<?php echo htmlspecialchars($p['slug']); ?>">
                                        <button type="submit" class="btn-link btn-danger">删除</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ===== 面板 C：文件管理 ===== -->
        <section class="admin-panel" id="panel-files" <?php echo $tab !== 'files' ? 'hidden' : ''; ?>>
            <h2 style="margin-top:0;">文件管理</h2>
            <p style="color:var(--fg-3); margin-bottom:1rem;">此处上传的文件会展示在 <a class="link" href="projects.html">个人云盘</a>，访客可下载。</p>

            <form method="post" enctype="multipart/form-data" class="upload-form">
                <input type="hidden" name="action" value="upload">
                <input type="file" name="file" required>
                <button type="submit" class="btn-primary">上传文件</button>
            </form>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr><th>文件名</th><th>大小</th><th>上传时间</th><th style="text-align:right;">操作</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!$files): ?>
                            <tr class="empty-row"><td colspan="4">暂无文件，上传一个试试。</td></tr>
                        <?php endif; ?>
                        <?php foreach ($files as $f): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($f['name']); ?></td>
                                <td style="font-family:var(--font-mono);font-size:0.82rem;color:var(--fg-3);"><?php echo admin_fmt_size($f['size']); ?></td>
                                <td style="font-family:var(--font-mono);font-size:0.82rem;color:var(--fg-3);"><?php echo admin_fmt_date($f['mtime']); ?></td>
                                <td class="actions-cell">
                                    <a class="btn-link" href="download.php?f=<?php echo urlencode($f['name']); ?>" download>下载</a>
                                    <form method="post" style="display:inline;margin:0;" onsubmit="return confirm('确认删除文件 [<?php echo htmlspecialchars($f['name']); ?>]？此操作不可恢复。');">
                                        <input type="hidden" name="action" value="delete_file">
                                        <input type="hidden" name="name" value="<?php echo htmlspecialchars($f['name']); ?>">
                                        <button type="submit" class="btn-link btn-danger">删除</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <footer style="margin-top:3rem;">
            <div class="foot-line">
                <span>&copy; 2024 SYFZ 2509 常乐天</span>
                <span>POWERED BY CLOUDFLARE PAGES</span>
            </div>
        </footer>
    </div>

    <!-- ===== 在线预览弹层 ===== -->
    <div class="preview-modal" id="preview-modal">
        <div class="preview-box">
            <button class="preview-close" id="preview-close" title="关闭">&times;</button>
            <article id="markdown-content"><p>加载中...</p></article>
        </div>
    </div>

    <script>
        // ===== 实时预览（撰写区右侧） =====
        function renderPreview() {
            document.getElementById('md-preview').innerHTML = marked.parse(document.getElementById('md-input').value);
        }
        renderPreview(); // 编辑模式下初始化

        // ===== Tab 切换 =====
        var tabs = document.querySelectorAll('.admin-tab');
        var panels = {
            write: document.getElementById('panel-write'),
            posts: document.getElementById('panel-posts'),
            files: document.getElementById('panel-files')
        };
        function switchTab(name, pushState) {
            Object.keys(panels).forEach(function (k) { panels[k].hidden = (k !== name); });
            tabs.forEach(function (t) { t.classList.toggle('active', t.dataset.tab === name); });
            if (pushState) history.replaceState(null, '', 'admin.php?tab=' + name);
        }
        tabs.forEach(function (t) {
            t.addEventListener('click', function () { switchTab(t.dataset.tab, true); });
        });

        // ===== 预览弹层 =====
        var modal = document.getElementById('preview-modal');
        var modalContent = document.getElementById('markdown-content');
        function openPreview(html) {
            modalContent.innerHTML = html;
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        function closePreview() {
            modal.classList.remove('show');
            modalContent.innerHTML = '';
            document.body.style.overflow = '';
        }
        document.getElementById('preview-close').addEventListener('click', closePreview);
        modal.addEventListener('click', function (e) { if (e.target === modal) closePreview(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closePreview(); });

        // 撰写区「在线预览」按钮：渲染当前输入框内容
        document.getElementById('btn-preview-write').addEventListener('click', function () {
            openPreview(marked.parse(document.getElementById('md-input').value));
        });

        // 博客列表「预览」按钮：拉取 md 后渲染
        document.querySelectorAll('.btn-preview-post').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var slug = btn.dataset.slug;
                openPreview('<p>加载中...</p>');
                fetch('posts/' + slug + '.md')
                    .then(function (r) { if (!r.ok) throw new Error('文章走丢了...'); return r.text(); })
                    .then(function (t) { modalContent.innerHTML = marked.parse(t); })
                    .catch(function (err) { modalContent.innerHTML = '<p>' + err.message + '</p>'; });
            });
        });
    </script>
</body>
</html>
