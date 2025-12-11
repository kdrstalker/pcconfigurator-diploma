<?php
/**
 * API: Отримання детальної інформації про збірку
 * 
 * GET /api/get_build_details.php?build_id=1
 * 
 * Requires: Authorization (session)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/helpers.php';

// Headers
setCorsHeaders();

// Перевірка методу
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Метод не дозволений. Використовуйте GET', 405);
}

// Перевірка авторизації
$user = requireAuth();

// Отримати параметри
if (!isset($_GET['build_id'])) {
    sendError('Параметр build_id є обов\'язковим', 400);
}

$buildId = (int)$_GET['build_id'];
$userId = (int)$user['id'];

if ($buildId <= 0) {
    sendError('Невірний ID збірки', 400);
}

try {
    $db = Database::getInstance();
    
    // Отримати збірку
    $stmt = $db->query(
        "SELECT id, user_id, build_name, total_price, total_tdp, created_at 
         FROM saved_builds 
         WHERE id = ?",
        [$buildId]
    );
    $build = $stmt->fetch();
    
    if (!$build) {
        sendError('Збірка не знайдена', 404);
    }
    
    // Перевірити що збірка належить користувачу
    if ((int)$build['user_id'] !== $userId) {
        sendError('Ви не маєте доступу до цієї збірки', 403);
    }
    
    // Отримати компоненти збірки
    $stmt = $db->query(
        "SELECT c.id, c.name, c.price, c.tdp, c.socket, c.ram_type, 
                cat.name as category_name, cat.slug as category_slug
         FROM build_items bi
         INNER JOIN components c ON bi.component_id = c.id
         INNER JOIN categories cat ON c.category_id = cat.id
         WHERE bi.build_id = ?
         ORDER BY cat.sort_order",
        [$buildId]
    );
    $components = $stmt->fetchAll();
    
    // Сформувати відповідь
    $response = [
        'id' => (int)$build['id'],
        'build_name' => $build['build_name'],
        'total_price' => (float)$build['total_price'],
        'total_tdp' => (int)$build['total_tdp'],
        'created_at' => $build['created_at'],
        'components' => []
    ];
    
    foreach ($components as $component) {
        $response['components'][] = [
            'id' => (int)$component['id'],
            'name' => $component['name'],
            'price' => (float)$component['price'],
            'tdp' => $component['tdp'] ? (int)$component['tdp'] : null,
            'socket' => $component['socket'],
            'ram_type' => $component['ram_type'],
            'category_name' => $component['category_name'],
            'category_slug' => $component['category_slug']
        ];
    }
    
    // Відправити відповідь
    sendSuccess($response, 'Дані збірки отримано успішно');
    
} catch (PDOException $e) {
    error_log("Error fetching build details: " . $e->getMessage());
    sendError('Помилка бази даних', 500);
} catch (Exception $e) {
    error_log("Error in get_build_details: " . $e->getMessage());
    sendError('Помилка сервера', 500);
}




