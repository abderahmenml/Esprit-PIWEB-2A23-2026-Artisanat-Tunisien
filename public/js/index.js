// index.js - Version propre
function getAppBasePath() {
    const path = window.location.pathname.replace(/\/$/, '');
    const parts = path.split('/').filter(Boolean);
    const knownRoutes = ['auth', 'dashboard', 'profil', 'admin', 'index.php'];
    const routeIndex = parts.findIndex((part) => knownRoutes.includes(part));

    if (parts.length === 0) {
        return '';
    }

    if (routeIndex === -1) {
        return '/' + parts.join('/');
    }

    if (routeIndex === 0) {
        return '';
    }

    return '/' + parts.slice(0, routeIndex).join('/');
}

function appUrl(route) {
    const base = getAppBasePath();
    const normalized = route.startsWith('/') ? route : '/' + route;
    return base + normalized;
}

function showSection(id) {
    if (id === 'profil') {
        window.location.href = appUrl('/profil');
    } else if (id === 'accueil') {
        window.location.href = appUrl('/dashboard');
    } else {
        alert('Page en construction : ' + id);
    }
}

function openModal(id) {
    const el = document.getElementById('modal-' + id);
    if (el) {
        el.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(id) {
    const el = document.getElementById('modal-' + id);
    if (el) {
        el.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function switchTab(name) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    const activeTab = document.querySelector(`[onclick="switchTab('${name}')"]`);
    const activePanel = document.getElementById('panel-' + name);
    if (activeTab) activeTab.classList.add('active');
    if (activePanel) activePanel.classList.add('active');
    togglePortfolioManagementSections(name);
}

function togglePortfolioManagementSections(activeTabName) {
    const shouldShow = activeTabName === 'portfolio';
    document.querySelectorAll('.portfolio-gestion').forEach((section) => {
        section.style.display = shouldShow ? '' : 'none';
    });
}

function handleLogout() {
    if (confirm('Voulez-vous vraiment vous déconnecter ?')) {
        window.location.href = appUrl('/auth/logout');
    }
}

function showAppNotification(message, type = 'success') {
    let notif = document.getElementById('app-floating-notification');
    if (!notif) {
        notif = document.createElement('div');
        notif.id = 'app-floating-notification';
        notif.style.position = 'fixed';
        notif.style.top = '85px';
        notif.style.right = '20px';
        notif.style.zIndex = '9999';
        notif.style.padding = '12px 16px';
        notif.style.borderRadius = '10px';
        notif.style.fontWeight = '700';
        notif.style.fontSize = '14px';
        notif.style.boxShadow = '0 8px 20px rgba(0,0,0,0.12)';
        notif.style.transition = 'opacity 0.2s ease';
        notif.style.opacity = '0';
        document.body.appendChild(notif);
    }

    if (type === 'success') {
        notif.style.background = '#e8f5e9';
        notif.style.color = '#2e7d32';
        notif.style.border = '1px solid #c8e6c9';
    } else {
        notif.style.background = '#ffebee';
        notif.style.color = '#b71c1c';
        notif.style.border = '1px solid #ffcdd2';
    }

    notif.textContent = message;
    notif.style.opacity = '1';

    clearTimeout(showAppNotification._timer);
    showAppNotification._timer = setTimeout(() => {
        notif.style.opacity = '0';
    }, 2800);
}

function textLength(value) {
    return Array.from(String(value || '')).length;
}

function isValidDateInput(value) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return false;
    }

    const date = new Date(`${value}T00:00:00`);
    return !Number.isNaN(date.getTime()) && date.toISOString().slice(0, 10) === value;
}

function getFormValue(form, name) {
    const field = form.elements[name];
    if (!field) {
        return '';
    }
    return String(field.value || '').trim();
}

function hasLetter(value) {
    return /[A-Za-zÀ-ÖØ-öø-ÿ]/.test(String(value || ''));
}

function isValidSkillName(value, minLength = 2, maxLength = 100) {
    const text = String(value || '').trim();
    if (textLength(text) < minLength || textLength(text) > maxLength) {
        return false;
    }

    if (!hasLetter(text)) {
        return false;
    }

    return /^[A-Za-zÀ-ÖØ-öø-ÿ0-9\s'’().,+&\/-]+$/.test(text);
}

function isValidSkillDescription(value, maxLength = 500) {
    const text = String(value || '').trim();
    if (textLength(text) > maxLength) {
        return false;
    }

    if (!text) {
        return true;
    }

    return /^[A-Za-zÀ-ÖØ-öø-ÿ0-9\s'’().,!?+&\/:-]+$/.test(text);
}

function validateProfilWriteForm(form) {
    if (!form || !form.action) {
        return '';
    }

    const action = form.action;

    if (action.includes('/profil/addCompetence')) {
        const nom = getFormValue(form, 'nom');
        const description = getFormValue(form, 'description');
        if (!nom) {
            return 'Le champ Nom est obligatoire.';
        }
        if (!isValidSkillName(nom, 2, 80)) {
            return 'Nom de competence invalide. Utilisez uniquement lettres, chiffres et ponctuation simple.';
        }
        if (!isValidSkillDescription(description, 500)) {
            return 'Description invalide. Caractere non autorise ou longueur depassee (500 max).';
        }
        return '';
    }

    if (action.includes('/profil/addCertification')) {
        const nom = getFormValue(form, 'nom');
        if (!nom) {
            return 'Le champ Nom de la certification est obligatoire.';
        }
        if (!isValidSkillName(nom, 2, 100)) {
            return 'Nom de certification invalide. Utilisez uniquement lettres, chiffres et ponctuation simple.';
        }
        return '';
    }

    if (action.includes('/profil/addExperience')) {
        const poste = getFormValue(form, 'poste');
        const entreprise = getFormValue(form, 'entreprise');
        const dateDebut = getFormValue(form, 'date_debut');
        const dateFin = getFormValue(form, 'date_fin');
        const description = getFormValue(form, 'description');

        if (!poste) {
            return 'Le champ Poste est obligatoire.';
        }
        if (textLength(poste) < 2 || textLength(poste) > 100) {
            return 'Le poste doit contenir entre 2 et 100 caracteres.';
        }
        if (textLength(entreprise) > 120) {
            return 'Le nom de l\'entreprise ne doit pas depasser 120 caracteres.';
        }
        if (!dateDebut) {
            return 'La date de debut est obligatoire.';
        }
        if (!isValidDateInput(dateDebut)) {
            return 'La date de debut est invalide.';
        }
        if (dateFin && !isValidDateInput(dateFin)) {
            return 'La date de fin est invalide.';
        }
        if (dateFin && dateFin < dateDebut) {
            return 'La date de fin doit etre apres la date de debut.';
        }
        if (textLength(description) > 1000) {
            return 'La description ne doit pas depasser 1000 caracteres.';
        }
        return '';
    }

    if (action.includes('/profil/addBio') || action.includes('/profil/updateBio')) {
        const bio = getFormValue(form, 'bio');
        if (textLength(bio) < 2 || textLength(bio) > 2000) {
            return 'La bio doit contenir entre 2 et 2000 caracteres.';
        }
        return '';
    }

    if (action.includes('/profil/update') && !action.includes('/profil/updateExperience') && !action.includes('/profil/updateCompetence') && !action.includes('/profil/updateCertification')) {
        const nameRegex = /^[A-Za-zÀ-ÖØ-öø-ÿ' -]{2,60}$/;
        const phoneRegex = /^\+?[0-9][0-9\s().-]{7,19}$/;

        const nom = getFormValue(form, 'nom');
        const prenom = getFormValue(form, 'prenom');
        const email = getFormValue(form, 'email');
        const telephone = getFormValue(form, 'telephone');
        const specialite = getFormValue(form, 'specialite');
        const ville = getFormValue(form, 'ville');
        const disponibilite = getFormValue(form, 'disponibilite').toLowerCase();
        const disponibiliteHoraire = getFormValue(form, 'disponibilite_horaire');
        const disponibiliteMessage = getFormValue(form, 'disponibilite_message');

        if (!nameRegex.test(nom) || !nameRegex.test(prenom)) {
            return 'Nom ou prenom invalide.';
        }
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            return 'Adresse email invalide.';
        }
        if (telephone) {
            const digits = telephone.replace(/\D+/g, '');
            if (!phoneRegex.test(telephone) || digits.length < 8 || digits.length > 15) {
                return 'Numero de telephone invalide.';
            }
        }
        if (textLength(specialite) > 120 || textLength(ville) > 120) {
            return 'Specialite ou ville trop longue.';
        }
        if (disponibilite && !['disponible', 'occupe', 'indisponible'].includes(disponibilite)) {
            return 'Choisissez une disponibilite valide.';
        }
        if (textLength(disponibiliteHoraire) > 120) {
            return 'La plage de disponibilite ne doit pas depasser 120 caracteres.';
        }
        if (textLength(disponibiliteMessage) > 120) {
            return 'Le message de disponibilite ne doit pas depasser 120 caracteres.';
        }
        return '';
    }

    if (action.includes('/profil/addPortfolioFile')) {
        const titre = getFormValue(form, 'titre');
        const realisationDefault = getFormValue(form, 'realisation_default');
        const realisationCustom = getFormValue(form, 'realisation_custom');
        const fileInput = form.querySelector('input[name="portfolio_file"]');
        if (textLength(titre) > 120) {
            return 'Le titre ne doit pas depasser 120 caracteres.';
        }
        if (!realisationDefault) {
            return 'Veuillez choisir une realisation.';
        }
        if (realisationDefault === '__custom__') {
            if (textLength(realisationCustom) < 2 || textLength(realisationCustom) > 120) {
                return 'La nouvelle realisation doit contenir entre 2 et 120 caracteres.';
            }
        }

        if (!fileInput || !fileInput.files || !fileInput.files[0]) {
            return 'Le fichier PDF est obligatoire.';
        }

        const file = fileInput.files[0];
        if (!/\.pdf$/i.test(file.name)) {
            return 'Le fichier doit etre en PDF.';
        }
        if (file.size > 8 * 1024 * 1024) {
            return 'Le fichier depasse 8 Mo.';
        }
        return '';
    }

    return '';
}

async function postFormAsText(form) {
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new FormData(form)
        });

        const message = await response.text();
        return {
            ok: Boolean(response.ok),
            message,
            response
        };
    } catch (error) {
        return {
            ok: false,
            message: null,
            response: null,
            error
        };
    }
}

