/*!
* Start Bootstrap - Business Casual v7.0.9 (https://startbootstrap.com/theme/business-casual)
* Copyright 2013-2023 Start Bootstrap
* Licensed under MIT (https://github.com/StartBootstrap/startbootstrap-business-casual/blob/master/LICENSE)
*/

function getScriptsBasePath() {
    const scriptEl = document.querySelector('script[src$="js/scripts.js"], script[src$="/js/scripts.js"], script[src$="../js/scripts.js"]');
    if (!scriptEl) {
        return 'js/';
    }
    const src = scriptEl.getAttribute('src') || '';
    return src.replace(/scripts\.js(?:\?.*)?$/, '');
}

// Highlights current date on contact page
window.addEventListener('DOMContentLoaded', event => {
    const listHoursArray = document.body.querySelectorAll('.list-hours li');
    const todayItem = listHoursArray[new Date().getDay()];
    if (todayItem) {
        todayItem.classList.add('today');
    }
});

// Load the shared language selector on all Bootstrap-style pages.
(function () {
    // Language module temporarily disabled.
    return;
})();

// Global modern interactions (applies to every page loading scripts.js).
window.addEventListener('DOMContentLoaded', () => {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const body = document.body;

    // Scroll progress indicator.
    let progressBar = document.getElementById('scrollProgress');
    if (!progressBar && body) {
        progressBar = document.createElement('div');
        progressBar.id = 'scrollProgress';
        progressBar.className = 'scroll-progress-global';
        progressBar.setAttribute('aria-hidden', 'true');
        body.prepend(progressBar);
    }

    const updateScrollProgress = () => {
        if (!progressBar) {
            return;
        }
        const scrollTop = window.scrollY;
        const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
        const ratio = maxScroll > 0 ? Math.min(scrollTop / maxScroll, 1) : 0;
        progressBar.style.width = `${ratio * 100}%`;
    };

    updateScrollProgress();
    window.addEventListener('scroll', updateScrollProgress, { passive: true });

    // Smooth in-page anchor scrolling.
    if (!reduceMotion) {
        document.querySelectorAll('a[href^="#"]').forEach(link => {
            link.addEventListener('click', (event) => {
                const href = link.getAttribute('href');
                if (!href || href.length <= 1) {
                    return;
                }
                const target = document.querySelector(href);
                if (!target) {
                    return;
                }
                event.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    }

    // Auto-attach reveal classes on common content blocks.
    const revealSelector = [
        '.card', '.project-card', '.formation-card', '.offer-card', '.artisan-card',
        '.user-section', '.cta-section', '.stat-card', '.info-row', '.field-group',
        '.role-card', '.product-item', '.intro', '.page-section .container'
    ].join(', ');

    const revealItems = document.querySelectorAll(revealSelector);
    revealItems.forEach((item, index) => {
        if (!item.classList.contains('reveal-on-scroll')) {
            item.classList.add('reveal-on-scroll');
        }
        if (!reduceMotion) {
            item.style.transitionDelay = `${Math.min(index * 40, 260)}ms`;
        }
    });

    if (reduceMotion) {
        revealItems.forEach(item => item.classList.add('is-visible'));
    } else if (revealItems.length && 'IntersectionObserver' in window) {
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        revealItems.forEach(item => revealObserver.observe(item));
    } else {
        revealItems.forEach(item => item.classList.add('is-visible'));
    }

    // Subtle 3D hover interaction on cards.
    if (!reduceMotion) {
        const tiltCards = document.querySelectorAll('.card, .project-card, .formation-card, .offer-card, .artisan-card');
        tiltCards.forEach(card => {
            card.classList.add('interactive-card');
            card.addEventListener('mousemove', (event) => {
                const rect = card.getBoundingClientRect();
                const x = (event.clientX - rect.left) / rect.width;
                const y = (event.clientY - rect.top) / rect.height;
                const rotateY = (x - 0.5) * 8;
                const rotateX = (0.5 - y) * 6;
                card.style.transform = `perspective(900px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-6px)`;
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
            });
        });
    }
});
