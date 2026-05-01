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

async function postFormAsJson(form) {
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: new FormData(form)
        });

        let payload = null;
        try {
            payload = await response.json();
        } catch (error) {
            payload = null;
        }

        return {
            ok: Boolean(response.ok && payload && payload.success),
            payload,
            response
        };
    } catch (error) {
        return {
            ok: false,
            payload: null,
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

    const result = await postFormAsJson(form);

    if (result.ok) {
        showAppNotification((result.payload && result.payload.message) || 'Ajout reussi', 'success');
    } else {
        if (result.error) {
            showAppNotification('Erreur reseau, reessayez.', isBioForm ? 'success' : 'error');
        } else {
            const errorMessage = result.payload && result.payload.message
                ? result.payload.message
                : 'Erreur lors de l\'ajout';
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
            const result = await postFormAsJson(form);

            if (result.ok) {
                const row = form.closest('tr');
                if (row) {
                    row.remove();
                    syncExperienceEmptyState();
                }
                applyExperienceTableTools();
                showAppNotification((result.payload && result.payload.message) || 'Experience supprimee', 'success');
                return;
            }

            const errorMessage = result.error
                ? 'Erreur reseau, reessayez.'
                : ((result.payload && result.payload.message) || 'Suppression impossible');
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

            if (isExperienceAddForm(form)) {
                if (result.payload && result.payload.experience) {
                    appendExperienceRow(result.payload.experience);
                }
                form.reset();
                return;
            }

            if (isBioAjaxForm(form)) {
                const bioText = result.payload && typeof result.payload.bio === 'string'
                    ? result.payload.bio
                    : getFormValue(form, 'bio');
                setBioDisplayText(bioText);
                switchBioFormToUpdate(form);
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

function parseJsonArraySafe(value) {
    const raw = String(value || '').trim();
    if (!raw) {
        return [];
    }

    try {
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
        return [];
    }
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

function bindAvailabilityDesigner() {
    const designer = document.querySelector('[data-availability-designer="true"]');
    if (!designer) {
        return;
    }

    const availabilityInputs = {
        status: designer.querySelector('input[name="disponibilite"]'),
        hours: designer.querySelector('input[name="disponibilite_horaire"]'),
        message: designer.querySelector('input[name="disponibilite_message"]'),
        slots: designer.querySelector('input[name="disponibilite_slots"]'),
        exceptions: designer.querySelector('input[name="disponibilite_exceptions"]'),
        conges: designer.querySelector('input[name="disponibilite_conges"]')
    };

    const previewLabel = designer.querySelector('[data-availability-preview-label="true"]');
    const previewHours = designer.querySelector('[data-availability-preview-hours="true"]');
    const previewBadge = designer.querySelector('[data-availability-preview-badge="true"]');
    const previewDot = designer.querySelector('[data-availability-preview-dot="true"]');
    const previewMessage = designer.querySelector('[data-availability-preview-message="true"]');

    let currentStatus = normalizeAvailabilityStatus(designer.dataset.initialStatus || 'disponible');
    let currentSummary = String(designer.dataset.initialSummary || '').trim();
    let currentMessage = String(designer.dataset.initialMessage || '').trim();
    let currentSlots = parseJsonArraySafe(designer.dataset.initialSlots || '');
    let currentExceptions = parseJsonArraySafe(designer.dataset.initialExceptions || '');
    let currentConges = parseJsonArraySafe(designer.dataset.initialConges || '');

    const daysInputs = Array.from(designer.querySelectorAll('[data-slot-day="true"]'));
    const slotStartInput = designer.querySelector('[data-slot-start="true"]');
    const slotEndInput = designer.querySelector('[data-slot-end="true"]');
    const slotAddButton = designer.querySelector('[data-slot-add="true"]');
    const slotTags = designer.querySelector('[data-slot-tags="true"]');

    const exceptionDateInput = designer.querySelector('[data-exception-date="true"]');
    const exceptionTypeInput = designer.querySelector('[data-exception-type="true"]');
    const exceptionAddButton = designer.querySelector('[data-exception-add="true"]');
    const exceptionTags = designer.querySelector('[data-exception-tags="true"]');

    const congesStartInput = designer.querySelector('[data-conges-start="true"]');
    const congesEndInput = designer.querySelector('[data-conges-end="true"]');
    const congesAddButton = designer.querySelector('[data-conges-add="true"]');

    const statusButtons = Array.from(designer.querySelectorAll('[data-status-value]'));
    const statusMessageInput = designer.querySelector('[data-status-message="true"]');

    const tabButtons = Array.from(designer.querySelectorAll('[data-availability-tab-btn]'));
    const tabPanels = Array.from(designer.querySelectorAll('[data-availability-tab-panel]'));

    function updatePreview() {
        const label = getAvailabilityStatusLabel(currentStatus);
        if (previewLabel) previewLabel.textContent = label;
        if (previewBadge) {
            previewBadge.classList.remove('availability-preview-badge-disponible', 'availability-preview-badge-occupe', 'availability-preview-badge-indisponible');
            previewBadge.classList.add(`availability-preview-badge-${currentStatus}`);
        }
        if (previewDot) {
            previewDot.classList.remove('availability-preview-dot-disponible', 'availability-preview-dot-occupe', 'availability-preview-dot-indisponible');
            previewDot.classList.add(`availability-preview-dot-${currentStatus}`);
        }

        if (!currentSummary) {
            currentSummary = summarizeAvailabilitySlots(currentSlots, currentStatus);
        }

        if (previewHours) previewHours.textContent = currentSummary;

        if (previewMessage) {
            if (currentMessage) {
                previewMessage.style.display = '';
                previewMessage.textContent = currentMessage;
            } else {
                previewMessage.style.display = 'none';
            }
        }
    }

    function syncHiddenInputs() {
        if (availabilityInputs.status) availabilityInputs.status.value = currentStatus;
        if (availabilityInputs.hours) availabilityInputs.hours.value = currentSummary;
        if (availabilityInputs.message) availabilityInputs.message.value = currentMessage;
        if (availabilityInputs.slots) availabilityInputs.slots.value = JSON.stringify(currentSlots);
        if (availabilityInputs.exceptions) availabilityInputs.exceptions.value = JSON.stringify(currentExceptions);
        if (availabilityInputs.conges) availabilityInputs.conges.value = JSON.stringify(currentConges);
    }

    function refreshSlotTags() {
        if (!slotTags) {
            return;
        }
        slotTags.innerHTML = '';

        if (!currentSlots.length) {
            const empty = document.createElement('div');
            empty.className = 'availability-empty-tag';
            empty.textContent = 'Aucun creneau ajoute.';
            slotTags.appendChild(empty);
            return;
        }

        currentSlots.forEach((slot, index) => {
            const days = normalizeSlotDays(slot.days || []);
            const label = `${days.join(', ')} · ${slot.start}-${slot.end}`;
            const tag = createAvailabilityTag(label, () => {
                currentSlots.splice(index, 1);
                currentSummary = summarizeAvailabilitySlots(currentSlots, currentStatus);
                refreshSlotTags();
                updatePreview();
                syncHiddenInputs();
            });
            slotTags.appendChild(tag);
        });
    }

    function refreshExceptionTags() {
        if (!exceptionTags) {
            return;
        }
        exceptionTags.innerHTML = '';

        if (!currentExceptions.length && !currentConges.length) {
            const empty = document.createElement('div');
            empty.className = 'availability-empty-tag';
            empty.textContent = 'Aucune exception renseignee.';
            exceptionTags.appendChild(empty);
            return;
        }

        currentExceptions.forEach((exception, index) => {
            const label = `${exception.date} · ${exception.type}`;
            const tag = createAvailabilityTag(label, () => {
                currentExceptions.splice(index, 1);
                refreshExceptionTags();
                syncHiddenInputs();
            });
            exceptionTags.appendChild(tag);
        });

        currentConges.forEach((conges, index) => {
            const label = `${conges.start} → ${conges.end}`;
            const tag = createAvailabilityTag(label, () => {
                currentConges.splice(index, 1);
                refreshExceptionTags();
                syncHiddenInputs();
            });
            exceptionTags.appendChild(tag);
        });
    }

    function setActiveTab(tabName) {
        tabButtons.forEach((btn) => {
            const isActive = btn.dataset.availabilityTabBtn === tabName;
            btn.classList.toggle('is-active', isActive);
        });
        tabPanels.forEach((panel) => {
            const isActive = panel.dataset.availabilityTabPanel === tabName;
            panel.classList.toggle('is-active', isActive);
        });
    }

    function bindTabButtons() {
        tabButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                setActiveTab(btn.dataset.availabilityTabBtn || 'horaires');
            });
        });
    }

    function bindStatusButtons() {
        statusButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                currentStatus = normalizeAvailabilityStatus(btn.dataset.statusValue || 'disponible');
                statusButtons.forEach((item) => item.classList.remove('is-selected'));
                btn.classList.add('is-selected');
                currentSummary = summarizeAvailabilitySlots(currentSlots, currentStatus);
                updatePreview();
                syncHiddenInputs();
            });
        });

        statusButtons.forEach((btn) => {
            if (normalizeAvailabilityStatus(btn.dataset.statusValue || '') === currentStatus) {
                btn.classList.add('is-selected');
            }
        });
    }

    function bindStatusMessage() {
        if (!statusMessageInput) {
            return;
        }
        statusMessageInput.value = currentMessage;
        statusMessageInput.addEventListener('input', () => {
            currentMessage = String(statusMessageInput.value || '').trim();
            updatePreview();
            syncHiddenInputs();
        });
    }

    function bindSlotAdd() {
        if (!slotAddButton) {
            return;
        }
        slotAddButton.addEventListener('click', () => {
            const days = daysInputs
                .filter((input) => input.checked)
                .map((input) => String(input.value || '').trim())
                .filter(Boolean);
            if (!days.length) {
                showAppNotification('Selectionnez au moins un jour.', 'error');
                return;
            }

            const start = slotStartInput ? slotStartInput.value : '';
            const end = slotEndInput ? slotEndInput.value : '';
            if (!start || !end) {
                showAppNotification('Renseignez les horaires.', 'error');
                return;
            }

            currentSlots.push({ days, start, end });
            currentSummary = summarizeAvailabilitySlots(currentSlots, currentStatus);
            refreshSlotTags();
            updatePreview();
            syncHiddenInputs();
        });
    }

    function bindExceptionAdd() {
        if (!exceptionAddButton) {
            return;
        }
        exceptionAddButton.addEventListener('click', () => {
            const date = exceptionDateInput ? exceptionDateInput.value : '';
            if (!date) {
                showAppNotification('Choisissez une date.', 'error');
                return;
            }

            const type = exceptionTypeInput ? exceptionTypeInput.value : 'ferme';
            currentExceptions.push({ date, type });
            if (exceptionDateInput) exceptionDateInput.value = '';
            refreshExceptionTags();
            syncHiddenInputs();
        });
    }

    function bindCongesAdd() {
        if (!congesAddButton) {
            return;
        }
        congesAddButton.addEventListener('click', () => {
            const start = congesStartInput ? congesStartInput.value : '';
            const end = congesEndInput ? congesEndInput.value : '';
            if (!start || !end) {
                showAppNotification('Choisissez les dates de conges.', 'error');
                return;
            }

            if (end < start) {
                showAppNotification('La date de fin doit etre apres la date de debut.', 'error');
                return;
            }

            currentConges.push({ start, end });
            if (congesStartInput) congesStartInput.value = '';
            if (congesEndInput) congesEndInput.value = '';
            refreshExceptionTags();
            syncHiddenInputs();
        });
    }

    function bindDayChips() {
        daysInputs.forEach((input) => {
            const chip = input.closest('.availability-day-chip');
            if (!chip) {
                return;
            }
            const toggle = () => chip.classList.toggle('is-checked', input.checked);
            input.addEventListener('change', toggle);
            toggle();
        });
    }

    function init() {
        setActiveTab('horaires');
        bindTabButtons();
        bindStatusButtons();
        bindStatusMessage();
        bindSlotAdd();
        bindExceptionAdd();
        bindCongesAdd();
        bindDayChips();
        refreshSlotTags();
        refreshExceptionTags();
        updatePreview();
        syncHiddenInputs();
    }

    init();
}

