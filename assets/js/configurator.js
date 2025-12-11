/**
 * PC Configurator - JavaScript Logic
 * Робота з API для ручного та автоматичного режимів
 */

// ============================================
// ГЛОБАЛЬНІ ЗМІННІ
// ============================================

let currentMode = 'manual';
let selectedComponents = {}; // { cpu: {...}, motherboard: {...}, ... }
let generatedBuild = null;
let categoriesData = [];

// ============================================
// ІНІЦІАЛІЗАЦІЯ
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Configurator initialized');
    
    // Завантажити категорії
    loadCategories();
    
    // Налаштувати listeners для accordion
    setupAccordionListeners();
    
    // Налаштувати listeners для автопідбору
    setupAutoModeListeners();
    
    // Перевірити чи є категорія для автоматичного відкриття (з URL)
    if (window.openCategoryOnLoad) {
        console.log('🎯 Opening category from URL:', window.openCategoryOnLoad);
        setTimeout(function() {
            openCategoryBySlug(window.openCategoryOnLoad);
        }, 300);
    } else {
        // Завантажити першу відкриту категорію (CPU) за замовчуванням
        setTimeout(function() {
            const firstOpenedCollapse = document.querySelector('.accordion-collapse.show');
            if (firstOpenedCollapse) {
                const container = firstOpenedCollapse.querySelector('[id^="components-"]');
                if (container) {
                    const category = container.getAttribute('data-category');
                    if (category) {
                        console.log('📦 Loading first category:', category);
                        loadComponents(category);
                    }
                }
            }
        }, 300);
    }
});

// ============================================
// ПЕРЕМИКАННЯ РЕЖИМІВ
// ============================================

function switchMode(mode) {
    currentMode = mode;
    
    // Оновити табс
    document.getElementById('manualTab').classList.toggle('active', mode === 'manual');
    document.getElementById('autoTab').classList.toggle('active', mode === 'auto');
    
    // Показати/сховати контент
    document.getElementById('manualMode').style.display = mode === 'manual' ? 'block' : 'none';
    document.getElementById('autoMode').style.display = mode === 'auto' ? 'block' : 'none';
    
    console.log('📍 Switched to:', mode);
}

// ============================================
// РУЧНИЙ РЕЖИМ
// ============================================

/**
 * Налаштувати listeners для accordion
 */
function setupAccordionListeners() {
    const accordionButtons = document.querySelectorAll('.accordion-button');
    
    accordionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            const container = document.getElementById(`components-${category}`);
            
            // Якщо вже завантажено - не завантажувати знову
            if (container && container.dataset.loaded !== 'true') {
                loadComponents(category);
            }
        });
    });
}

/**
 * Завантажити компоненти категорії (з фільтрацією сумісних)
 */
async function loadComponents(category) {
    const container = document.getElementById(`components-${category}`);
    if (!container) return;
    
    console.log(`📦 Loading components for: ${category}`);
    
    // Показати спінер
    container.innerHTML = `
        <div class="col-12 text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Завантаження...</span>
            </div>
        </div>
    `;
    
    try {
        // Отримати ID вже обраних компонентів
        const currentBuildIds = Object.values(selectedComponents).map(c => c.id);
        
        let components;
        
        // Якщо є обрані компоненти - завантажити сумісні
        if (currentBuildIds.length > 0) {
            const response = await fetch('/api/get_compatible.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    category: category,
                    current_build: currentBuildIds
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                components = data.data.components;
                console.log(`✅ Loaded ${components.length} compatible components`);
            } else {
                throw new Error(data.error);
            }
        } else {
            // Завантажити всі компоненти категорії
            const response = await fetch('/api/get_components.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ category: category })
            });
            
            const data = await response.json();
            
            if (data.success) {
                components = data.data;
                console.log(`✅ Loaded ${components.length} components`);
            } else {
                throw new Error(data.error);
            }
        }
        
        // Відобразити компоненти
        displayComponents(container, category, components);
        container.dataset.loaded = 'true';
        
    } catch (error) {
        console.error('❌ Error loading components:', error);
        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Помилка завантаження компонентів: ${error.message}
                </div>
            </div>
        `;
    }
}

/**
 * Відобразити компоненти
 */
function displayComponents(container, category, components) {
    if (components.length === 0) {
        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    Немає сумісних компонентів для вашої поточної збірки.
                </div>
            </div>
        `;
        return;
    }
    
    container.innerHTML = '';
    
    components.forEach(component => {
        const isSelected = selectedComponents[category]?.id === component.id;
        
        const card = document.createElement('div');
        card.className = 'col-md-6';
        card.innerHTML = `
            <div class="card component-card ${isSelected ? 'selected' : ''}" 
                 onclick="selectComponent('${category}', ${component.id})">
                <div class="card-body">
                    <h6 class="card-title">${escapeHtml(component.name)}</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-success fw-bold">${formatPrice(component.price)} грн</span>
                        ${isSelected ? '<i class="fas fa-check-circle text-success"></i>' : ''}
                    </div>
                    ${renderSpecs(component)}
                </div>
            </div>
        `;
        
        container.appendChild(card);
    });
}

