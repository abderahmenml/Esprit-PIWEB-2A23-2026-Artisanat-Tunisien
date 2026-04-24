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
const titleInput = document.getElementById('titleInput');
const categorySelect = document.getElementById('categorySelect');
const statusSelect = document.getElementById('statusSelect');

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
