/**
 * admin.js — CraftLink BackOffice
 * Validation côté client sans HTML5 (required/pattern interdits)
 * + interactions dynamiques
 */

// ── Validation côté client (sans HTML5) ─────────────────────────────────────

/**
 * Valide tous les champs marqués [data-validate]
 * Retourne true si le formulaire est valide
 */
function validateAdminForm(formEl) {
  let isValid = true;

  // Effacer les erreurs précédentes
  formEl.querySelectorAll('.form-error').forEach(el => el.textContent = '');
  formEl.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-error'));

  formEl.querySelectorAll('[data-validate]').forEach(function(input) {
    const rules  = input.dataset.validate.split('|');
    const label  = input.dataset.label || input.name || 'Ce champ';
    const value  = input.value.trim();
    const errEl  = document.getElementById(input.id + '-err');
    let   fieldOk = true;
    let   msg     = '';

    for (const rule of rules) {
      if (!fieldOk) break;

      if (rule === 'required' && value === '') {
        msg = `Le champ « ${label} » est obligatoire.`;
        fieldOk = false;
      }

      else if (rule === 'email') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (value !== '' && !emailRegex.test(value)) {
          msg = `« ${label} » doit être une adresse e-mail valide.`;
          fieldOk = false;
        }
      }

      else if (rule.startsWith('min:')) {
        const min = parseInt(rule.split(':')[1]);
        if (value !== '' && value.length < min) {
          msg = `« ${label} » doit contenir au moins ${min} caractères.`;
          fieldOk = false;
        }
      }

      else if (rule.startsWith('max:')) {
        const max = parseInt(rule.split(':')[1]);
        if (value.length > max) {
          msg = `« ${label} » ne doit pas dépasser ${max} caractères.`;
          fieldOk = false;
        }
      }

      else if (rule === 'alpha') {
        const alphaRegex = /^[\p{L}\s\-]+$/u;
        if (value !== '' && !alphaRegex.test(value)) {
          msg = `« ${label} » ne doit contenir que des lettres.`;
          fieldOk = false;
        }
      }

      else if (rule.startsWith('matches:')) {
        const otherId = rule.split(':')[1];
        const otherEl = document.getElementById(otherId);
        if (otherEl && value !== otherEl.value) {
          msg = `« ${label} » ne correspond pas.`;
          fieldOk = false;
        }
      }

      else if (rule === 'not_empty_select') {
        if (value === '' || value === '0') {
          msg = `Veuillez sélectionner « ${label} ».`;
          fieldOk = false;
        }
      }
    }

    if (!fieldOk) {
      input.classList.add('is-error');
      if (errEl) errEl.textContent = msg;
      isValid = false;
      if (isValid === false && document.activeElement !== input) {
        // focus sur le premier champ en erreur
      }
    }
  });

  return isValid;
}

// ── Attacher la validation à tous les formulaires admin ──────────────────────

document.addEventListener('DOMContentLoaded', function () {

  // Formulaires avec data-ajax="false" : validation classique
  document.querySelectorAll('form[data-validate-form]').forEach(function(form) {
    form.addEventListener('submit', function(e) {
      if (!validateAdminForm(form)) {
        e.preventDefault();
        // Scroller vers la première erreur
        const firstErr = form.querySelector('.is-error');
        if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  });

  // Effacer l'erreur dès que l'utilisateur retape
  document.querySelectorAll('[data-validate]').forEach(function(input) {
    input.addEventListener('input', function() {
      this.classList.remove('is-error');
      const errEl = document.getElementById(this.id + '-err');
      if (errEl) errEl.textContent = '';
    });
  });

  // ── Modal de confirmation suppression ─────────────────────────────────────
  const modal = document.getElementById('confirm-modal');
  if (modal) {
    let deleteHref = '';
    document.querySelectorAll('.btn-delete-trigger').forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        deleteHref = this.dataset.href;
        const name = this.dataset.name || 'cet utilisateur';
        modal.querySelector('.modal-body').textContent =
          `Êtes-vous sûr de vouloir supprimer ${name} ? Cette action est irréversible.`;
        modal.classList.add('open');
      });
    });

    modal.querySelector('.modal-cancel')?.addEventListener('click', function() {
      modal.classList.remove('open');
    });

    modal.querySelector('.modal-confirm')?.addEventListener('click', function() {
      if (deleteHref) window.location.href = deleteHref;
    });

    modal.addEventListener('click', function(e) {
      if (e.target === modal) modal.classList.remove('open');
    });
  }

  // ── Toggle status AJAX ────────────────────────────────────────────────────
  document.querySelectorAll('.status-toggle').forEach(function(sel) {
    sel.addEventListener('change', function() {
      const id     = this.dataset.id;
      const status = this.value;
      const fd     = new FormData();
      fd.append('id', id);
      fd.append('status', status);

      fetch('index.php?action=toggleStatus', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            showNotif('Statut mis à jour avec succès.', 'success');
          } else {
            showNotif('Erreur : ' + data.message, 'error');
          }
        })
        .catch(() => showNotif('Erreur réseau.', 'error'));
    });
  });

  // ── Recherche en temps réel ───────────────────────────────────────────────
  const searchInput = document.getElementById('live-search');
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      const q = this.value.toLowerCase();
      document.querySelectorAll('tbody tr').forEach(function(row) {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
      });
    });
  }

  // ── Auto-hide flash ───────────────────────────────────────────────────────
  document.querySelectorAll('.flash').forEach(function(el) {
    setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 400); }, 4000);
  });
});

// ── Notification toast ──────────────────────────────────────────────────────
function showNotif(message, type = 'success') {
  const notif = document.createElement('div');
  notif.className = `flash flash-${type}`;
  notif.textContent = message;
  notif.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:9999;min-width:200px;animation:fadeIn .3s ease;';
  document.body.appendChild(notif);
  setTimeout(() => { notif.style.opacity = '0'; setTimeout(() => notif.remove(), 400); }, 3000);
}
