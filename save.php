<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'error'=>'Method not allowed']); exit(); }

$i = json_decode(file_get_contents('php://input'), true);
if (!$i) { echo json_encode(['success'=>false,'error'=>'Invalid input']); exit(); }

$f = 'save.json';
if ($i['action'] === 'save') {
    $d = $i['data'] ?? null;
    if ($d) {
        $s = ['data' => $d, 'time' => time(), 'date' => date('Y-m-d H:i:s')];
        file_put_contents($f, json_encode($s, JSON_PRETTY_PRINT)) 
            ? echo json_encode(['success'=>true]) 
            : echo json_encode(['success'=>false,'error'=>'Write failed']);
    } else echo json_encode(['success'=>false,'error'=>'No data']);
} elseif ($i['action'] === 'load') {
    if (file_exists($f)) {
        $d = json_decode(file_get_contents($f), true);
        echo json_encode(['success'=>true, 'data'=>($d['data'] ?? null)]);
    } else echo json_encode(['success'=>false,'error'=>'No save']);
} else echo json_encode(['success'=>false,'error'=>'Invalid action']);
?>