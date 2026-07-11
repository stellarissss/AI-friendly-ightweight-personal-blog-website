# 网站黑客风格重制计划

## Progress（实施进度 · 本次会话续作）

> 上一轮会话已完成 5/7 实施步骤，本轮仅需完成剩余 2 个页面重写 + 本地验证。

| # | 任务 | 状态 |
|---|---|---|
| 1 | 新建 `/workspace/background.js`（节点图 + 光标） | ✅ 已完成 |
| 2 | 重写 `/workspace/style.css`（深色黑客主题） | ✅ 已完成 |
| 3 | 重写 `/workspace/index.html` | ✅ 已完成 |
| 4 | 重写 `/workspace/blog.html`（保留 BLOG_LIST 锚点） | ✅ 已完成 |
| 5 | 重写 `/workspace/blog-post.html`（保留 marked.js + 动态 meta 脚本） | ✅ 已完成 |
| 6 | 重写 `/workspace/projects.html` | ⏳ **待办** |
| 7 | 重写 `/workspace/guestbook.html` | ⏳ **待办** |
| 8 | 本地起服务验证各页面无报错 | ⏳ **待办** |

已确认的兼容性核查（admin.php 不改，仅继承深色）：
- admin.php 引用的 `.container / h1 / .gb-form / var(--line) / var(--bg-soft) / var(--fg) / var(--bg) / var(--font-mono)` 全部存在于新 style.css ✓
- admin.php 内联 `<style>` 用到的 CSS 变量在新 `:root` 均有定义 ✓
- admin.php 第 121 行 `$msg` 段落硬编码 `background:#f0f0f0`（浅灰）——仅站长可见的后台提示框，深色下略违和但可读，按"不改 admin.php"决策保持原样。

---

## Summary（摘要）

将 stellaris 个人主页的 5 个主页面（index / blog / blog-post / projects / guestbook）的前端 HTML + CSS 重新制作为**深色黑客/终端风格**，参考现有 `404.html` 的美学语言（黑底、等宽字体、glitch 抖动、终端提示符），但更精致、更现代、更有设计感。

核心亮点：
- **可拖拽节点图背景**：全屏 canvas，节点缓慢漂浮、近距离自动连线；用户可拖拽节点改变拓扑、点击空白处生成新节点。替代 404 的代码雨，是真正可玩的交互背景。
- **终端式排版**：JetBrains Mono 为主（与未改动的 404.html 保持视觉统一），配 Noto Sans SC 中文；标题用 glitch/扫描线创意处理。
- **保留全部 AI-friendly 设置**：所有 meta、Open Graph、JSON-LD 结构化数据、blog-post 动态更新 meta/JSON-LD 的脚本、blog.html 的 `BLOG_LIST_START/END` 锚点、marked.js 渲染、留言板 API、云盘 PHP 集成、Markdown 编辑器功能，全部不动逻辑，仅换皮。

## Current State Analysis（现状分析）

| 文件 | 现状 | 关键约束 |
|---|---|---|
| `style.css` | 白底极简（Inter/JetBrains Mono/Noto Serif SC），`--bg:#fff` | 被 5 主页 + admin.php 共用；含 `#markdown-content`、`.gb-form`、`.comment-box` 等大量类 |
| `index.html` | 白底，含完整 meta + Person JSON-LD + 5 个 section | 内容/链接/JSON-LD 必须原样保留 |
| `blog.html` | 文章列表，含 `<!-- BLOG_LIST_START/END -->` 锚点 + Blog JSON-LD | 锚点被 admin.php 用正则插入新卡片，**结构与注释必须保留** |
| `blog-post.html` | marked.js 渲染 md，JS 动态改 title/meta description/JSON-LD headline | 整个 `<script>` 逻辑必须原样保留 |
| `projects.html` | Markdown 编辑器 + 云盘 `/list.php` fetch + 内联编辑器样式 | 编辑器 JS 函数、fetch、内联 `<style>` 需保留并改为深色 |
| `guestbook.html` | 留言板，`API_BASE` + loadComments/submitComment/likeComment 等 | 全部 API 逻辑与渲染函数不动；`.comment-box` 结构由 JS 生成需与新 CSS 兼容 |
| `404.html` | 已是黑客风格（matrix 雨 + 终端） | **不改**，作为风格参照 |
| `admin.php` | 后台发布文章，引用 style.css | **不改**，但会因 style.css 变深色而继承深色外观（可接受，仅站长可见） |

