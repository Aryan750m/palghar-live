// assets/js/app_v2.js
// Core visual operations, theme triggers, PWA registrations, and scroll helpers

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialise theme preferences
    initThemeManager();
    
    // 2. Setup header calendar and date updates
    setupHeaderDateTime();
    
    // 3. Mobile menu drawer listeners
    setupMobileMenuDrawer();
    
    // 4. Image lightbox gallery overlay viewer
    setupImageLightbox();
    
    // 5. Scroll-to-top FAB action
    setupScrollToTop();
    
    // 6. Register PWA Service Worker
    registerServiceWorker();
});

// Theme Management System
function initThemeManager() {
    const themeToggleBtn = document.getElementById('theme-toggle');
    const cookieName = 'theme_preference';
    
    let currentTheme = getCookie(cookieName) || 'system';
    
    applyTheme(currentTheme);
    
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            let nextTheme = 'light';
            if (currentTheme === 'light') {
                nextTheme = 'dark';
            } else if (currentTheme === 'dark') {
                nextTheme = 'system';
            }
            
            currentTheme = nextTheme;
            setCookie(cookieName, nextTheme, 365);
            applyTheme(nextTheme);
            
            let label = 'System preference';
            if (nextTheme === 'light') label = 'Light theme';
            if (nextTheme === 'dark') label = 'Dark theme';
            
            if (typeof showToast === 'function') {
                showToast(`Theme changed to: ${label}`, 'info');
            }
        });
    }
}

function applyTheme(theme) {
    const btn = document.getElementById('theme-toggle');
    const rootEl = document.documentElement;
    const bodyEl = document.body;
    
    if (theme === 'system') {
        bodyEl.setAttribute('data-theme-mode', 'system');
        bodyEl.removeAttribute('data-theme');
        rootEl.setAttribute('data-theme-mode', 'system');
        rootEl.removeAttribute('data-theme');
        if (btn) btn.innerHTML = '<i class="fas fa-desktop"></i>';
    } else {
        bodyEl.setAttribute('data-theme-mode', 'fixed');
        bodyEl.setAttribute('data-theme', theme);
        rootEl.setAttribute('data-theme-mode', 'fixed');
        rootEl.setAttribute('data-theme', theme);
        if (btn) btn.innerHTML = theme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
    }
}

// DateTime Widget Helper
function setupHeaderDateTime() {
    const dateContainer = document.getElementById('current-date');
    if (dateContainer) {
        const fmt = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        dateContainer.innerHTML = `<i class="far fa-calendar-alt"></i> ${new Date().toLocaleDateString('en-US', fmt)}`;
    }
}

// Mobile navigation links toggle
function setupMobileMenuDrawer() {
    const trigger = document.getElementById('mobile-menu-toggle');
    const drawer = document.getElementById('nav-links-menu');
    
    if (trigger && drawer) {
        trigger.addEventListener('click', () => {
            drawer.classList.toggle('active');
            trigger.innerHTML = drawer.classList.contains('active') ? '✕' : '☰';
        });
        
        // Hide if clicking outside drawer
        document.addEventListener('click', (e) => {
            if (!trigger.contains(e.target) && !drawer.contains(e.target)) {
                drawer.classList.remove('active');
                trigger.innerHTML = '☰';
            }
        });
    }
}

// Global Image Zoom Lightbox
function setupImageLightbox() {
    const modal = document.getElementById('image-lightbox-modal');
    const modalImg = document.getElementById('lightbox-zoom-img');
    const modalCaption = document.getElementById('lightbox-zoom-caption');
    const closeBtn = document.getElementById('lightbox-close-btn');
    
    if (!modal || !modalImg) return;
    
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
        });
    }
    
    // Listen for image zoom clicks globally
    document.body.addEventListener('click', (e) => {
        const clickableImg = e.target.closest('.article-img-wrap img, .media-thumb img, .trending-item img, .section-static-banner img, .news-card-img');
        if (clickableImg && !e.target.closest('.media-manager-item')) {
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
            modalImg.src = clickableImg.src;
            if (modalCaption) {
                modalCaption.textContent = clickableImg.alt || 'Palghar LIVE Preview';
            }
        }
    });
}

// Scroll to Top floating actions
function setupScrollToTop() {
    const btn = document.getElementById('scroll-to-top-btn');
    if (!btn) return;
    
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            btn.classList.add('show');
        } else {
            btn.classList.remove('show');
        }
    });
    
    btn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

function registerServiceWorker() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register((window.APP_URL || '') + '/service-worker.js')
            .then(() => console.log('Palghar LIVE Service Worker registered.'))
            .catch(err => console.log('SW registration failure:', err));
    }
}

// Cookie Helper routines
function setCookie(name, value, days) {
    let expires = "";
    if (days) {
        let date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "")  + expires + "; path=/; SameSite=Lax";
}

function getCookie(name) {
    let nameEQ = name + "=";
    let ca = document.cookie.split(';');
    for(let i=0;i < ca.length;i++) {
        let c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}
