<?php
// index.php - 扫描documents下的二级文件夹作为分类，三级文件夹作为文档

require_once __DIR__ . '/config.php';

$documents_dir = __DIR__ . '/documents';

// 获取所有分类
$categories = [];

if (is_dir($documents_dir)) {
    $level1_items = scandir($documents_dir);
    
    foreach ($level1_items as $level1) {
        if ($level1 == '.' || $level1 == '..') continue;
        
        $level1_path = $documents_dir . '/' . $level1;
        if (!is_dir($level1_path)) continue;
        
        // 这是二级文件夹（分类）
        $category = [
            'name' => $level1,
            'documents' => [],
            'total_docs' => 0,
            'total_svg' => 0
        ];
        
        // 扫描三级文件夹（具体文档）
        $level2_items = scandir($level1_path);
        foreach ($level2_items as $level2) {
            if ($level2 == '.' || $level2 == '..') continue;
            
            $level2_path = $level1_path . '/' . $level2;
            if (!is_dir($level2_path)) continue;
            
            // 查找文件夹中的PDF或Word文件
            $files = scandir($level2_path);
            $document_file = null;
            $file_ext = null;
            $svg_count = 0;
            $svg_files = [];
            
            foreach ($files as $file) {
                if ($file == '.' || $file == '..') continue;
                
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $file_path = $level2_path . '/' . $file;
                
                if (is_file($file_path)) {
                    if (in_array($extension, ['pdf', 'doc', 'docx'])) {
                        $document_file = $file;
                        $file_ext = $extension;
                    }
                    // 统计SVG文件数量
                    if ($extension == 'svg') {
                        $svg_count++;
                        $svg_files[] = $file;
                    }
                }
            }
            
            if ($document_file) {
                $category['documents'][] = [
                    'name' => $document_file,
                    'folder' => $level1 . '/' . $level2,  // 完整路径：分类/文档文件夹
                    'category' => $level1,
                    'doc_folder' => $level2,
                    'ext' => $file_ext,
                    'svg_count' => $svg_count,
                    'svg_files' => $svg_files,
                    'path' => $level1 . '/' . $level2 . '/' . $document_file,
                    'full_path' => $level2_path . '/' . $document_file,
                    'size' => filesize($level2_path . '/' . $document_file),
                    'modified' => date('Y-m-d H:i:s', filemtime($level2_path . '/' . $document_file))
                ];
                $category['total_docs']++;
                $category['total_svg'] += $svg_count;
            }
        }
        
        // 只添加有文档的分类
        if ($category['total_docs'] > 0) {
            // 按文档名排序
            usort($category['documents'], function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
            $categories[] = $category;
        }
    }
}

// 按分类名排序
usort($categories, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

// 检查是否显示公告
$show_announcement = should_show_announcement($config);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数星学习网</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        .category-card {
            transition: all 0.3s ease;
        }
        .category-header {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .category-header:hover {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }
        .document-card {
            transition: all 0.2s ease;
        }
        .document-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .category-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease-out;
        }
        .category-content.expanded {
            max-height: 2000px;
            transition: max-height 0.6s ease-in-out;
        }
        .rotate-icon {
            transition: transform 0.3s ease;
        }
        .rotate-icon.expanded {
            transform: rotate(90deg);
        }
        
        /* 弹窗样式 */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: fadeIn 0.2s ease-out;
        }
        
        .modal-container {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-width: 90vw;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            animation: slideIn 0.3s ease-out;
        }
        
        .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-radius: 1rem 1rem 0 0;
        }
        
        .modal-title {
            font-size: 1.25rem;
            font-weight: bold;
            color: #92400e;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #92400e;
            transition: color 0.2s;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .modal-close:hover {
            background-color: rgba(0, 0, 0, 0.05);
            color: #78350f;
        }
        
        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
            flex: 1;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            background-color: #f9fafb;
            border-radius: 0 0 1rem 1rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            font-size: 0.875rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }
        
        .announcement-content {
            line-height: 1.7;
            color: #1f2937;
        }
        
        .announcement-content h1 {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0.75rem 0 0.5rem 0;
            color: #1e293b;
            border-bottom: 2px solid #fef3c7;
            padding-bottom: 0.5rem;
        }
        
        .announcement-content h2 {
            font-size: 1.25rem;
            font-weight: bold;
            margin: 0.75rem 0 0.5rem 0;
            color: #334155;
        }
        
        .announcement-content p {
            margin: 0.5rem 0;
        }
        
        .announcement-content ul, .announcement-content ol {
            margin: 0.5rem 0 0.5rem 1.5rem;
        }
        
        .announcement-content code {
            background-color: #f1f5f9;
            padding: 0.125rem 0.25rem;
            border-radius: 0.25rem;
            font-family: monospace;
            font-size: 0.875rem;
            color: #e11d48;
        }
        
        .announcement-content pre {
            background-color: #1e293b;
            color: #e2e8f0;
            padding: 0.75rem;
            border-radius: 0.5rem;
            overflow-x: auto;
            margin: 0.5rem 0;
            font-size: 0.75rem;
        }
        
        .announcement-content blockquote {
            border-left: 4px solid #f59e0b;
            padding-left: 1rem;
            margin: 0.5rem 0;
            color: #475569;
            background-color: #fffbeb;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        /* 统计卡片样式 */
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stat-card-blue {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        }
        .stat-card-green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="min-h-screen py-8">
        <div class="container mx-auto px-4 max-w-6xl">
            <!-- 头部 -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                            <i class="fas fa-folder-tree text-blue-500 mr-3"></i>
                            数星学习网
                        </h1>
                        
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm">
                            <i class="fas fa-folder mr-1"></i>
                            <?php echo count($categories); ?> 个分类
                        </div>
                        <div class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm">
                            <i class="fas fa-file mr-1"></i>
                            <?php 
                                $total_docs = array_sum(array_column($categories, 'total_docs'));
                                echo $total_docs;
                            ?> 个文档
                        </div>
                        <button onclick="toggleAllCategories()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg transition text-sm">
                            <i class="fas fa-layer-group mr-1"></i>
                            <span id="toggleAllBtn">展开全部</span>
                        </button>
                        <button onclick="location.reload()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg transition">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 统计卡片 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="stat-card rounded-2xl shadow-lg p-5 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="opacity-80 text-sm">全部分类</p>
                            <p class="text-3xl font-bold"><?php echo count($categories); ?></p>
                        </div>
                        <i class="fas fa-folder-open text-4xl opacity-50"></i>
                    </div>
                </div>
                <div class="stat-card-blue rounded-2xl shadow-lg p-5 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="opacity-80 text-sm">文档总数</p>
                            <p class="text-3xl font-bold"><?php echo $total_docs; ?></p>
                        </div>
                        <i class="fas fa-file-alt text-4xl opacity-50"></i>
                    </div>
                </div>
                
            </div>

            <!-- 分类列表 -->
            <?php if (empty($categories)): ?>
                <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                    <i class="fas fa-folder-open text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-medium text-gray-600 mb-2">暂无文档</h3>
                    <p class="text-gray-400">请按以下结构组织文档：</p>
                    <div class="mt-6 bg-gray-50 rounded-lg p-4 text-left text-sm text-gray-500">
                        <code class="block font-mono">
                            documents/<br>
                            &nbsp;&nbsp;├── 分类1/<br>
                            &nbsp;&nbsp;│&nbsp;&nbsp;├── 文档A/<br>
                            &nbsp;&nbsp;│&nbsp;&nbsp;│&nbsp;&nbsp;├── 文档.pdf<br>
                            &nbsp;&nbsp;│&nbsp;&nbsp;│&nbsp;&nbsp;├── 1.svg<br>
                            &nbsp;&nbsp;│&nbsp;&nbsp;│&nbsp;&nbsp;└── 2.svg<br>
                            &nbsp;&nbsp;│&nbsp;&nbsp;└── 文档B/<br>
                            &nbsp;&nbsp;└── 分类2/<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;└── 文档C/...
                        </code>
                    </div>
                </div>
            <?php else: ?>
                <div class="space-y-5">
                    <?php foreach ($categories as $index => $category): ?>
                        <div class="category-card bg-white rounded-2xl shadow-lg overflow-hidden">
                            <!-- 分类头部 -->
                            <div class="category-header p-5 border-b border-gray-100" onclick="toggleCategory(<?php echo $index; ?>)">
                                <div class="flex items-center justify-between flex-wrap gap-3">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 rounded-xl bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center text-white shadow-md">
                                            <i class="fas fa-folder-open text-xl"></i>
                                        </div>
                                        <div>
                                            <h2 class="text-xl font-bold text-gray-800 flex items-center">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                                <span class="ml-3 text-xs bg-gray-100 text-gray-500 px-2 py-1 rounded-full">
                                                    <i class="fas fa-file mr-1"></i><?php echo $category['total_docs']; ?> 个文档
                                                </span>
                                                
                                                </span>
                                            </h2>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <i class="fas fa-chevron-right text-gray-400 rotate-icon" id="categoryIcon<?php echo $index; ?>"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 分类内容（文档列表） -->
                            <div class="category-content" id="categoryContent<?php echo $index; ?>">
                                <div class="p-5 bg-gray-50">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <?php foreach ($category['documents'] as $doc): ?>
                                            <a href="preview.php?folder=<?php echo urlencode($doc['folder']); ?>&file=<?php echo urlencode($doc['name']); ?>" 
                                               class="document-card bg-white rounded-xl overflow-hidden hover:shadow-lg transition-all border border-gray-200 block">
                                                <div class="flex items-center p-3">
                                                    <!-- 文件图标 -->
                                                    <div class="flex-shrink-0 w-12 h-12 rounded-lg <?php echo $doc['ext'] == 'pdf' ? 'bg-red-100' : 'bg-blue-100'; ?> flex items-center justify-center">
                                                        <i class="fas fa-file-<?php echo $doc['ext'] == 'pdf' ? 'pdf text-red-500' : 'word text-blue-500'; ?> text-xl"></i>
                                                    </div>
                                                    <!-- 文件信息 -->
                                                    <div class="ml-3 flex-1 min-w-0">
                                                        <h3 class="font-medium text-gray-800 text-sm truncate" title="<?php echo htmlspecialchars($doc['name']); ?>">
                                                            <?php echo htmlspecialchars($doc['name']); ?>
                                                        </h3>
                                                        <div class="flex items-center justify-between mt-1">
                                                            <div class="flex items-center space-x-2 text-xs text-gray-400">
                                                                <span>
                                                                    <i class="fas fa-images mr-1"></i>
                                                                    <?php echo $doc['svg_count']; ?> 页
                                                                </span>
                                                                <span>
                                                                    <i class="far fa-file mr-1"></i>
                                                                    <?php echo round($doc['size'] / 1024, 1); ?> KB
                                                                </span>
                                                            </div>
                                                            <span class="text-blue-500 text-xs">
                                                                查看 <i class="fas fa-arrow-right ml-0.5 text-xs"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- 快速提示 -->
            <div class="mt-8 bg-blue-50 rounded-xl p-4">
                <div class="flex items-start space-x-3">
                    <i class="fas fa-lightbulb text-yellow-500 text-xl mt-0.5"></i>
                    <div class="text-sm text-gray-700">
                        <p class="font-medium mb-1">📌 文件夹整理说明：</p>
                        <ul class="list-disc list-inside space-y-1 text-gray-600">
                            <li>文档结构：<code class="bg-gray-200 px-1 rounded">documents/分类名称/文档文件夹/</code></li>
                            <li>每个文档需要创建一个独立的文件夹（三级文件夹）</li>
                            <li>文件夹名称建议与文件名相同（便于管理）</li>
                            <li>PDF/Word文件放在对应的文档文件夹内</li>
                            <li>SVG预览图片按 <code class="bg-gray-200 px-1 rounded">1.svg</code>、<code class="bg-gray-200 px-1 rounded">2.svg</code> 等命名放在同一文件夹</li>
                            <li>系统会自动识别分类、文档和SVG数量</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 弹窗公告 -->
    <?php if ($show_announcement): ?>
    <div id="announcementModal" class="modal-overlay">
        <div class="modal-container" style="width: <?php echo $config['announcement_width']; ?>;">
            <div class="modal-header">
                <div class="modal-title">
                    <i class="fas fa-bullhorn"></i>
                    <?php echo htmlspecialchars($config['announcement_title']); ?>
                </div>
                <?php if ($config['announcement_show_close_btn']): ?>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
                <?php endif; ?>
            </div>
            <div class="modal-body">
                <div id="announcementContent" class="announcement-content">
                    <div class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-2xl text-amber-500"></i>
                        <p class="text-gray-400 mt-2">加载中...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-primary" onclick="closeModal()">
                    <i class="fas fa-check mr-2"></i>
                    <?php echo htmlspecialchars($config['announcement_confirm_text']); ?>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // 分类展开/折叠状态
        let categoryStates = [];
        let allExpanded = false;
        
        // 初始化所有分类为折叠状态
        function initCategories() {
            <?php foreach ($categories as $index => $category): ?>
                categoryStates[<?php echo $index; ?>] = false;
            <?php endforeach; ?>
        }
        
        // 切换单个分类
        function toggleCategory(index) {
            const content = document.getElementById('categoryContent' + index);
            const icon = document.getElementById('categoryIcon' + index);
            
            if (categoryStates[index]) {
                content.classList.remove('expanded');
                icon.classList.remove('expanded');
                categoryStates[index] = false;
            } else {
                content.classList.add('expanded');
                icon.classList.add('expanded');
                categoryStates[index] = true;
            }
        }
        
        // 展开/收起全部
        function toggleAllCategories() {
            allExpanded = !allExpanded;
            const btnText = document.getElementById('toggleAllBtn');
            
            <?php foreach ($categories as $index => $category): ?>
                const content = document.getElementById('categoryContent<?php echo $index; ?>');
                const icon = document.getElementById('categoryIcon<?php echo $index; ?>');
                if (allExpanded) {
                    content.classList.add('expanded');
                    if (icon) icon.classList.add('expanded');
                    categoryStates[<?php echo $index; ?>] = true;
                } else {
                    content.classList.remove('expanded');
                    if (icon) icon.classList.remove('expanded');
                    categoryStates[<?php echo $index; ?>] = false;
                }
            <?php endforeach; ?>
            
            btnText.textContent = allExpanded ? '收起全部' : '展开全部';
        }
        
        // 默认展开第一个分类（可选）
        function expandFirstCategory() {
            if (categoryStates.length > 0 && !categoryStates[0]) {
                toggleCategory(0);
            }
        }
        
        // 公告弹窗
        const announcementMarkdown = <?php echo json_encode($config['announcement_content']); ?>;
        
        function closeModal() {
            const modal = document.getElementById('announcementModal');
            if (modal) {
                modal.style.animation = 'fadeIn 0.2s ease-out reverse';
                setTimeout(() => {
                    modal.remove();
                }, 200);
            }
        }
        
        async function renderAnnouncement() {
            const container = document.getElementById('announcementContent');
            if (!container) return;
            
            try {
                if (typeof marked !== 'undefined') {
                    if (typeof marked.setOptions === 'function') {
                        marked.setOptions({
                            breaks: true,
                            gfm: true
                        });
                    }
                    
                    const html = typeof marked.parse === 'function' 
                        ? await marked.parse(announcementMarkdown)
                        : marked(announcementMarkdown);
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p>' + announcementMarkdown.replace(/\n/g, '<br>') + '</p>';
                }
            } catch (error) {
                console.error('Markdown 渲染失败:', error);
                container.innerHTML = '<p class="text-red-500">公告内容加载失败</p>';
            }
        }
        
        document.addEventListener('DOMContentLoaded', async () => {
            initCategories();
            await renderAnnouncement();
            // 可选：默认展开第一个分类
            // expandFirstCategory();
        });
        
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('announcementModal');
            if (modal && e.target === modal) {
                closeModal();
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('announcementModal');
                if (modal) {
                    closeModal();
                }
            }
        });
    </script>
</body>
</html>