AI-friendly 设置清单（必须 100% 保留）：
1. `<meta>` author/description/keywords/robots/canonical
2. Open Graph（og:type/title/description/url/locale, profile:first_name/last_name）
3. JSON-LD（Person/Blog/BlogPosting/WebPage）
4. blog-post.html 中动态同步 title → meta description → JSON-LD headline 的脚本
5. blog.html 的 `BLOG_LIST_START` / `BLOG_LIST_END` 注释锚点
6. marked.js CDN 引入与 md 渲染流程
7. 留言板 API_BASE 与全部交互函数
8. 云盘 `/list.php`、`/download.php` 集成
9. 现有所有正文文字内容、链接 URL、strong/em 标记

## Proposed Changes（具体改动）

### 1. 新建 `/workspace/background.js`（共享交互背景脚本）

**为什么**：5 个主页面都需要节点图背景 + 自定义光标，内联会造成 5 份重复（约 120 行/页）。共享脚本是生产级做法，DRY 且易维护。

**做什么**：单文件实现两件事：
- **可拖拽节点图**：canvas（`#bg-canvas`，`position:fixed`，`z-index:0`，`pointer-events:none`）。生成 ~60 个节点 `{x,y,vx,vy}`，每帧漂浮、绘制距离 < 140px 的节点间连线。监听 `document` 的 `pointerdown/move/up`：若 `e.target` 不是交互元素（`A/BUTTON/INPUT/TEXTAREA/SELECT/.gb-form/#cmd-input` 等），则按下时找最近节点（半径 < 50px）拖拽，否则在点击处生成新节点（上限 90 个，超出删最旧）。节点靠近指针时连线变亮。配色用暗绿 `rgba(0,255,100,0.12)` 连线、`rgba(0,255,100,0.5)` 节点，呼应 404 的 matrix 绿但非代码雨。
- **自定义终端光标**：`.cursor-block` div 跟随 `mousemove`（`transform: translate()`，带 0.08s 过渡），触屏设备隐藏，悬停 `INPUT/TEXTAREA` 时隐藏（避免干扰文本光标）。

**怎么用**：5 个页面 `<script src="background.js" defer></script>` + 各自 body 内放 `<canvas id="bg-canvas"></canvas>` 与 `<div class="cursor-block" id="cursor-block"></div>`。`prefers-reduced-motion` 时节点静止。

### 2. 重写 `/workspace/style.css`（完全重写，深色黑客主题）

**为什么**：这是换皮核心。保留所有现有类名（HTML 结构与 admin.php 兼容），仅改变量值与视觉。

**做什么**：新 `:root` 调色板：
```
--bg:#0a0a0a; --bg-soft:#111; --bg-card:#0f0f0f;
--fg:#fafafa; --fg-2:#a3a3a3; --fg-3:#6b6b6b; --fg-4:#3d3d3d;
--line:#1f1f1f; --line-2:#2a2a2a;
--accent:#00ff9c;            /* 单一霓虹绿，仅用于交互高光 */
--font-mono:'JetBrains Mono','SF Mono',Menlo,Consolas,monospace;
--font-cjk:'Noto Sans SC',-apple-system,"PingFang SC","Microsoft YaHei",sans-serif;
```
字体：JetBrains Mono 为主（与 404 统一），CJK 用 Noto Sans SC，从 Google Fonts 引入。body 用 `var(--font-cjk)` 作中文底，`font-feature-settings` 开启等宽对齐。