async function submitAjaxAddForm(form) {
    const isBioForm = isBioAjaxForm(form);
    const validationError = validateProfilWriteForm(form);
    if (validationError) {
        showAppNotification(validationError, isBioForm ? 'success' : 'error');
        return {
            ok: false,
            payload: { success: false, message: validationError },
            response: null
        };
    }

    const submitButton = form.querySelector('button[type="submit"]');
    const initialButtonText = submitButton ? submitButton.textContent : '';

    if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Envoi...';
    }

    const result = await postFormAsText(form);

    if (result.ok) {
        showAppNotification(result.message || 'Ajout reussi', 'success');
    } else {
        if (result.error) {
            showAppNotification('Erreur reseau, reessayez.', isBioForm ? 'success' : 'error');
        } else {
            const errorMessage = result.message || 'Erreur lors de l\'ajout';
            showAppNotification(errorMessage, isBioForm ? 'success' : 'error');
        }
    }

    if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = initialButtonText;
    }

    return result;
}

function getExperienceListBody() {
    return document.getElementById('experiences-list');
}

function isExperienceAddForm(form) {
    return form.action.includes('/profil/addExperience');
}

function togglePortfolioRealisationCustomInput(form) {
    if (!form) {
        return;
    }

    const select = form.querySelector('[data-realisation-select]');
    const customInput = form.querySelector('[data-realisation-custom]');
    if (!select || !customInput) {
        return;
    }

    const isCustom = select.value === '__custom__';
    customInput.style.display = isCustom ? 'block' : 'none';
    customInput.required = false;

    if (!isCustom) {
        customInput.value = '';
    }
}

function bindPortfolioRealisationSelector() {
    const forms = document.querySelectorAll('form[action*="/profil/addPortfolioFile"]');

    forms.forEach((form) => {
        const select = form.querySelector('[data-realisation-select]');
        if (!select || select.dataset.realisationBound === '1') {
            return;
        }

        select.dataset.realisationBound = '1';
        togglePortfolioRealisationCustomInput(form);
        select.addEventListener('change', () => {
            togglePortfolioRealisationCustomInput(form);
        });
    });
}

function createHiddenInput(name, value) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value || '';
    return input;
}

function syncExperienceEmptyState() {
    const tbody = getExperienceListBody();
    if (!tbody) {
        return;
    }

    const hasDataRows = Boolean(tbody.querySelector('tr[data-id]'));
    let emptyRow = tbody.querySelector('.empty-experience-row');

    if (hasDataRows && emptyRow) {
        emptyRow.remove();
        return;
    }

    if (!hasDataRows && !emptyRow) {
        emptyRow = document.createElement('tr');
        emptyRow.className = 'empty-experience-row';

        const emptyCell = document.createElement('td');
        emptyCell.colSpan = 5;
        emptyCell.style.textAlign = 'center';
        emptyCell.style.padding = '1rem';
        emptyCell.textContent = 'Aucune expérience ajoutée';

        emptyRow.appendChild(emptyCell);
        tbody.appendChild(emptyRow);
    }
}

function buildExperienceRow(experience) {
    const id = Number.parseInt(String(experience.id_experience || 0), 10);
    if (!id) {
        return null;
    }

    const poste = String(experience.poste || '');
    const entreprise = String(experience.entreprise || '');
    const dateDebut = String(experience.date_debut || '');
    const dateFin = String(experience.date_fin || '');
    const description = String(experience.description || '');

    const row = document.createElement('tr');
    row.dataset.id = String(id);

    const posteCell = document.createElement('td');
    posteCell.className = 'exp-poste';
    posteCell.textContent = poste;

    const entrepriseCell = document.createElement('td');
    entrepriseCell.className = 'exp-entreprise';
    entrepriseCell.textContent = entreprise;

    const periodeCell = document.createElement('td');
    periodeCell.className = 'exp-periode';
    periodeCell.textContent = formatPeriode(dateDebut, dateFin);

    const descriptionCell = document.createElement('td');
    descriptionCell.className = 'exp-description';
    descriptionCell.textContent = description;

    const actionsCell = document.createElement('td');
    actionsCell.className = 'exp-actions';

    const editButton = document.createElement('button');
    editButton.type = 'button';
    editButton.className = 'modif-btn';
    editButton.title = 'Modifier';
    editButton.textContent = '✏️';
    editButton.addEventListener('click', () => editExperience(id));

    const updateForm = document.createElement('form');
    updateForm.id = `edit-exp-${id}`;
    updateForm.action = appUrl('/profil/updateExperience');
    updateForm.method = 'post';
    updateForm.style.display = 'none';
    updateForm.appendChild(createHiddenInput('id', String(id)));
    updateForm.appendChild(createHiddenInput('poste', poste));
    updateForm.appendChild(createHiddenInput('entreprise', entreprise));
    updateForm.appendChild(createHiddenInput('date_debut', dateDebut));
    updateForm.appendChild(createHiddenInput('date_fin', dateFin));
    updateForm.appendChild(createHiddenInput('description', description));

    const deleteForm = document.createElement('form');
    deleteForm.action = appUrl('/profil/deleteExperience');
    deleteForm.method = 'post';
    deleteForm.style.display = 'inline';
    deleteForm.setAttribute('onsubmit', "return confirm('Supprimer cette expérience ?');");
    deleteForm.appendChild(createHiddenInput('id', String(id)));

    const deleteButton = document.createElement('button');
    deleteButton.className = 'suppr-btn';
    deleteButton.type = 'submit';
    deleteButton.textContent = '🗑️';
    deleteForm.appendChild(deleteButton);

    actionsCell.appendChild(editButton);
    actionsCell.appendChild(updateForm);
    actionsCell.appendChild(deleteForm);

    row.appendChild(posteCell);
    row.appendChild(entrepriseCell);
    row.appendChild(periodeCell);
    row.appendChild(descriptionCell);
    row.appendChild(actionsCell);

    return row;
}

function appendExperienceRow(experience) {
    const tbody = getExperienceListBody();
    if (!tbody) {
        return;
    }

    const row = buildExperienceRow(experience);
    if (!row) {
        return;
    }

    tbody.prepend(row);
    syncExperienceEmptyState();
    bindAjaxExperienceDeleteForms(row);
    applyExperienceTableTools();
}

function bindAjaxExperienceDeleteForms(scope = document) {
    const forms = scope.querySelectorAll
        ? scope.querySelectorAll('form[action*="/profil/deleteExperience"]')
        : [];

    forms.forEach((form) => {
        if (form.dataset.ajaxDeleteBound === '1') {
            return;
        }
        form.dataset.ajaxDeleteBound = '1';

        form.addEventListener('submit', async (event) => {
            if (event.defaultPrevented) {
                return;
            }

            event.preventDefault();
            const result = await postFormAsText(form);

            if (result.ok) {
                const row = form.closest('tr');
                if (row) {
                    row.remove();
                    syncExperienceEmptyState();
                }
                applyExperienceTableTools();
                showAppNotification(result.message || 'Experience supprimee', 'success');
                return;
            }

            const errorMessage = result.error
                ? 'Erreur reseau, reessayez.'
                : (result.message || 'Suppression impossible');
            showAppNotification(errorMessage, 'error');
        });
    });
}

function isBioAjaxForm(form) {
    const action = form && form.action ? form.action : '';
    return action.includes('/profil/addBio') || action.includes('/profil/updateBio') || form.dataset.ajaxBio === 'true';
}

function setBioDisplayText(bioText) {
    const bioDisplay = document.querySelector('[data-bio-display="true"]');
    if (!bioDisplay) {
        return;
    }

    const safeText = String(bioText || '').trim();
    if (!safeText) {
        bioDisplay.textContent = 'Aucune biographie disponible.';
        bioDisplay.style.color = '#777';
        bioDisplay.style.whiteSpace = '';
        return;
    }

    bioDisplay.textContent = safeText;
    bioDisplay.style.color = '';
    bioDisplay.style.whiteSpace = 'pre-line';
}

function switchBioFormToUpdate(form) {
    if (!form) {
        return;
    }

    form.action = appUrl('/profil/updateBio');
    const submitButton = form.querySelector('[data-bio-submit="true"]');
    if (submitButton) {
        submitButton.textContent = 'Modifier une bio existante';
    }
}

function bindAjaxAddForms() {
    const forms = document.querySelectorAll('form[data-ajax-add="true"]');
    forms.forEach((form) => {
        form.noValidate = true;

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const result = await submitAjaxAddForm(form);

            if (!result.ok) {
                return;
            }

            if (isExperienceAddForm(form) || isBioAjaxForm(form)) {
                window.location.reload();
                return;
            }

            form.reset();
        });
    });
}

function bindGeneralWriteFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach((form) => {
        if (form.dataset.writeValidationBound === '1') {
            return;
        }

        if (form.style.display === 'none') {
            return;
        }

        const action = form.action || '';
        if (!action.includes('/profil/')) {
            return;
        }

        form.noValidate = true;

        if (form.dataset.ajaxAdd === 'true') {
            return;
        }

        form.dataset.writeValidationBound = '1';
        form.addEventListener('submit', (event) => {
            const error = validateProfilWriteForm(form);
            if (!error) {
                return;
            }

            event.preventDefault();
            showAppNotification(error, 'error');
        });
    });
}

function renderCompletionBreakdownText(breakdown) {
    const safe = breakdown && typeof breakdown === 'object' ? breakdown : {};
    const descriptionDone = Boolean(safe.description && safe.description.completed);
    const competencesDone = Boolean(safe.competences && safe.competences.completed);
    const portfolioDone = Boolean(safe.portfolio && safe.portfolio.completed);

    return `Description ${descriptionDone ? '✅' : '❌'} (+20%) · Competences ${competencesDone ? '✅' : '❌'} (+30%) · Portfolio ${portfolioDone ? '✅' : '❌'} (+50%)`;
}

function bindCompletionRecalculateForm() {
    const form = document.querySelector('form[data-completion-form="true"]');
    if (!form || form.dataset.completionBound === '1') {
        return;
    }
    form.dataset.completionBound = '1';

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const submitButton = form.querySelector('[data-completion-submit="true"]');
        const completionValue = document.querySelector('[data-completion-value="true"]');
        const completionFill = document.querySelector('[data-completion-fill="true"]');
        const completionDetail = document.querySelector('[data-completion-detail="true"]');

        const initialLabel = submitButton ? submitButton.textContent : '';
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Calcul en cours...';
        }

        const result = await postFormAsText(form);

        if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = initialLabel;
        }

        if (!result.ok) {
            const message = result.error
                ? 'Erreur reseau, reessayez.'
                : (result.message || 'Recalcul impossible');
            showAppNotification(message, 'error');
            return;
        }

        showAppNotification(result.message || 'Progression recalculee', 'success');
        window.location.reload();
    });
}

function parseJsonArraySafe() {
    return [];
}

function normalizeAvailabilityStatus(value) {
    const status = String(value || '').toLowerCase();
    if (status === 'occupe' || status === 'indisponible') {
        return status;
    }
    return 'disponible';
}

function getAvailabilityStatusLabel(status) {
    const map = {
        disponible: 'Disponible',
        occupe: 'Absent momentanement',
        indisponible: 'Indisponible'
    };
    return map[normalizeAvailabilityStatus(status)] || map.disponible;
}

function getAvailabilityFallbackSummary(status) {
    const map = {
        disponible: 'Lun - Sam · 8h-17h',
        occupe: 'Disponible plus tard dans la journee',
        indisponible: 'Temporairement indisponible'
    };
    return map[normalizeAvailabilityStatus(status)] || map.disponible;
}

function normalizeSlotDays(days) {
    const order = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
    const uniqueDays = new Set(Array.isArray(days) ? days.map((d) => String(d || '').trim()) : []);
    return order.filter((day) => uniqueDays.has(day));
}

function summarizeAvailabilitySlots(slots, status) {
    if (!Array.isArray(slots) || slots.length === 0) {
        return getAvailabilityFallbackSummary(status);
    }

    const first = slots[0];
    const days = Array.isArray(first.days) ? first.days : [];
    const dayLabel = days.length ? days.join(', ') : 'Jours definis';
    const start = String(first.start || '').trim();
    const end = String(first.end || '').trim();
    let summary = `${dayLabel} · ${start}-${end}`;

    if (slots.length > 1) {
        summary += ` (+${slots.length - 1})`;
    }

    if (textLength(summary) > 120) {
        summary = `${dayLabel} · ${start}-${end}`;
    }

    if (textLength(summary) > 120) {
        summary = summary.slice(0, 120);
    }

    return summary;
}

function createAvailabilityTag(text, onRemove) {
    const tag = document.createElement('span');
    tag.className = 'availability-tag';

    const label = document.createElement('span');
    label.textContent = text;

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'availability-tag-remove';
    removeButton.textContent = '×';
    removeButton.setAttribute('aria-label', 'Supprimer');
    removeButton.addEventListener('click', onRemove);

    tag.appendChild(label);
    tag.appendChild(removeButton);
    return tag;
}

