// ========== DONNÉES DES ATELIERS ==========
let workshopsData = [
  {
    id: 1,
    titre: "Atelier poterie berbère en famille",
    lieu: "Nabeul",
    description: "Modelage, décoration et cuisson rapide. Chaque participant repart avec sa création.",
    artisan: "Fatma Ayari",
    avatar: "FA",
    duree: "2h",
    prix: 65,
    places: 8,
    type: "famille",
    certificat: "non",
    statut: "actif",
    infoSup: "🥤 Thé offert"
  },
  {
    id: 2,
    titre: "Création mosaïque & zellige",
    lieu: "Tunis Médina",
    description: "Atelier intensif : découpe de tesselles, composition géométrique et pose.",
    artisan: "Rim Gharbi",
    avatar: "RG",
    duree: "journee",
    prix: 165,
    places: 6,
    type: "adulte",
    certificat: "oui",
    statut: "actif",
    infoSup: "📸 Photo souvenir, repas offert"
  },
  {
    id: 3,
    titre: "Calligraphie arabe sur céramique",
    lieu: "Sousse",
    description: "Initiation aux lettres arabes et décoration d'une assiette en céramique.",
    artisan: "Nadia Amri",
    avatar: "NA",
    duree: "2h",
    prix: 55,
    places: 10,
    type: "famille",
    certificat: "non",
    statut: "actif",
    infoSup: "🎁 Emballage cadeau"
  },
  {
    id: 4,
    titre: "Maroquinerie : fabriquez votre ceinture",
    lieu: "Tunis Médina",
    description: "Découpe, teinture végétale, couture main et finition.",
    artisan: "Hedi Trabelsi",
    avatar: "HT",
    duree: "journee",
    prix: 210,
    places: 5,
    type: "adulte",
    certificat: "oui",
    statut: "actif",
    infoSup: "🍽️ Déjeuner inclus"
  },
  {
    id: 5,
    titre: "Mini métier à tisser — initiation au tapis",
    lieu: "Kairouan",
    description: "Créez un petit tapis sur un métier portatif.",
    artisan: "Mohamed Karoui",
    avatar: "MK",
    duree: "2h",
    prix: 70,
    places: 6,
    type: "famille",
    certificat: "non",
    statut: "actif",
    infoSup: "🏷️ Étiquette personnalisée"
  },
  {
    id: 6,
    titre: "Broderie traditionnelle sfaxienne",
    lieu: "Sfax",
    description: "Points emblématiques de la broderie sfaxienne.",
    artisan: "Sonia Ben Salah",
    avatar: "SB",
    duree: "journee",
    prix: 125,
    places: 8,
    type: "adulte",
    certificat: "oui",
    statut: "actif",
    infoSup: "☕ Collation incluse"
  }
];

// ========== VARIABLES GLOBALES ==========
let pageActuelle = 1;
const ateliersParPage = 5;
let idASupprimer = null;
let idAModifier = null;

// ========== INITIALISATION ==========
document.addEventListener('DOMContentLoaded', function() {
  afficherTableau();
});

// ========== AFFICHER LE TABLEAU ==========
function afficherTableau() {
  // 1. Récupérer les ateliers filtrés
  let ateliersFiltres = getAteliersFiltres();
  
  // 2. Calculer le nombre de pages
  const nombreTotalPages = Math.ceil(ateliersFiltres.length / ateliersParPage);
  
  // 3. Corriger la page actuelle si nécessaire
  if (pageActuelle > nombreTotalPages) {
    if (nombreTotalPages > 0) {
      pageActuelle = nombreTotalPages;
    } else {
      pageActuelle = 1;
    }
  }
  
  // 4. Calculer quels ateliers afficher sur la page actuelle
  const debut = (pageActuelle - 1) * ateliersParPage;
  const fin = debut + ateliersParPage;
  const ateliersDeLaPage = ateliersFiltres.slice(debut, fin);
  
  // 5. Récupérer le corps du tableau
  const corpsTableau = document.getElementById('workshopsTableBody');
  corpsTableau.innerHTML = ''; // Vider le tableau
  
  // 6. Ajouter chaque atelier au tableau
  for (let i = 0; i < ateliersDeLaPage.length; i++) {
    const atelier = ateliersDeLaPage[i];
    
    // Créer une nouvelle ligne
    const ligne = document.createElement('tr');
    
    // Remplir la ligne
    ligne.innerHTML = `
      <td>
        <strong>${atelier.titre}</strong><br>
        <small style="color:var(--gris)">${atelier.lieu}</small>
      </td>
      <td>${atelier.artisan}</td>
      <td>${afficherDuree(atelier.duree)}</td>
      <td>${atelier.prix} TND</td>
      <td>${atelier.places}</td>
      <td>${afficherType(atelier.type)}</td>
      <td>${afficherCertificat(atelier.certificat)}</td>
      <td>${afficherStatut(atelier.statut)}</td>
      <td class="action-buttons">
        <button class="btn-edit" onclick="editerAtelier(${atelier.id})">✏️ Modifier</button>
        <button class="btn-delete" onclick="ouvrirModalDelete(${atelier.id}, '${atelier.titre}')">🗑️ Supprimer</button>
      </td>
    `;
    
    corpsTableau.appendChild(ligne);
  }
  
  // 7. Mettre à jour les informations de pagination
  const infoPage = document.getElementById('pageInfo');
  if (infoPage) {
    let totalPagesAffiche = nombreTotalPages;
    if (totalPagesAffiche === 0) {
      totalPagesAffiche = 1;
    }
    infoPage.innerHTML = `Page ${pageActuelle} / ${totalPagesAffiche}`;
  }
  
  // 8. Gérer l'état des boutons de pagination
  const boutonPrecedent = document.getElementById('prevPage');
  const boutonSuivant = document.getElementById('nextPage');
  
  if (boutonPrecedent) {
    boutonPrecedent.disabled = (pageActuelle === 1);
  }
  
  if (boutonSuivant) {
    boutonSuivant.disabled = (pageActuelle === nombreTotalPages || nombreTotalPages === 0);
  }
}