关键样式要点：
- `body`：`background:var(--bg); color:var(--fg)`；顶部加一层固定 `scanline` 伪元素（极淡水平扫描线，`opacity:.03`）增强 CRT 质感
- `nav`：sticky，`background:rgba(10,10,10,.82)` + `backdrop-filter:blur(14px)`；`.nav-brand` 改终端提示符样式 `visitor@stellaris:~$` 风格，前面 `::before` 改为闪烁方块光标
- `.nav-links a`：hover 时前缀显示 `>`，下划线用 `--accent`
- `h1`：大号 mono，加 `glitch` 动画（复用 404 的 `@keyframes glitch`，RGB 分离 text-shadow）
- `h2 .sec-num`：改 `[01]` 方括号样式，`--accent` 色
- `.card`：深色卡片，hover 左移 + 左侧 `--accent` 竖线生长 + 轻微 `box-shadow` 霓虹辉光
- `a.link`：下划线 hover 加粗，颜色 `--accent`
- `ul.list-line li`：hover 左缩进，`.li-label` 用 `--accent` 暗色
- `#markdown-content`：深色化，`pre` 保持黑底白字（已黑），`code` 改深灰底 + `--accent` 边框，`blockquote` 左边框 `--accent`
- `.gb-form`：深色输入框（`background:var(--bg-soft)`，`border:var(--line)`），focus 时 `border-color:var(--accent)` + 微辉光；保留四角 `::before/::after` 装饰（改 `--accent`）
- `.comment-box`/`.reply-box`：深色，`.reply-box` 左边框 `--accent`
- `footer`：mono 状态栏风格
- `.fade-in` 入场动画保留并加深（从 `translateY(12px)` + `opacity:0`）
- 新增 `.glitch` 通用类、`.scanline` 类、`#bg-canvas` 与 `.cursor-block` 定位样式
- `@media (prefers-reduced-motion)`：禁用 drift/glitch
- 响应式与 print 媒体查询保留并适配深色

### 3. 重写 `/workspace/index.html`（保留 head 全部 meta/JSON-LD，重排 body）

- **`<head>` 原样保留**：所有 meta、Open Graph、Person JSON-LD、`<link rel="stylesheet" href="style.css">`。新增 `<script src="background.js" defer></script>` 与 Google Fonts 链接。
- **body**：加 `<canvas id="bg-canvas"></canvas>` 与 `<div class="cursor-block" id="cursor-block"></div>`；内容包一层 `<div class="content">`（`position:relative;z-index:1`）。
- **nav**：brand 改 `stellaris@site:~$`，保留 4 个链接与 active 状态。
- **Hero**：`<h1>` 加 `class="glitch"`；subtitle 加打字机效果（纯 JS 逐字显示，放在 background.js 之外的页面内联小脚本，或直接 CSS steps）。保留"本站已通过腾讯云服务器…"小字与学校官网链接。
- **01-05 section**：文字/链接/strong/列表**全部原样**，仅靠 CSS 换皮。保留"内容真实性声明"全部 VERIFY 列表与 Last updated 注脚。
- 保留 footer 与返回顶部链接。

### 4. 重写 `/workspace/blog.html`（保留锚点 + JSON-LD）

- head 的 meta/OG/canonical/Blog JSON-LD 原样。
- **`<!-- BLOG_LIST_START -->` 与 `<!-- BLOG_LIST_END -->` 注释必须原样保留**，中间的 `<article class="card">` 结构保持（admin.php 正则按此插入）。
- 加 canvas + cursor + background.js。
- 列表卡片靠 CSS 换皮。

### 5. 重写 `/workspace/blog-post.html`（保留 marked.js + 动态 meta 脚本）

- head 的 BlogPosting JSON-LD、marked.js 引入原样。
- **整个 `<script>`（fetch md → marked.parse → 动态改 title/meta description/JSON-LD headline）原样保留，一字不改。**
- `#markdown-content` article 保留 id/class。
- 加 canvas + cursor + background.js。
- back-link 与 footer 保留。

### 6. 重写 `/workspace/projects.html`（保留编辑器 JS + 云盘 fetch + 内联样式深色化）

