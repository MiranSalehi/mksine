/**
 * MKSine Default Theme JavaScript
 */

// Import Alpine.js
import Alpine from 'alpinejs';

// Make Alpine available globally
window.Alpine = Alpine;

// Initialize theme functionality
document.addEventListener('DOMContentLoaded', () => {
    // Start Alpine AFTER DOM is ready
    Alpine.start();
    
    initDarkMode();
    initDirectionToggle();
    initMobileMenu();
    initSmoothScroll();
    initScrollToTop();
});

/**
 * Dark/Light Mode Toggle.
 * Uses key "site-theme" so the frontend does not conflict with Filament admin (which uses "theme").
 */
const SITE_THEME_KEY = 'site-theme';

function initDarkMode() {
    var currentTheme = localStorage.getItem(SITE_THEME_KEY) || 'light';
    var html = document.documentElement;

    if (currentTheme === 'dark') {
        html.classList.add('dark');
    } else {
        html.classList.remove('dark');
    }

    var themeToggle = document.querySelector('[data-theme-toggle]');
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            var isDark = document.documentElement.classList.contains('dark');
            if (isDark) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem(SITE_THEME_KEY, 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem(SITE_THEME_KEY, 'dark');
            }
        });
    }
}

/**
 * RTL/LTR Direction Toggle
 */
function initDirectionToggle() {
    const currentDir = localStorage.getItem('direction') || 'ltr';
    applyDirection(currentDir);

    const dirToggle = document.querySelector('[data-direction-toggle]');
    if (dirToggle) {
        dirToggle.addEventListener('click', () => {
            const html = document.documentElement;
            const currentDir = html.getAttribute('dir') || 'ltr';
            const newDir = currentDir === 'ltr' ? 'rtl' : 'ltr';
            applyDirection(newDir);
        });
    }
}

function applyDirection(dir) {
    const html = document.documentElement;
    html.setAttribute('dir', dir);
    html.setAttribute('lang', dir === 'rtl' ? 'fa' : 'en');
    document.body.classList.toggle('rtl', dir === 'rtl');
    document.body.classList.toggle('ltr', dir === 'ltr');
    localStorage.setItem('direction', dir);
}

/**
 * Mobile Menu Toggle
 */
function initMobileMenu() {
    const menuButton = document.querySelector('[data-mobile-menu]');
    if (menuButton) {
        menuButton.addEventListener('click', () => {
            const menu = document.querySelector('[data-mobile-nav]');
            menu?.classList.toggle('hidden');
        });
    }
}

/**
 * Smooth Scroll for Anchor Links
 */
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', (e) => {
            e.preventDefault();
            const target = document.querySelector(anchor.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
}

/**
 * Scroll to Top Button
 */
function initScrollToTop() {
    const scrollButton = document.querySelector('[data-scroll-top]');

    if (scrollButton) {
        window.addEventListener('scroll', () => {
            scrollButton.classList.toggle('hidden', window.pageYOffset < 300);
        });

        scrollButton.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
}
