const API_URL = 'api/ideas.php';

const statusFilter = document.getElementById('statusFilter');
const categoryFilter = document.getElementById('categoryFilter');
const searchInput = document.getElementById('searchInput');
const tableBody = document.getElementById('ideasTableBody');
const statTotal = document.getElementById('statTotal');
const statOpen = document.getElementById('statOpen');
const statProgress = document.getElementById('statProgress');
const statClosed = document.getElementById('statClosed');
const resultsInfo = document.getElementById('resultsInfo');
const refreshBtn = document.getElementById('refreshBtn');

const editDialog = document.getElementById('editDialog');
const editForm = document.getElementById('editForm');
const cancelEditBtn = document.getElementById('cancelEditBtn');
const editId = document.getElementById('editId');
const editTitle = document.getElementById('editTitle');
const editCategory = document.getElementById('editCategory');
const editStatus = document.getElementById('editStatus');
const editBudget = document.getElementById('editBudget');
const editDescription = document.getElementById('editDescription');

let ideas = [];

function escapeHtml(value) {
  const text = String(value ?? '');
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function parseBudget(value) {
  const amount = Number(value);
  return Number.isFinite(amount) ? amount : 0;
}

function formatDate(dateString) {
  if (!dateString) {
    return '-';
  }

  const date = new Date(dateString);
  if (Number.isNaN(date.getTime())) {
    return dateString;
  }

  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  });
}

function getStatusClass(status) {
  if (status === 'Ouvert') {
    return 'status-open';
  }
  if (status === 'En cours') {
    return 'status-progress';
  }
  return 'status-closed';
}

async function fetchIdeas() {
  const response = await fetch(API_URL);
  const result = await response.json();

  if (!response.ok || !result.ok || !Array.isArray(result.data)) {
    throw new Error('Impossible de charger les idees');
  }

  return result.data;
}

async function updateIdea(payload) {
  const response = await fetch(API_URL, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(payload)
  });

  const result = await response.json();
  if (!response.ok || !result.ok) {
    const message = result && result.message ? result.message : 'Erreur serveur';
    throw new Error(message);
  }

  return result.data;
}

async function deleteIdea(id) {
  const response = await fetch(API_URL + '?id=' + encodeURIComponent(id), {
    method: 'DELETE'
  });

  const result = await response.json();
  if (!response.ok || !result.ok) {
    throw new Error('Suppression impossible');
  }
}

function updateCategoryFilter() {
  const categories = [...new Set(ideas.map((idea) => idea.category).filter(Boolean))].sort();
  categoryFilter.innerHTML = '<option value="">Toutes les categories</option>';

  for (let i = 0; i < categories.length; i += 1) {
    const option = document.createElement('option');
    option.value = categories[i];
    option.textContent = categories[i];
    categoryFilter.appendChild(option);
  }
}

function getFilteredIdeas() {
  const query = searchInput.value.trim().toLowerCase();
  const statusValue = statusFilter.value;
  const categoryValue = categoryFilter.value;

  return ideas.filter((idea) => {
    const inStatus = !statusValue || idea.status === statusValue;
    const inCategory = !categoryValue || idea.category === categoryValue;

    const searchable = [idea.title, idea.category, idea.status].join(' ').toLowerCase();
    const inSearch = !query || searchable.includes(query);

    return inStatus && inCategory && inSearch;
  });
}

function renderStats(filtered) {
  const total = ideas.length;
  const opened = ideas.filter((idea) => idea.status === 'Ouvert').length;
  const inProgress = ideas.filter((idea) => idea.status === 'En cours').length;
  const closed = ideas.filter((idea) => idea.status === 'Ferme').length;

  statTotal.textContent = String(total);
  statOpen.textContent = String(opened);
  statProgress.textContent = String(inProgress);
  statClosed.textContent = String(closed);

  resultsInfo.textContent = filtered.length + ' resultat(s)';
}

function renderTable() {
  const filtered = getFilteredIdeas();
  tableBody.innerHTML = '';

  if (filtered.length === 0) {
    const row = document.createElement('tr');
    row.innerHTML = '<td colspan="7">Aucune idee trouvee.</td>';
    tableBody.appendChild(row);
    renderStats(filtered);
    return;
  }

  for (let i = 0; i < filtered.length; i += 1) {
    const idea = filtered[i];
    const row = document.createElement('tr');

    row.innerHTML =
      '<td>' + (i + 1) + '</td>' +
      '<td><strong>' + escapeHtml(idea.title) + '</strong></td>' +
      '<td>' + escapeHtml(idea.category || '-') + '</td>' +
      '<td><span class="status-pill ' + getStatusClass(idea.status) + '">' + escapeHtml(idea.status) + '</span></td>' +
      '<td>' + parseBudget(idea.budget).toLocaleString('fr-FR') + ' DT</td>' +
      '<td>' + escapeHtml(formatDate(idea.createdAt)) + '</td>' +
      '<td class="row-actions">' +
      '<button class="btn-sm" data-action="edit" data-id="' + idea.id + '">Modifier</button>' +
      '<button class="btn-sm danger" data-action="delete" data-id="' + idea.id + '">Supprimer</button>' +
      '</td>';

    tableBody.appendChild(row);
  }

  renderStats(filtered);
}

async function refreshIdeas() {
  try {
    ideas = await fetchIdeas();
    updateCategoryFilter();
    renderTable();
  } catch (error) {
    alert(error.message || 'Impossible de charger les idees');
  }
}

function openEditDialog(ideaId) {
  const idea = ideas.find((item) => String(item.id) === String(ideaId));
  if (!idea) {
    return;
  }

  editId.value = idea.id;
  editTitle.value = idea.title;
  editCategory.value = idea.category || '';
  editStatus.value = idea.status || '';
  editBudget.value = parseBudget(idea.budget);
  editDescription.value = idea.description || '';

  editDialog.showModal();
}

function handleTableClick(event) {
  const target = event.target;
  if (!(target instanceof HTMLElement)) {
    return;
  }

  const action = target.getAttribute('data-action');
  const id = target.getAttribute('data-id');

  if (!action || !id) {
    return;
  }

  if (action === 'edit') {
    openEditDialog(id);
  }

  if (action === 'delete') {
    const ok = confirm('Supprimer cette idee ?');
    if (!ok) {
      return;
    }

    deleteIdea(id)
      .then(refreshIdeas)
      .catch(() => alert('Suppression impossible'));
  }
}

function handleEditSubmit(event) {
  event.preventDefault();

  const payload = {
    id: editId.value,
    title: editTitle.value.trim(),
    category: editCategory.value.trim(),
    status: editStatus.value,
    budget: editBudget.value,
    description: editDescription.value.trim()
  };

  updateIdea(payload)
    .then(() => {
      editDialog.close();
      refreshIdeas();
    })
    .catch((error) => alert(error.message || 'Erreur de mise a jour'));
}

statusFilter.addEventListener('change', renderTable);
categoryFilter.addEventListener('change', renderTable);
searchInput.addEventListener('input', renderTable);
refreshBtn.addEventListener('click', refreshIdeas);
tableBody.addEventListener('click', handleTableClick);
editForm.addEventListener('submit', handleEditSubmit);
cancelEditBtn.addEventListener('click', function () {
  editDialog.close();
});

refreshIdeas();
