<?php
/**
 * API: Зберегти збірку (потрібна авторизація)
 * POST /api/save_build.php
 * Body: {
 *   "build_name": "Моя збірка",
 *   "component_ids": [1, 5, 8, 12, 15, 18, 20]
 * }
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Component.php';

setCorsHeaders();

try {
    // Перевірка авторизації
    $user = requireAuth();
    
    // Отримати вхідні дані
    $input = getJsonInput();
    
    if (!$input) {
        sendError('Невалідний JSON', 400);
    }
    
    validateRequired($input, ['build_name', 'component_ids']);
    
    $buildName = trim($input['build_name']);
    $componentIds = $input['component_ids'];
    
    // Валідація
    if (empty($buildName)) {
        sendError('Назва збірки не може бути пустою', 400);
    }
    
    if (strlen($buildName) > 255) {
        sendError('Назва збірки занадто довга (макс. 255 символів)', 400);
    }
    
    if (!is_array($componentIds) || empty($componentIds)) {
        sendError('Список компонентів має бути непустим масивом', 400);
    }
    
    // Конвертувати в integer
    $componentIds = array_map('intval', $componentIds);
    $componentIds = array_filter($componentIds, fn($id) => $id > 0);
    
    if (empty($componentIds)) {
        sendError('Не знайдено валідних компонентів', 400);
    }
    
    $db = Database::getInstance();
    $component = new Component($db);
    
    // Перевірити що всі компоненти існують
    $components = $component->getComponentsByIds($componentIds);
    if (count($components) !== count($componentIds)) {
        sendError('Деякі компоненти не знайдено в БД', 400);
    }
    
    // Валідація сумісності
    $validation = $component->validateBuild($componentIds);
    if (!$validation['compatible']) {
        sendError(
            'Збірка несумісна',
            400,
            ['compatibility_errors' => $validation['errors']]
        );
    }
    
    // Підрахунок статистики
    $totalPrice = $component->calculateTotalPrice($componentIds);
    $totalTDP = $component->calculateTotalTDP($componentIds);
    
    // Збереження збірки
    $db->getConnection()->beginTransaction();
    
    try {
        // Вставка в saved_builds
        $sql = "INSERT INTO saved_builds (user_id, build_name, total_price, total_tdp) 
                VALUES (?, ?, ?, ?)";
        $db->query($sql, [$user['id'], $buildName, $totalPrice, $totalTDP]);
        
        $buildId = (int) $db->getConnection()->lastInsertId();
        
        // Вставка компонентів в build_items
        $sql = "INSERT INTO build_items (build_id, component_id, quantity) VALUES (?, ?, 1)";
        foreach ($componentIds as $componentId) {
            $db->query($sql, [$buildId, $componentId]);
        }
        
        $db->getConnection()->commit();
        
        sendSuccess([
            'build_id' => $buildId,
            'build_name' => $buildName,
            'total_price' => $totalPrice,
            'total_tdp' => $totalTDP,
            'components_count' => count($componentIds)
        ], 'Збірку успішно збережено');
        
    } catch (Exception $e) {
        $db->getConnection()->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("API Error (save_build): " . $e->getMessage());
    sendError('Помилка збереження збірки', 500);
}





