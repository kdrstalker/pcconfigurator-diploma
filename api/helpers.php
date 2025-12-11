<?php
/**
 * API Helpers
 * Допоміжні функції для роботи з API
 */

declare(strict_types=1);

/**
 * Налаштування CORS headers
 */
function setCorsHeaders(): void
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/json; charset=utf-8');
    
    // Обробка preflight запиту
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

/**
 * Отримати JSON тіло запиту
 * 
 * @return array|null
 */
function getJsonInput(): ?array
{
    $input = file_get_contents('php://input');
    if (empty($input)) {
        return null;
    }
    
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    
    return $data;
}

/**
 * Відправити JSON response
 * 
 * @param mixed $data Дані для відправки
 * @param int $statusCode HTTP статус код
 */
function sendJson($data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Відправити success response
 * 
 * @param mixed $data Дані
 * @param string|null $message Повідомлення
 */
function sendSuccess($data, ?string $message = null): void
{
    $response = [
        'success' => true,
        'data' => $data
    ];
    
    if ($message) {
        $response['message'] = $message;
    }
    
    sendJson($response, 200);
}

/**
 * Відправити error response
 * 
 * @param string $message Повідомлення про помилку
 * @param int $statusCode HTTP статус код
 * @param array $details Додаткові деталі
 */
function sendError(string $message, int $statusCode = 400, array $details = []): void
{
    $response = [
        'success' => false,
        'error' => $message
    ];
    
    if (!empty($details)) {
        $response['details'] = $details;
    }
    
    sendJson($response, $statusCode);
}

/**
 * Валідація required полів
 * 
 * @param array $data Вхідні дані
 * @param array $required Обов'язкові поля
 * @return bool true якщо валідація пройшла
 */
function validateRequired(array $data, array $required): bool
{
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            sendError("Поле '{$field}' є обов'язковим", 400);
            return false;
        }
    }
    return true;
}

/**
 * Перевірка авторизації (для захищених endpoints)
 * 
 * @return array Дані користувача
 */
function requireAuth(): array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        sendError('Необхідна авторизація', 401);
        exit;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'login' => $_SESSION['login'] ?? '',
        'role' => $_SESSION['role'] ?? 'user'
    ];
}

/**
 * Логування API запитів (опціонально)
 * 
 * @param string $endpoint Назва endpoint
 * @param array $data Дані запиту
 */
function logApiRequest(string $endpoint, array $data = []): void
{
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'endpoint' => $endpoint,
        'method' => $_SERVER['REQUEST_METHOD'],
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_id' => $_SESSION['user_id'] ?? null,
        'data' => $data
    ];
    
    error_log("API Request: " . json_encode($logEntry, JSON_UNESCAPED_UNICODE));
}





