/* =========================================================
   Jambu Kristal Monitoring — Shared JavaScript Module
   Contains: Theme management, Chart theme helpers
   ========================================================= */

// ══════════════════════════════════════════════════════════
//  THEME MANAGEMENT
// ══════════════════════════════════════════════════════════

function getTheme() {
    return localStorage.getItem('jambu-theme') || 'dark';
}

function setTheme(theme) {
    localStorage.setItem('jambu-theme', theme);
}

function applyTheme(theme) {
    const body = document.body;

    if (theme === 'light') {
        body.classList.remove('dark-mode');
        body.classList.add('light-mode');
    } else {
        body.classList.remove('light-mode');
        body.classList.add('dark-mode');
    }

    updateThemeToggleUI(theme);
}

function updateThemeToggleUI(theme) {
    const icon = document.getElementById('theme-icon');
    const label = document.getElementById('theme-label');

    if (!icon || !label) return;

    if (theme === 'light') {
        icon.className = 'bi bi-moon-fill theme-icon';
        label.textContent = 'Dark';
    } else {
        icon.className = 'bi bi-sun-fill theme-icon';
        label.textContent = 'Light';
    }
}

function toggleTheme() {
    const current = getTheme();
    const next = current === 'dark' ? 'light' : 'dark';

    // Trigger body fade animation
    document.body.classList.add('theme-switching');

    // Trigger icon spin animation
    const icon = document.getElementById('theme-icon');
    if (icon) {
        icon.classList.add('spin-swap');
    }

    // Apply new theme
    setTheme(next);
    applyTheme(next);

    // Rebuild charts if the page has a rebuildCharts function
    if (typeof rebuildChartsForTheme === 'function') {
        rebuildChartsForTheme();
    }

    // Clean up animation classes after they finish
    setTimeout(() => {
        document.body.classList.remove('theme-switching');
        if (icon) icon.classList.remove('spin-swap');
    }, 650);
}


// ══════════════════════════════════════════════════════════
//  MOBILE NAVBAR TOGGLE
// ══════════════════════════════════════════════════════════

function toggleNavMenu() {
    const hamburger = document.getElementById('nav-hamburger');
    const navContent = document.getElementById('nav-content');

    if (!hamburger || !navContent) return;

    hamburger.classList.toggle('open');
    navContent.classList.toggle('show');
}

// Close mobile menu when clicking a nav link + start live clock
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.nav-link-item').forEach(link => {
        link.addEventListener('click', () => {
            const hamburger = document.getElementById('nav-hamburger');
            const navContent = document.getElementById('nav-content');
            if (hamburger && navContent && window.innerWidth < 768) {
                hamburger.classList.remove('open');
                navContent.classList.remove('show');
            }
        });
    });

    // Live clock
    startNavClock();
});

function startNavClock() {
    const el = document.getElementById('nav-clock-time');
    if (!el) return;

    function tick() {
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        const s = String(now.getSeconds()).padStart(2, '0');
        el.textContent = `${h}:${m}:${s}`;
    }

    tick(); // run immediately
    setInterval(tick, 1000);
}


// ══════════════════════════════════════════════════════════
//  CHART THEME HELPERS
// ══════════════════════════════════════════════════════════

function getChartThemeColors() {
    const style = getComputedStyle(document.body);
    return {
        grid: style.getPropertyValue('--chart-grid').trim() || 'rgba(76,175,80,0.07)',
        tick: style.getPropertyValue('--chart-tick').trim() || '#7a9980',
        legend: style.getPropertyValue('--chart-legend').trim() || '#a5c4ab',
        tooltipBg: style.getPropertyValue('--chart-tooltip-bg').trim() || 'rgba(22,32,25,0.95)',
        tooltipTitle: style.getPropertyValue('--chart-tooltip-title').trim() || '#e8f5e9',
        tooltipBody: style.getPropertyValue('--chart-tooltip-body').trim() || '#c8e6c9',
        tooltipBorder: style.getPropertyValue('--chart-tooltip-border').trim() || 'rgba(76,175,80,0.3)',
    };
}

// Initialise theme ASAP (before DOMContentLoaded to reduce FOUC)
(function initThemeEarly() {
    const theme = getTheme();
    document.documentElement.style.colorScheme = theme;
})();
