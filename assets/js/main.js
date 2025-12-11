/**
 * PC Configurator - Main JavaScript
 */

// Плавна прокрутка до якорів
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href !== '#' && href !== '') {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    });
});

// Активний пункт меню
const currentPage = window.location.pathname;
document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
    if (link.getAttribute('href') === currentPage) {
        link.classList.add('active');
    }
});

// Консольне повідомлення
console.log('%cPC Configurator', 'color: #667eea; font-size: 20px; font-weight: bold;');
console.log('Дипломний проєкт 2025');












