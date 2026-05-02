const form = document.querySelector('.idea-form');
const skillRows = document.getElementById('skillsRows');
const materialRows = document.getElementById('materialsRows');
const addSkillBtn = document.getElementById('addSkillBtn');
const addMaterialBtn = document.getElementById('addMaterialBtn');
const publishIdeaBtn = document.getElementById('publishIdeaBtn');
const heroPublishBtn = document.getElementById('heroPublishBtn');
const asidePublishBtn = document.getElementById('asidePublishBtn');
const closeFormBtn = document.getElementById('closeFormBtn');
const quickCreateSection = document.getElementById('quickCreateSection');
const cards = document.getElementById('projectCards');
const emptyState = document.getElementById('emptyState');
const projectFilterForm = document.getElementById('projectFilterForm');
const projectFilterBtn = document.getElementById('projectFilterBtn');
const ideesProjetSection = document.getElementById('ideesProjet');
const titleInput = document.getElementById('titleInput');
const categorySelect = document.getElementById('categorySelect');
const statusSelect = document.getElementById('statusSelect');
const budgetInput = document.getElementById('budgetInput');
const descriptionInput = document.getElementById('descriptionInput');
const analyseAiBtn = document.getElementById('analyseAiBtn');
const aiFeedback = document.getElementById('aiFeedback');
const aiResult = document.getElementById('aiResult');

function toggleEmptyState() {
  if (!cards || !emptyState) {
    return;
  }

  emptyState.style.display = cards.children.length === 0 ? 'block' : 'none';
}

