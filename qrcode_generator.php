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
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { text-align: center; margin-bottom: 20px; color: #333; }
        .form-box { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 500; color: #555; }
        textarea { width: 100%; height: 150px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; resize: vertical; }
        select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .btn { padding: 10px 20px; background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .btn:hover { background: #0056b3; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        .results { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; }
        .qr-item { background: #fff; padding: 15px; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .qr-item img { max-width: 100%; height: auto; }
        .qr-item .data { margin-top: 10px; font-size: 12px; color: #666; word-break: break-all; }
        .history-section { margin-top: 30px; }
        .history-item { background: #fff; padding: 15px; border-radius: 8px; margin-bottom: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .history-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .history-title { font-weight: 500; color: #333; }
        .history-meta { font-size: 12px; color: #999; }
        .history-items { display: flex; flex-wrap: wrap; gap: 10px; }
        .history-qr { display: flex; align-items: center; gap: 8px; background: #f8f9fa; padding: 8px; border-radius: 4px; }
        .history-qr img { width: 60px; height: 60px; }
        .history-qr span { font-size: 12px; color: #666; max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .btn-del { padding: 5px 10px; background: #dc3545; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .btn-del:hover { background: #c82333; }
        .history-results { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>二维码批量生成器</h1>
        
        <div class="form-box">
            <?php if ($message): ?>
            <div class="message <?php echo strpos($message, '成功') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>二维码内容（每行一个）</label>
                    <textarea name="content" placeholder="请输入二维码内容，每行一个&#10;例如：&#10;Example1&#10;Example2&#10;Example3"><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                </div>
                <div class="form-group">
                    <label>尺寸</label>
                    <select name="size">
                        <option value="128x128" <?php echo (isset($_POST['size']) && $_POST['size'] === '128x128') ? 'selected' : ''; ?>>128x128</option>
                        <option value="256x256" <?php echo (isset($_POST['size']) && $_POST['size'] === '256x256') ? 'selected' : ''; ?>>256x256</option>
                        <option value="512x512" <?php echo (isset($_POST['size']) && $_POST['size'] === '512x512') ? 'selected' : ''; ?>>512x512</option>
                    </select>
                </div>
                <button type="submit" name="generate" class="btn">生成二维码</button>
            </form>
        </div>
        
        <?php if (!empty($results)): ?>
        <div class="form-box">
            <h3>生成结果（<?php echo count($results); ?>个）</h3>
            <div class="results">
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
            <p style="color: #999; text-align: center; padding: 20px;">暂无历史记录</p>
            <?php else: ?>
            <?php foreach ($history as $index => $record): ?>
            <div class="history-item">
                <div class="history-header">
                    <div>
                        <span class="history-title"><?php echo htmlspecialchars($record['time']); ?></span>
                        <span class="history-meta">（<?php echo $record['count']; ?>个，尺寸：<?php echo htmlspecialchars($record['size']); ?>）</span>
                    </div>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="index" value="<?php echo $index; ?>">
                        <button type="submit" class="btn-del">删除</button>
                    </form>
                </div>
                <div class="history-results">
                    <?php foreach ($record['items'] as $item): ?>
                    <div class="history-qr">
                        <img src="<?php echo htmlspecialchars($item['qr_url']); ?>" alt="QR">
                        <span><?php echo htmlspecialchars($item['data']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>