function initAvailabilityDesigner() {
    const designers = document.querySelectorAll('[data-availability-designer="true"]');
    if (!designers.length) {
        return;
    }

    designers.forEach((designer) => {
        if (designer.dataset.designerBound === '1') {
            return;
        }
        designer.dataset.designerBound = '1';

        const form = designer.closest('form');
        if (!form) {
            return;
        }

        const hiddenStatus = form.querySelector('input[name="disponibilite"]');
        const hiddenSummary = form.querySelector('input[name="disponibilite_horaire"]');
        const hiddenMessage = form.querySelector('input[name="disponibilite_message"]');
        const hiddenSlots = form.querySelector('input[name="disponibilite_slots"]');
        const hiddenExceptions = form.querySelector('input[name="disponibilite_exceptions"]');
        const hiddenConges = form.querySelector('input[name="disponibilite_conges"]');

        if (!hiddenStatus || !hiddenSummary || !hiddenMessage || !hiddenSlots || !hiddenExceptions || !hiddenConges) {
            return;
        }

        let localId = 1;
        const nextId = () => `availability-${Date.now()}-${localId++}`;

        const parseTime = (value) => {
            const cleaned = String(value || '').trim();
            return /^\d{2}:\d{2}$/.test(cleaned) ? cleaned : '';
        };

        const parseDate = (value) => {
            const cleaned = String(value || '').trim();
            return /^\d{4}-\d{2}-\d{2}$/.test(cleaned) ? cleaned : '';
        };

        const normalizeSlot = (item) => {
            if (!item || typeof item !== 'object') {
                return null;
            }

            const days = normalizeSlotDays(item.days);
            const start = parseTime(item.start);
            const end = parseTime(item.end);

            if (!days.length || !start || !end || end <= start) {
                return null;
            }

            return {
                id: String(item.id || nextId()),
                days,
                start,
                end
            };
        };

        const normalizeException = (item) => {
            if (!item || typeof item !== 'object') {
                return null;
            }

            const date = parseDate(item.date);
            const type = String(item.type || '').toLowerCase();
            if (!date || !['ferme', 'reduits', 'speciaux'].includes(type)) {
                return null;
            }

            return {
                id: String(item.id || nextId()),
                date,
                type
            };
        };

        const normalizeConge = (item) => {
            if (!item || typeof item !== 'object') {
                return null;
            }

            const start = parseDate(item.start);
            const end = parseDate(item.end);
            if (!start || !end || end < start) {
                return null;
            }

            return {
                id: String(item.id || nextId()),
                start,
                end
            };
        };

        const state = {
            status: normalizeAvailabilityStatus(hiddenStatus.value || designer.dataset.initialStatus || 'disponible'),
            message: String(hiddenMessage.value || designer.dataset.initialMessage || '').trim(),
            slots: []
                .map(normalizeSlot)
                .filter(Boolean),
            exceptions: []
                .map(normalizeException)
                .filter(Boolean),
            conges: []
                .map(normalizeConge)
                .filter(Boolean)
        };

        const tabButtons = Array.from(designer.querySelectorAll('[data-availability-tab-btn]'));
        const tabPanels = Array.from(designer.querySelectorAll('[data-availability-tab-panel]'));

        const previewBadge = designer.querySelector('[data-availability-preview-badge="true"]');
        const previewDot = designer.querySelector('[data-availability-preview-dot="true"]');
        const previewLabel = designer.querySelector('[data-availability-preview-label="true"]');
        const previewHours = designer.querySelector('[data-availability-preview-hours="true"]');
        const previewMessage = designer.querySelector('[data-availability-preview-message="true"]');

        const dayChecks = Array.from(designer.querySelectorAll('[data-slot-day="true"]'));
        const slotStartInput = designer.querySelector('[data-slot-start="true"]');
        const slotEndInput = designer.querySelector('[data-slot-end="true"]');
        const addSlotButton = designer.querySelector('[data-slot-add="true"]');
        const slotTagsWrap = designer.querySelector('[data-slot-tags="true"]');

        const statusButtons = Array.from(designer.querySelectorAll('[data-status-value]'));
        const messageInput = designer.querySelector('[data-status-message="true"]');

        const exceptionDateInput = designer.querySelector('[data-exception-date="true"]');
        const exceptionTypeInput = designer.querySelector('[data-exception-type="true"]');
        const addExceptionButton = designer.querySelector('[data-exception-add="true"]');
        const congesStartInput = designer.querySelector('[data-conges-start="true"]');
        const congesEndInput = designer.querySelector('[data-conges-end="true"]');
        const addCongesButton = designer.querySelector('[data-conges-add="true"]');
        const exceptionTagsWrap = designer.querySelector('[data-exception-tags="true"]');

        const visitorRow = document.querySelector('[data-availability-visitor="true"]');
        const visitorDot = document.querySelector('[data-visitor-dot="true"]');
        const visitorLabel = document.querySelector('[data-visitor-label="true"]');
        const visitorHours = document.querySelector('[data-visitor-hours="true"]');
        const visitorMessage = document.querySelector('[data-visitor-message="true"]');

        const setActiveTab = (name) => {
            tabButtons.forEach((btn) => {
                const isActive = btn.dataset.availabilityTabBtn === name;
                btn.classList.toggle('is-active', isActive);
            });
            tabPanels.forEach((panel) => {
                const isActive = panel.dataset.availabilityTabPanel === name;
                panel.classList.toggle('is-active', isActive);
            });
        };

        const syncDayChips = () => {
            dayChecks.forEach((checkbox) => {
                if (!checkbox.parentElement) {
                    return;
                }
                checkbox.parentElement.classList.toggle('is-checked', checkbox.checked);
            });
        };

        const renderSlots = () => {
            if (!slotTagsWrap) {
                return;
            }

            slotTagsWrap.innerHTML = '';
            if (!state.slots.length) {
                const empty = document.createElement('span');
                empty.className = 'availability-empty-tag';
                empty.textContent = 'Aucun creneau ajoute';
                slotTagsWrap.appendChild(empty);
                return;
            }

            state.slots.forEach((slot) => {
                const slotText = `${slot.days.join(', ')} · ${slot.start}-${slot.end}`;
                const tag = createAvailabilityTag(slotText, () => {
                    state.slots = state.slots.filter((item) => item.id !== slot.id);
                    renderAll();
                });
                slotTagsWrap.appendChild(tag);
            });
        };

        const renderExceptions = () => {
            if (!exceptionTagsWrap) {
                return;
            }

            const labels = {
                ferme: 'Ferme',
                reduits: 'Horaires reduits',
                speciaux: 'Horaires speciaux'
            };

            exceptionTagsWrap.innerHTML = '';
            const rows = [];

            state.exceptions.forEach((item) => {
                rows.push({
                    id: item.id,
                    text: `${item.date} · ${labels[item.type] || item.type}`,
                    remove: () => {
                        state.exceptions = state.exceptions.filter((entry) => entry.id !== item.id);
                        renderAll();
                    }
                });
            });

            state.conges.forEach((item) => {
                rows.push({
                    id: item.id,
                    text: `Conges ${item.start} -> ${item.end}`,
                    remove: () => {
                        state.conges = state.conges.filter((entry) => entry.id !== item.id);
                        renderAll();
                    }
                });
            });

            if (!rows.length) {
                const empty = document.createElement('span');
                empty.className = 'availability-empty-tag';
                empty.textContent = 'Aucune exception';
                exceptionTagsWrap.appendChild(empty);
                return;
            }

            rows.forEach((row) => {
                const tag = createAvailabilityTag(row.text, row.remove);
                exceptionTagsWrap.appendChild(tag);
            });
        };

        const syncStatusButtons = () => {
            statusButtons.forEach((button) => {
                const buttonStatus = normalizeAvailabilityStatus(button.dataset.statusValue || '');
                button.classList.toggle('is-selected', buttonStatus === state.status);
            });
        };

        const syncPreview = () => {
            const status = normalizeAvailabilityStatus(state.status);
            const label = getAvailabilityStatusLabel(status);
            const summary = summarizeAvailabilitySlots(state.slots, status);
            const message = String(state.message || '').trim();

            if (previewBadge) {
                ['disponible', 'occupe', 'indisponible'].forEach((value) => {
                    previewBadge.classList.remove(`availability-preview-badge-${value}`);
                });
                previewBadge.classList.add(`availability-preview-badge-${status}`);
            }

            if (previewDot) {
                ['disponible', 'occupe', 'indisponible'].forEach((value) => {
                    previewDot.classList.remove(`availability-preview-dot-${value}`);
                });
                previewDot.classList.add(`availability-preview-dot-${status}`);
            }

            if (previewLabel) {
                previewLabel.textContent = label;
            }
            if (previewHours) {
                previewHours.textContent = summary;
            }
            if (previewMessage) {
                previewMessage.textContent = message;
                previewMessage.style.display = message ? 'block' : 'none';
            }

            if (visitorRow && visitorDot && visitorLabel && visitorHours) {
                ['disponible', 'occupe', 'indisponible'].forEach((value) => {
                    visitorRow.classList.remove(`availability-${value}`);
                    visitorDot.classList.remove(`avail-dot-${value}`);
                    visitorLabel.classList.remove(`avail-text-${value}`);
                });

                visitorRow.classList.add(`availability-${status}`);
                visitorDot.classList.add(`avail-dot-${status}`);
                visitorLabel.classList.add(`avail-text-${status}`);
                visitorLabel.textContent = label;
                visitorHours.textContent = summary;

                if (visitorMessage) {
                    visitorMessage.textContent = message;
                    visitorMessage.style.display = message ? '' : 'none';
                }
            }

            hiddenStatus.value = status;
            hiddenSummary.value = summary;
            hiddenMessage.value = message.slice(0, 120);
            hiddenSlots.value = '';
            hiddenExceptions.value = '';
            hiddenConges.value = '';
        };

        const renderAll = () => {
            syncDayChips();
            renderSlots();
            renderExceptions();
            syncStatusButtons();
            syncPreview();
        };

        tabButtons.forEach((button) => {
            button.addEventListener('click', () => {
                setActiveTab(button.dataset.availabilityTabBtn || 'horaires');
            });
        });

        dayChecks.forEach((checkbox) => {
            checkbox.addEventListener('change', syncDayChips);
        });

        statusButtons.forEach((button) => {
            button.addEventListener('click', () => {
                state.status = normalizeAvailabilityStatus(button.dataset.statusValue || 'disponible');
                renderAll();
            });
        });

        if (messageInput) {
            messageInput.value = state.message;
            messageInput.addEventListener('input', () => {
                state.message = messageInput.value.trim().slice(0, 120);
                renderAll();
            });
        }

        if (addSlotButton && slotStartInput && slotEndInput) {
            addSlotButton.addEventListener('click', () => {
                const selectedDays = dayChecks.filter((input) => input.checked).map((input) => input.value);
                const days = normalizeSlotDays(selectedDays);
                const start = parseTime(slotStartInput.value);
                const end = parseTime(slotEndInput.value);

                if (!days.length) {
                    showAppNotification('Choisissez au moins un jour.', 'error');
                    return;
                }
                if (!start || !end || end <= start) {
                    showAppNotification('Choisissez un horaire valide.', 'error');
                    return;
                }

                state.slots.push({
                    id: nextId(),
                    days,
                    start,
                    end
                });

                dayChecks.forEach((input) => {
                    input.checked = false;
                });
                slotStartInput.value = start;
                slotEndInput.value = end;

                renderAll();
            });
        }

        if (addExceptionButton && exceptionDateInput && exceptionTypeInput) {
            addExceptionButton.addEventListener('click', () => {
                const date = parseDate(exceptionDateInput.value);
                const type = String(exceptionTypeInput.value || '').toLowerCase();

                if (!date) {
                    showAppNotification('Choisissez une date.', 'error');
                    return;
                }
                if (!['ferme', 'reduits', 'speciaux'].includes(type)) {
                    showAppNotification('Type d\'exception invalide.', 'error');
                    return;
                }

                state.exceptions.push({
                    id: nextId(),
                    date,
                    type
                });

                exceptionDateInput.value = '';
                exceptionTypeInput.value = 'ferme';
                renderAll();
            });
        }

        if (addCongesButton && congesStartInput && congesEndInput) {
            addCongesButton.addEventListener('click', () => {
                const start = parseDate(congesStartInput.value);
                const end = parseDate(congesEndInput.value);

                if (!start || !end) {
                    showAppNotification('Selectionnez debut et fin des conges.', 'error');
                    return;
                }
                if (end < start) {
                    showAppNotification('La date de fin doit etre apres le debut.', 'error');
                    return;
                }

                state.conges.push({
                    id: nextId(),
                    start,
                    end
                });

                congesStartInput.value = '';
                congesEndInput.value = '';
                renderAll();
            });
        }

        setActiveTab('horaires');
        renderAll();
    });
}

function parseNiveau(value) {
    const n = Number.parseInt(String(value), 10);
    if (Number.isNaN(n) || n < 0 || n > 100) {
        return null;
    }
    return n;
}

function niveauCategoryBadge(niveau) {
    if (niveau >= 80) {
        return '<span class="badge-advanced">Avance</span>';
    }
    if (niveau >= 50) {
        return '<span class="badge-intermediate">Intermediaire</span>';
    }
    return '<span class="badge-beginner">Debutant</span>';
}

function formatPeriode(dateDebut, dateFin) {
    const debut = (dateDebut || '').trim();
    const fin = (dateFin || '').trim();
    return `${debut} - ${fin || 'Present'}`;
}

function createActionButton(text, className, title, onClick) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = className;
    button.title = title;
    button.textContent = text;
    button.addEventListener('click', onClick);
    return button;
}

function bindInlineEditShortcuts(inputs, onSave, onCancel) {
    inputs.forEach((input) => {
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && input.tagName !== 'TEXTAREA') {
                event.preventDefault();
                onSave();
            }
            if (event.key === 'Escape') {
                event.preventDefault();
                onCancel();
            }
        });
    });
}