function openFormSection() {
  if (!quickCreateSection) {
    return;
  }

  quickCreateSection.classList.add('is-open');
  quickCreateSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function closeFormSection() {
  if (!quickCreateSection) {
    return;
  }

  quickCreateSection.classList.remove('is-open');
}

function addSkillRow() {
  if (!skillRows) {
    return;
  }

  const row = document.createElement('div');
  row.className = 'builder-row skill-row';

  const nameInput = document.createElement('input');
  nameInput.className = 'row-name';
  nameInput.name = 'skills_name[]';
  nameInput.type = 'text';
  nameInput.placeholder = 'Nom de la competence';

  const levelSelect = document.createElement('select');
  levelSelect.className = 'row-level';
  levelSelect.name = 'skills_level[]';
  levelSelect.innerHTML =
    '<option value="">Niveau</option>' +
    '<option>Debutant</option>' +
    '<option>Intermediaire</option>' +
    '<option>Avance</option>' +
    '<option>Expert</option>';

  const removeBtn = document.createElement('button');
  removeBtn.type = 'button';
  removeBtn.className = 'row-remove';
  removeBtn.textContent = 'Retirer';

  row.appendChild(nameInput);
  row.appendChild(levelSelect);
  row.appendChild(removeBtn);
  skillRows.appendChild(row);
}

function addMaterialRow() {
  if (!materialRows) {
    return;
  }

  const row = document.createElement('div');
  row.className = 'builder-row material-row';

  const nameInput = document.createElement('input');
  nameInput.className = 'row-name';
  nameInput.name = 'materials_name[]';
  nameInput.type = 'text';
  nameInput.placeholder = 'Nom du materiau';

  const qtyInput = document.createElement('input');
  qtyInput.className = 'row-qty';
  qtyInput.name = 'materials_qty[]';
  qtyInput.type = 'number';
  qtyInput.min = '1';
  qtyInput.step = '1';
  qtyInput.placeholder = 'Quantite';

  const unitPriceInput = document.createElement('input');
  unitPriceInput.className = 'row-price';
  unitPriceInput.name = 'materials_price[]';
  unitPriceInput.type = 'number';
  unitPriceInput.min = '0';
  unitPriceInput.step = '0.01';
  unitPriceInput.placeholder = 'Prix unitaire';

  const removeBtn = document.createElement('button');
  removeBtn.type = 'button';
  removeBtn.className = 'row-remove';
  removeBtn.textContent = 'Retirer';

  row.appendChild(nameInput);
  row.appendChild(qtyInput);
  row.appendChild(unitPriceInput);
  row.appendChild(removeBtn);
  materialRows.appendChild(row);
}

function handleSkillRowRemove(event) {
  const target = event.target;
  if (!(target instanceof HTMLElement)) {
    return;
  }

  if (!target.classList.contains('row-remove')) {
    return;
  }

  const row = target.closest('.builder-row');
  if (row) {
    row.remove();
  }

  if (skillRows && skillRows.children.length === 0) {
    addSkillRow();
  }
}

function handleMaterialRowRemove(event) {
  const target = event.target;
  if (!(target instanceof HTMLElement)) {
    return;
  }

  if (!target.classList.contains('row-remove')) {
    return;
  }

  const row = target.closest('.builder-row');
  if (row) {
    row.remove();
  }

  if (materialRows && materialRows.children.length === 0) {
    addMaterialRow();
  }
}

function validateSkillRows() {
  if (!skillRows) {
    return true;
  }

  const rows = Array.from(skillRows.querySelectorAll('.skill-row'));

  for (const row of rows) {
    const nameInput = row.querySelector('.row-name');
    const levelSelect = row.querySelector('.row-level');

    if (!(nameInput instanceof HTMLInputElement) || !(levelSelect instanceof HTMLSelectElement)) {
      continue;
    }

    const nameValue = nameInput.value.trim();
    const levelValue = levelSelect.value.trim();

    if (nameValue !== '' && levelValue === '') {
      alert('Chaque competence doit avoir un niveau.');
      levelSelect.focus();
      return false;
    }

    if (nameValue === '' && levelValue !== '') {
      alert('Ajoutez aussi le nom de la competence.');
      nameInput.focus();
      return false;
    }
  }

  return true;
}

function setAiFeedback(message, isError) {
  if (!aiFeedback) {
    return;
  }

  aiFeedback.textContent = message;
  aiFeedback.classList.remove('ok', 'err');
  if (message === '') {
    return;
  }

  if (isError) {
    aiFeedback.classList.add('err');
  } else {
    aiFeedback.classList.add('ok');
  }
}

function escapeHtml(value) {
  const text = String(value);
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function renderSimpleList(title, items) {
  if (!Array.isArray(items) || items.length === 0) {
    return '';
  }

  const listHtml = items
    .map(function (item) {
      return '<li>' + escapeHtml(item) + '</li>';
    })
    .join('');

  return '<article class="ai-block"><h4>' + escapeHtml(title) + '</h4><ul>' + listHtml + '</ul></article>';
}

function renderCorrections(corrections) {
  if (!Array.isArray(corrections) || corrections.length === 0) {
    return '';
  }

  const listHtml = corrections.map(function (item) {
    if (!item || typeof item !== 'object') {
      return '';
    }
    const field = item.field || item.champ || 'Champ';
    const issue = item.issue || item.probleme || '';
    const suggestion = item.suggestion || '';
    return '<li><strong>' + escapeHtml(field) + ':</strong> '
      + escapeHtml(issue)
      + (suggestion ? ' -> ' + escapeHtml(suggestion) : '')
      + '</li>';
  }).join('');

  if (listHtml === '') {
    return '';
  }

  return '<article class="ai-block"><h4>Corrections</h4><ul>' + listHtml + '</ul></article>';
}

function renderOrthographe(items) {
  if (!Array.isArray(items) || items.length === 0) {
    return '';
  }

  const listHtml = items.map(function (item) {
    if (!item || typeof item !== 'object') {
      return '';
    }
    const src = item.texte || item.source || '';
    const fix = item.correction || '';
    const level = item.gravite || '';
    if (!src && !fix) {
      return '';
    }
    return '<li>'
      + (src ? '<strong>' + escapeHtml(src) + '</strong>' : '')
      + (fix ? ' -> ' + escapeHtml(fix) : '')
      + (level ? ' (' + escapeHtml(level) + ')' : '')
      + '</li>';
  }).join('');

  if (listHtml === '') {
    return '';
  }

  return '<article class="ai-block"><h4>Orthographe</h4><ul>' + listHtml + '</ul></article>';
}

function renderSimilaires(items) {
  if (!Array.isArray(items) || items.length === 0) {
    return '';
  }

  const listHtml = items.map(function (item) {
    if (!item || typeof item !== 'object') {
      return '';
    }
    const name = item.name || item.nom || 'Projet similaire';
    const similarity = item.similarity !== undefined ? item.similarity : item.similarite;
    const reason = item.reason || item.raison || '';
    const link = item.proofLink || item.lien_recherche || '';
    const similarityText = similarity !== undefined && similarity !== null ? ' (' + escapeHtml(similarity) + '%)' : '';
    const linkHtml = link ? ' <a href="' + escapeHtml(link) + '" target="_blank" rel="noopener noreferrer">source</a>' : '';
    return '<li><strong>' + escapeHtml(name) + '</strong>' + similarityText
      + (reason ? ': ' + escapeHtml(reason) : '')
      + linkHtml + '</li>';
  }).join('');

  if (listHtml === '') {
    return '';
  }

  return '<article class="ai-block"><h4>Projets similaires</h4><ul>' + listHtml + '</ul></article>';
}

function renderAiResult(result) {
  if (!aiResult) {
    return;
  }

  const score = typeof result.score === 'number' ? result.score : null;
  const resume = typeof result.resume === 'string' ? result.resume : '';

  const verdictObj = result.verdict_originalite && typeof result.verdict_originalite === 'object'
    ? result.verdict_originalite
    : null;
  const verdictStatut = verdictObj ? (verdictObj.statut || '') : '';
  const verdictJustif = verdictObj ? (verdictObj.justification || '') : '';
  const verdictConfiance = verdictObj && verdictObj.confiance !== undefined ? verdictObj.confiance : null;

  const verdictProofsRaw = verdictObj ? (verdictObj.preuves || verdictObj.sources || []) : [];
  const verdictProofs = Array.isArray(verdictProofsRaw) ? verdictProofsRaw : [];

  const fallbackProofs = Array.isArray(result.projets_similaires)
    ? result.projets_similaires
      .filter(function (item) {
        return item && typeof item === 'object' && (item.proofLink || item.lien_recherche);
      })
      .slice(0, 3)
      .map(function (item) {
        return {
          source: item.name || item.nom || 'Projet similaire',
          url: item.proofLink || item.lien_recherche,
          raison: item.reason || item.raison || ''
        };
      })
    : [];

  const allProofs = verdictProofs.length > 0 ? verdictProofs : fallbackProofs;

  const proofList = allProofs
    .map(function (proof) {
      if (!proof || typeof proof !== 'object') {
        return '';
      }

      const url = proof.url || proof.link || '';
      if (!url) {
        return '';
      }

      const source = proof.source || proof.nom || 'Source';
      const reason = proof.raison || proof.reason || '';
      return '<li><a href="' + escapeHtml(url) + '" target="_blank" rel="noopener noreferrer">'
        + escapeHtml(source)
        + '</a>'
        + (reason ? ' - ' + escapeHtml(reason) : '')
        + '</li>';
    })
    .filter(function (line) {
      return line !== '';
    })
    .join('');

  const proofHtml = proofList
    ? '<p><strong>Preuves:</strong></p><ul>' + proofList + '</ul>'
    : '<p><strong>Preuves:</strong> aucune source fournie.</p>';

  const header = '<div class="ai-result-head">'
    + (score !== null ? '<p class="ai-score">Score: ' + escapeHtml(score) + '/100</p>' : '')
    + (resume ? '<p class="ai-resume">' + escapeHtml(resume) + '</p>' : '')
    + '</div>';

  const originality = verdictStatut || verdictJustif
    ? '<article class="ai-block"><h4>Originalite de l idee</h4><p><strong>' + escapeHtml(verdictStatut || 'Non precise') + '</strong>'
      + (verdictConfiance !== null ? ' - confiance: ' + escapeHtml(verdictConfiance) + '%' : '')
      + '</p>'
      + (verdictJustif ? '<p>' + escapeHtml(verdictJustif) + '</p>' : '')
      + proofHtml
      + '</article>'
    : '';

  const improvements = renderSimpleList('Ameliorations', result.ameliorations);
  const corrections = renderCorrections(result.corrections);
  const orthographe = renderOrthographe(result.orthographe);
  const trends = renderSimpleList('Tendances', result.tendances);
  const risques = renderSimpleList('Risques', result.risques);
  const similaires = renderSimilaires(result.projets_similaires);

  aiResult.innerHTML = header + originality + improvements + corrections + orthographe + trends + risques + similaires;
  aiResult.classList.add('is-visible');
}

function collectSkillsForAi() {
  if (!skillRows) {
    return [];
  }

  const rows = Array.from(skillRows.querySelectorAll('.skill-row'));
  const items = [];
  for (const row of rows) {
    const nameInput = row.querySelector('.row-name');
    const levelSelect = row.querySelector('.row-level');
    if (!(nameInput instanceof HTMLInputElement) || !(levelSelect instanceof HTMLSelectElement)) {
      continue;
    }

    const name = nameInput.value.trim();
    const level = levelSelect.value.trim();
    if (name !== '' || level !== '') {
      items.push({ name, level });
    }
  }

  return items;
}

function collectMaterialsForAi() {
  if (!materialRows) {
    return [];
  }

  const rows = Array.from(materialRows.querySelectorAll('.material-row'));
  const items = [];
  for (const row of rows) {
    const nameInput = row.querySelector('.row-name');
    const qtyInput = row.querySelector('.row-qty');
    if (!(nameInput instanceof HTMLInputElement) || !(qtyInput instanceof HTMLInputElement)) {
      continue;
    }

    const name = nameInput.value.trim();
    const quantity = qtyInput.value.trim();
    if (name !== '' || quantity !== '') {
      items.push({ name, quantity });
    }
  }

  return items;
}

async function runAiAnalysis() {
  const payload = {
    title: titleInput ? titleInput.value.trim() : '',
    category: categorySelect ? categorySelect.value.trim() : '',
    status: statusSelect ? statusSelect.value.trim() : '',
    budget: budgetInput ? budgetInput.value.trim() : '',
    description: descriptionInput ? descriptionInput.value.trim() : '',
    competences: collectSkillsForAi(),
    materials: collectMaterialsForAi()
  };

  if (payload.title === '' && payload.description === '') {
    setAiFeedback('Ajoutez au moins un titre ou une description avant analyse.', true);
    return;
  }

  if (analyseAiBtn instanceof HTMLButtonElement) {
    analyseAiBtn.disabled = true;
    analyseAiBtn.textContent = 'Analyse en cours...';
  }
  if (aiResult) {
    aiResult.classList.remove('is-visible');
    aiResult.innerHTML = '';
  }
  setAiFeedback('Analyse IA en cours...', false);

  try {
    const response = await fetch('ai_analyse.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    const result = await response.json();

    if (!response.ok || (result && result.error)) {
      const errorMessage = result && result.error ? result.error : 'Erreur IA inconnue';
      setAiFeedback(errorMessage, true);
      return;
    }

    const score = typeof result.score === 'number' ? result.score : null;
    const resume = typeof result.resume === 'string' ? result.resume : '';
    if (score !== null && resume !== '') {
      setAiFeedback('Score: ' + score + '/100. ' + resume, false);
    } else if (resume !== '') {
      setAiFeedback(resume, false);
    } else {
      setAiFeedback('Analyse terminee avec succes.', false);
    }

    renderAiResult(result);
  } catch (error) {
    setAiFeedback('Impossible de contacter le service IA.', true);
  } finally {
    if (analyseAiBtn instanceof HTMLButtonElement) {
      analyseAiBtn.disabled = false;
      analyseAiBtn.textContent = 'Analyser avec IA';
    }
  }
}

if (skillRows) {
  skillRows.addEventListener('click', handleSkillRowRemove);
  if (skillRows.children.length === 0) {
    addSkillRow();
  }
}

if (materialRows) {
  materialRows.addEventListener('click', handleMaterialRowRemove);
  if (materialRows.children.length === 0) {
    addMaterialRow();
  }
}

if (addSkillBtn) {
  addSkillBtn.addEventListener('click', function () {
    addSkillRow();
  });
}

if (addMaterialBtn) {
  addMaterialBtn.addEventListener('click', function () {
    addMaterialRow();
  });
}

if (publishIdeaBtn) {
  publishIdeaBtn.addEventListener('click', function () {
    openFormSection();
  });
}

if (heroPublishBtn) {
  heroPublishBtn.addEventListener('click', function () {
    openFormSection();
  });
}

if (asidePublishBtn) {
  asidePublishBtn.addEventListener('click', function () {
    openFormSection();
  });
}

if (closeFormBtn) {
  closeFormBtn.addEventListener('click', function () {
    closeFormSection();
  });
}

if (analyseAiBtn) {
  analyseAiBtn.addEventListener('click', function () {
    runAiAnalysis();
  });
}

if (projectFilterForm) {
  projectFilterForm.addEventListener('submit', function () {
    if (projectFilterBtn instanceof HTMLButtonElement) {
      projectFilterBtn.disabled = true;
      projectFilterBtn.textContent = 'Filtrage...';
    }
  });
}

if (form) {
  form.addEventListener('submit', function (event) {
    const titleValue = titleInput ? titleInput.value.trim() : '';
    const categoryValue = categorySelect ? categorySelect.value.trim() : '';
    const statusValue = statusSelect ? statusSelect.value.trim() : '';

    if (titleValue === '' || categoryValue === '' || statusValue === '') {
      event.preventDefault();
      alert('Titre, categorie et statut sont obligatoires.');

      if (titleInput && titleValue === '') {
        titleInput.focus();
      } else if (categorySelect && categoryValue === '') {
        categorySelect.focus();
      } else if (statusSelect && statusValue === '') {
        statusSelect.focus();
      }
      return;
    }

    if (!validateSkillRows()) {
      event.preventDefault();
      return;
    }
  });
}

toggleEmptyState();

if (ideesProjetSection) {
  const params = new URLSearchParams(window.location.search);
  const hasFilters = params.has('q') || params.has('filter_category') || params.has('filter_skill');
  if (hasFilters) {
    ideesProjetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}
