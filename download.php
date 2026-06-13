<?php
// download.php - 流式传输文件，隐藏真实存储路径
// 用法示例: download.php?folder=分类/文档文件夹&file=原始文件名.pdf

// 配置允许下载的文件类型（白名单）
$allowed_extensions = ['pdf', 'doc', 'docx'];

// 获取并验证参数
$folder = isset($_GET['folder']) ? trim($_GET['folder']) : '';
$filename = isset($_GET['file']) ? basename(trim($_GET['file'])) : '';

// 防止空参数
if (empty($folder) || empty($filename)) {
    die('无效的下载链接');
}

// 安全检查：防止目录遍历（移除 ../ 等危险字符）
$folder = str_replace(['..', '\\', '//'], '', $folder);
$folder = ltrim($folder, '/');

// 构建文件实际路径（存储目录固定为 documents/）
$base_dir = __DIR__ . '/documents/';
$folder_path = $base_dir . $folder;
$file_path = $folder_path . '/' . $filename;

// 安全检查：确保路径在 documents 内（防止目录遍历）
$real_base = realpath($base_dir);
if ($real_base === false) {
    die('存储目录不存在');
}

$real_file = realpath($file_path);
if ($real_file === false) {
    die('文件不存在或无法访问');
}

// 确保文件路径确实在 documents 目录下
if (strpos($real_file, $real_base) !== 0) {
    die('非法访问');
}

// 检查文件是否存在且可读
if (!is_file($real_file) || !is_readable($real_file)) {
    die('文件不存在或无法读取');
}

// 检查文件扩展名是否允许
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
if (!in_array($ext, $allowed_extensions)) {
    die('不支持下载此类型文件');
}

// 获取文件大小
$file_size = filesize($real_file);

// 设置输出头，强制下载并隐藏原始路径
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . addcslashes($filename, '"') . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . $file_size);

// 清除缓冲区，避免内存溢出
if (ob_get_level()) {
    ob_end_clean();
}
flush();

// 以流式方式读取并输出文件
$chunk_size = 1024 * 1024; // 每次1MB
$handle = fopen($real_file, 'rb');
if ($handle !== false) {
    while (!feof($handle)) {
        $buffer = fread($handle, $chunk_size);
        echo $buffer;
        flush();
    }
    fclose($handle);
    exit;
} else {
    die('无法打开文件');
}