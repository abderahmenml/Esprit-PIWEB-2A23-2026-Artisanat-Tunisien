/**
 * admin.js - CraftLink BackOffice
 */

function validateAdminForm(formEl) {
    let isValid = true;

    formEl.querySelectorAll('.form-error').forEach((el) => (el.textContent = ''));
    formEl.querySelectorAll('.form-control').forEach((el) => el.classList.remove('is-error'));

    formEl.querySelectorAll('[data-validate]').forEach(function(input) {
        const rules = input.dataset.validate.split('|');
        const label = input.dataset.label || input.name || 'Ce champ';
        const value = input.value.trim();
        const errEl = document.getElementById(input.id + '-err');
        let fieldOk = true;
        let msg = '';

        for (const rule of rules) {
            if (!fieldOk) break;

            if (rule === 'required' && value === '') {
                msg = `Le champ "${label}" est obligatoire.`;
                fieldOk = false;
            } else if (rule === 'email') {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (value !== '' && !emailRegex.test(value)) {
                    msg = `"${label}" doit etre une adresse e-mail valide.`;
                    fieldOk = false;
                }
            } else if (rule.startsWith('min:')) {
                const min = parseInt(rule.split(':')[1], 10);
                if (value !== '' && value.length < min) {
                    msg = `"${label}" doit contenir au moins ${min} caracteres.`;
                    fieldOk = false;
                }
            } else if (rule.startsWith('max:')) {
                const max = parseInt(rule.split(':')[1], 10);
                if (value.length > max) {
                    msg = `"${label}" ne doit pas depasser ${max} caracteres.`;
                    fieldOk = false;
                }
            } else if (rule === 'alpha') {
                const alphaRegex = /^[\p{L}\s\-]+$/u;
                if (value !== '' && !alphaRegex.test(value)) {
                    msg = `"${label}" ne doit contenir que des lettres.`;
                    fieldOk = false;
                }
            } else if (rule.startsWith('matches:')) {
                const otherId = rule.split(':')[1];
                const otherEl = document.getElementById(otherId);
                if (otherEl && value !== otherEl.value) {
                    msg = `"${label}" ne correspond pas.`;
                    fieldOk = false;
                }
            } else if (rule === 'not_empty_select') {
                if (value === '' || value === '0') {
                    msg = `Veuillez selectionner "${label}".`;
                    fieldOk = false;
                }
            }
        }

        if (!fieldOk) {
            input.classList.add('is-error');
            if (errEl) errEl.textContent = msg;
            isValid = false;
        }
    });

    return isValid;
}

function showNotif(message, type = 'success') {
    const notif = document.createElement('div');
    notif.className = `flash flash-${type}`;
    notif.textContent = message;
    notif.style.cssText =
        'position:fixed;top:1rem;right:1rem;z-index:9999;min-width:220px;animation:fadeIn .3s ease;';
    document.body.appendChild(notif);
    setTimeout(() => {
        notif.style.opacity = '0';
        setTimeout(() => notif.remove(), 400);
    }, 3000);
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('form[data-validate-form]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!validateAdminForm(form)) {
                e.preventDefault();
                const firstErr = form.querySelector('.is-error');
                if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    });

    document.querySelectorAll('[data-validate]').forEach(function(input) {
        input.addEventListener('input', function() {
            this.classList.remove('is-error');
            const errEl = document.getElementById(this.id + '-err');
            if (errEl) errEl.textContent = '';
        });
    });

    const modal = document.getElementById('confirm-modal');
    if (modal) {
        let deleteHref = '';
        document.querySelectorAll('.btn-delete-trigger').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                deleteHref = this.dataset.href;
                const name = this.dataset.name || 'cet utilisateur';
                modal.querySelector('.modal-body').textContent =
                    `Etes-vous sur de vouloir supprimer ${name} ? Cette action est irreversible.`;
                modal.classList.add('open');
            });
        });

        modal.querySelector('.modal-cancel') ? .addEventListener('click', function() {
            modal.classList.remove('open');
        });

        modal.querySelector('.modal-confirm') ? .addEventListener('click', function() {
            if (deleteHref) window.location.href = deleteHref;
        });

        modal.addEventListener('click', function(e) {
            if (e.target === modal) modal.classList.remove('open');
        });
    }

    document.querySelectorAll('.status-toggle').forEach(function(sel) {
        sel.addEventListener('change', function() {
            const fd = new FormData();
            fd.append('id', this.dataset.id);
            fd.append('status', this.value);

            fetch('index.php?action=toggleStatus', { method: 'POST', body: fd })
                .then((r) => r.json())
                .then((data) => {
                    if (data.success) {
                        showNotif('Statut mis a jour avec succes.', 'success');
                    } else {
                        showNotif('Erreur : ' + data.message, 'error');
                    }
                })
                .catch(() => showNotif('Erreur reseau.', 'error'));
        });
    });

    document.querySelectorAll('.btn-block-toggle').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();

            if (this.dataset.self === '1') {
                showNotif('Vous ne pouvez pas bloquer votre propre compte.', 'error');
                return;
            }

            const fd = new FormData();
            fd.append('id', this.dataset.id);

            fetch('index.php?action=toggleBlock', { method: 'POST', body: fd })
                .then((r) => r.json())
                .then((data) => {
                    if (!data.success) {
                        showNotif('Erreur : ' + data.message, 'error');
                        return;
                    }

                    const isBlocked = !!Number(data.is_blocked);
                    this.textContent = isBlocked ? 'Debloquer' : 'Bloquer';
                    this.title = isBlocked ? 'Debloquer' : 'Bloquer';
                    this.classList.remove('btn-view', 'btn-delete');
                    this.classList.add(isBlocked ? 'btn-view' : 'btn-delete');

                    const badge = this.closest('tr') ? .querySelector('.user-block-state');
                    if (badge) {
                        badge.textContent = isBlocked ? 'Bloque' : 'Debloque';
                        badge.classList.remove('badge-danger', 'badge-success');
                        badge.classList.add(isBlocked ? 'badge-danger' : 'badge-success');
                    }

                    showNotif(data.message || 'Blocage mis a jour.', 'success');
                })
                .catch(() => showNotif('Erreur reseau.', 'error'));
        });
    });

    const searchInput = document.getElementById('live-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(function(row) {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }

    document.querySelectorAll('.flash').forEach(function(el) {
        setTimeout(() => {
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 400);
        }, 4000);
    });
});