function editCompetence(id) {
    const row = document.querySelector(`tr[data-id="${id}"]`);
    if (!row) return;

    const nom = row.querySelector('.comp-nom')?.textContent?.trim() || '';
    const desc = row.querySelector('.comp-desc')?.textContent?.trim() || '';
    const niveauText = row.querySelector('.comp-niveau')?.textContent?.replace('%', '').trim();
    const niveau = niveauText ? parseInt(niveauText, 10) : 50;

    const newNom = prompt('Nouveau nom :', nom);
    if (!newNom) return;
    const newDesc = prompt('Nouvelle description :', desc);
    if (newDesc === null) return;
    const newNiveau = prompt('Nouveau niveau (0-100) :', String(niveau));
    if (newNiveau === null) return;

    const editForm = document.getElementById('edit-comp-' + id);
    if (!editForm) return;

    editForm.querySelector('[name="nom"]').value = newNom;
    editForm.querySelector('[name="description"]').value = newDesc;
    editForm.querySelector('[name="niveau"]').value = parseInt(newNiveau, 10) || 0;
    editForm.submit();
}

function editCertification(id) {
    const row = document.querySelector(`tr[data-id="${id}"]`);
    if (!row) return;

    const nom = row.querySelector('.cert-nom')?.textContent?.trim() || '';
    const niveauText = row.querySelector('.cert-niveau')?.textContent?.replace('%', '').trim();
    const niveau = niveauText ? parseInt(niveauText, 10) : 50;

    const newNom = prompt('Nouveau nom :', nom);
    if (!newNom) return;
    const newNiveau = prompt('Nouveau niveau (0-100) :', String(niveau));
    if (newNiveau === null) return;

    const editForm = document.getElementById('edit-cert-' + id);
    if (!editForm) return;

    editForm.querySelector('[name="nom"]').value = newNom;
    editForm.querySelector('[name="niveau"]').value = parseInt(newNiveau, 10) || 0;
    editForm.submit();
}

