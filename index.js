// index.js - Version propre
function showSection(id) {
    if (id === 'profil') {
        window.location.href = 'profil_complet.php';
    } else if (id === 'accueil') {
        window.location.href = 'dashboard.php';
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
}

function handleLogout() {
    if (confirm('Voulez-vous vraiment vous déconnecter ?')) {
        window.location.href = 'logout.php';
    }
}

document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id.replace('modal-', ''));
    });
});