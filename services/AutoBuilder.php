<?php
/**
 * Service: AutoBuilder
 * Автоматичний підбір конфігурації ПК за завданнями та бюджетом
 */

declare(strict_types=1);

require_once __DIR__ . '/../models/Component.php';

class AutoBuilder
{
    private Database $db;
    private Component $componentModel;
    
    // Шаблони розподілу бюджету (%)
    private const BUDGET_TEMPLATES = [
        'cyber_sport' => [ // CS2, Dota, Valorant
            'cpu' => 35,
            'motherboard' => 12,
            'ram' => 15,
            'gpu' => 25,
            'psu' => 8,
            'case' => 3,
            'storage' => 2
        ],
        'gaming_aaa' => [ // Cyberpunk, GTA VI, AAA ігри
            'cpu' => 20,
            'motherboard' => 12,
            'ram' => 12,
            'gpu' => 45,
            'psu' => 7,
            'case' => 2,
            'storage' => 2
        ],
        'work_3d' => [ // Blender, 3D моделювання
            'cpu' => 40,
            'motherboard' => 12,
            'ram' => 20,
            'gpu' => 20,
            'psu' => 5,
            'case' => 2,
            'storage' => 1
        ],
        'streaming' => [ // Стрімінг + Геймінг
            'cpu' => 30,
            'motherboard' => 12,
            'ram' => 15,
            'gpu' => 30,
            'psu' => 8,
            'case' => 3,
            'storage' => 2
        ],
        'office' => [ // Офісна робота
            'cpu' => 25,
            'motherboard' => 15,
            'ram' => 20,
            'gpu' => 15,
            'psu' => 10,
            'case' => 10,
            'storage' => 5
        ]
    ];
    