// ========== FONCTIONS D'AFFICHAGE (petites aides) ==========
function afficherDuree(duree) {
  if (duree === '2h') {
    return '⏱️ 2h-3h';
  } else {
    return '📅 Journée (4h+)';
  }
}

function afficherType(type) {
  if (type === 'famille') {
    return '👨‍👩‍👧‍👦 En famille';
  } else {
    return '👤 Adultes';
  }
}

function afficherCertificat(certificat) {
  if (certificat === 'oui') {
    return '✅ Oui';
  } else {
    return '❌ Non';
  }
}

function afficherStatut(statut) {
  if (statut === 'actif') {
    return '<span class="status-badge status-actif">Actif</span>';
  } else {
    return '<span class="status-badge status-inactif">Inactif</span>';
  }
}

// ========== FILTRER LES ATELIERS ==========
function getAteliersFiltres() {
  // Commencer avec tous les ateliers
  let resultat = [];
  for (let i = 0; i < workshopsData.length; i++) {
    resultat.push(workshopsData[i]);
  }
  
  // Filtrer par recherche texte
  const champRecherche = document.getElementById('searchWorkshopBack');
  let recherche = '';
  if (champRecherche) {
    recherche = champRecherche.value.toLowerCase();
  }
  
  if (recherche !== '') {
    let nouveauResultat = [];
    for (let i = 0; i < resultat.length; i++) {
      const atelier = resultat[i];
      const titreCorrespond = atelier.titre.toLowerCase().includes(recherche);
      const lieuCorrespond = atelier.lieu.toLowerCase().includes(recherche);
      const artisanCorrespond = atelier.artisan.toLowerCase().includes(recherche);
      
      if (titreCorrespond || lieuCorrespond || artisanCorrespond) {
        nouveauResultat.push(atelier);
      }
    }
    resultat = nouveauResultat;
  }
  
  // Filtrer par durée
  const filtreDuree = document.getElementById('filterDuree');
  let dureeChoisie = 'all';
  if (filtreDuree) {
    dureeChoisie = filtreDuree.value;
  }
  
  if (dureeChoisie !== 'all') {
    let nouveauResultat = [];
    for (let i = 0; i < resultat.length; i++) {
      if (resultat[i].duree === dureeChoisie) {
        nouveauResultat.push(resultat[i]);
      }
    }
    resultat = nouveauResultat;
  }
  
  // Filtrer par type (famille/adulte)
  const filtreType = document.getElementById('filterType');
  let typeChoisi = 'all';
  if (filtreType) {
    typeChoisi = filtreType.value;
  }
  
  if (typeChoisi !== 'all') {
    let nouveauResultat = [];
    for (let i = 0; i < resultat.length; i++) {
      if (resultat[i].type === typeChoisi) {
        nouveauResultat.push(resultat[i]);
      }
    }
    resultat = nouveauResultat;
  }
  
  // Filtrer par certificat
  const filtreCertif = document.getElementById('filterCertif');
  let certifChoisi = 'all';
  if (filtreCertif) {
    certifChoisi = filtreCertif.value;
  }
  
  if (certifChoisi !== 'all') {
    let nouveauResultat = [];
    for (let i = 0; i < resultat.length; i++) {
      if (resultat[i].certificat === certifChoisi) {
        nouveauResultat.push(resultat[i]);
      }
    }
    resultat = nouveauResultat;
  }
  
  return resultat;
}

