// Palghar LIVE Portal Core UI Animations and Widgets (Phase 1)
// Contains strictly visual micro-interactions, dark themes, and lightbox zooms.
// Direct database query listings and form postbacks are server-side PHP driven.

const THEME_STORAGE_KEY = 'palghar_live_theme';

// =========================================================================
// 1. Theme Management (Persistent across requests)
// =========================================================================
function initTheme() {
    const savedTheme = localStorage.getItem(THEME_STORAGE_KEY) || 'light';
    document.body.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);
}

function toggleTheme() {
    const currentTheme = document.body.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    document.body.setAttribute('data-theme', newTheme);
    localStorage.setItem(THEME_STORAGE_KEY, newTheme);
    updateThemeIcon(newTheme);
    showToast(`Theme updated: ${newTheme === 'dark' ? 'Dark Mode' : 'Light Mode'}`);
}

function updateThemeIcon(theme) {
    const btn = document.getElementById('theme-toggle');
    if (btn) {
        btn.innerHTML = theme === 'dark' ? '☀️' : '🌙';
    }
}

// =========================================================================
// 2. Weather & Date Widgets (English)
// =========================================================================
function initHeaderWidgets() {
    const dateEl = document.getElementById('current-date');
    if (dateEl) {
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        dateEl.innerHTML = `<i class="far fa-calendar-alt"></i> ${new Date().toLocaleDateString('en-US', options)}`;
    }
    
    const tempEl = document.getElementById('weather-temp');
    if (tempEl) {
        const temp = 28 + Math.floor(Math.random() * 5);
        tempEl.textContent = `${temp}°C`;
    }
}

// =========================================================================
// 3. Mobile Menu drawer drawer drawer
// =========================================================================
function setupMobileMenu() {
    const menuBtn = document.getElementById('mobile-menu-toggle');
    const navLinks = document.getElementById('nav-links-menu');
    if (menuBtn && navLinks) {
        menuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            menuBtn.innerHTML = navLinks.classList.contains('active') ? '✕' : '☰';
        });
        
        document.addEventListener('click', (e) => {
            if (!menuBtn.contains(e.target) && !navLinks.contains(e.target)) {
                navLinks.classList.remove('active');
                menuBtn.innerHTML = '☰';
            }
        });
    }
}

// =========================================================================
// 4. Toast notifications manager
// =========================================================================
function showToast(message, isSuccess = true) {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.style.borderLeftColor = isSuccess ? '#25D366' : '#E31B23';
    toast.innerHTML = `<span style="font-size: 1.2rem;">${isSuccess ? '✓' : '✗'}</span> <span>${message}</span>`;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
        if (container.children.length === 0) {
            container.remove();
        }
    }, 4000);
}

// =========================================================================
// 5. Category Helpers
// =========================================================================
function getCategoryLabel(catId) {
    switch (catId) {
        case 'local': return 'Palghar Local';
        case 'state': return 'Maharashtra';
        case 'national': return 'National';
        case 'sports': return 'Sports';
        case 'business': return 'Business';
        case 'culture': return 'Art & Culture';
        default: return catId;
    }
}