function editCompetence(id) {
    const form = document.getElementById(`edit-comp-${id}`);
    if (!form) {
        return;
    }
    const row = form.closest('tr');
    if (!row || row.dataset.inlineEdit === '1') {
        return;
    }

    const nomCell = row.querySelector('.comp-nom');
    const descriptionCell = row.querySelector('.comp-desc');
    const niveauCell = row.querySelector('.comp-niveau');
    const categorieCell = row.querySelector('.comp-cat');
    const progressFill = row.querySelector('.progress-fill');
    const actionCell = form.parentElement;
    const editButton = actionCell.querySelector('.modif-btn');
    const deleteForm = actionCell.querySelector('form[action*="/profil/deleteCompetence"]');

    if (!nomCell || !descriptionCell || !niveauCell || !categorieCell || !actionCell) {
        return;
    }

    row.dataset.inlineEdit = '1';

    const nomInput = document.createElement('input');
    nomInput.type = 'text';
    nomInput.value = form.elements.nom.value || '';
    nomInput.minLength = 2;
    nomInput.maxLength = 80;
    nomInput.style.width = '100%';

    const descriptionInput = document.createElement('input');
    descriptionInput.type = 'text';
    descriptionInput.value = form.elements.description.value || '';
    descriptionInput.maxLength = 500;
    descriptionInput.style.width = '100%';

    const niveauInput = document.createElement('input');
    niveauInput.type = 'number';
    niveauInput.min = '0';
    niveauInput.max = '100';
    niveauInput.value = form.elements.niveau.value || '0';
    niveauInput.style.width = '90px';

    nomCell.innerHTML = '';
    nomCell.appendChild(nomInput);
    descriptionCell.innerHTML = '';
    descriptionCell.appendChild(descriptionInput);
    niveauCell.innerHTML = '';
    niveauCell.appendChild(niveauInput);

    const syncNiveauPreview = () => {
        const niveau = parseNiveau(niveauInput.value);
        const safeNiveau = niveau === null ? 0 : niveau;
        if (progressFill) {
            progressFill.style.width = `${safeNiveau}%`;
        }
        categorieCell.innerHTML = niveauCategoryBadge(safeNiveau);
    };

    syncNiveauPreview();
    niveauInput.addEventListener('input', syncNiveauPreview);

    if (editButton) {
        editButton.style.display = 'none';
    }
    if (deleteForm) {
        deleteForm.style.display = 'none';
    }

    const cancelEdit = () => {
        const originalNiveau = parseNiveau(form.elements.niveau.value);
        const safeNiveau = originalNiveau === null ? 0 : originalNiveau;

        nomCell.textContent = form.elements.nom.value || '';
        descriptionCell.textContent = form.elements.description.value || '';
        niveauCell.textContent = `${safeNiveau}%`;
        if (progressFill) {
            progressFill.style.width = `${safeNiveau}%`;
        }
        categorieCell.innerHTML = niveauCategoryBadge(safeNiveau);

        saveButton.remove();
        cancelButton.remove();
        if (editButton) {
            editButton.style.display = '';
        }
        if (deleteForm) {
            deleteForm.style.display = 'inline';
        }
        row.dataset.inlineEdit = '0';
    };

    const saveEdit = async () => {
        const nom = nomInput.value.trim();
        const description = descriptionInput.value.trim();
        const niveau = parseNiveau(niveauInput.value);

        if (!nom) {
            showAppNotification('Nom obligatoire', 'error');
            return;
        }
        if (!isValidSkillName(nom, 2, 80)) {
            showAppNotification('Nom invalide. Utilisez lettres/chiffres et ponctuation simple', 'error');
            return;
        }
        if (!isValidSkillDescription(description, 500)) {
            showAppNotification('Description invalide (caractere non autorise ou > 500)', 'error');
            return;
        }
        if (niveau === null) {
            showAppNotification('Niveau invalide (0-100)', 'error');
            return;
        }

        form.elements.nom.value = nom;
        form.elements.description.value = description;
        form.elements.niveau.value = String(niveau);

        saveButton.disabled = true;
        const result = await postFormAsText(form);
        saveButton.disabled = false;

        if (!result.ok) {
            const msg = result.error ? 'Erreur reseau, reessayez.' : ((result.payload && result.payload.message) || 'Modification impossible');
            showAppNotification(msg, 'error');
            return;
        }

        nomCell.textContent = nom;
        descriptionCell.textContent = description;
        niveauCell.textContent = niveau + '%';
        if (progressFill) { progressFill.style.width = niveau + '%'; }
        categorieCell.innerHTML = niveauCategoryBadge(niveau);

        saveButton.remove();
        cancelButton.remove();
        if (editButton) { editButton.style.display = ''; }
        if (deleteForm) { deleteForm.style.display = 'inline'; }
        row.dataset.inlineEdit = '0';

        applyCompetenceTableTools();
        showAppNotification('Competence modifiee', 'success');
    };

    const saveButton = createActionButton('💾', 'modif-btn', 'Enregistrer', saveEdit);
    const cancelButton = createActionButton('✖', 'suppr-btn', 'Annuler', cancelEdit);
    actionCell.appendChild(saveButton);
    actionCell.appendChild(cancelButton);

    bindInlineEditShortcuts([nomInput, descriptionInput, niveauInput], saveEdit, cancelEdit);
    nomInput.focus();
}

function editCertification(id) {
    const form = document.getElementById(`edit-cert-${id}`);
    if (!form) {
        return;
    }
    const row = form.closest('tr');
    if (!row || row.dataset.inlineEdit === '1') {
        return;
    }

    const nomCell = row.querySelector('.cert-nom');
    const niveauCell = row.querySelector('.cert-niveau');
    const categorieCell = row.querySelector('.cert-cat');
    const progressFill = row.querySelector('.progress-fill');
    const actionCell = form.parentElement;
    const editButton = actionCell.querySelector('.modif-btn');
    const deleteForm = actionCell.querySelector('form[action*="/profil/deleteCertification"]');

    if (!nomCell || !niveauCell || !categorieCell || !actionCell) {
        return;
    }

    row.dataset.inlineEdit = '1';

    const nomInput = document.createElement('input');
    nomInput.type = 'text';
    nomInput.value = form.elements.nom.value || '';
    nomInput.minLength = 2;
    nomInput.maxLength = 100;
    nomInput.style.width = '100%';

    const niveauInput = document.createElement('input');
    niveauInput.type = 'number';
    niveauInput.min = '0';
    niveauInput.max = '100';
    niveauInput.value = form.elements.niveau.value || '0';
    niveauInput.style.width = '90px';

    nomCell.innerHTML = '';
    nomCell.appendChild(nomInput);
    niveauCell.innerHTML = '';
    niveauCell.appendChild(niveauInput);

    const syncNiveauPreview = () => {
        const niveau = parseNiveau(niveauInput.value);
        const safeNiveau = niveau === null ? 0 : niveau;
        if (progressFill) {
            progressFill.style.width = `${safeNiveau}%`;
        }
        categorieCell.innerHTML = niveauCategoryBadge(safeNiveau);
    };

    syncNiveauPreview();
    niveauInput.addEventListener('input', syncNiveauPreview);

    if (editButton) {
        editButton.style.display = 'none';
    }
    if (deleteForm) {
        deleteForm.style.display = 'none';
    }

    const cancelEdit = () => {
        const originalNiveau = parseNiveau(form.elements.niveau.value);
        const safeNiveau = originalNiveau === null ? 0 : originalNiveau;

        nomCell.textContent = form.elements.nom.value || '';
        niveauCell.textContent = `${safeNiveau}%`;
        if (progressFill) {
            progressFill.style.width = `${safeNiveau}%`;
        }
        categorieCell.innerHTML = niveauCategoryBadge(safeNiveau);

        saveButton.remove();
        cancelButton.remove();
        if (editButton) {
            editButton.style.display = '';
        }
        if (deleteForm) {
            deleteForm.style.display = 'inline';
        }
        row.dataset.inlineEdit = '0';
    };

    const saveEdit = async () => {
        const nom = nomInput.value.trim();
        const niveau = parseNiveau(niveauInput.value);

        if (!nom) {
            showAppNotification('Nom obligatoire', 'error');
            return;
        }
        if (!isValidSkillName(nom, 2, 100)) {
            showAppNotification('Nom invalide. Utilisez lettres/chiffres et ponctuation simple', 'error');
            return;
        }
        if (niveau === null) {
            showAppNotification('Niveau invalide (0-100)', 'error');
            return;
        }

        form.elements.nom.value = nom;
        form.elements.niveau.value = String(niveau);

        saveButton.disabled = true;
        const result = await postFormAsText(form);
        saveButton.disabled = false;

        if (!result.ok) {
            const msg = result.error ? 'Erreur reseau, reessayez.' : ((result.payload && result.payload.message) || 'Modification impossible');
            showAppNotification(msg, 'error');
            return;
        }

        nomCell.textContent = nom;
        niveauCell.textContent = niveau + '%';
        if (progressFill) { progressFill.style.width = niveau + '%'; }
        categorieCell.innerHTML = niveauCategoryBadge(niveau);

        saveButton.remove();
        cancelButton.remove();
        if (editButton) { editButton.style.display = ''; }
        if (deleteForm) { deleteForm.style.display = 'inline'; }
        row.dataset.inlineEdit = '0';

        applyCertificationTableTools();
        showAppNotification('Certification modifiee', 'success');
    };

    const saveButton = createActionButton('💾', 'modif-btn', 'Enregistrer', saveEdit);
    const cancelButton = createActionButton('✖', 'suppr-btn', 'Annuler', cancelEdit);
    actionCell.appendChild(saveButton);
    actionCell.appendChild(cancelButton);

    bindInlineEditShortcuts([nomInput, niveauInput], saveEdit, cancelEdit);
    nomInput.focus();
}

