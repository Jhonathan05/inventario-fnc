// Firma corporativa login FNC — template vanilla (frontend only, sin backend)
// Canon: elecciones-fnc public/js/src/07-consulta-login.js (email UI + toggle) + modal legal.
// Uso:
//   <script src="login.js"></script>
//   <script>initFncLogin({ onSubmit: async ({ usuario, password }) => { ... } });</script>
// Si no se pasa onSubmit, emite el evento `fnc-login-submit` con detail { usuario, password }.
(function () {
  function toEl(id) { return document.getElementById(id); }

  function setStatus(msg, kind) {
    const el = toEl('login-status');
    if (!el) return;
    if (!msg) { el.style.display = 'none'; el.textContent = ''; el.classList.remove('login-status-banner--aviso'); return; }
    el.style.display = 'block';
    el.textContent = msg;
    el.classList.toggle('login-status-banner--aviso', kind === 'aviso');
  }

  function initLegalModal() {
    const openBtn = toEl('btn-legal-modal');
    const overlay = toEl('legal-modal-container');
    const closeBtn = toEl('btn-close-legal-modal');
    const closeBottom = toEl('btn-close-legal-modal-bottom');
    if (!overlay) return;
    if (openBtn) openBtn.addEventListener('click', () => {
      overlay.classList.add('is-active');
      if (window.lucide) lucide.createIcons();
    });
    const close = () => overlay.classList.remove('is-active');
    if (closeBtn) closeBtn.addEventListener('click', close);
    if (closeBottom) closeBottom.addEventListener('click', close);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
  }

  function initPremiumEmail() {
    const loginUserEl = toEl('login-user');
    const btnClearEmail = toEl('btn-clear-email');
    const domainButtons = document.querySelectorAll('.domain-btn');
    if (!loginUserEl) return;

    const updateEmailUI = () => {
      const val = loginUserEl.value;
      if (btnClearEmail) btnClearEmail.style.display = val.length > 0 ? 'flex' : 'none';
      domainButtons.forEach((btn) => {
        const domain = btn.dataset.domain;
        btn.classList.toggle('active', val.endsWith('@' + domain));
      });
    };

    loginUserEl.addEventListener('input', updateEmailUI);
    if (btnClearEmail) btnClearEmail.addEventListener('click', () => {
      loginUserEl.value = '';
      loginUserEl.dispatchEvent(new Event('input', { bubbles: true }));
      loginUserEl.focus();
    });
    domainButtons.forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const domain = btn.dataset.domain;
        const currentVal = loginUserEl.value.trim();
        if (!currentVal) return;
        const atIdx = currentVal.indexOf('@');
        const username = atIdx > -1 ? currentVal.slice(0, atIdx) : currentVal;
        if (!username) return;
        loginUserEl.value = `${username}@${domain}`;
        loginUserEl.dispatchEvent(new Event('input', { bubbles: true }));
        loginUserEl.focus();
      });
    });
    setTimeout(updateEmailUI, 100);
  }

  function initPasswordToggle() {
    const loginPassEl = toEl('login-pass');
    const btnTogglePassword = toEl('btn-toggle-password');
    if (!loginPassEl || !btnTogglePassword) return;
    btnTogglePassword.addEventListener('click', (e) => {
      e.preventDefault();
      const isPass = loginPassEl.getAttribute('type') === 'password';
      loginPassEl.setAttribute('type', isPass ? 'text' : 'password');
      btnTogglePassword.innerHTML = isPass ? '<i data-lucide="eye-off"></i>' : '<i data-lucide="eye"></i>';
      if (window.lucide) lucide.createIcons();
    });
  }

  function initForm(options) {
    const form = toEl('form-login');
    if (!form) return;
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const usuario = (toEl('login-user') || {}).value || '';
      const password = (toEl('login-pass') || {}).value || '';
      setStatus('Verificando...');
      try {
        if (options && typeof options.onSubmit === 'function') {
          await options.onSubmit({ usuario, password, setStatus });
        } else {
          document.dispatchEvent(new CustomEvent('fnc-login-submit', { detail: { usuario, password, setStatus } }));
        }
      } catch (err) {
        setStatus(err && err.message ? err.message : 'Error al iniciar sesión');
      }
    });
  }

  function initFncLogin(options) {
    initPremiumEmail();
    initPasswordToggle();
    initLegalModal();
    initForm(options || {});
    if (window.lucide) lucide.createIcons();
  }

  window.initFncLogin = initFncLogin;
  window.fncLoginSetStatus = setStatus;
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initFncLogin(window.FNC_LOGIN_OPTIONS || {}));
  } else {
    initFncLogin(window.FNC_LOGIN_OPTIONS || {});
  }
})();
