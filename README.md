
# 1st

一个简洁的个人博客与作品集网站，基于纯 HTML/CSS/JS 构建。

## 特性

- **简洁现代的设计** - 采用暗色主题，视觉效果优雅
- **响应式布局** - 适配各种屏幕尺寸
- **Markdown 支持** - 博客文章使用 Markdown 格式撰写
- **留言板功能** - 访客可留言互动
- **项目展示** - 展示个人项目作品
- **终端风格 404 页面** - 趣味性的错误页面设计

## 页面结构

- `index.html` - 首页，展示个人简介、文章列表和链接
- `blog.html` - 博客列表页
- `blog-post.html` - 博客文章详情页
- `projects.html` - 项目展示页
- `guestbook.html` - 留言板页
- `admin.php` - 后台管理页面
- `404.html` - 404 错误页面（终端风格）
- `style.css` - 全局样式文件

## 技术栈

- HTML5
- CSS3（自定义属性、动画）
- JavaScript（原生）
- Markdown

## 使用方法

1. 克隆仓库到本地或服务器
2. 直接在浏览器中打开 `index.html` 即可预览
3. 根据需要修改 HTML 文件中的内容
4. 博客文章放在 `posts/` 目录下，使用 Markdown 格式

## 目录结构

```
.
├── index.html          # 首页
├── blog.html           # 博客列表
├── blog-post.html      # 博客文章页
├── projects.html       # 项目展示
├── guestbook.html     # 留言板
├── admin.php          # 后台管理
├── 404.html           # 404 页面
├── style.css          # 样式文件
└── posts/             # 博客文章目录
    └── how-to-build-this-site.md
```

## 自定义

### 修改导航链接

在每个 HTML 文件中找到 `<div class="nav-links">` 部分，修改对应的 `<a>` 标签内容。

### 添加新博客文章

在 `posts/` 目录下创建新的 `.md` 文件，编写完成后在博客列表页添加对应链接。

### 修改样式

编辑 `style.css` 文件，可修改以下变量：

- 颜色方案
- 字体大小
- 间距
- 动画效果

## 浏览器支持

- Chrome / Edge
- Firefox
- Safari
- Opera

## 许可证

MIT License