function editExperience(id) {
    const form = document.getElementById(`edit-exp-${id}`);
    if (!form) {
        return;
    }
    const row = form.closest('tr');
    if (!row || row.dataset.inlineEdit === '1') {
        return;
    }

    const posteCell = row.querySelector('.exp-poste');
    const entrepriseCell = row.querySelector('.exp-entreprise');
    const periodeCell = row.querySelector('.exp-periode');
    const descriptionCell = row.querySelector('.exp-description');
    const actionCell = row.querySelector('.exp-actions') || form.parentElement;
    const editButton = actionCell.querySelector('.modif-btn');
    const deleteForm = actionCell.querySelector('form[action*="/profil/deleteExperience"]');

    if (!posteCell || !entrepriseCell || !periodeCell || !descriptionCell || !actionCell) {
        return;
    }

    row.dataset.inlineEdit = '1';

    const posteInput = document.createElement('input');
    posteInput.type = 'text';
    posteInput.value = form.elements.poste.value || '';
    posteInput.minLength = 2;
    posteInput.maxLength = 100;
    posteInput.style.width = '100%';

    const entrepriseInput = document.createElement('input');
    entrepriseInput.type = 'text';
    entrepriseInput.value = form.elements.entreprise.value || '';
    entrepriseInput.maxLength = 120;
    entrepriseInput.style.width = '100%';

    const dateDebutInput = document.createElement('input');
    dateDebutInput.type = 'date';
    dateDebutInput.value = form.elements.date_debut.value || '';
    dateDebutInput.style.width = '100%';

    const dateFinInput = document.createElement('input');
    dateFinInput.type = 'date';
    dateFinInput.value = form.elements.date_fin.value || '';
    dateFinInput.style.width = '100%';

    const descriptionInput = document.createElement('input');
    descriptionInput.type = 'text';
    descriptionInput.value = form.elements.description.value || '';
    descriptionInput.maxLength = 1000;
    descriptionInput.style.width = '100%';

    posteCell.innerHTML = '';
    posteCell.appendChild(posteInput);
    entrepriseCell.innerHTML = '';
    entrepriseCell.appendChild(entrepriseInput);
    descriptionCell.innerHTML = '';
    descriptionCell.appendChild(descriptionInput);

    periodeCell.innerHTML = '';
    const dateWrap = document.createElement('div');
    dateWrap.style.display = 'flex';
    dateWrap.style.flexDirection = 'column';
    dateWrap.style.gap = '6px';
    dateWrap.appendChild(dateDebutInput);
    dateWrap.appendChild(dateFinInput);
    periodeCell.appendChild(dateWrap);

    if (editButton) {
        editButton.style.display = 'none';
    }
    if (deleteForm) {
        deleteForm.style.display = 'none';
    }

    const leaveInlineMode = () => {
        saveButton.remove();
        cancelButton.remove();
        if (editButton) {
            editButton.style.display = '';
        }
        if (deleteForm) {
            deleteForm.style.display = 'inline';
        }
        row.dataset.inlineEdit = '0';
    };

    const cancelEdit = () => {
        const dateDebut = form.elements.date_debut.value || '';
        const dateFin = form.elements.date_fin.value || '';

        posteCell.textContent = form.elements.poste.value || '';
        entrepriseCell.textContent = form.elements.entreprise.value || '';
        descriptionCell.textContent = form.elements.description.value || '';
        periodeCell.textContent = formatPeriode(dateDebut, dateFin);

        leaveInlineMode();
    };

    let isSaving = false;
    const saveEdit = async () => {
        if (isSaving) {
            return;
        }

        const poste = posteInput.value.trim();
        const entreprise = entrepriseInput.value.trim();
        const dateDebut = dateDebutInput.value.trim();
        const dateFin = dateFinInput.value.trim();
        const description = descriptionInput.value.trim();

        if (!poste) {
            showAppNotification('Poste obligatoire', 'error');
            return;
        }
        if (textLength(poste) < 2 || textLength(poste) > 100) {
            showAppNotification('Le poste doit contenir entre 2 et 100 caracteres', 'error');
            return;
        }
        if (textLength(entreprise) > 120) {
            showAppNotification('Entreprise trop longue (120 max)', 'error');
            return;
        }
        if (!dateDebut) {
            showAppNotification('Date debut obligatoire', 'error');
            return;
        }
        if (!isValidDateInput(dateDebut)) {
            showAppNotification('Date debut invalide', 'error');
            return;
        }
        if (dateFin && !isValidDateInput(dateFin)) {
            showAppNotification('Date fin invalide', 'error');
            return;
        }
        if (dateFin && dateFin < dateDebut) {
            showAppNotification('La date de fin doit etre apres la date de debut', 'error');
            return;
        }
        if (textLength(description) > 1000) {
            showAppNotification('Description trop longue (1000 max)', 'error');
            return;
        }

        const previousValues = {
            poste: form.elements.poste.value || '',
            entreprise: form.elements.entreprise.value || '',
            dateDebut: form.elements.date_debut.value || '',
            dateFin: form.elements.date_fin.value || '',
            description: form.elements.description.value || ''
        };

        form.elements.poste.value = poste;
        form.elements.entreprise.value = entreprise;
        form.elements.date_debut.value = dateDebut;
        form.elements.date_fin.value = dateFin;
        form.elements.description.value = description;

        isSaving = true;
        saveButton.disabled = true;
        const result = await postFormAsText(form);
        isSaving = false;
        saveButton.disabled = false;

        if (!result.ok) {
            form.elements.poste.value = previousValues.poste;
            form.elements.entreprise.value = previousValues.entreprise;
            form.elements.date_debut.value = previousValues.dateDebut;
            form.elements.date_fin.value = previousValues.dateFin;
            form.elements.description.value = previousValues.description;

            const errorMessage = result.error
                ? 'Erreur reseau, reessayez.'
                : ((result.payload && result.payload.message) || 'Modification impossible');
            showAppNotification(errorMessage, 'error');
            return;
        }

        const updated = result.payload && result.payload.experience
            ? result.payload.experience
            : {
                poste,
                entreprise,
                date_debut: dateDebut,
                date_fin: dateFin,
                description
            };

        const updatedPoste = String(updated.poste || '');
        const updatedEntreprise = String(updated.entreprise || '');
        const updatedDateDebut = String(updated.date_debut || '');
        const updatedDateFin = String(updated.date_fin || '');
        const updatedDescription = String(updated.description || '');

        form.elements.poste.value = updatedPoste;
        form.elements.entreprise.value = updatedEntreprise;
        form.elements.date_debut.value = updatedDateDebut;
        form.elements.date_fin.value = updatedDateFin;
        form.elements.description.value = updatedDescription;

        posteCell.textContent = updatedPoste;
        entrepriseCell.textContent = updatedEntreprise;
        descriptionCell.textContent = updatedDescription;
        periodeCell.textContent = formatPeriode(updatedDateDebut, updatedDateFin);

        leaveInlineMode();
        applyExperienceTableTools();
        showAppNotification((result.payload && result.payload.message) || 'Experience modifiee', 'success');
    };

    const saveButton = createActionButton('💾', 'modif-btn', 'Enregistrer', saveEdit);
    const cancelButton = createActionButton('✖', 'suppr-btn', 'Annuler', cancelEdit);
    actionCell.appendChild(saveButton);
    actionCell.appendChild(cancelButton);

    bindInlineEditShortcuts(
        [posteInput, entrepriseInput, dateDebutInput, dateFinInput, descriptionInput],
        saveEdit,
        cancelEdit
    );
    posteInput.focus();
}

function parseDateToTimestamp(value) {
    const raw = String(value || '').trim();
    if (!/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
        return 0;
    }

    const date = new Date(`${raw}T00:00:00`);
    if (Number.isNaN(date.getTime())) {
        return 0;
    }

    return date.getTime();
}

function toggleNoResultsRow(tbody, rowClass, colSpan, message, shouldShow) {
    if (!tbody) {
        return;
    }

    let noResultRow = tbody.querySelector(`.${rowClass}`);

    if (!shouldShow) {
        if (noResultRow) {
            noResultRow.remove();
        }
        return;
    }

    if (!noResultRow) {
        noResultRow = document.createElement('tr');
        noResultRow.className = `${rowClass} table-empty-search`;

        const cell = document.createElement('td');
        cell.colSpan = colSpan;
        cell.className = 'table-empty-search';
        cell.textContent = message;
        noResultRow.appendChild(cell);
    }

    tbody.appendChild(noResultRow);
}

function parseHumanDateToTimestamp(value) {
    const raw = String(value || '').trim();
    const match = raw.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (!match) {
        return 0;
    }

    const day = Number.parseInt(match[1], 10);
    const month = Number.parseInt(match[2], 10);
    const year = Number.parseInt(match[3], 10);

    if (!day || !month || !year) {
        return 0;
    }

    const date = new Date(year, month - 1, day, 0, 0, 0, 0);
    if (Number.isNaN(date.getTime())) {
        return 0;
    }

    return date.getTime();
}

function buildPortfolioDocRowMeta(row) {
    const title = String((row.querySelector('.doc-title')?.textContent || '')).trim();
    const realisation = String((row.querySelector('.doc-realisation')?.textContent || '')).trim();
    const dateText = String((row.querySelector('.doc-date')?.textContent || '')).trim();
    const createdAtRaw = String(row.getAttribute('data-created-at') || '').trim();

    let createdAtTs = parseDateToTimestamp(createdAtRaw.slice(0, 10));
    if (!createdAtTs) {
        createdAtTs = parseHumanDateToTimestamp(dateText);
    }

    return {
        row,
        title,
        titleLower: title.toLowerCase(),
        realisation,
        realisationLower: realisation.toLowerCase(),
        searchable: `${title} ${realisation}`.toLowerCase(),
        createdAtTs
    };
}

