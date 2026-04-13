(() => {
  const STORAGE_KEY = 'herfa_lang';
  const SUPPORTED = ['fr', 'en', 'ar'];

  function getLang() {
    const saved = (localStorage.getItem(STORAGE_KEY) || 'fr').toLowerCase();
    return SUPPORTED.includes(saved) ? saved : 'fr';
  }

  function setLang(lang) {
    const next = SUPPORTED.includes(lang) ? lang : 'fr';
    localStorage.setItem(STORAGE_KEY, next);
    document.documentElement.lang = next;
    document.documentElement.dir = next === 'ar' ? 'rtl' : 'ltr';
    const event = new CustomEvent('herfa:langchange', { detail: { lang: next } });
    window.dispatchEvent(event);
  }

  function buildSelector() {
    if (document.getElementById('herfa-lang-switcher')) return;

    const wrapper = document.createElement('div');
    wrapper.id = 'herfa-lang-switcher';
    wrapper.className = 'd-flex align-items-center ms-lg-3 mt-3 mt-lg-0';
    wrapper.innerHTML = `
      <label class="visually-hidden" for="herfa-lang-select">Language</label>
      <select id="herfa-lang-select" class="form-select form-select-sm" style="min-width: 110px;">
        <option value="fr">FR</option>
        <option value="en">EN</option>
        <option value="ar">AR</option>
      </select>
    `;

    const navContainer = document.querySelector('#mainNav .container');
    if (navContainer) {
      navContainer.appendChild(wrapper);
    } else {
      wrapper.style.position = 'fixed';
      wrapper.style.right = '1rem';
      wrapper.style.bottom = '1rem';
      wrapper.style.zIndex = '1040';
      wrapper.style.background = 'rgba(255,255,255,.95)';
      wrapper.style.padding = '.5rem';
      wrapper.style.borderRadius = '999px';
      wrapper.style.boxShadow = '0 8px 20px rgba(0,0,0,.12)';
      document.body.appendChild(wrapper);
    }

    const select = document.getElementById('herfa-lang-select');
    select.value = getLang();
    select.addEventListener('change', () => setLang(select.value));
  }

  function init() {
    buildSelector();
    setLang(getLang());
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();