<?php
// preview.php - 从指定文件夹读取PDF和对应的SVG序列

$folder = isset($_GET['folder']) ? trim($_GET['folder']) : '';
$filename = isset($_GET['file']) ? basename(trim($_GET['file'])) : '';

// 安全检查：防止目录遍历
$folder = str_replace(['..', '\\', '//'], '', $folder);
$folder = ltrim($folder, '/');

$folder_path = __DIR__ . '/documents/' . $folder;
$file_path = $folder_path . '/' . $filename;

// 检查文件夹和文件是否存在
if (!is_dir($folder_path)) {
    die('文件夹不存在: ' . htmlspecialchars($folder));
}

if (!file_exists($file_path)) {
    die('文件不存在: ' . htmlspecialchars($filename));
}

$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

// 扫描文件夹中的所有SVG文件
$svg_images = [];
$page_count = 0;

// 查找数字命名的SVG文件 (1.svg, 2.svg, 3.svg...)
if (is_dir($folder_path)) {
    $files = scandir($folder_path);
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        
        // 匹配纯数字命名的SVG
        if (preg_match('/^(\d+)\.svg$/i', $file, $matches)) {
            $page_num = (int)$matches[1];
            $svg_images[$page_num] = $file;
            $page_count++;
        }
    }
    
    // 按页码排序
    ksort($svg_images);
    $svg_images = array_values($svg_images); // 重新索引
}

// 如果没有找到数字命名的SVG，尝试其他命名规则
if (empty($svg_images)) {
    // 尝试 page-1.svg, page-2.svg 格式
    $files = scandir($folder_path);
    foreach ($files as $file) {
        if (preg_match('/page[-_]?(\d+)\.svg/i', $file, $matches)) {
            $page_num = (int)$matches[1];
            $svg_images[$page_num] = $file;
            $page_count++;
        }
    }
    ksort($svg_images);
    $svg_images = array_values($svg_images);
}

// 获取当前页码
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
if ($page > $page_count && $page_count > 0) $page = $page_count;

$current_svg = $page_count > 0 ? 'documents/' . $folder . '/' . $svg_images[$page - 1] : null;

