<?php
/**
 * API: Отримати сумісні компоненти (ручний режим)
 * POST /api/get_compatible.php
 * Body: { "category": "motherboard", "current_build": [1, 5] }
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
    
    validateRequired($input, ['category']);
    
    $categorySlug = $input['category'];
    $currentBuild = $input['current_build'] ?? [];
    
    // Валідація current_build
    if (!is_array($currentBuild)) {
        sendError('Поле current_build має бути масивом', 400);
    }
    
    // Конвертувати в масив integer
    $currentBuildIds = array_map('intval', $currentBuild);
    $currentBuildIds = array_filter($currentBuildIds, fn($id) => $id > 0);
    
    // Отримати сумісні компоненти
    $db = Database::getInstance();
    $component = new Component($db);
    
    $compatibleComponents = $component->getCompatibleComponents($categorySlug, $currentBuildIds);
    
    // Додаткова інформація
    $response = [
        'category' => $categorySlug,
        'total_found' => count($compatibleComponents),
        'components' => $compatibleComponents
    ];
    
    // Якщо є обрані компоненти - додати інфо про них
    if (!empty($currentBuildIds)) {
        $selectedComponents = $component->getComponentsByIds($currentBuildIds);
        $response['selected_components'] = $selectedComponents;
    }
    
    sendSuccess($response);
    
} catch (Exception $e) {
    error_log("API Error (get_compatible): " . $e->getMessage());
    sendError('Помилка фільтрації сумісних компонентів', 500);
}





