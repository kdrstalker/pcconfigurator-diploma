<?php
/**
 * API: Валідація збірки
 * POST /api/validate_build.php
 * Body: { "component_ids": [1, 5, 8, 12, 15, 18, 20] }
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Component.php';

setCorsHeaders();

try {
    // Отримати вхідні дані
    $input = getJsonInput();
    
    if (!$input) {
        sendError('Невалідний JSON', 400);
    }
    
    validateRequired($input, ['component_ids']);
    
    $componentIds = $input['component_ids'];
    
    // Валідація
    if (!is_array($componentIds)) {
        sendError('Поле component_ids має бути масивом', 400);
    }
    
    if (empty($componentIds)) {
        sendError('Список компонентів не може бути пустим', 400);
    }
    
    // Конвертувати в integer
    $componentIds = array_map('intval', $componentIds);
    $componentIds = array_filter($componentIds, fn($id) => $id > 0);
    
    // Валідація збірки
    $db = Database::getInstance();
    $component = new Component($db);
    
    $validation = $component->validateBuild($componentIds);
    
    // Додаткова статистика
    $totalPrice = $component->calculateTotalPrice($componentIds);
    $totalTDP = $component->calculateTotalTDP($componentIds);
    $components = $component->getComponentsByIds($componentIds);
    
    $response = [
        'validation' => $validation,
        'components' => $components,
        'stats' => [
            'total_components' => count($components),
            'total_price' => $totalPrice,
            'total_tdp' => $totalTDP
        ]
    ];
    
    sendSuccess($response);
    
} catch (Exception $e) {
    error_log("API Error (validate_build): " . $e->getMessage());
    sendError('Помилка валідації збірки', 500);
}





