<?php
/**
 * Model: Component
 * Робота з компонентами ПК та перевірка сумісності
 */

declare(strict_types=1);

class Component
{
    private Database $db;
    
    public function __construct(Database $db)
    {
        $this->db = $db;
    }
    
    /**
     * Отримати всі компоненти за категорією
     * 
     * @param string $categorySlug Slug категорії (cpu, motherboard, ram, etc.)
     * @return array Масив компонентів
     */
    public function getByCategory(string $categorySlug): array
    {
        try {
            $sql = "SELECT c.* 
                    FROM components c
                    INNER JOIN categories cat ON c.category_id = cat.id
                    WHERE cat.slug = ?
                    ORDER BY c.price ASC";
            
            $stmt = $this->db->query($sql, [$categorySlug]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Error fetching components by category: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Отримати компонент за ID
     * 
     * @param int $id ID компонента
     * @return array|null Дані компонента або null
     */
    public function getById(int $id): ?array
    {
        try {
            $sql = "SELECT c.*, cat.slug as category_slug
                    FROM components c
                    INNER JOIN categories cat ON c.category_id = cat.id
                    WHERE c.id = ?
                    LIMIT 1";
            
            $stmt = $this->db->query($sql, [$id]);
            $result = $stmt->fetch();
            
            return $result ?: null;
            
        } catch (PDOException $e) {
            error_log("Error fetching component by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Отримати сумісні компоненти на основі вже обраних
     * 
     * @param string $categorySlug Категорія, для якої шукаємо сумісні компоненти
     * @param array $currentBuildIds Масив ID вже обраних компонентів
     * @return array Масив сумісних компонентів
     */
    public function getCompatibleComponents(string $categorySlug, array $currentBuildIds = []): array
    {
        // Якщо нічого не обрано - повернути всі компоненти категорії
        if (empty($currentBuildIds)) {
            return $this->getByCategory($categorySlug);
        }
        
        // Отримати дані про вже обрані компоненти
        $selectedComponents = $this->getComponentsByIds($currentBuildIds);
        
        // Побудувати карту обраних компонентів за категоріями
        $selectedByCategory = [];
        foreach ($selectedComponents as $comp) {
            $selectedByCategory[$comp['category_slug']] = $comp;
        }
        
        // Отримати всі компоненти потрібної категорії
        $allComponents = $this->getByCategory($categorySlug);
        
        // Фільтрувати за сумісністю
        $compatibleComponents = [];
        
        foreach ($allComponents as $component) {
            if ($this->isCompatible($component, $categorySlug, $selectedByCategory)) {
                $compatibleComponents[] = $component;
            }
        }
        
        return $compatibleComponents;
    }
    
    /**
     * Перевірка сумісності компонента з вже обраними
     * 
     * @param array $component Компонент для перевірки
     * @param string $categorySlug Категорія компонента
     * @param array $selectedByCategory Обрані компоненти, згруповані за категоріями
     * @return bool true якщо сумісний
     */
    private function isCompatible(array $component, string $categorySlug, array $selectedByCategory): bool
    {
        switch ($categorySlug) {
            case 'cpu':
                // CPU має бути сумісний з Motherboard по socket
                if (isset($selectedByCategory['motherboard'])) {
                    $motherboard = $selectedByCategory['motherboard'];
                    if ($motherboard['socket'] && $component['socket']) {
                        return $motherboard['socket'] === $component['socket'];
                    }
                }
                break;
                
            case 'motherboard':
                // Motherboard має бути сумісна з CPU по socket
                if (isset($selectedByCategory['cpu'])) {
                    $cpu = $selectedByCategory['cpu'];
                    if ($cpu['socket'] && $component['socket']) {
                        if ($cpu['socket'] !== $component['socket']) {
                            return false;
                        }
                    }
                }
                
                // Motherboard має бути сумісна з RAM по ram_type
                if (isset($selectedByCategory['ram'])) {
                    $ram = $selectedByCategory['ram'];
                    if ($ram['ram_type'] && $component['ram_type']) {
                        if ($ram['ram_type'] !== $component['ram_type']) {
                            return false;
                        }
                    }
                }
                break;
                
            case 'ram':
                // RAM має бути сумісна з Motherboard по ram_type
                if (isset($selectedByCategory['motherboard'])) {
                    $motherboard = $selectedByCategory['motherboard'];
                    if ($motherboard['ram_type'] && $component['ram_type']) {
                        return $motherboard['ram_type'] === $component['ram_type'];
                    }
                }
                break;
                
            case 'psu':
                // PSU має мати достатню потужність для CPU + GPU + запас
                $totalTDP = 0;
                
                if (isset($selectedByCategory['cpu']) && $selectedByCategory['cpu']['tdp']) {
                    $totalTDP += (int)$selectedByCategory['cpu']['tdp'];
                }
                
                if (isset($selectedByCategory['gpu']) && $selectedByCategory['gpu']['tdp']) {
                    $totalTDP += (int)$selectedByCategory['gpu']['tdp'];
                }
                
                // Додаємо 100-150W запасу для інших компонентів
                $requiredWattage = $totalTDP + 100;
                
                if ($component['psu_wattage']) {
                    return (int)$component['psu_wattage'] >= $requiredWattage;
                }
                break;
        }
        
        // Для інших категорій (GPU, Case, Storage) - завжди сумісні
        return true;
    }
    
    /**
     * Отримати компоненти за масивом ID
     * 
     * @param array $ids Масив ID компонентів
     * @return array Масив компонентів
     */
    public function getComponentsByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        
        try {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            
            $sql = "SELECT c.*, cat.slug as category_slug
                    FROM components c
                    INNER JOIN categories cat ON c.category_id = cat.id
                    WHERE c.id IN ($placeholders)";
            
            $stmt = $this->db->query($sql, $ids);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Error fetching components by IDs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Перевірити повну сумісність збірки
     * 
     * @param array $componentIds Масив ID компонентів у збірці
     * @return array ['compatible' => bool, 'errors' => array]
     */
    public function validateBuild(array $componentIds): array
    {
        $components = $this->getComponentsByIds($componentIds);
        $errors = [];
        
        // Згрупувати компоненти за категоріями
        $byCategory = [];
        foreach ($components as $comp) {
            $byCategory[$comp['category_slug']] = $comp;
        }
        
        // Перевірка CPU ↔ Motherboard
        if (isset($byCategory['cpu']) && isset($byCategory['motherboard'])) {
            if ($byCategory['cpu']['socket'] !== $byCategory['motherboard']['socket']) {
                $errors[] = "Процесор (Socket: {$byCategory['cpu']['socket']}) несумісний з материнською платою (Socket: {$byCategory['motherboard']['socket']})";
            }
        }
        
        // Перевірка RAM ↔ Motherboard
        if (isset($byCategory['ram']) && isset($byCategory['motherboard'])) {
            if ($byCategory['ram']['ram_type'] !== $byCategory['motherboard']['ram_type']) {
                $errors[] = "Оперативна пам'ять ({$byCategory['ram']['ram_type']}) несумісна з материнською платою ({$byCategory['motherboard']['ram_type']})";
            }
        }
        
        // Перевірка потужності PSU
        if (isset($byCategory['psu'])) {
            $totalTDP = 0;
            
            if (isset($byCategory['cpu']) && $byCategory['cpu']['tdp']) {
                $totalTDP += (int)$byCategory['cpu']['tdp'];
            }
            
            if (isset($byCategory['gpu']) && $byCategory['gpu']['tdp']) {
                $totalTDP += (int)$byCategory['gpu']['tdp'];
            }
            
            $requiredWattage = $totalTDP + 100; // + запас
            $psuWattage = (int)$byCategory['psu']['psu_wattage'];
            
            if ($psuWattage < $requiredWattage) {
                $errors[] = "Блок живлення ({$psuWattage}W) недостатньо потужний. Рекомендовано мінімум {$requiredWattage}W (TDP системи: {$totalTDP}W + запас 100W)";
            }
        }
        
        return [
            'compatible' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Отримати загальну вартість збірки
     * 
     * @param array $componentIds Масив ID компонентів
     * @return float Загальна вартість
     */
    public function calculateTotalPrice(array $componentIds): float
    {
        $components = $this->getComponentsByIds($componentIds);
        $total = 0.0;
        
        foreach ($components as $comp) {
            $total += (float)$comp['price'];
        }
        
        return $total;
    }
    
    /**
     * Отримати загальний TDP збірки
     * 
     * @param array $componentIds Масив ID компонентів
     * @return int Загальний TDP у Ватах
     */
    public function calculateTotalTDP(array $componentIds): int
    {
        $components = $this->getComponentsByIds($componentIds);
        $totalTDP = 0;
        
        foreach ($components as $comp) {
            if ($comp['tdp']) {
                $totalTDP += (int)$comp['tdp'];
            }
        }
        
        return $totalTDP;
    }
}





