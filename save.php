<?php
// ====== save.php ======
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit();
}

$action = $input['action'] ?? '';
$saveFile = 'game_save.json';

if ($action === 'save') {
    $data = $input['data'] ?? null;
    if ($data) {
        $saveData = [
            'data' => $data,
            'timestamp' => time(),
            'datetime' => date('Y-m-d H:i:s')
        ];
        if (file_put_contents($saveFile, json_encode($saveData, JSON_PRETTY_PRINT))) {
            echo json_encode(['success' => true, 'message' => 'Saved successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to write file']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No data provided']);
    }
} elseif ($action === 'load') {
    if (file_exists($saveFile)) {
        $content = file_get_contents($saveFile);
        $saveData = json_decode($content, true);
        if ($saveData && isset($saveData['data'])) {
            echo json_encode(['success' => true, 'data' => $saveData['data']]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid save format']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No save found']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>
