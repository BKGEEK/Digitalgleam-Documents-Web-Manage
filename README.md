# 📚 数星学习网 - 文档管理系统

一个基于PHP的轻量级文档管理系统，支持PDF、Word文档的分类管理，并将SVG图片作为文档预览页面。

## ✨ 功能特性

- 📁 **多级分类管理**：支持二级分类、三级文档文件夹结构
- 📄 **多格式支持**：支持 PDF、DOC、DOCX 格式文档
- 🖼️ **SVG预览**：将SVG图片作为文档预览页面，支持翻页查看
- 🔒 **安全防护**：内置开发者工具检测、防下载保护
- 📱 **响应式设计**：基于TailwindCSS，支持移动端访问
- 🎨 **优雅界面**：现代化UI设计，流畅的动画效果
- 📢 **公告系统**：支持Markdown格式的系统公告弹窗
- ⌨️ **快捷键支持**：键盘翻页、缩放等便捷操作

## 🗂️ 目录结构

```
项目根目录/
├── index.php              # 主页面，文档分类展示
├── preview.php            # 文档预览页面（SVG翻页）
├── download.php           # 文件下载接口（隐藏真实路径）
├── config.php             # 系统配置文件
└── documents/            # 文档存储目录（需手动创建）
    ├── .htaccess              # Apache访问控制配置
    ├── 分类名称/           # 二级目录：文档分类
    │   ├── 文档文件夹A/     # 三级目录：具体文档
    │   │   ├── 文档.pdf    # PDF或Word文件
    │   │   ├── 1.svg       # 第1页预览图
    │   │   ├── 2.svg       # 第2页预览图
    │   │   └── ...
    │   └── 文档文件夹B/
    │       ├── 文档.docx
    │       ├── 1.svg
    │       └── ...
    └── 另一分类/
        └── ...

## 📋 环境要求

- PHP 7.4 或更高版本
- Web服务器（Apache/Nginx）
- 文件写入权限（用于文档目录）
- 现代浏览器（支持ES6、CSS3）

## 🚀 安装步骤

### 1. 部署文件

将所有PHP文件上传到您的Web服务器目录（如 `htdocs` 或 `public_html`）。

### 2. 创建文档目录

在项目根目录下创建 `documents` 文件夹：

```bash
mkdir documents
```

### 3. 设置目录权限

确保 `documents` 目录可读：

```bash
chmod 755 documents
```

### 4. 配置Web服务器

#### Apache
项目已包含 `.htaccess` 文件，确保Apache启用了 `mod_rewrite` 模块。

#### Nginx
如果使用Nginx，请添加以下配置：

```nginx
location ~ \.php$ {
    include fastcgi_params;
    fastcgi_pass php-fpm;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

### 5. 配置公告（可选）

编辑 `config.php` 文件，修改公告内容、标题、弹窗宽度等。

## 📖 使用指南

### 添加文档

1. 在 `documents` 目录下创建**分类文件夹**（二级目录）
2. 在分类文件夹下创建**文档文件夹**（三级目录）
3. 将PDF/Word文件放入文档文件夹
4. 将SVG预览图片放入同一文件夹，命名为 `1.svg`、`2.svg`...

### 文件命名规则

| 文件类型 | 命名格式 | 示例 |
|---------|---------|------|
| 文档文件 | 任意名称 | `用户手册.pdf` |
| 预览图片 | 数字编号.svg | `1.svg`, `2.svg` |
| 替代格式 | page-1.svg | `page-1.svg`, `page-2.svg` |

### 访问系统

在浏览器中访问 `index.php` 即可进入文档管理页面。

## 🎮 预览页面快捷键

| 快捷键 | 功能 |
|-------|------|
| ← / → | 上一页/下一页 |
| + / - | 放大/缩小 |
| 0 | 重置缩放 |
| F | 全屏模式 |
| 鼠标拖拽 | 移动视图 |

## ⚙️ 配置说明

### 公告配置 (`config.php`)

```php
$config = [
    'announcement_enabled' => true,      // 是否显示公告
    'announcement_title' => '📢 系统公告', // 公告标题
    'announcement_content' => '...',      // 公告内容（支持Markdown）
    'announcement_width' => '500px',      // 弹窗宽度
    'announcement_confirm_text' => '我知道了', // 确认按钮文字
    'announcement_show_close_btn' => false, // 是否显示关闭按钮
];
```

### 下载限制 (`download.php`)

可修改 `$allowed_extensions` 数组来限制允许下载的文件类型：

```php
$allowed_extensions = ['pdf', 'doc', 'docx'];
```

## 🛡️ 安全特性

- **路径遍历防护**：过滤 `../`、`\\` 等危险字符
- **文件类型白名单**：只允许指定格式下载
- **真实路径隐藏**：通过 `download.php` 流式传输文件
- **开发者工具检测**：防止F12、右键查看源码等操作
- **目录权限验证**：确保文件访问在 `documents` 目录范围内

## 🔧 故障排除

### 问题1：分类不显示
**解决方案**：检查 `documents` 目录结构是否正确，确保有二级和三级文件夹。

### 问题2：预览图片不显示
**解决方案**：
- 确认SVG文件命名为 `1.svg`、`2.svg`...
- 检查文件权限是否为可读

### 问题3：下载功能失效
**解决方案**：
- 检查PHP配置 `max_execution_time` 和 `memory_limit`
- 确认文件扩展名在白名单内

### 问题4：公告弹窗不显示
**解决方案**：
- 检查 `config.php` 中 `announcement_enabled` 是否为 `true`
- 确认 `marked.js` CDN 可以正常访问

## 📝 文档组织建议

1. **文件夹命名**：使用有意义的名称，建议使用英文或拼音
2. **预览图片生成**：可将PDF每页导出为SVG格式
3. **文档版本管理**：建议在文档文件夹名称中加入版本号
4. **定期备份**：定期备份 `documents` 目录

## 🔄 更新日志

### 2026-06-13
- 新增整套系统

## 📄 许可证

本项目仅供内部学习使用。
DigitalGleam，版权所有盗版必究。

## 📞 技术支持

如有问题，请检查：
- PHP错误日志
- Web服务器错误日志
- 浏览器控制台错误信息
- 实在严重请联系```QQ:3799599152```
---

**注意**：请勿将 `documents` 目录设置为Web可写权限，建议使用 `755` 或 `750`。