function editExperience(id) {
    const row = document.querySelector(`tr[data-id="${id}"]`);
    if (!row) return;

    const poste = row.querySelector('.exp-poste')?.textContent?.trim() || '';
    const entreprise = row.querySelector('.exp-entreprise')?.textContent?.trim() || '';
    const periode = row.querySelector('.exp-periode')?.textContent?.trim() || '';
    const description = row.querySelector('.exp-description')?.textContent?.trim() || '';

    const posteEdit = prompt('Poste :', poste);
    if (!posteEdit) return;

    const entrepriseEdit = prompt('Entreprise :', entreprise);
    if (entrepriseEdit === null) return;

    const dateDebut = prompt('Date debut (YYYY-MM-DD) :', periode.split(' - ')[0] || '');
    if (!dateDebut) return;

    const dateFin = prompt('Date fin (YYYY-MM-DD ou vide) :', periode.split(' - ')[1] || '');
    if (dateFin === null) return;

    const descriptionEdit = prompt('Description :', description);
    if (descriptionEdit === null) return;

    const editForm = document.getElementById('edit-exp-' + id);
    if (!editForm) return;

    editForm.querySelector('[name="poste"]').value = posteEdit;
    editForm.querySelector('[name="entreprise"]').value = entrepriseEdit;
    editForm.querySelector('[name="date_debut"]').value = dateDebut;
    editForm.querySelector('[name="date_fin"]').value = dateFin;
    editForm.querySelector('[name="description"]').value = descriptionEdit;
    editForm.submit();
}