// 获取分类名称（二级文件夹名）
$category_name = '';
$folder_parts = explode('/', $folder);
if (count($folder_parts) >= 1) {
    $category_name = $folder_parts[0];
}
$doc_name = count($folder_parts) >= 2 ? $folder_parts[1] : $folder;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <script>
(function() {
    // ==================== 配置 ====================
    const CONFIG = {
        REDIRECT_URL: null,           // 检测后跳转地址，null 则只清空页面
        SHOW_WARNING: true,           // 是否显示友好警告
        CLEAR_CONTENT: true,          // 是否清空页面内容
        DISABLE_LONG_PRESS: true,     // 禁用长按菜单（部分移动端可调出检查）
        BLOCK_KEYBOARD_SHORTCUTS: true // 拦截 F12、Ctrl+Shift+I 等
    };
    // ==============================================

    let devtoolsOpened = false;
    let detectionInterval = null;
    let debuggerLoopActive = true;

    function triggerProtection() {
        if (devtoolsOpened) return;
        devtoolsOpened = true;

        if (detectionInterval) clearInterval(detectionInterval);
        debuggerLoopActive = false;

        console.warn('[Security] DevTools detected');

        if (CONFIG.REDIRECT_URL) {
            window.location.href = CONFIG.REDIRECT_URL;
            return;
        }

        if (!CONFIG.CLEAR_CONTENT) return;

        if (CONFIG.SHOW_WARNING) {
            document.body.innerHTML = `
                <div style="display:flex;align-items:center;justify-content:center;height:100vh;background:#f0f2f5;color:#e74c3c;font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;text-align:center;flex-direction:column;padding:20px;">
                    <div style="font-size:64px;margin-bottom:20px;">🔒</div>
                    <h2 style="font-size:22px;margin-bottom:10px;color:#333;">开发者工具已禁用</h2>
                    <p style="font-size:14px;color:#666;max-width:280px;">本站为防止攻击已禁用右键、长按、F12、等功能，如您不想妨碍使用请不要这么做</p>
                    <button onclick="location.reload()" style="margin-top:24px;padding:10px 28px;background:#3498db;color:white;border:none;border-radius:30px;font-size:14px;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,0.1);">刷新页面</button>
                </div>
            `;
        } else {
            while (document.body.firstChild) document.body.removeChild(document.body.firstChild);
        }
    }

    function startDebuggerDetection() {
        function loop() {
            if (!debuggerLoopActive) return;
            const start = performance.now();
            debugger;
            const duration = performance.now() - start;
            if (duration > 150) {
                triggerProtection();
                return;
            }
            requestAnimationFrame(loop);
        }
        loop();
    }

    let lastWidth = window.innerWidth;
    let lastHeight = window.innerHeight;

    function detectResize() {
        const w = window.innerWidth;
        const h = window.innerHeight;
        if (Math.abs(w - lastWidth) > 60 || Math.abs(h - lastHeight) > 60) {
            setTimeout(() => {
                const newW = window.innerWidth;
                const newH = window.innerHeight;
                if (Math.abs(newW - w) > 60 || Math.abs(newH - h) > 60) {
                    if (!devtoolsOpened) triggerProtection();
                }
                lastWidth = newW;
                lastHeight = newH;
            }, 150);
        } else {
            lastWidth = w;
            lastHeight = h;
        }
        if (!devtoolsOpened) requestAnimationFrame(detectResize);
    }

    function detectConsoleOverride() {
        let consoleCallCount = 0;
        const originalLog = console.log;
        console.log = function() {
            consoleCallCount++;
            originalLog.apply(console, arguments);
        };
        setInterval(() => {
            const before = consoleCallCount;
            console.log('__security_check__');
            const after = consoleCallCount;
            if (after - before > 1) {
                // 可能被拦截或篡改，但不作为主要判断依据
            }
            consoleCallCount = 0;
        }, 2000);
    }

    function detectElementGetter() {
        const el = document.createElement('div');
        let accessed = false;
        Object.defineProperty(el, 'id', {
            get: function() {
                if (!accessed && !devtoolsOpened) {
                    accessed = true;
                    setTimeout(() => {
                        if (!devtoolsOpened) triggerProtection();
                    }, 50);
                }
                return '';
            }
        });
        console.log(el);
    }

    function detectRemoteDebugging() {
        if (typeof window._phantom !== 'undefined' ||
            typeof window.__webdriver_script_func !== 'undefined' ||
            typeof window.__nightmare !== 'undefined' ||
            typeof window.__selenium_evaluate !== 'undefined') {
            triggerProtection();
        }
        
        const ua = navigator.userAgent.toLowerCase();
        if (ua.includes('chrome') && window.outerWidth - window.innerWidth > 100) {
            triggerProtection();
        }
    }

    function blockKeyboardShortcuts(e) {
        if (!CONFIG.BLOCK_KEYBOARD_SHORTCUTS) return;
        
        if (e.key === 'F12') {
            e.preventDefault();
            triggerProtection();
            return false;
        }
        if (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'C' || e.key === 'J')) {
            e.preventDefault();
            triggerProtection();
            return false;
        }
        if (e.metaKey && e.altKey && (e.key === 'I' || e.key === 'C' || e.key === 'J')) {
            e.preventDefault();
            triggerProtection();
            return false;
        }
        if (e.ctrlKey && e.key === 'u') {
            e.preventDefault();
            triggerProtection();
            return false;
        }
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            triggerProtection();
            return false;
        }
        if (e.ctrlKey && e.shiftKey && e.key === 'K') {
            e.preventDefault();
            triggerProtection();
            return false;
        }
    }

    function blockContextMenu(e) {
        e.preventDefault();
        if (CONFIG.DISABLE_LONG_PRESS) {
            triggerProtection();
        }
        return false;
    }

    function blockSelect(e) {
        e.preventDefault();
    }

    function detectMobileDebugUI() {
        const observer = new MutationObserver(function(mutations) {
            for (let i = 0; i < mutations.length; i++) {
                const addedNodes = mutations[i].addedNodes;
                for (let j = 0; j < addedNodes.length; j++) {
                    const node = addedNodes[j];
                    if (node.nodeType === 1) {
                        const className = (node.className || '').toString().toLowerCase();
                        const id = (node.id || '').toLowerCase();
                        if (className.includes('inspect') || className.includes('devtools') ||
                            id.includes('inspect') || id.includes('devtools')) {
                            triggerProtection();
                            observer.disconnect();
                            return;
                        }
                    }
                }
            }
        });
        observer.observe(document.documentElement, { childList: true, subtree: true });
    }

    function detectIframeInjection() {
        if (window.self !== window.top) {
            triggerProtection();
        }
    }

    function init() {
        document.addEventListener('keydown', blockKeyboardShortcuts);
        document.addEventListener('contextmenu', blockContextMenu);
        if (CONFIG.DISABLE_LONG_PRESS) {
            document.addEventListener('selectstart', blockSelect);
            document.addEventListener('copy', blockSelect);
        }
        
        startDebuggerDetection();
        setTimeout(detectResize, 500);
        setTimeout(detectElementGetter, 800);
        setTimeout(detectRemoteDebugging, 1000);
        setTimeout(detectMobileDebugUI, 1500);
        setTimeout(detectIframeInjection, 500);
        
        detectConsoleOverride();
        
        let touchStartCount = 0;
        document.addEventListener('touchstart', function(e) {
            if (e.touches.length > 3) {
                touchStartCount++;
                if (touchStartCount > 2) triggerProtection();
            }
        });
        
        window.addEventListener('blur', function() {
            setTimeout(() => {
                if (document.hidden === false && !devtoolsOpened) {
                }
            }, 200);
        });
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数星学习网（文档预览） - <?php echo htmlspecialchars($filename); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .preview-container {
            background: #e5e7eb;
            min-height: 100vh;
        }
        .svg-viewer {
            background: #f9fafb;
            border-radius: 12px;
            overflow: auto;
            max-height: 75vh;
            position: relative;
            cursor: grab;
            user-select: none;
        }
        .svg-viewer:active {
            cursor: grabbing;
        }
        #zoomContainer {
            transform-origin: 0 0;
            transition: transform 0.2s ease;
            display: inline-block;
            min-width: 100%;
        }
        #previewImage {
            display: block;
            max-width: none;
            width: auto;
            height: auto;
            pointer-events: none;
        }
        .page-btn {
            transition: all 0.2s;
        }
        .page-btn:hover:not(:disabled) {
            transform: translateY(-1px);
            background-color: #f3f4f6;
        }
        .page-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        .thumbnails-container::-webkit-scrollbar {
            height: 6px;
        }
        .thumbnails-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .thumbnails-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        .reset-btn {
            transition: all 0.2s;
        }
        .reset-btn:hover {
            background-color: #e5e7eb;
            transform: rotate(180deg);
        }
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(249, 250, 251, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: 12px;
        }
        .fit-indicator {
            position: absolute;
            bottom: 12px;
            right: 12px;
            background: rgba(0,0,0,0.6);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            pointer-events: none;
            z-index: 5;
        }
        .breadcrumb-link {
            transition: color 0.2s;
        }
        .breadcrumb-link:hover {
            color: #3b82f6;
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <!-- 顶部工具栏 -->
        <div class="bg-white shadow-lg border-b border-gray-200 sticky top-0 z-20">
            <div class="container mx-auto px-4 py-3">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <!-- 左侧导航 -->
                    <div class="flex items-center space-x-4">
                        <a href="index.php" class="text-gray-600 hover:text-gray-900 transition p-2 hover:bg-gray-100 rounded-lg">
                            <i class="fas fa-arrow-left text-xl"></i>
                        </a>
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-lg <?php echo $extension == 'pdf' ? 'bg-red-100' : 'bg-blue-100'; ?> flex items-center justify-center">
                                <i class="fas fa-file-<?php echo $extension == 'pdf' ? 'pdf text-red-500' : 'word text-blue-500'; ?> text-xl"></i>
                            </div>
                            <div>
                                <h1 class="font-semibold text-gray-800">
                                    <?php echo htmlspecialchars($filename); ?>
                                </h1>
                                <div class="flex items-center space-x-2 text-xs text-gray-500">
                                    <a href="index.php" class="breadcrumb-link hover:text-blue-500">
                                        <i class="fas fa-folder-open mr-1"></i>首页
                                    </a>
                                    <span>/</span>
                                    <a href="index.php" class="breadcrumb-link hover:text-blue-500">
                                        <?php echo htmlspecialchars($category_name); ?>
                                    </a>
                                    <span>/</span>
                                    <span class="text-gray-400"><?php echo htmlspecialchars($doc_name); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 右侧操作 -->
                    <div class="flex items-center space-x-2">
                        <a href="download.php?folder=<?php echo urlencode($folder); ?>&file=<?php echo urlencode($filename); ?>"
                           class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition flex items-center space-x-2 shadow-sm">
                            <i class="fas fa-download"></i>
                            <span class="hidden sm:inline">下载源文件</span>
                        </a>
                        
                        <div class="flex items-center space-x-1 bg-gray-100 rounded-lg p-1">
                            <button onclick="zoomOut()" class="p-2 hover:bg-gray-200 rounded transition" title="缩小">
                                <i class="fas fa-search-minus text-gray-700"></i>
                            </button>
                            <span id="zoomLevel" class="text-sm text-gray-700 min-w-[50px] text-center font-medium">100%</span>
                            <button onclick="zoomIn()" class="p-2 hover:bg-gray-200 rounded transition" title="放大">
                                <i class="fas fa-search-plus text-gray-700"></i>
                            </button>
                            <button onclick="resetZoom()" class="p-2 hover:bg-gray-200 rounded transition reset-btn" title="重置">
                                <i class="fas fa-sync-alt text-gray-700 text-xs"></i>
                            </button>
                        </div>
                        
                        <button onclick="toggleFullscreen()" class="p-2 hover:bg-gray-100 rounded-lg transition">
                            <i class="fas fa-expand text-gray-600"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 主体内容 -->
        <div class="container mx-auto px-4 py-6">
            <?php if ($page_count == 0): ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 rounded-lg p-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl mr-4"></i>
                        <div>
                            <h3 class="font-semibold text-yellow-800">未找到预览图片</h3>
                            <p class="text-yellow-700 text-sm mt-1">
                                请在 <code class="bg-yellow-100 px-2 py-0.5 rounded">documents/<?php echo htmlspecialchars($folder); ?>/</code> 目录下放置
                                <code class="bg-yellow-100 px-2 py-0.5 rounded">1.svg</code>、<code class="bg-yellow-100 px-2 py-0.5 rounded">2.svg</code> 等预览图片
                            </p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="svg-viewer p-4" id="svgViewer">
                    <div id="zoomContainer" style="transform-origin: 0 0;">
                        <img src="<?php echo $current_svg; ?>"
                             alt="第<?php echo $page; ?>页"
                             id="previewImage"
                             class="rounded-lg shadow-xl">
                    </div>
                    <div class="fit-indicator hidden" id="fitIndicator">
                        <i class="fas fa-mouse-pointer mr-1"></i> 拖拽移动
                    </div>
                    <div id="loadingOverlay" class="loading-overlay hidden">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-spinner fa-spin text-3xl text-blue-500 mb-2"></i>
                            <span class="text-sm text-gray-600">加载中...</span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex flex-col items-center space-y-4">
                    <div class="flex items-center space-x-2">
                        <button onclick="goToPage(1)" class="page-btn px-3 py-2 bg-white rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 shadow-sm" <?php echo $page <= 1 ? 'disabled' : ''; ?>>
                            <i class="fas fa-angle-double-left"></i>
                        </button>
                        <button onclick="goToPage(<?php echo $page - 1; ?>)" class="page-btn px-4 py-2 bg-white rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 shadow-sm" <?php echo $page <= 1 ? 'disabled' : ''; ?>>
                            <i class="fas fa-chevron-left mr-1"></i> 上一页
                        </button>
                        
                        <div class="flex items-center space-x-2 bg-white rounded-lg border border-gray-300 px-3 py-1 shadow-sm">
                            <i class="fas fa-file-alt text-gray-400"></i>
                            <input type="number"
                                   id="pageInput"
                                   value="<?php echo $page; ?>"
                                   min="1"
                                   max="<?php echo $page_count; ?>"
                                   class="w-16 text-center border-0 focus:ring-0 text-sm font-medium"
                                   onchange="goToPage(this.value)">
                            <span class="text-sm text-gray-500">/ <?php echo $page_count; ?></span>
                        </div>
                        
                        <button onclick="goToPage(<?php echo $page + 1; ?>)" class="page-btn px-4 py-2 bg-white rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 shadow-sm" <?php echo $page >= $page_count ? 'disabled' : ''; ?>>
                            下一页 <i class="fas fa-chevron-right ml-1"></i>
                        </button>
                        <button onclick="goToPage(<?php echo $page_count; ?>)" class="page-btn px-3 py-2 bg-white rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 shadow-sm" <?php echo $page >= $page_count ? 'disabled' : ''; ?>>
                            <i class="fas fa-angle-double-right"></i>
                        </button>
                    </div>
                    
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-keyboard mr-1"></i>
                        快捷键：←/→ 翻页 &nbsp;&nbsp; +/- 缩放 &nbsp;&nbsp; 0 重置 &nbsp;&nbsp; 拖拽移动视图
                    </div>
                </div>
                
                <?php if ($page_count > 1): ?>
                <div class="mt-8">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-medium text-gray-700 flex items-center">
                            <i class="fas fa-th-large mr-2 text-blue-500"></i>
                            页面缩略图
                        </h3>
                        <button onclick="toggleThumbnails()" class="text-xs text-blue-500 hover:text-blue-600 transition">
                            <span id="toggleText">收起</span> <i id="toggleIcon" class="fas fa-chevron-up ml-1"></i>
                        </button>
                    </div>
                    <div id="thumbnailsContainer" class="thumbnails-container overflow-x-auto pb-2">
                        <div class="flex space-x-3" style="min-width: min-content;">
                            <?php for ($i = 1; $i <= $page_count; $i++): ?>
                                <div onclick="goToPage(<?php echo $i; ?>)" 
                                     class="flex-shrink-0 w-24 cursor-pointer transition-all duration-200 hover:transform hover:scale-105 <?php echo $i == $page ? 'ring-2 ring-blue-500 ring-offset-2 rounded-lg' : 'opacity-70 hover:opacity-100'; ?>">
                                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
                                        <img src="documents/<?php echo $folder . '/' . $svg_images[$i-1]; ?>" 
                                             alt="第<?php echo $i; ?>页" 
                                             class="w-full h-auto" 
                                             loading="lazy">
                                        <div class="text-center text-xs py-1 <?php echo $i == $page ? 'bg-blue-50 text-blue-600 font-medium' : 'text-gray-500'; ?>">
                                            <?php echo $i; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        let currentZoom = 100;
        let minZoom = 30;
        let maxZoom = 300;
        let naturalWidth = 0;
        let naturalHeight = 0;
        
        const zoomContainer = document.getElementById('zoomContainer');
        const zoomLevelSpan = document.getElementById('zoomLevel');
        const previewImage = document.getElementById('previewImage');
        const svgViewer = document.getElementById('svgViewer');
        const fitIndicator = document.getElementById('fitIndicator');
        
        let thumbnailsVisible = true;
        
        function fitToWidth() {
            if (!previewImage || !svgViewer) return;
            
            if (naturalWidth === 0) {
                naturalWidth = previewImage.naturalWidth;
                naturalHeight = previewImage.naturalHeight;
            }
            
            const containerWidth = svgViewer.clientWidth - 32;
            const targetZoom = (containerWidth / naturalWidth) * 100;
            currentZoom = Math.min(maxZoom, Math.max(minZoom, targetZoom));
            applyZoom();
            
            svgViewer.scrollTop = 0;
            svgViewer.scrollLeft = 0;
        }
        
        let isDragging = false;
        let startX, startY;
        let scrollLeft, scrollTop;
        
        function initDragEvents() {
            if (!svgViewer) return;
            
            svgViewer.addEventListener('mousedown', (e) => {
                if (e.target.closest('.page-btn') || e.target.closest('a') || e.target.closest('.thumbnails-container')) return;
                
                isDragging = true;
                startX = e.pageX - svgViewer.offsetLeft;
                startY = e.pageY - svgViewer.offsetTop;
                scrollLeft = svgViewer.scrollLeft;
                scrollTop = svgViewer.scrollTop;
                svgViewer.style.cursor = 'grabbing';
                e.preventDefault();
            });
            
            window.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                e.preventDefault();
                const x = e.pageX - svgViewer.offsetLeft;
                const y = e.pageY - svgViewer.offsetTop;
                const walkX = (x - startX);
                const walkY = (y - startY);
                svgViewer.scrollLeft = scrollLeft - walkX;
                svgViewer.scrollTop = scrollTop - walkY;
            });
            
            window.addEventListener('mouseup', () => {
                isDragging = false;
                svgViewer.style.cursor = 'grab';
            });
            
            let hintTimeout;
            svgViewer.addEventListener('mouseenter', () => {
                if (fitIndicator) {
                    fitIndicator.classList.remove('hidden');
                    clearTimeout(hintTimeout);
                    hintTimeout = setTimeout(() => {
                        fitIndicator.classList.add('hidden');
                    }, 2000);
                }
            });
        }
        
        function zoomIn() {
            if (currentZoom < maxZoom) {
                currentZoom = Math.min(currentZoom + 10, maxZoom);
                applyZoom();
            }
        }
        
        function zoomOut() {
            if (currentZoom > minZoom) {
                currentZoom = Math.max(currentZoom - 10, minZoom);
                applyZoom();
            }
        }
        
        function resetZoom() {
            fitToWidth();
        }
        
        function applyZoom() {
            if (zoomContainer && previewImage) {
                const containerRect = svgViewer.getBoundingClientRect();
                const centerXRatio = (svgViewer.scrollLeft + containerRect.width / 2) / (zoomContainer.scrollWidth || 1);
                const centerYRatio = (svgViewer.scrollTop + containerRect.height / 2) / (zoomContainer.scrollHeight || 1);
                
                zoomContainer.style.transform = `scale(${currentZoom / 100})`;
                zoomLevelSpan.textContent = Math.round(currentZoom) + '%';
                
                setTimeout(() => {
                    if (zoomContainer.scrollWidth && zoomContainer.scrollHeight) {
                        const newScrollLeft = centerXRatio * zoomContainer.scrollWidth - containerRect.width / 2;
                        const newScrollTop = centerYRatio * zoomContainer.scrollHeight - containerRect.height / 2;
                        svgViewer.scrollLeft = Math.max(0, newScrollLeft);
                        svgViewer.scrollTop = Math.max(0, newScrollTop);
                    }
                }, 10);
            }
        }
        
        function showLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) overlay.classList.remove('hidden');
        }
        
        function hideLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) overlay.classList.add('hidden');
        }
        
        function goToPage(page) {
            page = parseInt(page);
            const maxPage = <?php echo $page_count; ?>;
            if (isNaN(page)) page = 1;
            if (page < 1) page = 1;
            if (page > maxPage) page = maxPage;
            
            if (page === <?php echo $page; ?>) return;
            
            showLoading();
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            window.location.href = url.toString();
        }
        
        function toggleFullscreen() {
            const viewer = document.getElementById('svgViewer');
            if (!document.fullscreenElement) {
                viewer.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        }
        
        function toggleThumbnails() {
            const container = document.getElementById('thumbnailsContainer');
            const toggleText = document.getElementById('toggleText');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (thumbnailsVisible && container) {
                container.style.display = 'none';
                if (toggleText) toggleText.textContent = '展开';
                if (toggleIcon) toggleIcon.className = 'fas fa-chevron-down ml-1';
            } else if (container) {
                container.style.display = 'block';
                if (toggleText) toggleText.textContent = '收起';
                if (toggleIcon) toggleIcon.className = 'fas fa-chevron-up ml-1';
            }
            thumbnailsVisible = !thumbnailsVisible;
        }
        
        if (previewImage) {
            previewImage.addEventListener('load', function() {
                naturalWidth = this.naturalWidth;
                naturalHeight = this.naturalHeight;
                hideLoading();
                fitToWidth();
            });
            
            if (previewImage.complete) {
                naturalWidth = previewImage.naturalWidth;
                naturalHeight = previewImage.naturalHeight;
                hideLoading();
                fitToWidth();
            }
        }
        
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                if (previewImage && previewImage.complete && naturalWidth > 0) {
                    fitToWidth();
                }
            }, 200);
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            
            switch(e.key) {
                case 'ArrowLeft':
                    goToPage(<?php echo $page - 1; ?>);
                    break;
                case 'ArrowRight':
                    goToPage(<?php echo $page + 1; ?>);
                    break;
                case '+':
                case '=':
                    zoomIn();
                    e.preventDefault();
                    break;
                case '-':
                case '_':
                    zoomOut();
                    e.preventDefault();
                    break;
                case '0':
                    resetZoom();
                    e.preventDefault();
                    break;
                case 'f':
                case 'F':
                    toggleFullscreen();
                    e.preventDefault();
                    break;
            }
        });
        
        const nextPage = <?php echo $page + 1; ?>;
        if (nextPage <= <?php echo $page_count; ?>) {
            const img = new Image();
            img.src = 'documents/<?php echo $folder . '/' . ($svg_images[$page] ?? ''); ?>';
        }
        
        const prevPage = <?php echo $page - 1; ?>;
        if (prevPage >= 1) {
            const img = new Image();
            img.src = 'documents/<?php echo $folder . '/' . ($svg_images[$page - 2] ?? ''); ?>';
        }
        
        initDragEvents();
        
        window.addEventListener('pageshow', function() {
            hideLoading();
        });
    </script>
</body>
</html>