/**
 * Рендер характеристик компонента
 */
function renderSpecs(component) {
    let specs = [];
    
    if (component.socket) specs.push(`Socket: ${component.socket}`);
    if (component.ram_type) specs.push(`${component.ram_type}`);
    if (component.tdp) specs.push(`TDP: ${component.tdp}W`);
    if (component.psu_wattage) specs.push(`${component.psu_wattage}W`);
    
    if (specs.length === 0) return '';
    
    return `<div class="mt-2"><small class="text-muted">${specs.join(' • ')}</small></div>`;
}

/**
 * Вибрати компонент
 */
async function selectComponent(category, componentId) {
    console.log(`🎯 Selected: ${category} #${componentId}`);
    
    try {
        // Отримати повну інформацію про компонент
        const response = await fetch('/api/get_components.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ category: category })
        });
        
        const data = await response.json();
        
        if (data.success) {
            const component = data.data.find(c => c.id === componentId);
            
            if (component) {
                // Зберегти обраний компонент
                selectedComponents[category] = component;
                
                // Оновити UI
                updateBuildSummary();
                updateCategoryBadge(category);
                
                // Перезавантажити наступні категорії (для фільтрації сумісних)
                reloadDependentCategories(category);
            }
        }
        
    } catch (error) {
        console.error('❌ Error selecting component:', error);
    }
}

/**
 * Перезавантажити залежні категорії
 */
function reloadDependentCategories(changedCategory) {
    // Карта залежностей
    const dependencies = {
        'cpu': ['motherboard', 'psu'],
        'motherboard': ['ram', 'psu'],
        'gpu': ['psu']
    };
    
    const dependentCategories = dependencies[changedCategory] || [];
    
    dependentCategories.forEach(category => {
        const container = document.getElementById(`components-${category}`);
        if (container) {
            container.dataset.loaded = 'false';
            
            // Якщо accordion відкритий - перезавантажити
            const collapse = document.getElementById(`collapse${getCategoryId(category)}`);
            if (collapse && collapse.classList.contains('show')) {
                loadComponents(category);
            }
        }
    });
}

/**
 * Оновити summary збірки
 */
