<?php
// config.php - 系统配置文件

// 公告配置
$config = [
    // 公告开关 (true: 显示公告, false: 隐藏公告)
    'announcement_enabled' => true,
    
    // 公告标题
    'announcement_title' => '📢 系统公告',
    
    // 公告内容 (支持Markdown格式)
    'announcement_content' => '
# 欢迎使用数星文档管理系统！

## 更新内容
- **新增功能**：支持文件夹独立管理文档
- **SVG预览**：自动识别并渲染SVG图片
- **文档类型**：支持 PDF、DOC、DOCX 格式

## 使用说明
1. 在 `documents` 目录下创建分类文件夹
2. 在`documents/分类文件夹`下创建具体文件夹
3. 将文档和SVG图片放入对应文件夹
4. 点击文档卡片查看详细预览

> 💡 提示：具体文件夹名称建议与文档名称保持一致

---

*最后更新：2026年6月13日*
    ',
    
    // 弹窗宽度 (px 或 百分比)
    'announcement_width' => '500px',
    
    // 确认按钮文字
    'announcement_confirm_text' => '我知道了',
    
    // 是否显示关闭按钮 (右上角X)
    'announcement_show_close_btn' => false,
];

// 检查公告是否应该显示（强制每次访问都显示）
function should_show_announcement($config) {
    return $config['announcement_enabled'];
}
?>