    // Мінімальні вимоги за завданнями
    private const MIN_REQUIREMENTS = [
        'cyber_sport' => [
            'ram_min_gb' => 16,
            'cpu_min_cores' => 6,
            'gpu_tdp_min' => 100
        ],
        'gaming_aaa' => [
            'ram_min_gb' => 16,
            'cpu_min_cores' => 8,
            'gpu_tdp_min' => 150
        ],
        'work_3d' => [
            'ram_min_gb' => 32,
            'cpu_min_cores' => 8,
            'gpu_tdp_min' => 150
        ],
        'streaming' => [
            'ram_min_gb' => 16,
            'cpu_min_cores' => 8,
            'gpu_tdp_min' => 120
        ],
        'office' => [
            'ram_min_gb' => 8,
            'cpu_min_cores' => 4,
            'gpu_tdp_min' => 50
        ]
    ];
    
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->componentModel = new Component($db);
    }
    
    /**
     * Згенерувати конфігурацію ПК автоматично
     * 
     * @param string $taskType Тип завдання (cyber_sport, gaming_aaa, work_3d, etc.)
     * @param int $budget Бюджет в гривнях
     * @return array ['success' => bool, 'build' => array, 'errors' => array, 'stats' => array]
     */
    public function generateBuild(string $taskType, int $budget): array
    {
        // Валідація типу завдання
        if (!isset(self::BUDGET_TEMPLATES[$taskType])) {
            return [
                'success' => false,
                'errors' => ['Невідомий тип завдання. Доступні: ' . implode(', ', array_keys(self::BUDGET_TEMPLATES))]
            ];
        }
        
        // Перевірка мінімального бюджету
        if ($budget < 10000) {
            return [
                'success' => false,
                'errors' => ['Бюджет занадто малий. Мінімум: 10 000 грн']
            ];
        }
        
        // Отримати шаблон розподілу бюджету
        $template = self::BUDGET_TEMPLATES[$taskType];
        $requirements = self::MIN_REQUIREMENTS[$taskType];
        
        // Розрахувати бюджети для кожної категорії
        $budgets = [];
        foreach ($template as $category => $percentage) {
            $budgets[$category] = ($budget * $percentage) / 100;
        }
        
        // Послідовний підбір компонентів
        $build = [];
        $errors = [];
        $totalSpent = 0;
        
        try {
            // 1. Обрати CPU
            $cpu = $this->selectCPU($budgets['cpu'], $requirements['cpu_min_cores']);
            if (!$cpu) {
                throw new Exception("Не вдалося знайти процесор у бюджеті {$budgets['cpu']} грн");
            }
            $build['cpu'] = $cpu;
            $totalSpent += (float)$cpu['price'];
            
            // 2. Обрати Motherboard (сумісну з CPU)
            $motherboard = $this->selectMotherboard($budgets['motherboard'], $cpu['socket']);
            if (!$motherboard) {
                throw new Exception("Не вдалося знайти материнську плату (Socket: {$cpu['socket']}) у бюджеті {$budgets['motherboard']} грн");
            }
            $build['motherboard'] = $motherboard;
            $totalSpent += (float)$motherboard['price'];
            
            // 3. Обрати RAM (сумісну з Motherboard)
            $ram = $this->selectRAM($budgets['ram'], $motherboard['ram_type'], $requirements['ram_min_gb']);
            if (!$ram) {
                throw new Exception("Не вдалося знайти RAM ({$motherboard['ram_type']}) у бюджеті {$budgets['ram']} грн");
            }
            $build['ram'] = $ram;
            $totalSpent += (float)$ram['price'];
            
            // 4. Обрати GPU
            $gpu = $this->selectGPU($budgets['gpu'], $requirements['gpu_tdp_min']);
            if (!$gpu) {
                throw new Exception("Не вдалося знайти відеокарту у бюджеті {$budgets['gpu']} грн");
            }
            $build['gpu'] = $gpu;
            $totalSpent += (float)$gpu['price'];
            
            // 5. Обрати PSU (достатньої потужності)
            $requiredPSU = (int)$cpu['tdp'] + (int)$gpu['tdp'] + 150; // + запас
            $psu = $this->selectPSU($budgets['psu'], $requiredPSU);
            if (!$psu) {
                throw new Exception("Не вдалося знайти блок живлення (мін. {$requiredPSU}W) у бюджеті {$budgets['psu']} грн");
            }
            $build['psu'] = $psu;
            $totalSpent += (float)$psu['price'];
            
            // 6. Обрати Case
            $case = $this->selectCase($budgets['case']);
            if ($case) {
                $build['case'] = $case;
                $totalSpent += (float)$case['price'];
            }
            
            // 7. Обрати Storage
            $storage = $this->selectStorage($budgets['storage']);
            if ($storage) {
                $build['storage'] = $storage;
                $totalSpent += (float)$storage['price'];
            }
            
            // Перевірка повної сумісності
            $componentIds = array_map(fn($comp) => $comp['id'], $build);
            $validation = $this->componentModel->validateBuild($componentIds);
            
            if (!$validation['compatible']) {
                $errors = array_merge($errors, $validation['errors']);
            }
            
            // Статистика
            $stats = [
                'total_price' => $totalSpent,
                'budget_used_percent' => ($totalSpent / $budget) * 100,
                'total_tdp' => $this->componentModel->calculateTotalTDP($componentIds),
                'psu_margin' => (int)$psu['psu_wattage'] - ((int)$cpu['tdp'] + (int)$gpu['tdp']),
                'task_type' => $taskType,
                'budget_template' => $template
            ];
            
            return [
                'success' => true,
                'build' => $build,
                'errors' => $errors,
                'stats' => $stats
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'build' => $build,
                'errors' => [$e->getMessage()],
                'stats' => ['total_spent' => $totalSpent]
            ];
        }
    }
    
    /**
     * Підбір CPU
     */
    private function selectCPU(float $budget, int $minCores): ?array
    {
        $allCPUs = $this->componentModel->getByCategory('cpu');
        
        // Фільтрувати за мінімальними ядрами (якщо є JSON specs)
        $suitableCPUs = array_filter($allCPUs, function($cpu) use ($minCores) {
            if ($cpu['specs_json']) {
                $specs = json_decode($cpu['specs_json'], true);
                if (isset($specs['cores']) && $specs['cores'] < $minCores) {
                    return false;
                }
            }
            return true;
        });
        
        // Знайти найближчий до бюджету (трохи дешевше)
        return $this->findClosestComponent($suitableCPUs, $budget);
    }
    
    /**
     * Підбір Motherboard
     */
    private function selectMotherboard(float $budget, ?string $socket): ?array
    {
        $allMotherboards = $this->componentModel->getByCategory('motherboard');
        
        // Фільтрувати за socket
        $compatible = array_filter($allMotherboards, function($mb) use ($socket) {
            return $mb['socket'] === $socket;
        });
        
        return $this->findClosestComponent($compatible, $budget);
    }
    
    /**
     * Підбір RAM
     */
    private function selectRAM(float $budget, ?string $ramType, int $minGB): ?array
    {
        $allRAM = $this->componentModel->getByCategory('ram');
        
        // Фільтрувати за типом пам'яті та мінімальним об'ємом
        $compatible = array_filter($allRAM, function($ram) use ($ramType, $minGB) {
            if ($ram['ram_type'] !== $ramType) {
                return false;
            }
            
            // Перевірити об'єм (якщо є в JSON)
            if ($ram['specs_json']) {
                $specs = json_decode($ram['specs_json'], true);
                if (isset($specs['capacity'])) {
                    $capacityGB = (int)filter_var($specs['capacity'], FILTER_SANITIZE_NUMBER_INT);
                    if ($capacityGB < $minGB) {
                        return false;
                    }
                }
            }
            
            return true;
        });
        
        return $this->findClosestComponent($compatible, $budget);
    }
    
    /**
     * Підбір GPU
     */
    private function selectGPU(float $budget, int $minTDP): ?array
    {
        $allGPUs = $this->componentModel->getByCategory('gpu');
        
        // Фільтрувати за мінімальним TDP (показник продуктивності)
        $suitable = array_filter($allGPUs, function($gpu) use ($minTDP) {
            return ($gpu['tdp'] ?? 0) >= $minTDP;
        });
        
        return $this->findClosestComponent($suitable, $budget);
    }
    
    /**
     * Підбір PSU
     */
    private function selectPSU(float $budget, int $minWattage): ?array
    {
        $allPSUs = $this->componentModel->getByCategory('psu');
        
        // Фільтрувати за мінімальною потужністю
        $suitable = array_filter($allPSUs, function($psu) use ($minWattage) {
            return ($psu['psu_wattage'] ?? 0) >= $minWattage;
        });
        
        return $this->findClosestComponent($suitable, $budget);
    }
    
    /**
     * Підбір Case
     */
    private function selectCase(float $budget): ?array
    {
        $allCases = $this->componentModel->getByCategory('case');
        return $this->findClosestComponent($allCases, $budget);
    }
    
    /**
     * Підбір Storage
     */
    private function selectStorage(float $budget): ?array
    {
        $allStorage = $this->componentModel->getByCategory('storage');
        return $this->findClosestComponent($allStorage, $budget);
    }
    
    /**
     * Знайти компонент найближчий до бюджету (але не дорожчий)
     * Якщо не знайдено в бюджеті - взяти найдешевший сумісний
     */
    private function findClosestComponent(array $components, float $budget): ?array
    {
        if (empty($components)) {
            return null;
        }
        
        // Сортувати за ціною
        usort($components, fn($a, $b) => (float)$a['price'] <=> (float)$b['price']);
        
        // Спробувати знайти в межах бюджету
        $inBudget = array_filter($components, fn($c) => (float)$c['price'] <= $budget);
        
        if (!empty($inBudget)) {
            // Взяти найдорожчий з тих, що в бюджеті
            return end($inBudget);
        }
        
        // Якщо нічого не підходить - взяти найдешевший
        return $components[0];
    }
    
    /**
     * Отримати список доступних типів завдань
     */
    public static function getTaskTypes(): array
    {
        return [
            'cyber_sport' => [
                'name' => 'Кіберспорт',
                'description' => 'CS2, Dota 2, Valorant - високий FPS',
                'icon' => 'fa-gamepad'
            ],
            'gaming_aaa' => [
                'name' => 'ААА Геймінг',
                'description' => 'Cyberpunk, GTA VI - максимальні налаштування',
                'icon' => 'fa-trophy'
            ],
            'work_3d' => [
                'name' => '3D Робота',
                'description' => 'Blender, Maya - рендеринг та моделювання',
                'icon' => 'fa-cube'
            ],
            'streaming' => [
                'name' => 'Стрімінг',
                'description' => 'Twitch, YouTube - геймінг + трансляція',
                'icon' => 'fa-video'
            ],
            'office' => [
                'name' => 'Офісна робота',
                'description' => 'Word, Excel, браузер',
                'icon' => 'fa-briefcase'
            ]
        ];
    }
    
    /**
     * Отримати рекомендовані діапазони бюджету
     */
    public static function getBudgetRanges(): array
    {
        return [
            'minimal' => [
                'name' => 'Мінімальний',
                'min' => 20000,
                'max' => 30000,
                'recommended' => 25000,
                'description' => 'Базова конфігурація для нетребовательних завдань'
            ],
            'optimal' => [
                'name' => 'Оптимальний',
                'min' => 35000,
                'max' => 55000,
                'recommended' => 45000,
                'description' => 'Збалансована збірка для більшості ігор'
            ],
            'progressive' => [
                'name' => 'Прогресивний',
                'min' => 60000,
                'max' => 90000,
                'recommended' => 75000,
                'description' => 'Потужна система для AAA ігор на високих налаштуваннях'
            ],
            'maximum' => [
                'name' => 'Максимальний',
                'min' => 100000,
                'max' => 200000,
                'recommended' => 125000,
                'description' => 'Топова конфігурація без компромісів'
            ]
        ];
    }
}