function formatPeriode(start, end) {
    if (!start && !end) return '';
    if (start && !end) return `${start} - Present`;
    if (!start && end) return `Present - ${end}`;
    return `${start} - ${end}`;
}

function applyTableSortAndSearch(rows, query, sortBy, orderBy, selectors) {
    if (!rows || !Array.isArray(rows)) {
        return [];
    }

    const normalizedQuery = String(query || '').trim().toLowerCase();
    let filteredRows = rows;

    if (normalizedQuery) {
        filteredRows = rows.filter((row) => {
            return selectors.some((selector) => {
                const text = row.querySelector(selector)?.textContent || '';
                return text.toLowerCase().includes(normalizedQuery);
            });
        });
    }

    const sortedRows = filteredRows.sort((a, b) => {
        const aText = (a.querySelector(sortBy)?.textContent || '').trim();
        const bText = (b.querySelector(sortBy)?.textContent || '').trim();

        if (orderBy === 'number') {
            const aNum = Number.parseFloat(aText.replace('%', '').replace(',', '.')) || 0;
            const bNum = Number.parseFloat(bText.replace('%', '').replace(',', '.')) || 0;
            return bNum - aNum;
        }

        return aText.localeCompare(bText, 'fr', { sensitivity: 'base' });
    });

    return sortedRows;
}

