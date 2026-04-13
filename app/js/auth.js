(() => {
  function appBase() {
    const path = window.location.pathname;
    const idx = path.indexOf('/app/');
    return idx >= 0 ? path.slice(0, idx + 5) : '/app/';
  }

  function appUrl(relativePath) {
    return appBase() + String(relativePath).replace(/^\/+/, '');
  }

  async function getSessionStatus() {
    try {
      const res = await fetch(appUrl('session_status.php'), { credentials: 'same-origin' });
      if (!res.ok) return null;
      return await res.json();
    } catch {
      return null;
    }
  }

  async function redirectIfLoggedIn() {
    const body = document.body;
    if (!body || !body.classList.contains('auth-page')) return;

    const status = await getSessionStatus();
    if (status && status.logged_in) {
      window.location.replace(appUrl('home.php'));
    }
  }

  function bindAuthLinks() {
    const statusLinks = document.querySelectorAll('[data-auth-link]');
    if (!statusLinks.length) return;

    getSessionStatus().then(status => {
      const loggedIn = !!(status && status.logged_in);
      statusLinks.forEach(link => {
        if (!(link instanceof HTMLAnchorElement)) return;
        const loginHref = link.getAttribute('data-login-href');
        const homeHref = link.getAttribute('data-home-href');
        if (loggedIn && homeHref) {
          link.href = appUrl(homeHref);
          link.textContent = link.getAttribute('data-home-label') || link.textContent;
        } else if (!loggedIn && loginHref) {
          link.href = appUrl(loginHref);
          link.textContent = link.getAttribute('data-login-label') || link.textContent;
        }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    redirectIfLoggedIn();
    bindAuthLinks();
  });

  // Handles browser back/forward cache so logged users don't stay on login/signup pages.
  window.addEventListener('pageshow', () => {
    redirectIfLoggedIn();
  });
})();
