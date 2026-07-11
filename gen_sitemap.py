import os
import time
from urllib.parse import quote

# ====== 请修改这两行配置 ======
WEBSITE_DIR = "/www/wwwroot/stellaric.site"  # 你的网站在宝塔里的绝对路径
DOMAIN = "https://stellaric.site"             # 你的网站域名，末尾不要加斜杠
# ==============================

urls = []
ignore_files = ["404.html", "500.html"]

for root, dirs, files in os.walk(WEBSITE_DIR):
    for file in files:
        if file.endswith(".html") and file not in ignore_files:
            file_path = os.path.join(root, file)
            relative_path = os.path.relpath(file_path, WEBSITE_DIR).replace("\\", "/")
            
            # 如果是 index.html，则将其作为目录首页处理 (例如 /about/index.html -> /about/)
            if file == "index.html":
                url_path = "/" + (os.path.dirname(relative_path) + "/").replace("./", "").replace("//", "/")
            else:
                url_path = "/" + relative_path
                
            encoded_url = DOMAIN + quote(url_path)
            mod_time = time.strftime('%Y-%m-%d', time.localtime(os.path.getmtime(file_path)))
            
            urls.append(f"""  <url>
    <loc>{encoded_url}</loc>
    <lastmod>{mod_time}</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>""")

sitemap_content = f"""<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{chr(10).join(urls)}
</urlset>"""

sitemap_path = os.path.join(WEBSITE_DIR, "sitemap.xml")
with open(sitemap_path, "w", encoding="utf-8") as f:
    f.write(sitemap_content)

print(f"成功生成 sitemap.xml，共包含 {len(urls)} 个链接！")