function updateBuildSummary() {
    const container = document.getElementById('buildSummary');
    const componentsArray = Object.entries(selectedComponents);
    
    if (componentsArray.length === 0) {
        container.innerHTML = `
            <p class="text-muted text-center">
                <i class="fas fa-info-circle"></i><br>
                Оберіть компоненти зліва
            </p>
        `;
        
        // Відключити кнопки
        document.getElementById('validateBtn').disabled = true;
        document.getElementById('saveBuildBtn').disabled = true;
        
        // Скинути ціну та TDP
        document.getElementById('totalPrice').textContent = '0.00 грн';
        document.getElementById('totalTDP').textContent = '0 W';
        
        return;
    }
    
    // Відобразити обрані компоненти
    let html = '<div class="list-group list-group-flush">';
    
    componentsArray.forEach(([category, component]) => {
        html += `
            <div class="list-group-item d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <small class="text-muted">${getCategoryName(category)}</small>
                    <div class="fw-bold">${escapeHtml(component.name)}</div>
                    <small class="text-success">${formatPrice(component.price)} грн</small>
                </div>
                <button class="btn btn-sm btn-outline-danger" 
                        onclick="removeComponent('${category}')"
                        title="Видалити">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
    
    // Підрахувати загальну ціну та TDP
    const totalPrice = componentsArray.reduce((sum, [_, c]) => sum + parseFloat(c.price), 0);
    const totalTDP = componentsArray.reduce((sum, [_, c]) => sum + (parseInt(c.tdp) || 0), 0);
    
    document.getElementById('totalPrice').textContent = formatPrice(totalPrice) + ' грн';
    document.getElementById('totalTDP').textContent = totalTDP + ' W';
    
    // Увімкнути кнопки
    document.getElementById('validateBtn').disabled = false;
    document.getElementById('saveBuildBtn').disabled = false;
}

/**
 * Видалити компонент зі збірки
 */
function removeComponent(category) {
    delete selectedComponents[category];
    updateBuildSummary();
    updateCategoryBadge(category);
    
    // Перезавантажити категорію
    const container = document.getElementById(`components-${category}`);
    if (container) {
        container.dataset.loaded = 'false';
        loadComponents(category);
    }
    
    // Перезавантажити залежні
    reloadDependentCategories(category);
}

/**
 * Оновити бейдж категорії
 */
function updateCategoryBadge(category) {
    const badge = document.getElementById(`badge-${category}`);
    if (badge) {
        if (selectedComponents[category]) {
            badge.textContent = '✓ Обрано';
            badge.className = 'badge bg-success ms-2';
        } else {
            badge.textContent = 'Не обрано';
            badge.className = 'badge bg-secondary ms-2';
        }
    }
}

/**
 * Валідація збірки
 */
async function validateBuild() {
    const componentIds = Object.values(selectedComponents).map(c => c.id);
    
    if (componentIds.length === 0) {
        showAlert('warning', 'Оберіть хоча б один компонент');
        return;
    }
    
    console.log('🔍 Validating build:', componentIds);
    
    // Показати спінер
    const btn = document.getElementById('validateBtn');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<span class="loading"></span> Перевірка...';
    btn.disabled = true;
    
    try {
        const response = await fetch('/api/validate_build.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ component_ids: componentIds })
        });
        
        const data = await response.json();
        
        if (data.success) {
            const validation = data.data.validation;
            
            if (validation.compatible) {
                showCompatibilityAlert('success', 'Збірка сумісна!', [
                    'Всі компоненти підходять один до одного',
                    `Загальна вартість: ${formatPrice(data.data.stats.total_price)} грн`,
                    `Споживання енергії: ${data.data.stats.total_tdp} W`
                ]);
            } else {
                showCompatibilityAlert('danger', 'Збірка несумісна!', validation.errors);
            }
        } else {
            throw new Error(data.error);
        }
        
    } catch (error) {
        console.error('❌ Validation error:', error);
        showAlert('danger', 'Помилка перевірки: ' + error.message);
    } finally {
        btn.innerHTML = originalHTML;
        btn.disabled = false;
    }
}

/**
 * Показати алерт сумісності
 */
function showCompatibilityAlert(type, title, messages) {
    const container = document.getElementById('compatibilityAlert');
    
    let html = `
        <div class="alert alert-${type} alert-dismissible fade show">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <h6 class="alert-heading">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                ${title}
            </h6>
            <ul class="mb-0">
    `;
    
    messages.forEach(msg => {
        html += `<li>${msg}</li>`;
    });
    
    html += `</ul></div>`;
    
    container.innerHTML = html;
    container.style.display = 'block';
    
    // Прокрутити до алерту
    container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

/**
 * Зберегти збірку
 */
async function saveBuild() {
    const componentIds = Object.values(selectedComponents).map(c => c.id);
    
    if (componentIds.length === 0) {
        showAlert('warning', 'Оберіть компоненти перед збереженням');
        return;
    }
    
    // Запитати назву збірки
    const buildName = prompt('Введіть назву збірки:', 'Моя збірка ' + new Date().toLocaleDateString());
    
    if (!buildName) return;
    
    console.log('💾 Saving build:', buildName);
    
    const btn = document.getElementById('saveBuildBtn');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<span class="loading"></span> Збереження...';
    btn.disabled = true;
    
    try {
        const response = await fetch('/api/save_build.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                build_name: buildName,
                component_ids: componentIds
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', `Збірку "${buildName}" успішно збережено!`);
            
            // Показати кнопку переходу
            setTimeout(() => {
                if (confirm('Перейти до збережених збірок?')) {
                    window.location.href = '/my_builds.php';
                }
            }, 1000);
        } else {
            if (response.status === 401) {
                showAlert('warning', 'Увійдіть щоб зберегти збірку');
                setTimeout(() => {
                    window.location.href = '/login.php?redirect=' + encodeURIComponent(window.location.pathname);
                }, 2000);
            } else {
                throw new Error(data.error);
            }
        }
        
    } catch (error) {
        console.error('❌ Save error:', error);
        showAlert('danger', 'Помилка збереження: ' + error.message);
    } finally {
        btn.innerHTML = originalHTML;
        btn.disabled = false;
    }
}

// ============================================
// АВТОМАТИЧНИЙ РЕЖИМ
// ============================================

/**
 * Налаштувати listeners для автопідбору
 */
function setupAutoModeListeners() {
    // Опис завдання
    const taskSelect = document.getElementById('taskType');
    if (taskSelect) {
        taskSelect.addEventListener('change', function() {
            const descriptions = {
                'cyber_sport': '🎮 Акцент на процесор та RAM для високого FPS в онлайн-іграх',
                'gaming_aaa': '🏆 Потужна відеокарта для сучасних ААА ігор на високих налаштуваннях',
                'work_3d': '🎨 Багатоядерний процесор та багато RAM для рендерингу',
                'streaming': '📹 Збалансована збірка для геймінгу та стрімінгу одночасно',
                'office': '💼 Надійна конфігурація для офісної роботи та навчання'
            };
            
            const desc = document.getElementById('taskDescription');
            if (desc) {
                desc.textContent = descriptions[this.value] || '';
            }
        });
    }
    
    // Синхронізація кастомного бюджету
    const customBudget = document.getElementById('customBudget');
    if (customBudget) {
        customBudget.addEventListener('input', function() {
            // Прибрати виділення з швидких кнопок
            document.querySelectorAll('.budget-option').forEach(el => {
                el.classList.remove('selected');
            });
        });
    }
}

/**
 * Вибрати бюджет
 */
function selectBudget(amount) {
    // Прибрати виділення з інших
    document.querySelectorAll('.budget-option').forEach(el => {
        el.classList.remove('selected');
    });
    
    // Виділити обраний
    const selected = document.querySelector(`[data-budget="${amount}"]`);
    if (selected) {
        selected.classList.add('selected');
    }
    
    // Встановити значення
    document.getElementById('customBudget').value = amount;
}

/**
 * Згенерувати збірку
 */
async function generateBuild() {
    const task = document.getElementById('taskType').value;
    const budget = parseInt(document.getElementById('customBudget').value);
    
    // Валідація
    if (!task) {
        showAlert('warning', 'Оберіть завдання для комп\'ютера');
        return;
    }
    
    if (!budget || budget < 10000) {
        showAlert('warning', 'Введіть бюджет (мінімум 10 000 грн)');
        return;
    }
    
    console.log('🎲 Generating build:', task, budget);
    
    // Сховати попередній результат
    document.getElementById('buildResult').style.display = 'none';
    
    // Показати спінер
    const btn = document.getElementById('generateBtn');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<span class="loading"></span> Генерація збірки...';
    btn.disabled = true;
    
    try {
        const response = await fetch('/api/auto_build.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ task: task, budget: budget })
        });
        
        const data = await response.json();
        
        if (data.success && data.data.success) {
            generatedBuild = data.data;
            displayAutoBuildResult(generatedBuild);
        } else {
            // Помилка генерації
            const errors = data.details?.errors || [data.error];
            showAlert('danger', 'Не вдалося згенерувати збірку', errors);
        }
        
    } catch (error) {
        console.error('❌ Generation error:', error);
        showAlert('danger', 'Помилка генерації: ' + error.message);
    } finally {
        btn.innerHTML = originalHTML;
        btn.disabled = false;
    }
}

/**
 * Відобразити результат автопідбору
 */
function displayAutoBuildResult(result) {
    const container = document.getElementById('buildResult');
    
    // Статистика
    document.getElementById('resultPrice').textContent = formatPrice(result.stats.total_price) + ' грн';
    document.getElementById('statComponents').textContent = Object.keys(result.build).length;
    document.getElementById('statBudget').textContent = result.stats.budget_used_percent.toFixed(1) + '%';
    document.getElementById('statTDP').textContent = result.stats.total_tdp + ' W';
    document.getElementById('statPSU').textContent = result.stats.psu_margin + ' W';
    
    // Список компонентів
    const componentsContainer = document.getElementById('resultComponents');
    let html = '<div class="table-responsive"><table class="table table-hover">';
    html += '<thead><tr><th>Компонент</th><th>Модель</th><th class="text-end">Ціна</th></tr></thead><tbody>';
    
    Object.entries(result.build).forEach(([category, component]) => {
        html += `
            <tr>
                <td><strong>${getCategoryName(category)}</strong></td>
                <td>${escapeHtml(component.name)}</td>
                <td class="text-end text-success fw-bold">${formatPrice(component.price)} грн</td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    componentsContainer.innerHTML = html;
    
    // Показати результат
    container.style.display = 'block';
    container.scrollIntoView({ behavior: 'smooth' });
}

/**
 * Скинути автопідбір
 */
function resetAutoBuild() {
    document.getElementById('buildResult').style.display = 'none';
    document.getElementById('taskType').value = '';
    document.getElementById('customBudget').value = '';
    document.getElementById('taskDescription').textContent = '';
    
    document.querySelectorAll('.budget-option').forEach(el => {
        el.classList.remove('selected');
    });
    
    generatedBuild = null;
}

/**
 * Редагувати вручну
 */
function editManually() {
    if (!generatedBuild) return;
    
    // Перенести згенеровані компоненти в ручний режим
    selectedComponents = { ...generatedBuild.build };
    
    // Перемкнутися на ручний режим
    switchMode('manual');
    
    // Оновити UI
    updateBuildSummary();
    
    // Оновити всі бейджі
    Object.keys(selectedComponents).forEach(category => {
        updateCategoryBadge(category);
    });
    
    showAlert('info', 'Збірку перенесено в ручний режим. Ви можете змінити будь-який компонент.');
}

/**
 * Зберегти автоматичну збірку
 */
async function saveAutoBuild() {
    if (!generatedBuild) return;
    
    const componentIds = Object.values(generatedBuild.build).map(c => c.id);
    
    // Запитати назву
    const taskNames = {
        'cyber_sport': 'Кіберспорт',
        'gaming_aaa': 'ААА Геймінг',
        'work_3d': '3D Робота',
        'streaming': 'Стрімінг',
        'office': 'Офісна збірка'
    };
    
    const taskName = taskNames[generatedBuild.stats.task_type] || 'Збірка';
    const buildName = prompt('Введіть назву збірки:', `${taskName} (${formatPrice(generatedBuild.stats.total_price)} грн)`);
    
    if (!buildName) return;
    
    console.log('💾 Saving auto build:', buildName);
    
    try {
        const response = await fetch('/api/save_build.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                build_name: buildName,
                component_ids: componentIds
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', `Збірку "${buildName}" успішно збережено!`);
            
            setTimeout(() => {
                if (confirm('Перейти до збережених збірок?')) {
                    window.location.href = '/my_builds.php';
                }
            }, 1000);
        } else {
            if (response.status === 401) {
                showAlert('warning', 'Увійдіть щоб зберегти збірку');
                setTimeout(() => {
                    window.location.href = '/login.php?redirect=' + encodeURIComponent(window.location.pathname);
                }, 2000);
            } else {
                throw new Error(data.error);
            }
        }
        
    } catch (error) {
        console.error('❌ Save error:', error);
        showAlert('danger', 'Помилка збереження: ' + error.message);
    }
}

// ============================================
// ДОПОМІЖНІ ФУНКЦІЇ
// ============================================

/**
 * Відкрити категорію за slug (для автоматичного відкриття з URL)
 */
function openCategoryBySlug(categorySlug) {
    console.log('🔍 Searching for category:', categorySlug);
    
    // Знайти кнопку accordion для цієї категорії
    const accordionButton = document.querySelector(`[data-category="${categorySlug}"]`);
    
    if (!accordionButton) {
        console.warn('⚠️ Category not found:', categorySlug);
        return;
    }
    
    // Отримати ID collapse елемента
    const targetId = accordionButton.getAttribute('data-bs-target');
    if (!targetId) {
        console.warn('⚠️ Target ID not found for category:', categorySlug);
        return;
    }
    
    const collapseElement = document.querySelector(targetId);
    if (!collapseElement) {
        console.warn('⚠️ Collapse element not found:', targetId);
        return;
    }
    
    // Закрити всі інші accordion
    document.querySelectorAll('.accordion-collapse').forEach(collapse => {
        if (collapse !== collapseElement && collapse.classList.contains('show')) {
            const bsCollapse = new bootstrap.Collapse(collapse, { toggle: false });
            bsCollapse.hide();
        }
    });
    
    // Відкрити потрібний accordion
    console.log('✅ Opening accordion for:', categorySlug);
    const bsCollapse = new bootstrap.Collapse(collapseElement, { toggle: false });
    bsCollapse.show();
    
    // Прокрутити до accordion
    setTimeout(() => {
        accordionButton.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 100);
    
    // Завантажити компоненти після відкриття
    setTimeout(() => {
        loadComponents(categorySlug);
    }, 400);
}

/**
 * Завантажити категорії
 */
async function loadCategories() {
    try {
        const response = await fetch('/api/get_categories.php');
        const data = await response.json();
        
        if (data.success) {
            categoriesData = data.data;
            console.log('✅ Categories loaded:', categoriesData.length);
        }
    } catch (error) {
        console.error('❌ Error loading categories:', error);
    }
}

/**
 * Отримати ID категорії з БД
 */
function getCategoryId(slug) {
    const cat = categoriesData.find(c => c.slug === slug);
    return cat ? cat.id : null;
}

/**
 * Отримати назву категорії
 */
function getCategoryName(slug) {
    const names = {
        'cpu': 'Процесор',
        'motherboard': 'Материнська плата',
        'ram': 'Оперативна пам\'ять',
        'gpu': 'Відеокарта',
        'psu': 'Блок живлення',
        'case': 'Корпус',
        'storage': 'Накопичувач'
    };
    
    return names[slug] || slug;
}

/**
 * Форматувати ціну
 */
function formatPrice(price) {
    return parseFloat(price).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

/**
 * Екранувати HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Показати загальний алерт (Toast)
 */
function showAlert(type, message, details = []) {
    const colors = {
        'success': '#28a745',
        'danger': '#dc3545',
        'warning': '#ffc107',
        'info': '#17a2b8'
    };
    
    const icons = {
        'success': 'fa-check-circle',
        'danger': 'fa-exclamation-triangle',
        'warning': 'fa-exclamation-circle',
        'info': 'fa-info-circle'
    };
    
    let html = `
        <div style="
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            background: white;
            border-left: 4px solid ${colors[type]};
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border-radius: 8px;
            padding: 20px;
            max-width: 400px;
            animation: slideInRight 0.3s ease-out;
        " id="customAlert">
            <div style="display: flex; align-items: start; gap: 12px;">
                <i class="fas ${icons[type]}" style="color: ${colors[type]}; font-size: 24px;"></i>
                <div style="flex: 1;">
                    <strong>${message}</strong>
    `;
    
    if (details.length > 0) {
        html += '<ul style="margin: 10px 0 0 0; padding-left: 20px; font-size: 14px;">';
        details.forEach(detail => {
            html += `<li>${detail}</li>`;
        });
        html += '</ul>';
    }
    
    html += `
                </div>
                <button onclick="this.parentElement.parentElement.remove()" 
                        style="border: none; background: none; font-size: 20px; cursor: pointer; color: #999;">
                    ×
                </button>
            </div>
        </div>
    `;
    
    // Додати в DOM
    const alertDiv = document.createElement('div');
    alertDiv.innerHTML = html;
    document.body.appendChild(alertDiv);
    
    // Автоматично прибрати через 5 секунд
    setTimeout(() => {
        const alert = document.getElementById('customAlert');
        if (alert) {
            alert.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => alert.remove(), 300);
        }
    }, 5000);
}

// Додати CSS для анімації
const style = document.createElement('style');
style.textContent = `
@keyframes slideInRight {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOutRight {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(400px);
        opacity: 0;
    }
}
`;
document.head.appendChild(style);