function rechercherAtelierBack() {
  pageActuelle = 1;
  afficherTableau();
}

function filtrerAteliersBack() {
  pageActuelle = 1;
  afficherTableau();
}

// ========== CHANGER DE PAGE ==========
function changerPage(delta) {
  const ateliersFiltres = getAteliersFiltres();
  const nombreTotalPages = Math.ceil(ateliersFiltres.length / ateliersParPage);
  const nouvellePage = pageActuelle + delta;
  
  if (nouvellePage >= 1 && nouvellePage <= nombreTotalPages) {
    pageActuelle = nouvellePage;
    afficherTableau();
  }
}

// ========== AJOUTER / MODIFIER UN ATELIER ==========
function ouvrirModalAjout() {
  idAModifier = null;
  
  // Changer le titre de la modale
  const titreModal = document.getElementById('modalTitle');
  if (titreModal) {
    titreModal.textContent = 'Ajouter un atelier';
  }
  
  // Vider le formulaire
  const formulaire = document.getElementById('atelierForm');
  if (formulaire) {
    formulaire.reset();
  }
  
  // Vider tous les champs manuellement
  document.getElementById('titreAtelier').value = '';
  document.getElementById('lieuAtelier').value = '';
  document.getElementById('descAtelier').value = '';
  document.getElementById('artisanAtelier').value = '';
  document.getElementById('avatarAtelier').value = '';
  document.getElementById('dureeAtelier').value = '2h';
  document.getElementById('prixAtelier').value = '';
  document.getElementById('placesAtelier').value = '';
  document.getElementById('typeAtelier').value = 'famille';
  document.getElementById('certifAtelier').value = 'non';
  document.getElementById('statutAtelier').value = 'actif';
  document.getElementById('infoSupAtelier').value = '';
  
  // Ouvrir la modale
  const modale = document.getElementById('modalAtelier');
  if (modale) {
    modale.classList.add('open');
  }
}

function editerAtelier(id) {
  // Trouver l'atelier à modifier
  let atelier = null;
  for (let i = 0; i < workshopsData.length; i++) {
    if (workshopsData[i].id === id) {
      atelier = workshopsData[i];
      break;
    }
  }
  
  if (!atelier) return;
  
  idAModifier = id;
  
  // Changer le titre de la modale
  const titreModal = document.getElementById('modalTitle');
  if (titreModal) {
    titreModal.textContent = "Modifier l'atelier";
  }
  
  // Remplir le formulaire avec les données existantes
  document.getElementById('titreAtelier').value = atelier.titre;
  document.getElementById('lieuAtelier').value = atelier.lieu;
  document.getElementById('descAtelier').value = atelier.description;
  document.getElementById('artisanAtelier').value = atelier.artisan;
  document.getElementById('avatarAtelier').value = atelier.avatar;
  document.getElementById('dureeAtelier').value = atelier.duree;
  document.getElementById('prixAtelier').value = atelier.prix;
  document.getElementById('placesAtelier').value = atelier.places;
  document.getElementById('typeAtelier').value = atelier.type;
  document.getElementById('certifAtelier').value = atelier.certificat;
  document.getElementById('statutAtelier').value = atelier.statut;
  document.getElementById('infoSupAtelier').value = atelier.infoSup || '';
  
  // Ouvrir la modale
  const modale = document.getElementById('modalAtelier');
  if (modale) {
    modale.classList.add('open');
  }
}

function fermerModalAtelier() {
  const modale = document.getElementById('modalAtelier');
  if (modale) {
    modale.classList.remove('open');
  }
  idAModifier = null;
}

