<?php
/**
 * API: Отримати інформацію про типи завдань та діапазони бюджету
 * GET /api/get_options.php
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../services/AutoBuilder.php';

setCorsHeaders();

try {
    $taskTypes = AutoBuilder::getTaskTypes();
    $budgetRanges = AutoBuilder::getBudgetRanges();
    
    sendSuccess([
        'task_types' => $taskTypes,
        'budget_ranges' => $budgetRanges
    ]);
    
} catch (Exception $e) {
    error_log("API Error (get_options): " . $e->getMessage());
    sendError('Помилка отримання опцій', 500);
}





