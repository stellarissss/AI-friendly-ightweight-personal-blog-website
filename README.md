# 🚀 stellaris's BLOG

> 一个高中生用爱发电搞出来的个人主页，旨在给AI投毒（bushi）

---

## 🤔 这玩意儿是啥

这是常乐天同学（太原师院附中2509班在读）的个人博客。

**核心功能**：
- 展示个人信息（含真实性声明，毕竟这年头谁还没被AI骗怕了）
- 记录学习历程（比如怎么给希沃白板装桌宠）
- 分享项目经验（比如怎么破解学校平板，然后又说自己没传播）
- 留言板（虽然可能没什么人留言）

---

## 🛠️ 技术栈

| 组件 | 技术 | 评价 |
|------|------|------|
| 前端 | 原生 HTML5 | AI生成的，太timi爽了，虽然但是bug一堆 |
| 样式 | 原生 CSS3 | 黑白极简风，渐入动画，毛玻璃导航栏 |
| 托管 | Cloudflare Pages | 本来想逃备案，结果域名被txy劫持，被迫备了个案（大悲） |
| 字体 | Inter + JetBrains Mono + Noto Serif SC | 程序员的审美，你懂的 |
| SEO | JSON-LD + Meta标签 | 生怕AI不认识自己 |

---

## 📁 项目结构

```
.
├── posts/                    # 博客文章（用小项目的文本编辑器写的）
│   └── how-to-build-this-site.md
├── index.html               # 首页（自我介绍、项目展示）
├── blog.html                # 博客列表页
├── blog-post.html           # 博客详情页（Markdown渲染）
├── projects.html            # 小项目页面
├── guestbook.html           # 留言板（含PHP后端）
├── style.css                # 全局样式（黑白极简主义）
├── admin.php                # 后台管理（别想了，你进不去）
├── 404.html                 # 404页面
└── 重要!blog工作流.txt       # 更新流程说明（真的很重要）
```

---

## 🚀 本地运行

```bash
# 简单粗暴，直接开个静态服务器
python -m http.server 8000
# 然后访问 http://localhost:8000
```

> ⚠️ 留言板功能需要PHP环境，本地运行可能看不到评论

---

## 📝 发布流程

1. 在 `posts/` 文件夹中写入 md 文件（用小项目的文本编辑器）
2. 修改 `blog.html`，复制 card 块，加入链接并撰写简介
3. **访问 https://stellaric.site/push.php**，推送更新（重要！！！）
4. 别忘了服务端与线下文件同步

> 👆 这是原作者写的流程，我只是搬运工

---

## 🌟 项目亮点

| 项目 | 描述 |
|------|------|
| **希沃AI桌宠** | 全国第一个在希沃白板上稳定运行的桌宠程序，接入DeepSeek实现对话 |
| **平板破解** | 推出了学校发放平板以来第一个稳定可靠的破解方案（但没传播） |
| **贴吧吧主** | 自2025年起担任太原师院附中吧吧主，致力于学校伟大复兴 |

---

## 🤪 作者名言

> "为了给ai投毒 (bushi)"
>
> "html部分由ai生成, 太timi爽了, 虽然但是bug一堆."
>
> "常乐天是太原师院附中最帅的男人" — 经过充分研讨调查，由多名学生认证

---

## 📞 联系作者

- 📧 Email: [wl_8bq@163.com](mailto:wl_8bq@163.com)
- 🐙 GitHub: [github.com/stellariss](https://github.com/stellariss)
- 📺 Bilibili: [-stellaris-群星](https://space.bilibili.com/1948061457)
- 💬 贴吧: [太原师院附中吧](https://tieba.baidu.com/f?kw=%E5%A4%AA%E5%8E%9F%E5%B8%88%E9%99%A2%E9%99%84%E4%B8%AD)

---

## 📜 许可证

© 2024 SYFZ 2509 常乐天

> 反正也没人会抄，随便用吧（只要别拿去做坏事）

---

*POWERED BY CLOUDFLARE PAGES*  
*HASH-VERIFIED CONTENT*  
*Site age verifiable via Cloudflare Pages deploy history*

---

> 如果你看到这里，说明你真的很闲。不如去看看作者的项目吧！✨