function sauvegarderAtelier() {
  // Récupérer toutes les valeurs
  const titre = document.getElementById('titreAtelier').value.trim();
  const lieu = document.getElementById('lieuAtelier').value.trim();
  const description = document.getElementById('descAtelier').value.trim();
  const artisan = document.getElementById('artisanAtelier').value;
  let avatar = document.getElementById('avatarAtelier').value.trim().toUpperCase();
  const duree = document.getElementById('dureeAtelier').value;
  const prix = parseInt(document.getElementById('prixAtelier').value);
  const places = parseInt(document.getElementById('placesAtelier').value);
  const type = document.getElementById('typeAtelier').value;
  const certificat = document.getElementById('certifAtelier').value;
  const statut = document.getElementById('statutAtelier').value;
  const infoSup = document.getElementById('infoSupAtelier').value;
  
  // Vérifier que les champs obligatoires sont remplis
  if (titre === "" || lieu === "" || description === "" || artisan === "" || isNaN(prix) || isNaN(places)) {
    afficherToast('Veuillez remplir tous les champs obligatoires', 'error');
    return;
  }
  
  // Gérer l'avatar (si vide, le créer à partir du nom de l'artisan)
  if (avatar === "") {
    const mots = artisan.split(' ');
    if (mots.length >= 2) {
      avatar = (mots[0][0] + mots[1][0]).toUpperCase();
    } else {
      avatar = artisan.substring(0, 2).toUpperCase();
    }
  } else {
    avatar = avatar.substring(0, 2);
  }
  
  // Si on modifie un atelier existant
  if (idAModifier !== null) {
    for (let i = 0; i < workshopsData.length; i++) {
      if (workshopsData[i].id === idAModifier) {
        workshopsData[i] = {
          id: idAModifier,
          titre: titre,
          lieu: lieu,
          description: description,
          artisan: artisan,
          avatar: avatar,
          duree: duree,
          prix: prix,
          places: places,
          type: type,
          certificat: certificat,
          statut: statut,
          infoSup: infoSup
        };
        break;
      }
    }
    afficherToast('Atelier modifié avec succès', 'success');
  } 
  // Sinon, ajouter un nouvel atelier
  else {
    // Trouver le plus grand ID
    let plusGrandId = 0;
    for (let i = 0; i < workshopsData.length; i++) {
      if (workshopsData[i].id > plusGrandId) {
        plusGrandId = workshopsData[i].id;
      }
    }
    const nouvelId = plusGrandId + 1;
    
    // Ajouter le nouvel atelier
    workshopsData.push({
      id: nouvelId,
      titre: titre,
      lieu: lieu,
      description: description,
      artisan: artisan,
      avatar: avatar,
      duree: duree,
      prix: prix,
      places: places,
      type: type,
      certificat: certificat,
      statut: statut,
      infoSup: infoSup
    });
    
    afficherToast('Atelier ajouté avec succès', 'success');
  }
  
  // Fermer la modale et rafraîchir le tableau
  fermerModalAtelier();
  pageActuelle = 1;
  afficherTableau();
}

// ========== SUPPRIMER UN ATELIER ==========
function ouvrirModalDelete(id, titre) {
  idASupprimer = id;
  
  const nomAtelier = document.getElementById('deleteAtelierNom');
  if (nomAtelier) {
    nomAtelier.textContent = titre;
  }
  
  const modale = document.getElementById('modalDelete');
  if (modale) {
    modale.classList.add('open');
  }
}

function fermerModalDelete() {
  const modale = document.getElementById('modalDelete');
  if (modale) {
    modale.classList.remove('open');
  }
  idASupprimer = null;
}

function confirmerSuppression() {
  if (idASupprimer !== null) {
    // Créer un nouveau tableau sans l'atelier à supprimer
    let nouveauTableau = [];
    for (let i = 0; i < workshopsData.length; i++) {
      if (workshopsData[i].id !== idASupprimer) {
        nouveauTableau.push(workshopsData[i]);
      }
    }
    workshopsData = nouveauTableau;
    
    afficherToast('Atelier supprimé avec succès', 'success');
    fermerModalDelete();
    
    // Ajuster la page actuelle si nécessaire
    const ateliersFiltres = getAteliersFiltres();
    const nombreTotalPages = Math.ceil(ateliersFiltres.length / ateliersParPage);
    
    if (pageActuelle > nombreTotalPages && nombreTotalPages > 0) {
      pageActuelle = nombreTotalPages;
    }
    if (ateliersFiltres.length === 0) {
      pageActuelle = 1;
    }
    
    afficherTableau();
  }
}

// ========== AFFICHER UN MESSAGE TOAST ==========
function afficherToast(message, type) {
  if (type !== 'success' && type !== 'error') {
    type = 'success';
  }

  const toast = document.getElementById('toast');
  if (!toast) return;
  
  toast.textContent = message;
  
  if (type === 'success') {
    toast.style.background = 'var(--vert)';
  } else {
    toast.style.background = 'var(--danger)';
  }
  
  toast.classList.add('show');
  
  setTimeout(function() {
    toast.classList.remove('show');
  }, 3000);
}

// ========== DÉCONNEXION ==========
function deconnexion() {
  afficherToast('Déconnexion réussie', 'success');
  setTimeout(function() {
    window.location.href = 'workshops.html';
  }, 1000);
}

// ========== FERMER LES MODALES EN CLIQUANT À L'EXTÉRIEUR ==========
const modaleAtelier = document.getElementById('modalAtelier');
if (modaleAtelier) {
  modaleAtelier.addEventListener('click', function(event) {
    if (event.target === this) {
      fermerModalAtelier();
    }
  });
}

const modaleDelete = document.getElementById('modalDelete');
if (modaleDelete) {
  modaleDelete.addEventListener('click', function(event) {
    if (event.target === this) {
      fermerModalDelete();
    }
  });
}