function applyPortfolioDocsTableTools() {
    const tbody = document.getElementById('portfolio-docs-list');
    const searchInput = document.querySelector('[data-doc-search="true"]');
    const sortSelect = document.querySelector('[data-doc-sort="true"]');

    if (!tbody || !searchInput || !sortSelect) {
        return;
    }

    const rows = Array.from(tbody.querySelectorAll('tr[data-id]'));
    const query = searchInput.value.trim().toLowerCase();
    const sortValue = sortSelect.value;

    const items = rows.map(buildPortfolioDocRowMeta);

    items.sort((a, b) => {
        if (sortValue === 'date-asc') {
            return a.createdAtTs - b.createdAtTs;
        }
        if (sortValue === 'titre-asc') {
            return a.titleLower.localeCompare(b.titleLower, 'fr');
        }
        if (sortValue === 'realisation-asc') {
            return a.realisationLower.localeCompare(b.realisationLower, 'fr');
        }
        return b.createdAtTs - a.createdAtTs;
    });

    items.forEach((item) => {
        tbody.appendChild(item.row);
    });

    const currentYear = new Date().getFullYear();
    let visibleCount = 0;
    let currentYearCount = 0;
    const realisations = new Set();

    items.forEach((item) => {
        const isVisible = query === '' || item.searchable.includes(query);
        item.row.style.display = isVisible ? '' : 'none';

        if (!isVisible) {
            return;
        }

        visibleCount++;
        if (item.realisationLower && item.realisationLower !== '-') {
            realisations.add(item.realisationLower);
        }

        if (item.createdAtTs > 0) {
            const year = new Date(item.createdAtTs).getFullYear();
            if (year === currentYear) {
                currentYearCount++;
            }
        }
    });

    const showNoResult = items.length > 0 && visibleCount === 0;
    toggleNoResultsRow(
        tbody,
        'doc-empty-search-row',
        4,
        'Aucun document ne correspond a votre recherche.',
        showNoResult
    );

    const totalEl = document.querySelector('[data-doc-stat-total="true"]');
    const realisationsEl = document.querySelector('[data-doc-stat-realisations="true"]');
    const yearEl = document.querySelector('[data-doc-stat-year="true"]');
    const visibleEl = document.querySelector('[data-doc-stat-visible="true"]');

    if (totalEl) {
        totalEl.textContent = String(items.length);
    }
    if (realisationsEl) {
        realisationsEl.textContent = String(realisations.size);
    }
    if (yearEl) {
        yearEl.textContent = String(currentYearCount);
    }
    if (visibleEl) {
        visibleEl.textContent = String(visibleCount);
    }
}

function initPortfolioDocsTableTools() {
    const tbody = document.getElementById('portfolio-docs-list');
    const searchInput = document.querySelector('[data-doc-search="true"]');
    const sortSelect = document.querySelector('[data-doc-sort="true"]');
    const statsButton = document.querySelector('[data-doc-stats-toggle="true"]');
    const statsPanel = document.querySelector('[data-doc-stats-panel="true"]');

    if (!tbody || !searchInput || !sortSelect) {
        return;
    }

    if (tbody.dataset.toolsBound === 'doc') {
        applyPortfolioDocsTableTools();
        return;
    }

    tbody.dataset.toolsBound = 'doc';
    searchInput.addEventListener('input', applyPortfolioDocsTableTools);
    sortSelect.addEventListener('change', applyPortfolioDocsTableTools);

    if (statsButton && statsPanel) {
        statsButton.addEventListener('click', () => {
            statsPanel.style.display = statsPanel.style.display === 'none' ? 'flex' : 'none';
        });
    }

    applyPortfolioDocsTableTools();
}

function buildCompetenceRowMeta(row) {
    const nom = String((row.querySelector('.comp-nom')?.textContent || '')).trim();
    const description = String((row.querySelector('.comp-desc')?.textContent || '')).trim();
    const categorie = String((row.querySelector('.comp-cat')?.textContent || '')).trim();
    const niveauText = String((row.querySelector('.comp-niveau')?.textContent || '')).replace('%', '');
    const niveau = Number.parseInt(niveauText, 10);

    return {
        row,
        nom,
        nomLower: nom.toLowerCase(),
        description,
        searchable: `${nom} ${description} ${categorie}`.toLowerCase(),
        niveau: Number.isNaN(niveau) ? 0 : niveau
    };
}

function applyCompetenceTableTools() {
    const tbody = document.getElementById('competences-list');
    const searchInput = document.querySelector('[data-comp-search="true"]');
    const sortSelect = document.querySelector('[data-comp-sort="true"]');

    if (!tbody || !searchInput || !sortSelect) {
        return;
    }

    const rows = Array.from(tbody.querySelectorAll('tr[data-id]'));
    const query = searchInput.value.trim().toLowerCase();
    const sortValue = sortSelect.value;

    const items = rows.map(buildCompetenceRowMeta);

    items.sort((a, b) => {
        if (sortValue === 'niveau-asc') {
            return a.niveau - b.niveau;
        }
        if (sortValue === 'nom-asc') {
            return a.nomLower.localeCompare(b.nomLower, 'fr');
        }
        if (sortValue === 'nom-desc') {
            return b.nomLower.localeCompare(a.nomLower, 'fr');
        }
        return b.niveau - a.niveau;
    });

    items.forEach((item) => {
        tbody.appendChild(item.row);
    });

    let visibleCount = 0;
    let visibleNiveauTotal = 0;
    let visibleAdvanced = 0;

    items.forEach((item) => {
        const isVisible = query === '' || item.searchable.includes(query);
        item.row.style.display = isVisible ? '' : 'none';

        if (!isVisible) {
            return;
        }

        visibleCount++;
        visibleNiveauTotal += item.niveau;
        if (item.niveau >= 80) {
            visibleAdvanced++;
        }
    });

    const showNoResult = items.length > 0 && visibleCount === 0;
    toggleNoResultsRow(
        tbody,
        'comp-empty-search-row',
        7,
        'Aucune competence ne correspond a votre recherche.',
        showNoResult
    );

    const totalEl = document.querySelector('[data-comp-stat-total="true"]');
    const avgEl = document.querySelector('[data-comp-stat-average="true"]');
    const advancedEl = document.querySelector('[data-comp-stat-advanced="true"]');
    const visibleEl = document.querySelector('[data-comp-stat-visible="true"]');

    if (totalEl) {
        totalEl.textContent = String(items.length);
    }
    if (avgEl) {
        avgEl.textContent = `${visibleCount > 0 ? Math.round(visibleNiveauTotal / visibleCount) : 0}%`;
    }
    if (advancedEl) {
        advancedEl.textContent = String(visibleAdvanced);
    }
    if (visibleEl) {
        visibleEl.textContent = String(visibleCount);
    }
}

function initCompetenceTableTools() {
    const tbody = document.getElementById('competences-list');
    const searchInput = document.querySelector('[data-comp-search="true"]');
    const sortSelect = document.querySelector('[data-comp-sort="true"]');
    const statsButton = document.querySelector('[data-comp-stats-toggle="true"]');
    const statsPanel = document.querySelector('[data-comp-stats-panel="true"]');

    if (!tbody || !searchInput || !sortSelect) {
        return;
    }

    if (tbody.dataset.toolsBound === 'comp') {
        applyCompetenceTableTools();
        return;
    }

    tbody.dataset.toolsBound = 'comp';
    searchInput.addEventListener('input', applyCompetenceTableTools);
    sortSelect.addEventListener('change', applyCompetenceTableTools);

    if (statsButton && statsPanel) {
        statsButton.addEventListener('click', () => {
            statsPanel.style.display = statsPanel.style.display === 'none' ? 'flex' : 'none';
        });
    }

    applyCompetenceTableTools();
}

function applyCertificationTableTools() {
    const tbody = document.getElementById('certifications-list');
    const searchInput = document.querySelector('[data-cert-search="true"]');
    const sortSelect = document.querySelector('[data-cert-sort="true"]');

    if (!tbody || !searchInput || !sortSelect) {
        return;
    }

    const rows = Array.from(tbody.querySelectorAll('tr[data-id]'));
    const query = searchInput.value.trim().toLowerCase();
    const sortValue = sortSelect.value;

    const items = rows.map((row) => {
        const nom = String((row.querySelector('.cert-nom')?.textContent || '')).trim();
        const niveauText = String((row.querySelector('.cert-niveau')?.textContent || '')).replace('%', '');
        const niveau = Number.parseInt(niveauText, 10);

        return {
            row,
            nom,
            nomLower: nom.toLowerCase(),
            niveau: Number.isNaN(niveau) ? 0 : niveau
        };
    });

    items.sort((a, b) => {
        if (sortValue === 'niveau-asc') {
            return a.niveau - b.niveau;
        }
        if (sortValue === 'nom-asc') {
            return a.nomLower.localeCompare(b.nomLower, 'fr');
        }
        if (sortValue === 'nom-desc') {
            return b.nomLower.localeCompare(a.nomLower, 'fr');
        }
        return b.niveau - a.niveau;
    });

    items.forEach((item) => {
        tbody.appendChild(item.row);
    });

    let visibleCount = 0;
    let visibleNiveauTotal = 0;
    let visibleAdvanced = 0;

    items.forEach((item) => {
        const isVisible = query === '' || item.nomLower.includes(query);
        item.row.style.display = isVisible ? '' : 'none';

        if (!isVisible) {
            return;
        }

        visibleCount++;
        visibleNiveauTotal += item.niveau;
        if (item.niveau >= 80) {
            visibleAdvanced++;
        }
    });

    const showNoResult = items.length > 0 && visibleCount === 0;
    toggleNoResultsRow(
        tbody,
        'cert-empty-search-row',
        6,
        'Aucune certification ne correspond a votre recherche.',
        showNoResult
    );

    const totalEl = document.querySelector('[data-cert-stat-total="true"]');
    const avgEl = document.querySelector('[data-cert-stat-average="true"]');
    const advancedEl = document.querySelector('[data-cert-stat-advanced="true"]');
    const visibleEl = document.querySelector('[data-cert-stat-visible="true"]');

    if (totalEl) {
        totalEl.textContent = String(items.length);
    }
    if (avgEl) {
        avgEl.textContent = `${visibleCount > 0 ? Math.round(visibleNiveauTotal / visibleCount) : 0}%`;
    }
    if (advancedEl) {
        advancedEl.textContent = String(visibleAdvanced);
    }
    if (visibleEl) {
        visibleEl.textContent = String(visibleCount);
    }
}

