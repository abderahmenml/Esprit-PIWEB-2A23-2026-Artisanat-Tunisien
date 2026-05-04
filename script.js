
let nextId = 7;
let currentTab = 'formations'; // 'formations' or 'workshops'

// ========== AFFICHER LES CARTES ==========
function afficherCartes() {
  const grid = document.getElementById('cardsGrid');
  grid.innerHTML = '';

  let dataToShow = formationsData;
  const sectionTitle = document.getElementById('sectionTitle');

  if (currentTab === 'formations') {
    dataToShow = formationsData;
    sectionTitle.textContent = 'Formations disponibles';
  } else {
    dataToShow = workshopsData;
    sectionTitle.textContent = 'Workshops & Ateliers pratiques';
  }

  for (let i = 0; i < dataToShow.length; i++) {
    const item = dataToShow[i];
    const card = document.createElement('div');
    card.className = 'card';
    card.setAttribute('data-niveau', item.niveau);
    card.setAttribute('data-certif', item.certif);
    card.setAttribute('data-type', item.type);

    let tagHtml = '';
    if (item.tag !== '') {
      tagHtml = `<span class="tag tag-new">${item.tag}</span>`;
    }

    let certifHtml = '';
    if (item.certif === 'oui') {
      certifHtml = '<span class="tag tag-certif">📜 Certifiant</span>';
    }

    let durationLabel = '🕐';
    if (item.type === 'workshop') {
      durationLabel = '⏱️ Durée';
    }

    let videosHtml = '';
    if (item.videos !== '0') {
      videosHtml = `<span>📹 ${item.videos} vidéos</span>`;
    }

    let priceNote = 'Attestation de suivi';
    if (item.certif === 'oui') {
      priceNote = 'Certificat inclus';
    } else if (item.type === 'workshop') {
      priceNote = 'Matériel inclus';
    }

    let buttonClass = 'btn-inscrit';
    if (item.certif !== 'oui') {
      buttonClass = 'btn-inscrit vert';
    }

    const titreSecurise = item.titre.replace(/'/g, "\\'");

    card.innerHTML = `
      <div class="card-body">
        <div class="card-meta">
          <span class="tag tag-niveau">${getNiveauText(item.niveau)}</span>
          ${certifHtml}
          ${tagHtml}
        </div>
        <h3>${item.titre}</h3>
        <p>${item.description}</p>
        <div class="card-info">
          <span>${durationLabel} ${item.duree}</span>
          ${videosHtml}
          <span>👤 ${item.inscrits} inscrits</span>
        </div>
      </div>
      <div class="card-mentor">
        <div class="mentor-avatar" style="background:var(--marron)">${item.avatar}</div>
        <div class="mentor-info">
          <strong>${item.mentor}</strong>
          <small>${item.ville}</small>
        </div>
      </div>
      <div class="card-footer">
        <div class="price">${item.prix} TND<small>${priceNote}</small></div>
        <button class="${buttonClass}" onclick="ouvrirModal('${titreSecurise}')">S'inscrire</button>
      </div>
    `;
    grid.appendChild(card);
  }

  mettreAJourStats();
}

function getNiveauText(niveau) {
  switch(niveau) {
    case 'debutant': return 'Débutant';
    case 'intermediaire': return 'Intermédiaire';
    case 'avance': return 'Avancé';
    default: return niveau;
  }
}

function mettreAJourStats() {
  const allItems = [];
  for (let i = 0; i < formationsData.length; i++) {
    allItems.push(formationsData[i]);
  }
  for (let i = 0; i < workshopsData.length; i++) {
    allItems.push(workshopsData[i]);
  }

  const actives = allItems.length;

  let totalInscrits = 0;
  const mentorsUniques = [];
  const disciplinesUniques = [];

  for (let i = 0; i < allItems.length; i++) {
    const item = allItems[i];
    totalInscrits = totalInscrits + item.inscrits;

    let mentorExiste = false;
    for (let j = 0; j < mentorsUniques.length; j++) {
      if (mentorsUniques[j] === item.mentor) {
        mentorExiste = true;
        break;
      }
    }
    if (!mentorExiste) {
      mentorsUniques.push(item.mentor);
    }

    const motDiscipline = item.titre.split(' ')[0];
    let disciplineExiste = false;
    for (let k = 0; k < disciplinesUniques.length; k++) {
      if (disciplinesUniques[k] === motDiscipline) {
        disciplineExiste = true;
        break;
      }
    }
    if (!disciplineExiste) {
      disciplinesUniques.push(motDiscipline);
    }
  }

  let nombreDisciplines = disciplinesUniques.length;
  if (nombreDisciplines < 6) {
    nombreDisciplines = 6;
  }

  document.getElementById('statFormations').textContent = actives;
  document.getElementById('statMentors').textContent = mentorsUniques.length;
  document.getElementById('statCertifies').textContent = totalInscrits + '+';
  document.getElementById('statDisciplines').textContent = nombreDisciplines;
}

// ========== SWITCH BETWEEN TABS ==========
function switchToFormations() {
  currentTab = 'formations';
  document.getElementById('formationsTabLink').classList.add('active');
  document.getElementById('workshopsTabLink').classList.remove('active');
  afficherCartes();
  resetFiltersAndSearch();
}

function switchToWorkshops() {
  currentTab = 'workshops';
  document.getElementById('workshopsTabLink').classList.add('active');
  document.getElementById('formationsTabLink').classList.remove('active');
  afficherCartes();
  resetFiltersAndSearch();
}

function resetFiltersAndSearch() {
  // Reset filter buttons
  const allFilterBtns = document.querySelectorAll('.filter-btn');
  for (let i = 0; i < allFilterBtns.length; i++) {
    const btn = allFilterBtns[i];
    btn.classList.remove('active');
    if (btn.textContent === 'Toutes') {
      btn.classList.add('active');
    }
  }

  // Clear search input
  document.getElementById('searchInput').value = '';

  // Show all cards
  const allCards = document.querySelectorAll('.card');
  for (let i = 0; i < allCards.length; i++) {
    const card = allCards[i];
    card.style.display = '';
  }
}

// ========== FILTRER LES CARTES ==========
function filtrer(btn, type) {
  const tousLesBoutons = document.querySelectorAll('.filter-btn');
  for (let i = 0; i < tousLesBoutons.length; i++) {
    tousLesBoutons[i].classList.remove('active');
  }
  btn.classList.add('active');
  
  const toutesLesCartes = document.querySelectorAll('.card');
  
  for (let i = 0; i < toutesLesCartes.length; i++) {
    const carte = toutesLesCartes[i];
    
    if (type === 'tous') {
      carte.style.display = '';
    } 
    else if (type === 'certif') {
      if (carte.dataset.certif === 'oui') {
        carte.style.display = '';
      } else {
        carte.style.display = 'none';
      }
    } 
    else {
      if (carte.dataset.niveau === type) {
        carte.style.display = '';
      } else {
        carte.style.display = 'none';
      }
    }
  }
}

// ========== RECHERCHER GLOBAL ==========
function rechercherGlobal() {
  const recherche = document.getElementById('searchInput').value.trim().toLowerCase();
  const toutesLesCartes = document.querySelectorAll('.card');
  
  if (recherche === "") {
    for (let i = 0; i < toutesLesCartes.length; i++) {
      toutesLesCartes[i].style.display = '';
    }
    return;
  }
  
  for (let i = 0; i < toutesLesCartes.length; i++) {
    const carte = toutesLesCartes[i];
    const titre = carte.querySelector('h3').textContent.toLowerCase();
    const description = carte.querySelector('p').textContent.toLowerCase();
    const mentor = carte.querySelector('.mentor-info strong').textContent.toLowerCase();
    const texteComplet = titre + " " + description + " " + mentor;
    
    if (texteComplet.includes(recherche)) {
      carte.style.display = '';
    } else {
      carte.style.display = 'none';
    }
  }
}

// ========== AJOUTER UNE FORMATION (FRONT HTML) ==========
function getInitialesMentor(nomMentor) {
  const nom = nomMentor.trim();
  if (nom === '') {
    return 'NA';
  }

  const mots = nom.split(' ');
  let initiales = '';

  for (let i = 0; i < mots.length; i++) {
    const mot = mots[i].trim();
    if (mot !== '') {
      initiales += mot[0].toUpperCase();
    }
    if (initiales.length === 2) {
      break;
    }
  }

  if (initiales.length === 0) {
    return 'NA';
  }

  if (initiales.length === 1) {
    return initiales + initiales;
  }

  return initiales;
}

function ouvrirModalAjoutFormation() {
  const modal = document.getElementById('modalAjoutFormationFront');
  if (!modal) {
    return;
  }

  document.getElementById('aj_titre').value = '';
  document.getElementById('aj_mentor').value = '';
  document.getElementById('aj_niveau').value = 'debutant';
  document.getElementById('aj_ville').value = '';
  document.getElementById('aj_duree').value = '';
  document.getElementById('aj_prix').value = '';
  document.getElementById('aj_description').value = '';
  document.getElementById('aj_certif').value = 'oui';

  modal.classList.add('open');
}

function fermerModalAjoutFormation() {
  const modal = document.getElementById('modalAjoutFormationFront');
  if (modal) {
    modal.classList.remove('open');
  }
}

function ajouterFormationDepuisFront() {
  const titre = document.getElementById('aj_titre').value.trim();
  const mentor = document.getElementById('aj_mentor').value.trim();
  const niveau = document.getElementById('aj_niveau').value;
  const ville = document.getElementById('aj_ville').value.trim();
  const duree = document.getElementById('aj_duree').value.trim();
  const prixTexte = document.getElementById('aj_prix').value.trim();
  const description = document.getElementById('aj_description').value.trim();
  const certif = document.getElementById('aj_certif').value;

  if (titre.length < 3 || mentor.length < 2 || ville.length < 2 || duree === '' || description.length < 10) {
    alert('Veuillez remplir tous les champs obligatoires (description min. 10 caractères).');
    return;
  }

  const prix = parseFloat(prixTexte);
  if (isNaN(prix) || prix < 0) {
    alert('Le prix doit être un nombre valide (>= 0).');
    return;
  }

  formationsData.push({
    id: nextId,
    titre: titre,
    niveau: niveau,
    certif: certif,
    description: description,
    duree: duree,
    videos: '0',
    inscrits: 0,
    mentor: mentor,
    avatar: getInitialesMentor(mentor),
    ville: ville,
    prix: prix,
    tag: 'Nouveau',
    type: 'formation'
  });

  nextId = nextId + 1;

  switchToFormations();
  fermerModalAjoutFormation();
  alert('Formation ajoutée avec succès.');
}

const modalAjoutFormation = document.getElementById('modalAjoutFormationFront');
if (modalAjoutFormation) {
  modalAjoutFormation.addEventListener('click', function(e) {
    if (e.target === this) {
      fermerModalAjoutFormation();
    }
  });
}

// ========== MODAL D'INSCRIPTION ==========
let formationActuelle = '';

function ouvrirModal(nomFormation) {
  formationActuelle = nomFormation;
  
  const titreModal = document.getElementById('modalFormationNom');
  if (titreModal) titreModal.textContent = nomFormation;
  
  const successBanner = document.getElementById('successBanner');
  if (successBanner) successBanner.classList.remove('show');
  
  document.getElementById('prenom').value = '';
  document.getElementById('nom').value = '';
  document.getElementById('email').value = '';
  document.getElementById('tel').value = '';
  document.getElementById('niveau').value = '';
  document.getElementById('motivation').value = '';
  
  const champs = ['prenom', 'nom', 'email', 'tel', 'niveau'];
  for (let i = 0; i < champs.length; i++) {
    const champ = document.getElementById(champs[i]);
    if (champ) champ.classList.remove('error');
  }
  
  const messagesErreur = ['err-prenom', 'err-nom', 'err-email', 'err-tel', 'err-niveau'];
  for (let i = 0; i < messagesErreur.length; i++) {
    const msg = document.getElementById(messagesErreur[i]);
    if (msg) msg.classList.remove('show');
  }
  
  const modal = document.getElementById('modalOverlay');
  if (modal) modal.classList.add('open');
}

function fermerModal() {
  const modal = document.getElementById('modalOverlay');
  if (modal) modal.classList.remove('open');
  formationActuelle = '';
}

const modalOverlay = document.getElementById('modalOverlay');
if (modalOverlay) {
  modalOverlay.addEventListener('click', function(e) {
    if (e.target === this) {
      fermerModal();
    }
  });
}


function estEmailValide(email) {
  if (email === "") return false;
  if (!email.includes('@')) return false;
  if (!email.includes('.')) return false;
  return true;
}

function estTelephoneValide(telephone) {
  let chiffres = '';
  for (let i = 0; i < telephone.length; i++) {
    const caractere = telephone[i];
    if (caractere >= '0' && caractere <= '9') {
      chiffres = chiffres + caractere;
    }
  }
  return chiffres.length === 8;
}

function afficherErreur(champId, estErreur) {
  const messageErreur = document.getElementById(`err-${champId}`);
  const champ = document.getElementById(champId);
  
  if (estErreur) {
    if (messageErreur) messageErreur.classList.add('show');
    if (champ) champ.classList.add('error');
  } else {
    if (messageErreur) messageErreur.classList.remove('show');
    if (champ) champ.classList.remove('error');
  }
}


function soumettreInscription() {
  let toutEstValide = true;
  
  const prenom = document.getElementById('prenom').value.trim();
  if (prenom.length < 2) {
    afficherErreur('prenom', true);
    toutEstValide = false;
  } else {
    afficherErreur('prenom', false);
  }
  
  const nom = document.getElementById('nom').value.trim();
  if (nom.length < 2) {
    afficherErreur('nom', true);
    toutEstValide = false;
  } else {
    afficherErreur('nom', false);
  }
  
  const email = document.getElementById('email').value.trim();
  if (!estEmailValide(email)) {
    afficherErreur('email', true);
    toutEstValide = false;
  } else {
    afficherErreur('email', false);
  }
  
  const telephone = document.getElementById('tel').value.trim();
  if (!estTelephoneValide(telephone)) {
    afficherErreur('tel', true);
    toutEstValide = false;
  } else {
    afficherErreur('tel', false);
  }
  
  const niveau = document.getElementById('niveau').value;
  if (niveau === '') {
    afficherErreur('niveau', true);
    toutEstValide = false;
  } else {
    afficherErreur('niveau', false);
  }
  
  if (toutEstValide) {
    const motivation = document.getElementById('motivation').value;

    // Incrémenter le nombre d'inscrits pour l'élément correspondant
    let itemTrouve = null;
    if (currentTab === 'formations') {
      for (let i = 0; i < formationsData.length; i++) {
        if (formationsData[i].titre === formationActuelle) {
          itemTrouve = formationsData[i];
          break;
        }
      }
      if (itemTrouve) itemTrouve.inscrits += 1;
    } else {
      for (let i = 0; i < workshopsData.length; i++) {
        if (workshopsData[i].titre === formationActuelle) {
          itemTrouve = workshopsData[i];
          break;
        }
      }
      if (itemTrouve) itemTrouve.inscrits += 1;
    }

    let motivationAffiche = motivation;
    if (motivationAffiche === '') {
      motivationAffiche = '(non renseigné)';
    }

    afficherCartes();

    const successBanner = document.getElementById('successBanner');
    if (successBanner) successBanner.classList.add('show');

    console.log('=== NOUVELLE INSCRIPTION ===');
    console.log('Type:', currentTab);
    console.log('Titre:', formationActuelle);
    console.log('Prénom:', prenom);
    console.log('Nom:', nom);
    console.log('Email:', email);
    console.log('Téléphone:', telephone);
    console.log('Niveau:', niveau);
    console.log('Motivation:', motivationAffiche);
    console.log('===========================');

    setTimeout(function() {
      fermerModal();
      alert(`✅ Félicitations ${prenom} !\n\nVotre inscription à "${formationActuelle}" a été enregistrée avec succès !`);
    }, 1500);
  }
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
  // Set up tab click handlers
  document.getElementById('formationsTabLink').addEventListener('click', switchToFormations);
  document.getElementById('workshopsTabLink').addEventListener('click', switchToWorkshops);

  const boutonAjoutFormation = document.getElementById('btnAjouterFormationFrontHtml');
  if (boutonAjoutFormation) {
    boutonAjoutFormation.addEventListener('click', function(event) {
      event.preventDefault();
      ouvrirModalAjoutFormation();
    });
  }

  afficherCartes();
});