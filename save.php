<?php
// ====== save.php ======
// Файл для сохранения и загрузки прогресса игры

// ====== КОНФИГУРАЦИЯ ======
define('SAVE_FILE', 'game_save.json');
define('BACKUP_DIR', 'backups/');
define('MAX_BACKUPS', 10);
define('SAVE_VERSION', '1.0');

// ====== КЛАСС ДЛЯ УПРАВЛЕНИЯ СОХРАНЕНИЯМИ ======
class GameSaveManager {
    private $saveFile;
    private $backupDir;
    private $maxBackups;

    public function __construct($saveFile = SAVE_FILE, $backupDir = BACKUP_DIR, $maxBackups = MAX_BACKUPS) {
        $this->saveFile = $saveFile;
        $this->backupDir = $backupDir;
        $this->maxBackups = $maxBackups;
        
        // Создаем директорию для бэкапов если её нет
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    // Сохранение данных
    public function save($data) {
        // Валидация данных
        if (!$this->validateData($data)) {
            return ['success' => false, 'error' => 'Invalid data format'];
        }

        // Создаем структуру сохранения
        $saveData = [
            'version' => SAVE_VERSION,
            'timestamp' => time(),
            'datetime' => date('Y-m-d H:i:s'),
            'data' => $data,
            'checksum' => $this->calculateChecksum($data)
        ];

        // Сохраняем в файл
        $json = json_encode($saveData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (file_put_contents($this->saveFile, $json, LOCK_EX) === false) {
            return ['success' => false, 'error' => 'Failed to write save file'];
        }

        // Создаем бэкап
        $this->createBackup($saveData);

        return ['success' => true, 'message' => 'Saved successfully'];
    }

    // Загрузка данных
    public function load() {
        if (!file_exists($this->saveFile)) {
            return ['success' => false, 'error' => 'No save found'];
        }

        $content = file_get_contents($this->saveFile);
        if ($content === false) {
            return ['success' => false, 'error' => 'Failed to read save file'];
        }

        $saveData = json_decode($content, true);
        if (!$saveData) {
            return ['success' => false, 'error' => 'Invalid JSON format'];
        }

        // Проверка версии
        if (!isset($saveData['version']) || $saveData['version'] !== SAVE_VERSION) {
            return ['success' => false, 'error' => 'Incompatible save version'];
        }

        // Проверка целостности
        if (!$this->verifyChecksum($saveData)) {
            return ['success' => false, 'error' => 'Save file corrupted (checksum mismatch)'];
        }

        if (!isset($saveData['data'])) {
            return ['success' => false, 'error' => 'Invalid save format'];
        }

        return ['success' => true, 'data' => $saveData['data']];
    }

    // Создание бэкапа
    private function createBackup($saveData) {
        $timestamp = time();
        $backupFile = $this->backupDir . 'save_' . $timestamp . '.json';
        
        // Сохраняем бэкап
        file_put_contents($backupFile, json_encode($saveData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        
        // Очищаем старые бэкапы
        $this->cleanOldBackups();
    }

    // Очистка старых бэкапов
    private function cleanOldBackups() {
        $files = glob($this->backupDir . 'save_*.json');
        if (count($files) > $this->maxBackups) {
            // Сортируем по времени создания
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Удаляем самые старые
            $toDelete = array_slice($files, 0, count($files) - $this->maxBackups);
            foreach ($toDelete as $file) {
                unlink($file);
            }
        }
    }

    // Валидация данных
    private function validateData($data) {
        $required = ['resources', 'clickPower', 'autoPower', 'clickCost', 'autoCost', 'totalClicks'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return false;
            }
            if (!is_numeric($data[$field]) || $data[$field] < 0) {
                return false;
            }
        }
        return true;
    }

    // Вычисление контрольной суммы
    private function calculateChecksum($data) {
        return md5(json_encode($data) . SAVE_VERSION);
    }

    // Проверка контрольной суммы
    private function verifyChecksum($saveData) {
        if (!isset($saveData['checksum']) || !isset($saveData['data'])) {
            return false;
        }
        $expected = $this->calculateChecksum($saveData['data']);
        return $saveData['checksum'] === $expected;
    }

    // Получение информации о сохранении
    public function getInfo() {
        if (!file_exists($this->saveFile)) {
            return ['success' => false, 'error' => 'No save found'];
        }

        $content = file_get_contents($this->saveFile);
        $saveData = json_decode($content, true);
        
        if (!$saveData) {
            return ['success' => false, 'error' => 'Invalid save format'];
        }

        return [
            'success' => true,
            'timestamp' => $saveData['timestamp'] ?? null,
            'datetime' => $saveData['datetime'] ?? null,
            'version' => $saveData['version'] ?? null,
            'size' => filesize($this->saveFile),
            'checksum' => $saveData['checksum'] ?? null
        ];
    }

    // Восстановление из бэкапа
    public function restoreFromBackup($timestamp = null) {
        if ($timestamp === null) {
            // Берем последний бэкап
            $files = glob($this->backupDir . 'save_*.json');
            if (empty($files)) {
                return ['success' => false, 'error' => 'No backups found'];
            }
            // Сортируем по времени создания (новый сверху)
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            $backupFile = $files[0];
        } else {
            $backupFile = $this->backupDir . 'save_' . $timestamp . '.json';
            if (!file_exists($backupFile)) {
                return ['success' => false, 'error' => 'Backup not found'];
            }
        }

        $content = file_get_contents($backupFile);
        $saveData = json_decode($content, true);
        
        if (!$saveData || !isset($saveData['data'])) {
            return ['success' => false, 'error' => 'Invalid backup format'];
        }

        // Восстанавливаем
        if (file_put_contents($this->saveFile, $content, LOCK_EX) === false) {
            return ['success' => false, 'error' => 'Failed to restore backup'];
        }

        return ['success' => true, 'message' => 'Backup restored successfully'];
    }
}

// ====== ОБРАБОТЧИК ЗАПРОСОВ ======

// Устанавливаем заголовки для CORS и JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Обработка preflight запросов
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Только POST запросы
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Получаем входные данные
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit();
}

$action = $input['action'] ?? '';
$manager = new GameSaveManager();

// Обработка действий
switch ($action) {
    case 'save':
        $data = $input['data'] ?? null;
        if ($data === null) {
            echo json_encode(['success' => false, 'error' => 'No data provided']);
            break;
        }
        $result = $manager->save($data);
        echo json_encode($result);
        break;

    case 'load':
        $result = $manager->load();
        echo json_encode($result);
        break;

    case 'info':
        $result = $manager->getInfo();
        echo json_encode($result);
        break;

    case 'restore':
        $timestamp = $input['timestamp'] ?? null;
        $result = $manager->restoreFromBackup($timestamp);
        echo json_encode($result);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}
?>