function applyTableStats(rows, panel, config) {
    if (!panel || !config) {
        return;
    }

    const totalEl = panel.querySelector(config.totalSelector);
    const averageEl = panel.querySelector(config.averageSelector);
    const advancedEl = panel.querySelector(config.advancedSelector);
    const visibleEl = panel.querySelector(config.visibleSelector);

    const total = rows.length;
    const values = rows.map((row) => {
        const valueText = row.querySelector(config.valueSelector)?.textContent || '0';
        return Number.parseFloat(valueText.replace('%', '').replace(',', '.')) || 0;
    });

    const average = values.length
        ? Math.round(values.reduce((acc, val) => acc + val, 0) / values.length)
        : 0;

    const advanced = values.filter((value) => value >= 80).length;

    if (totalEl) totalEl.textContent = String(total);
    if (averageEl) averageEl.textContent = `${average}%`;
    if (advancedEl) advancedEl.textContent = String(advanced);
    if (visibleEl) visibleEl.textContent = String(rows.length);
}

function bindTableTools(config) {
    if (!config) {
        return;
    }

    const tableBody = document.getElementById(config.tbodyId);
    if (!tableBody) {
        return;
    }

    const rows = Array.from(tableBody.querySelectorAll('tr[data-id]'));
    const searchInput = document.querySelector(config.searchSelector);
    const sortSelect = document.querySelector(config.sortSelector);
    const statsToggle = document.querySelector(config.statsToggleSelector);
    const statsPanel = document.querySelector(config.statsPanelSelector);

    function renderTable() {
        const sortValue = sortSelect ? sortSelect.value : '';
        const [sortKey, sortOrder] = sortValue.split('-');

        const sortMap = config.sortMap[sortValue] || config.sortMap.default;
        const sortedRows = applyTableSortAndSearch(rows, searchInput ? searchInput.value : '', sortMap.selector, sortMap.type, config.searchSelectors);

        tableBody.querySelectorAll('tr[data-id]').forEach((row) => row.remove());
        sortedRows.forEach((row) => tableBody.appendChild(row));

        if (statsPanel) {
            applyTableStats(sortedRows, statsPanel, config.stats);
        }

        const emptyRow = tableBody.querySelector('.table-empty-search');
        if (!sortedRows.length && !emptyRow) {
            const empty = document.createElement('tr');
            empty.className = 'table-empty-search';
            const cell = document.createElement('td');
            cell.colSpan = config.colspan;
            cell.textContent = 'Aucun resultat.';
            empty.appendChild(cell);
            tableBody.appendChild(empty);
        } else if (sortedRows.length && emptyRow) {
            emptyRow.remove();
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', renderTable);
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', renderTable);
    }

    if (statsToggle && statsPanel) {
        statsToggle.addEventListener('click', () => {
            const isVisible = statsPanel.style.display !== 'none';
            statsPanel.style.display = isVisible ? 'none' : 'flex';
            if (!isVisible) {
                applyTableStats(rows, statsPanel, config.stats);
            }
        });
    }

    renderTable();
}

function applyCompetenceTableTools() {
    bindTableTools({
        tbodyId: 'competences-list',
        colspan: 7,
        searchSelector: '[data-comp-search="true"]',
        sortSelector: '[data-comp-sort="true"]',
        statsToggleSelector: '[data-comp-stats-toggle="true"]',
        statsPanelSelector: '[data-comp-stats-panel="true"]',
        searchSelectors: ['.comp-nom', '.comp-desc'],
        sortMap: {
            'niveau-desc': { selector: '.comp-niveau', type: 'number' },
            'niveau-asc': { selector: '.comp-niveau', type: 'number' },
            'nom-asc': { selector: '.comp-nom', type: 'text' },
            'nom-desc': { selector: '.comp-nom', type: 'text' },
            default: { selector: '.comp-niveau', type: 'number' }
        },
        stats: {
            totalSelector: '[data-comp-stat-total="true"]',
            averageSelector: '[data-comp-stat-average="true"]',
            advancedSelector: '[data-comp-stat-advanced="true"]',
            visibleSelector: '[data-comp-stat-visible="true"]',
            valueSelector: '.comp-niveau'
        }
    });
}

function applyCertificationTableTools() {
    bindTableTools({
        tbodyId: 'certifications-list',
        colspan: 6,
        searchSelector: '[data-cert-search="true"]',
        sortSelector: '[data-cert-sort="true"]',
        statsToggleSelector: '[data-cert-stats-toggle="true"]',
        statsPanelSelector: '[data-cert-stats-panel="true"]',
        searchSelectors: ['.cert-nom'],
        sortMap: {
            'niveau-desc': { selector: '.cert-niveau', type: 'number' },
            'niveau-asc': { selector: '.cert-niveau', type: 'number' },
            'nom-asc': { selector: '.cert-nom', type: 'text' },
            'nom-desc': { selector: '.cert-nom', type: 'text' },
            default: { selector: '.cert-niveau', type: 'number' }
        },
        stats: {
            totalSelector: '[data-cert-stat-total="true"]',
            averageSelector: '[data-cert-stat-average="true"]',
            advancedSelector: '[data-cert-stat-advanced="true"]',
            visibleSelector: '[data-cert-stat-visible="true"]',
            valueSelector: '.cert-niveau'
        }
    });
}

function applyExperienceTableTools() {
    bindTableTools({
        tbodyId: 'experiences-list',
        colspan: 5,
        searchSelector: '[data-exp-search="true"]',
        sortSelector: '[data-exp-sort="true"]',
        statsToggleSelector: '[data-exp-stats-toggle="true"]',
        statsPanelSelector: '[data-exp-stats-panel="true"]',
        searchSelectors: ['.exp-poste', '.exp-entreprise', '.exp-description'],
        sortMap: {
            'date-desc': { selector: '.exp-periode', type: 'text' },
            'date-asc': { selector: '.exp-periode', type: 'text' },
            'poste-asc': { selector: '.exp-poste', type: 'text' },
            'entreprise-asc': { selector: '.exp-entreprise', type: 'text' },
            default: { selector: '.exp-periode', type: 'text' }
        },
        stats: {
            totalSelector: '[data-exp-stat-total="true"]',
            averageSelector: '[data-exp-stat-active="true"]',
            advancedSelector: '[data-exp-stat-companies="true"]',
            visibleSelector: '[data-exp-stat-visible="true"]',
            valueSelector: '.exp-periode'
        }
    });
}

function bindModalShortcuts() {
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            document.querySelectorAll('.modal-overlay').forEach((modal) => {
                if (modal.style.display === 'flex') {
                    modal.style.display = 'none';
                }
            });
            document.body.style.overflow = '';
        }
    });
}

function bindPortfolioFileInputs() {
    const forms = document.querySelectorAll('form[action*="/profil/addPortfolioFile"]');
    forms.forEach((form) => {
        if (form.dataset.portfolioBound === '1') {
            return;
        }
        form.dataset.portfolioBound = '1';
        const select = form.querySelector('[data-realisation-select]');
        if (select) {
            select.addEventListener('change', () => {
                togglePortfolioRealisationCustomInput(form);
            });
        }
    });
}

function initProfilePage() {
    bindAjaxAddForms();
    bindGeneralWriteFormValidation();
    bindPortfolioRealisationSelector();
    bindPortfolioFileInputs();
    bindAvailabilityDesigner();
    bindModalShortcuts();
    applyCompetenceTableTools();
    applyCertificationTableTools();
    applyExperienceTableTools();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initProfilePage);
} else {
    initProfilePage();
}