- head meta/OG/canonical 原样，marked.js 引用原样。
- **内联 `<style>` 改为深色**：`.md-toolbar`/`.md-editor-input`/`.md-editor-preview`/`.md-action-buttons` 全部深色化，按钮 hover 用 `--accent`。保留所有类名与布局逻辑。
- **`<script>` 全部保留**：renderMarkdown、formatText、insertLinePrefix、formatLink、copyMarkdown、downloadMarkdown、formatSize、formatDate、`fetch('/list.php')` 云盘渲染——逻辑不动。
- 云盘卡片靠 CSS 换皮。
- 加 canvas + cursor + background.js。

### 7. 重写 `/workspace/guestbook.html`（保留 API 全部逻辑）

- head meta/OG/canonical/WebPage JSON-LD 原样。
- **`<script>` 全部保留**：`API_BASE`、loadComments、renderSingleComment（注意它生成 `.comment-box`/`.comment-meta`/`.comment-content`/`.actions`/`.reply-box` 的 HTML，新 CSS 必须兼容这些类）、submitComment、likeComment、setReply、cancelReply——一字不改。
- 留言表单 `.gb-form` 结构保留（`#reply-indicator`/`#nickname`/`#content`/`#submit-btn`/`#submit-status`/`#comments-list` 的 id 不动）。
- 加 canvas + cursor + background.js。

## Assumptions & Decisions（假设与决策）

1. **字体**：选 JetBrains Mono（而非 IBM Plex Mono/Victor Mono）以与未改动的 404.html 保持站点视觉统一；通过 glitch/扫描线/字重对比营造设计感，不靠堆字体。
2. **单一霓虹绿强调色 `#00ff9c`**：呼应 404 的 matrix 绿，但用量极克制（仅交互高光、节点连线、focus 边框），主体仍是黑/白/灰，满足"炫酷而简洁"。
3. **admin.php 不改**，但因引用 style.css 会自动变深色外观——可接受（仅站长可见的后台），且与新站一致。其内联样式引用的 `var(--line)/--bg-soft/--fg/--bg` 会自动取新值，功能不受影响。
4. **新建 `background.js`** 是必要的（避免 5 份重复），符合"不创建非必要文件"的反向例外。
5. **节点图性能**：节点上限 90，连线距离阈值 140px，`requestAnimationFrame` 驱动；移动端节点数减半。
6. **自定义光标**：仅视觉装饰，不拦截指针事件；触屏与 reduce-motion 下禁用。
7. **不引入任何构建工具/框架**：纯原生 HTML/CSS/JS，保持静态站可部署到 Cloudflare Pages 的现状。
8. **404.html 保持原样**（用户明确要求仅 5 主页面）。

## Verification Steps（验证步骤）

1. **AI-friendly 完整性**：对比新旧 index.html，确认 meta/OG/JSON-LD 字段逐一对应；blog-post.html 的动态更新脚本行级 diff 为 0；blog.html 的 `BLOG_LIST_START/END` 注释存在。
2. **功能不回归**：
   - blog-post.html 用 `?file=posts/how-to-build-this-site.md` 打开，确认 marked 渲染、title/description/JSON-LD 动态更新生效。
   - projects.html 编辑器：输入 md 实时预览、加粗/斜体/标题/链接按钮、复制、下载均正常。
   - projects.html 云盘：`/list.php` fetch 正常渲染卡片（或优雅降级 error-msg）。
   - guestbook.html：API 调用、点赞、楼中楼回复、表单提交逻辑无报错（API_BASE 不变）。
3. **交互背景**：鼠标拖拽节点可见拓扑变化；点击空白生成新节点；悬停链接/输入框时光标不干扰；节点不遮挡正文可读性。
4. **视觉**：5 页面均为深色黑客风，与 404.html 风格协调；标题 glitch、扫描线、终端 nav 均生效；移动端 (<640px) 布局不破。
5. **本地起服务验证**：`python3 -m http.server` 后逐页打开，控制台无 404/JS 报错。
6. **reduce-motion**：系统开启"减少动态效果"时，glitch/drift 禁用，内容仍可读。
