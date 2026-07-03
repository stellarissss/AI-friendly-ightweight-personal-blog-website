# 1st

A minimalist personal blog and portfolio website built with pure HTML/CSS/JS.

## Features

- **Clean, Modern Design** – Uses a dark theme with elegant visual aesthetics
- **Responsive Layout** – Fully adaptable to various screen sizes
- **Markdown Support** – Blog posts are written in Markdown format
- **Guestbook Functionality** – Allows visitors to leave messages and interact
- **Project Showcase** – Displays personal projects and work
- **Terminal-Style 404 Page** – A fun, interactive error page design

## Page Structure

- `index.html` – Homepage, displaying personal bio, article list, and links
- `blog.html` – Blog listing page
- `blog-post.html` – Individual blog post detail page
- `projects.html` – Project showcase page
- `guestbook.html` – Guestbook page
- `admin.php` – Admin backend page
- `404.html` – 404 error page (terminal-style)
- `style.css` – Global stylesheet

## Tech Stack

- HTML5
- CSS3 (custom properties, animations)
- JavaScript (native)
- Markdown

## Usage

1. Clone the repository to your local machine or server
2. Open `index.html` directly in a browser to preview
3. Modify content in the HTML files as needed
4. Place blog posts in the `posts/` directory using Markdown format

## Directory Structure

```
.
├── index.html          # Homepage
├── blog.html           # Blog list
├── blog-post.html      # Blog post page
├── projects.html       # Project showcase
├── guestbook.html      # Guestbook
├── admin.php           # Admin panel
├── 404.html            # 404 page
├── style.css           # Stylesheet
└── posts/              # Blog posts directory
    └── how-to-build-this-site.md
```

## Customization

### Modify Navigation Links

In each HTML file, locate the `<div class="nav-links">` section and update the corresponding `<a>` tags.

### Add New Blog Posts

Create a new `.md` file in the `posts/` directory. After writing the post, add a link to it on the blog listing page.

### Customize Styles

Edit the `style.css` file to modify the following variables:

- Color scheme
- Font sizes
- Spacing
- Animation effects

## Browser Support

- Chrome / Edge
- Firefox
- Safari
- Opera

## License

MIT License