function initCertificationTableTools() {
    const tbody = document.getElementById('certifications-list');
    const searchInput = document.querySelector('[data-cert-search="true"]');
    const sortSelect = document.querySelector('[data-cert-sort="true"]');
    const statsButton = document.querySelector('[data-cert-stats-toggle="true"]');
    const statsPanel = document.querySelector('[data-cert-stats-panel="true"]');

    if (!tbody || !searchInput || !sortSelect) {
        return;
    }

    if (tbody.dataset.toolsBound === 'cert') {
        applyCertificationTableTools();
        return;
    }

    tbody.dataset.toolsBound = 'cert';
    searchInput.addEventListener('input', applyCertificationTableTools);
    sortSelect.addEventListener('change', applyCertificationTableTools);

    if (statsButton && statsPanel) {
        statsButton.addEventListener('click', () => {
            statsPanel.style.display = statsPanel.style.display === 'none' ? 'flex' : 'none';
        });
    }

    applyCertificationTableTools();
}

function buildExperienceRowMeta(row) {
    const id = Number.parseInt(String(row.dataset.id || '0'), 10);
    const poste = String((row.querySelector('.exp-poste')?.textContent || '')).trim();
    const entreprise = String((row.querySelector('.exp-entreprise')?.textContent || '')).trim();
    const description = String((row.querySelector('.exp-description')?.textContent || '')).trim();
    const periodText = String((row.querySelector('.exp-periode')?.textContent || '')).trim();
    const form = Number.isNaN(id) || id <= 0 ? null : document.getElementById(`edit-exp-${id}`);

    let dateDebut = '';
    let dateFin = '';

    if (form && form.elements) {
        dateDebut = String(form.elements.date_debut?.value || '').trim();
        dateFin = String(form.elements.date_fin?.value || '').trim();
    }

    if (!dateDebut && periodText.includes(' - ')) {
        const parts = periodText.split(' - ');
        dateDebut = String(parts[0] || '').trim();

        const periodFin = String(parts[1] || '').trim().toLowerCase();
        if (!dateFin && periodFin !== '' && periodFin !== 'present' && periodFin !== 'présent') {
            dateFin = String(parts[1] || '').trim();
        }
    }

    return {
        row,
        poste,
        entreprise,
        description,
        searchable: `${poste} ${entreprise} ${description}`.toLowerCase(),
        posteLower: poste.toLowerCase(),
        entrepriseLower: entreprise.toLowerCase(),
        dateDebutTs: parseDateToTimestamp(dateDebut),
        active: dateFin === ''
    };
}

function applyExperienceTableTools() {
    const tbody = document.getElementById('experiences-list');
    const searchInput = document.querySelector('[data-exp-search="true"]');
    const sortSelect = document.querySelector('[data-exp-sort="true"]');

    if (!tbody || !searchInput || !sortSelect) {
        return;
    }

    const rows = Array.from(tbody.querySelectorAll('tr[data-id]'));
    const query = searchInput.value.trim().toLowerCase();
    const sortValue = sortSelect.value;

    const items = rows.map(buildExperienceRowMeta);

    items.sort((a, b) => {
        if (sortValue === 'date-asc') {
            return a.dateDebutTs - b.dateDebutTs;
        }
        if (sortValue === 'poste-asc') {
            return a.posteLower.localeCompare(b.posteLower, 'fr');
        }
        if (sortValue === 'entreprise-asc') {
            return a.entrepriseLower.localeCompare(b.entrepriseLower, 'fr');
        }
        return b.dateDebutTs - a.dateDebutTs;
    });

    items.forEach((item) => {
        tbody.appendChild(item.row);
    });

    let visibleCount = 0;
    let activeCount = 0;
    const visibleCompanies = new Set();

    items.forEach((item) => {
        const isVisible = query === '' || item.searchable.includes(query);
        item.row.style.display = isVisible ? '' : 'none';

        if (!isVisible) {
            return;
        }

        visibleCount++;
        if (item.active) {
            activeCount++;
        }

        const company = item.entrepriseLower.trim();
        if (company !== '') {
            visibleCompanies.add(company);
        }
    });

    const showNoResult = items.length > 0 && visibleCount === 0;
    toggleNoResultsRow(
        tbody,
        'exp-empty-search-row',
        5,
        'Aucune experience ne correspond a votre recherche.',
        showNoResult
    );

    const totalEl = document.querySelector('[data-exp-stat-total="true"]');
    const activeEl = document.querySelector('[data-exp-stat-active="true"]');
    const companyEl = document.querySelector('[data-exp-stat-companies="true"]');
    const visibleEl = document.querySelector('[data-exp-stat-visible="true"]');

    if (totalEl) {
        totalEl.textContent = String(items.length);
    }
    if (activeEl) {
        activeEl.textContent = String(activeCount);
    }
    if (companyEl) {
        companyEl.textContent = String(visibleCompanies.size);
    }
    if (visibleEl) {
        visibleEl.textContent = String(visibleCount);
    }
}

function initExperienceTableTools() {
    const tbody = document.getElementById('experiences-list');
    const searchInput = document.querySelector('[data-exp-search="true"]');
    const sortSelect = document.querySelector('[data-exp-sort="true"]');
    const statsButton = document.querySelector('[data-exp-stats-toggle="true"]');
    const statsPanel = document.querySelector('[data-exp-stats-panel="true"]');

    if (!tbody || !searchInput || !sortSelect) {
        return;
    }

    if (tbody.dataset.toolsBound === 'exp') {
        applyExperienceTableTools();
        return;
    }

    tbody.dataset.toolsBound = 'exp';
    searchInput.addEventListener('input', applyExperienceTableTools);
    sortSelect.addEventListener('change', applyExperienceTableTools);

    if (statsButton && statsPanel) {
        statsButton.addEventListener('click', () => {
            statsPanel.style.display = statsPanel.style.display === 'none' ? 'flex' : 'none';
        });
    }

    applyExperienceTableTools();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.modal-overlay').forEach((overlay) => {
        overlay.addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal(this.id.replace('modal-', ''));
            }
        });
    });

    bindAjaxAddForms();
    bindGeneralWriteFormValidation();
    bindCompletionRecalculateForm();
    bindPortfolioRealisationSelector();
    initAvailabilityDesigner();
    bindAjaxExperienceDeleteForms();
    syncExperienceEmptyState();
    initPortfolioDocsTableTools();
    initCompetenceTableTools();
    initCertificationTableTools();
    initExperienceTableTools();

    bindEditProfilFormAjax();
    const activeTab = document.querySelector('.tab.active');
    let activeTabName = 'bio';
    if (activeTab) {
        const onclickValue = activeTab.getAttribute('onclick') || '';
        const match = onclickValue.match(/switchTab\('([^']+)'\)/);
        if (match && match[1]) {
            activeTabName = match[1];
        }
    }
    togglePortfolioManagementSections(activeTabName);
});
function bindEditProfilFormAjax() {
    const modal = document.getElementById('modal-edit');
    if (!modal) return;

    const form = modal.querySelector('form[action*="/profil/update"]');
    if (!form || form.dataset.editAjaxBound === '1') return;
    form.dataset.editAjaxBound = '1';

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const error = validateProfilWriteForm(form);
        if (error) {
            showAppNotification(error, 'error');
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.textContent : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Enregistrement...';
        }

        const result = await postFormAsText(form);

        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }

        if (result.ok) {
            const data = result.payload || {};
            const nameEl = document.querySelector('.profile-name');
            if (nameEl && data.prenom && data.nom) {
                nameEl.textContent = data.prenom + ' ' + data.nom;
            }
            const titleEl = document.querySelector('.profile-title');
            if (titleEl && data.specialite !== undefined) {
                titleEl.textContent = data.specialite || '';
            }
            const locationEl = document.querySelector('.profile-location');
            if (locationEl && data.ville !== undefined) {
                locationEl.textContent = '\uD83D\uDCCD ' + (data.ville || '');
            }
            showAppNotification((data && data.message) || 'Profil mis à jour !', 'success');
            closeModal('edit');
        } else {
            const msg = (result.payload && result.payload.message) || 'Erreur lors de la mise à jour.';
            showAppNotification(msg, 'error');
        }
    });
}