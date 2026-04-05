<?php
session_start();

$apiBase = 'https://api.2dcode.biz/v1/create-qr-code';
$historyFile = __DIR__ . '/history.json';
$message = '';
$results = [];

function loadHistory() {
    global $historyFile;
    if (file_exists($historyFile)) {
        $data = file_get_contents($historyFile);
        return json_decode($data, true) ?: [];
    }
    return [];
}

function saveHistory($record) {
    global $historyFile;
    $history = loadHistory();
    array_unshift($history, $record);
    $history = array_slice($history, 0, 100);
    file_put_contents($historyFile, json_encode($history, JSON_UNESCAPED_UNICODE));
}

function deleteHistoryItem($index) {
    global $historyFile;
    $history = loadHistory();
    if (isset($history[$index])) {
        unset($history[$index]);
        $history = array_values($history);
        file_put_contents($historyFile, json_encode($history, JSON_UNESCAPED_UNICODE));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $index = intval($_POST['index']);
        deleteHistoryItem($index);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if (isset($_POST['generate'])) {
        $content = trim($_POST['content']);
        $size = $_POST['size'] ?? '128x128';
        
        if (empty($content)) {
            $message = '请输入二维码内容';
        } else {
            $lines = array_filter(array_map('trim', explode("\n", $content)));
            
            if (empty($lines)) {
                $message = '请输入有效的二维码内容';
            } else {
                foreach ($lines as $index => $data) {
                    if (!empty($data)) {
                        $url = $apiBase . '?data=' . urlencode($data) . '&size=' . $size;
                        $results[] = [
                            'data' => $data,
                            'size' => $size,
                            'qr_url' => $url
                        ];
                    }
                }
                
                $record = [
                    'time' => date('Y-m-d H:i:s'),
                    'count' => count($results),
                    'size' => $size,
                    'items' => $results
                ];
                saveHistory($record);
                $message = '成功生成 ' . count($results) . ' 个二维码';
            }
        }
    }
}

$history = loadHistory();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>二维码批量生成器</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap');
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --success: #10b981;
            --danger: #ef4444;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Noto Sans SC', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #fff;
            font-size: 2.5rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            letter-spacing: 2px;
        }
        
        .header p {
            color: rgba(255,255,255,0.8);
            margin-top: 10px;
            font-size: 1rem;
        }
        
        .card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 30px;
            box-shadow: var(--shadow-lg);
            margin-bottom: 24px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-title::before {
            content: '';
            width: 4px;
            height: 24px;
            background: linear-gradient(to bottom, var(--primary), var(--primary-light));
            border-radius: 2px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text);
        }
        
        textarea {
            width: 100%;
            height: 180px;
            padding: 16px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 15px;
            resize: vertical;
            transition: border-color 0.3s, box-shadow 0.3s;
            font-family: inherit;
        }
        
        textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }
        
        textarea::placeholder {
            color: var(--text-light);
        }
        
        select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 15px;
            background: #fff;
            cursor: pointer;
            transition: border-color 0.3s;
            font-family: inherit;
        }
        
        select:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .btn-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.5);
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: var(--text);
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
            color: #fff;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
        }
        
        .message {
            padding: 16px 20px;
            margin-bottom: 20px;
            border-radius: 12px;
            font-weight: 500;
        }
        
        .message.success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .message.error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
            margin-top: 24px;
        }
        
        .qr-item {
            background: linear-gradient(145deg, #fff 0%, #f8fafc 100%);
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            box-shadow: var(--shadow);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid var(--border);
        }
        
        .qr-item:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .qr-item img {
            width: 120px;
            height: 120px;
            border-radius: 8px;
            background: #fff;
            padding: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .qr-item .data {
            margin-top: 12px;
            font-size: 13px;
            color: var(--text-light);
            word-break: break-all;
            max-height: 40px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .history-section {
            margin-top: 30px;
        }
        
        .history-section h2 {
            color: #fff;
            font-size: 1.5rem;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .history-item {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: var(--shadow);
        }
        
        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }
        
        .history-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .history-title {
            font-weight: 600;
            color: var(--text);
        }
        
        .history-meta {
            font-size: 13px;
            color: var(--text-light);
            background: #f1f5f9;
            padding: 4px 12px;
            border-radius: 20px;
        }
        
        .history-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
            border-radius: 8px;
        }
        
        .history-results {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px;
        }
        
        .history-qr {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8fafc;
            padding: 12px;
            border-radius: 12px;
            border: 1px solid var(--border);
        }
        
        .history-qr img {
            width: 50px;
            height: 50px;
            border-radius: 6px;
            background: #fff;
            padding: 4px;
        }
        
        .history-qr span {
            font-size: 12px;
            color: var(--text-light);
            max-width: 80px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: rgba(255,255,255,0.7);
        }
        
        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        @media print {
            body {
                background: #fff;
                padding: 20px;
            }
            .header h1, .header p, .history-section h2 {
                color: #000;
                text-shadow: none;
            }
            .btn, .history-actions, form {
                display: none !important;
            }
            .card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            .history-item {
                break-inside: avoid;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding: 16px;
            }
            .header h1 {
                font-size: 1.8rem;
            }
            .card {
                padding: 20px;
            }
            .results-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>二维码批量生成器</h1>
            <p>批量生成专属二维码，支持 PDF 导出</p>
        </div>
        
        <div class="card">
            <?php if ($message): ?>
            <div class="message <?php echo strpos($message, '成功') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>二维码内容（每行一个）</label>
                    <textarea name="content" placeholder="请输入二维码内容，每行一个

例如：
https://example.com
订单号：123456
名称：张三"><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                </div>
                <div class="form-group">
                    <label>尺寸选择</label>
                    <select name="size">
                        <option value="128x128" <?php echo (isset($_POST['size']) && $_POST['size'] === '128x128') ? 'selected' : ''; ?>>128 × 128 像素</option>
                        <option value="256x256" <?php echo (isset($_POST['size']) && $_POST['size'] === '256x256') ? 'selected' : ''; ?>>256 × 256 像素</option>
                        <option value="512x512" <?php echo (isset($_POST['size']) && $_POST['size'] === '512x512') ? 'selected' : ''; ?>>512 × 512 像素</option>
                    </select>
                </div>
                <div class="btn-group">
                    <button type="submit" name="generate" class="btn btn-primary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        生成二维码
                    </button>
                </div>
            </form>
        </div>
        
        <?php if (!empty($results)): ?>
        <div class="card">
            <div class="card-title">生成结果（<?php echo count($results); ?> 个）</div>
            <div class="btn-group" style="margin-bottom: 20px;">
                <button type="button" class="btn btn-secondary" onclick="exportCurrentResults()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    导出 PDF
                </button>
            </div>
            <div class="results-grid">
                <?php foreach ($results as $item): ?>
                <div class="qr-item">
                    <img src="<?php echo htmlspecialchars($item['qr_url']); ?>" alt="QR Code">
                    <div class="data"><?php echo htmlspecialchars($item['data']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="history-section">
            <h2>历史记录</h2>
            <?php if (empty($history)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                <p>暂无历史记录</p>
            </div>
            <?php else: ?>
            <?php foreach ($history as $index => $record): ?>
            <div class="history-item">
                <div class="history-header">
                    <div class="history-info">
                        <span class="history-title"><?php echo htmlspecialchars($record['time']); ?></span>
                        <span class="history-meta"><?php echo $record['count']; ?> 个 · <?php echo htmlspecialchars($record['size']); ?></span>
                    </div>
                    <div class="history-actions">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="printHistory(<?php echo $index; ?>)">导出</button>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="index" value="<?php echo $index; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">删除</button>
                        </form>
                    </div>
                </div>
                <div class="history-results" id="history-<?php echo $index; ?>">
                    <?php foreach ($record['items'] as $item): ?>
                    <div class="history-qr">
                        <img src="<?php echo htmlspecialchars($item['qr_url']); ?>" alt="QR">
                        <span style="text-align: center;" ><?php echo htmlspecialchars($item['data']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function printHistory(index) {
            const container = document.getElementById('history-' + index);
            const items = container.querySelectorAll('.history-qr');
            let html = '';
            items.forEach(item => {
                const img = item.querySelector('img').src;
                const text = item.querySelector('span').textContent;
                html += `<div class="item"><img src="${img}"><p>${text}</p></div>`;
            });
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>二维码导出</title>
                    <style>
                        @page { size: A4; margin: 15mm; }
                        * { box-sizing: border-box; margin: 0; padding: 0; }
                        body { font-family: 'Microsoft YaHei', 'Segoe UI', sans-serif; padding: 20px; background: #fff; }
                        .header { text-align: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #6366f1; }
                        .header h1 { font-size: 24px; color: #333; margin-bottom: 5px; }
                        .header p { font-size: 14px; color: #666; }
                        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }
                        .item { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 12px; text-align: center; transition: box-shadow 0.2s; }
                        .item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
                        .item img { width: 90px; height: 90px; background: #fff; padding: 6px; border: 1px solid #f0f0f0; border-radius: 4px; }
                        .item p { margin-top: 8px; font-size: 11px; color: #444; word-break: break-all; line-height: 1.4; max-height: 32px; overflow: hidden; }
                        .footer { text-align: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; font-size: 12px; color: #999; }
                        @media print {
                            body { padding: 0; }
                            .grid { grid-template-columns: repeat(4, 1fr); gap: 15px; }
                            .item { break-inside: avoid; border-color: #ccc; }
                            @page { margin: 10mm; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>二维码批量导出</h1>
                        <p>导出时间：${new Date().toLocaleString('zh-CN')}</p>
                    </div>
                    <div class="grid">${html}</div>
                    <div class="footer">二维码批量生成器</div>
                </body>
                </html>
            `);
            printWindow.document.close();
            setTimeout(() => printWindow.print(), 300);
        }
        
        function exportCurrentResults() {
            const container = document.querySelector('.results-grid');
            if (!container) return;
            
            const items = container.querySelectorAll('.qr-item');
            let html = '';
            items.forEach(item => {
                const img = item.querySelector('img').src;
                const text = item.querySelector('.data').textContent;
                html += `<div class="item"><img src="${img}"><p>${text}</p></div>`;
            });
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>二维码导出</title>
                    <style>
                        @page { size: A4; margin: 15mm; }
                        * { box-sizing: border-box; margin: 0; padding: 0; }
                        body { font-family: 'Microsoft YaHei', 'Segoe UI', sans-serif; padding: 20px; background: #fff; }
                        .header { text-align: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #6366f1; }
                        .header h1 { font-size: 24px; color: #333; margin-bottom: 5px; }
                        .header p { font-size: 14px; color: #666; }
                        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }
                        .item { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 12px; text-align: center; transition: box-shadow 0.2s; }
                        .item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
                        .item img { width: 90px; height: 90px; background: #fff; padding: 6px; border: 1px solid #f0f0f0; border-radius: 4px; }
                        .item p { margin-top: 8px; font-size: 11px; color: #444; word-break: break-all; line-height: 1.4; max-height: 32px; overflow: hidden; }
                        .footer { text-align: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; font-size: 12px; color: #999; }
                        @media print {
                            body { padding: 0; }
                            .grid { grid-template-columns: repeat(4, 1fr); gap: 15px; }
                            .item { break-inside: avoid; border-color: #ccc; }
                            @page { margin: 10mm; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>二维码批量导出</h1>
                        <p>导出时间：${new Date().toLocaleString('zh-CN')}</p>
                    </div>
                    <div class="grid">${html}</div>
                    <div class="footer">二维码批量生成器</div>
                </body>
                </html>
            `);
            printWindow.document.close();
            setTimeout(() => printWindow.print(), 300);
        }
    </script>
</body>
</html>