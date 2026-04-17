(() => {
  function projectBase() {
    const segments = window.location.pathname.split('/').filter(Boolean);
    if (segments.length > 0) {
      return `/${segments[0]}/`;
    }
    return '/';
  }

  function projectUrl(relativePath) {
    return projectBase() + String(relativePath).replace(/^\/+/, '');
  }

  async function getSessionStatus() {
    try {
      const res = await fetch(projectUrl('controllers/session_status.php'), {
        credentials: 'same-origin',
        cache: 'no-store'
      });
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
      window.location.replace(projectUrl('controllers/home.php'));
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
          link.href = projectUrl(homeHref);
          link.textContent = link.getAttribute('data-home-label') || link.textContent;
        } else if (!loggedIn && loginHref) {
          link.href = projectUrl(loginHref);
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
