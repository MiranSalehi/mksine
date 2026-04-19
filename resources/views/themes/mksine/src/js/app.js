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
    initMegaHeader();
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
 * Mobile drawer menu (sidebar + backdrop, smooth open/close)
 */
function initMobileMenu() {
    const trigger = document.querySelector('[data-mobile-menu]');
    const drawer = document.querySelector('[data-mobile-drawer]');
    const backdrop = document.querySelector('[data-mobile-backdrop]');
    const closeButtons = document.querySelectorAll('[data-mobile-menu-close]');

    if (!trigger || !drawer || !backdrop) {
        return;
    }

    const open = () => {
        drawer.classList.add('is-open');
        backdrop.classList.add('is-open');
        document.body.classList.add('site-mobile-drawer-open');
        trigger.setAttribute('aria-expanded', 'true');
        drawer.setAttribute('aria-hidden', 'false');
        backdrop.setAttribute('aria-hidden', 'false');
    };

    const close = (options = {}) => {
        const { restoreFocus = true } = options;
        drawer.classList.remove('is-open');
        backdrop.classList.remove('is-open');
        document.body.classList.remove('site-mobile-drawer-open');
        trigger.setAttribute('aria-expanded', 'false');
        drawer.setAttribute('aria-hidden', 'true');
        backdrop.setAttribute('aria-hidden', 'true');
        if (restoreFocus && window.matchMedia('(max-width: 1023px)').matches) {
            trigger.focus();
        }
    };

    trigger.addEventListener('click', () => {
        if (drawer.classList.contains('is-open')) {
            close();
        } else {
            open();
        }
    });

    backdrop.addEventListener('click', close);
    closeButtons.forEach((btn) => btn.addEventListener('click', close));

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && drawer.classList.contains('is-open')) {
            close();
        }
    });

    drawer.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            if (link.getAttribute('href') && link.getAttribute('href') !== '#') {
                close();
            }
        });
    });

    window.addEventListener('resize', () => {
        if (window.matchMedia('(min-width: 1024px)').matches && drawer.classList.contains('is-open')) {
            close({ restoreFocus: false });
        }
    });
}

/**
 * Desktop mega menu (click to toggle; outside click / Escape closes).
 */
function initMegaHeader() {
    const header = document.querySelector('.site-header-bar');
    const panel = document.getElementById('site-header-mega-panels');
    const triggers = document.querySelectorAll('[data-mega-nav-trigger][data-toggle-submenu]');

    if (!header || !panel || triggers.length === 0) {
        return;
    }

    let openId = null;

    const close = () => {
        openId = null;
        panel.classList.remove('is-open');
        panel.setAttribute('aria-hidden', 'true');
        triggers.forEach((t) => {
            t.classList.remove('is-active');
            t.setAttribute('aria-expanded', 'false');
        });
        panel.querySelectorAll('[data-header-submenu]').forEach((sub) => {
            sub.classList.add('hidden');
        });
    };

    const open = (id) => {
        if (openId === id) {
            close();
            return;
        }
        openId = id;
        panel.classList.add('is-open');
        panel.setAttribute('aria-hidden', 'false');
        triggers.forEach((t) => {
            const tid = t.getAttribute('data-toggle-submenu');
            const active = tid === id;
            t.classList.toggle('is-active', active);
            t.setAttribute('aria-expanded', active ? 'true' : 'false');
        });
        panel.querySelectorAll('[data-header-submenu]').forEach((sub) => {
            const sid = sub.getAttribute('data-header-submenu');
            sub.classList.toggle('hidden', sid !== id);
        });
    };

    triggers.forEach((t) => {
        t.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (!window.matchMedia('(min-width: 1024px)').matches) {
                return;
            }
            const id = t.getAttribute('data-toggle-submenu');
            if (id) {
                open(id);
            }
        });
    });

    document.addEventListener('click', (e) => {
        if (openId !== null && !header.contains(e.target)) {
            close();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && openId !== null) {
            close();
        }
    });

    window.addEventListener('resize', () => {
        if (window.matchMedia('(max-width: 1023px)').matches) {
            close();
        }
    });
}

/**
 * Smooth Scroll for Anchor Links
 */
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
        const href = anchor.getAttribute('href');
        if (!href || href === '#') {
            return;
        }
        anchor.addEventListener('click', (e) => {
            e.preventDefault();
            const target = document.querySelector(href);
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
