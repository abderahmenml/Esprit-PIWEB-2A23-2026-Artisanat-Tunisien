// Fichier legacy: utilise par back.html (version statique).
// La version base de donnees utilise back.php (sans ce fichier JS).

// ========== DONNÉES ==========
// Liste des formations
let formations = [
  { id:1, titre:"Initiation à la poterie berbère", mentor:"Fatma Ayari", niveau:"Débutant", duree:24, videos:12, prix:180, certif:"oui", inscrits:34, statut:"Actif" },
  { id:2, titre:"Broderie traditionnelle — Sfax", mentor:"Sonia Ben Salah", niveau:"Intermédiaire", duree:36, videos:18, prix:240, certif:"oui", inscrits:56, statut:"Actif" },
  { id:3, titre:"Tissage sur métier à bras — Kairouan", mentor:"Mohamed Karoui", niveau:"Avancé", duree:48, videos:24, prix:320, certif:"oui", inscrits:22, statut:"Actif" },
  { id:4, titre:"Calligraphie arabe pour artisans", mentor:"Nadia Amri", niveau:"Débutant", duree:16, videos:8, prix:95, certif:"non", inscrits:78, statut:"Actif" },
  { id:5, titre:"Travail du cuir — Maroquinerie", mentor:"Hedi Trabelsi", niveau:"Intermédiaire", duree:30, videos:15, prix:210, certif:"oui", inscrits:41, statut:"Brouillon" },
  { id:6, titre:"Zellige & Mosaïque artisanale", mentor:"Rim Gharbi", niveau:"Avancé", duree:60, videos:30, prix:390, certif:"oui", inscrits:18, statut:"Inactif" },
];

let prochainId = 7;
let idEnEdition = null;

// ========== AFFICHER LE TABLEAU ==========
// Displays content in the interface based on current data.
function afficherTableau(formationsAAfficher) {
  const corpsTableau = document.getElementById('tableBody');
  
  if (formationsAAfficher.length === 0) {
    corpsTableau.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:30px">Aucune formation trouvée</td></tr>';
    return;
  }
  
  let lignesHTML = '';
  
  for (let i = 0; i < formationsAAfficher.length; i++) {
    const f = formationsAAfficher[i];
    
    // Choisir la classe CSS pour le niveau
    let classeNiveau = '';
    if (f.niveau === 'Débutant') classeNiveau = 'badge-debutant';
    else if (f.niveau === 'Intermédiaire') classeNiveau = 'badge-inter';
    else if (f.niveau === 'Avancé') classeNiveau = 'badge-avance';
    
    // Choisir la classe CSS pour le statut
    let classeStatut = '';
    if (f.statut === 'Actif') classeStatut = 'badge-actif';
    else if (f.statut === 'Inactif') classeStatut = 'badge-inactif';
    else if (f.statut === 'Brouillon') classeStatut = 'badge-brouillon';
    
    lignesHTML += `
      <tr>
        <td>#${f.id}</td>
        <td><strong>${f.titre}</strong></td>
        <td>${f.mentor}</td>
        <td><span class="badge ${classeNiveau}">${f.niveau}</span></td>
        <td>${f.duree}h</td>
        <td>${f.inscrits}</td>
        <td>${f.certif === 'oui' ? '✅' : '—'}</td>
        <td><span class="badge ${classeStatut}">${f.statut}</span></td>
        <td>
          <div class="actions">
            <button class="btn-action btn-voir" onclick="voirFormation(${f.id})">👁 Voir</button>
            <button class="btn-action btn-edit" onclick="editerFormation(${f.id})">✏️ Éditer</button>
            <button class="btn-action btn-suppr" onclick="supprimerFormation(${f.id})">🗑 Suppr.</button>
          </div>
        </td>
      </tr>
    `;
  }
  
  corpsTableau.innerHTML = lignesHTML;
  
  // Mettre à jour le texte d'information
  const infoPagination = document.getElementById('paginInfo');
  if (infoPagination) {
    infoPagination.textContent = `${formationsAAfficher.length} formation(s)`;
  }
}

// ========== FILTRER LES FORMATIONS ==========
// Returns data needed by the current workflow.
function getFormationsFiltrees() {
  const recherche = document.getElementById('tbSearch').value.toLowerCase();
  const niveauChoisi = document.getElementById('tbNiveau').value;
  const statutChoisi = document.getElementById('tbStatut').value;
  
  let resultat = [];
  
  for (let i = 0; i < formations.length; i++) {
    const f = formations[i];
    
    // Vérifier la recherche
    const titreCorrespond = f.titre.toLowerCase().includes(recherche);
    const mentorCorrespond = f.mentor.toLowerCase().includes(recherche);
    const rechercheCorrespond = titreCorrespond || mentorCorrespond;
    
    // Vérifier le niveau
    let niveauCorrespond = true;
    if (niveauChoisi !== "") {
      niveauCorrespond = (f.niveau === niveauChoisi);
    }
    
    // Vérifier le statut
    let statutCorrespond = true;
    if (statutChoisi !== "") {
      statutCorrespond = (f.statut === statutChoisi);
    }
    
    // Si tout correspond, ajouter au résultat
    if (rechercheCorrespond && niveauCorrespond && statutCorrespond) {
      resultat.push(f);
    }
  }
  
  return resultat;
}

// Filters displayed data based on selected criteria.
function filtrerTableau() {
  const formationsFiltrees = getFormationsFiltrees();
  afficherTableau(formationsFiltrees);
}

// Compatibilite avec d'anciens attributs HTML encore en cache
// Keeps compatibility with legacy cached HTML handlers.
function filtrerTable() {
  filtrerTableau();
}

// ========== VOIR UNE FORMATION ==========
// Shows a quick preview of the selected item.
function voirFormation(id) {
  let formation = null;
  
  for (let i = 0; i < formations.length; i++) {
    if (formations[i].id === id) {
      formation = formations[i];
      break;
    }
  }
  
  if (formation) {
    afficherToast(`📚 ${formation.titre} - ${formation.inscrits} inscrits - ${formation.prix} TND`);
  }
}

// ========== AJOUTER / MODIFIER ==========
// Opens the related modal or panel.
function ouvrirFormulaireAjout() {
  idEnEdition = null;
  document.getElementById('modalTitre').textContent = 'Ajouter une formation';
  viderFormulaire();
  ouvrirModal();
}

// Opens the edit form and pre-fills it with the selected formation.
function editerFormation(id) {
  idEnEdition = id;
  
  // Trouver la formation
  let formation = null;
  for (let i = 0; i < formations.length; i++) {
    if (formations[i].id === id) {
      formation = formations[i];
      break;
    }
  }
  
  if (formation) {
    // Remplir le formulaire
    document.getElementById('f_titre').value = formation.titre;
    document.getElementById('f_niveau').value = formation.niveau;
    document.getElementById('f_duree').value = formation.duree;
    document.getElementById('f_videos').value = formation.videos;
    document.getElementById('f_prix').value = formation.prix;
    document.getElementById('f_mentor').value = formation.mentor;
    document.getElementById('f_certif').value = formation.certif;
    document.getElementById('f_statut').value = formation.statut;
    
    document.getElementById('modalTitre').textContent = 'Modifier la formation';
    ouvrirModal();
  }
}

// Resets all form inputs and clears validation states.
function viderFormulaire() {
  document.getElementById('f_titre').value = '';
  document.getElementById('f_niveau').value = '';
  document.getElementById('f_duree').value = '';
  document.getElementById('f_videos').value = '';
  document.getElementById('f_prix').value = '';
  document.getElementById('f_mentor').value = '';
  document.getElementById('f_certif').value = 'oui';
  document.getElementById('f_statut').value = 'Brouillon';
  
  // Enlever les messages d'erreur
  const erreurs = document.querySelectorAll('.error-msg');
  for (let i = 0; i < erreurs.length; i++) {
    erreurs[i].classList.remove('show');
  }
  
  // Enlever la classe error des champs
  const champs = document.querySelectorAll('.modal input, .modal select');
  for (let i = 0; i < champs.length; i++) {
    champs[i].classList.remove('error');
  }
}

// Opens the related modal or panel.
function ouvrirModal() {
  const modal = document.getElementById('modalOverlay');
  if (modal) modal.classList.add('open');
}

// Closes the related modal or panel.
function fermerModal() {
  const modal = document.getElementById('modalOverlay');
  if (modal) modal.classList.remove('open');
}

// Closes the related modal or panel.
function fermerModalSiExterieur(event) {
  if (event.target.id === 'modalOverlay') {
    fermerModal();
  }
}

// Compatibilite avec un ancien nom de handler dans le HTML
// Redirects legacy overlay-close handlers to the current function.
function fermerModalOverlay(event) {
  fermerModalSiExterieur(event);
}

// ========== VALIDATION SIMPLE ==========
// Validates input values and returns whether they are valid.
function estEntierPositif(valeur) {
  if (valeur === '') {
    return false;
  }

  for (let i = 0; i < valeur.length; i++) {
    const c = valeur[i];
    if (c < '0' || c > '9') {
      return false;
    }
  }

  if (parseInt(valeur, 10) > 0) {
    return true;
  }

  return false;
}

// Validates input values and returns whether they are valid.
function estPrixValide(valeur) {
  if (valeur === '') {
    return false;
  }

  let pointTrouve = false;
  for (let i = 0; i < valeur.length; i++) {
    const c = valeur[i];
    if (c === '.') {
      if (pointTrouve) {
        return false;
      }
      pointTrouve = true;
    } else if (c < '0' || c > '9') {
      return false;
    }
  }

  if (valeur === '.') {
    return false;
  }

  if (parseFloat(valeur) >= 0) {
    return true;
  }

  return false;
}

// Validates input values and returns whether they are valid.
function validerChamp(idChamp, typeValidation, idErreur) {
  const champ = document.getElementById(idChamp);
  const valeur = champ.value.trim();
  let estValide = false;

  if (typeValidation === 'titre') {
    if (valeur.length >= 5) {
      estValide = true;
    }
  } else if (typeValidation === 'duree') {
    estValide = estEntierPositif(valeur);
  } else if (typeValidation === 'prix') {
    estValide = estPrixValide(valeur);
  } else if (typeValidation === 'obligatoire') {
    if (valeur !== '') {
      estValide = true;
    }
  }
  
  if (estValide) {
    champ.classList.remove('error');
    document.getElementById(idErreur).classList.remove('show');
  } else {
    champ.classList.add('error');
    document.getElementById(idErreur).classList.add('show');
  }
  
  return estValide;
}

// Saves the provided data to persistent storage.
function sauvegarderFormation() {
  let toutEstValide = true;
  
  // Valider chaque champ
  const titreValide = validerChamp('f_titre', 'titre', 'err-f_titre');
  toutEstValide = toutEstValide && titreValide;
  
  const dureeValide = validerChamp('f_duree', 'duree', 'err-f_duree');
  toutEstValide = toutEstValide && dureeValide;
  
  const prixValide = validerChamp('f_prix', 'prix', 'err-f_prix');
  toutEstValide = toutEstValide && prixValide;
  
  const mentorValide = validerChamp('f_mentor', 'obligatoire', 'err-f_mentor');
  toutEstValide = toutEstValide && mentorValide;
  
  const niveauValide = validerChamp('f_niveau', 'obligatoire', 'err-f_niveau');
  toutEstValide = toutEstValide && niveauValide;
  
  if (!toutEstValide) {
    return;
  }
  
  // Récupérer les données
  const nouvelleFormation = {
    titre: document.getElementById('f_titre').value.trim(),
    niveau: document.getElementById('f_niveau').value,
    duree: parseInt(document.getElementById('f_duree').value),
    videos: parseInt(document.getElementById('f_videos').value) || 0,
    prix: parseFloat(document.getElementById('f_prix').value),
    mentor: document.getElementById('f_mentor').value,
    certif: document.getElementById('f_certif').value,
    statut: document.getElementById('f_statut').value,
    inscrits: 0
  };
  
  // Si on modifie
  if (idEnEdition !== null) {
    for (let i = 0; i < formations.length; i++) {
      if (formations[i].id === idEnEdition) {
        nouvelleFormation.id = idEnEdition;
        nouvelleFormation.inscrits = formations[i].inscrits; // Garder les inscrits
        formations[i] = nouvelleFormation;
        break;
      }
    }
    afficherToast('✅ Formation modifiée avec succès !');
  } 
  // Sinon on ajoute
  else {
    nouvelleFormation.id = prochainId;
    prochainId = prochainId + 1;
    formations.push(nouvelleFormation);
    afficherToast('✅ Formation ajoutée avec succès !');
  }
  
  fermerModal();
  filtrerTableau();
}

// ========== SUPPRESSION ==========
// Removes the selected item from the current dataset or form.
function supprimerFormation(id) {
  // Trouver le titre de la formation
  let titre = '';
  for (let i = 0; i < formations.length; i++) {
    if (formations[i].id === id) {
      titre = formations[i].titre;
      break;
    }
  }
  
  // Demander confirmation
  const confirmation = confirm(`Supprimer "${titre}" ? Cette action est irréversible.`);
  
  if (confirmation) {
    // Créer un nouveau tableau sans la formation
    let nouvellesFormations = [];
    for (let i = 0; i < formations.length; i++) {
      if (formations[i].id !== id) {
        nouvellesFormations.push(formations[i]);
      }
    }
    formations = nouvellesFormations;
    
    afficherToast('🗑 Formation supprimée');
    filtrerTableau();
  }
}

// ========== TOAST (Message popup) ==========
// Displays content in the interface based on current data.
function afficherToast(message) {
  const toast = document.getElementById('toast');
  if (!toast) return;
  
  toast.textContent = message;
  toast.classList.add('show');
  
  setTimeout(function() {
    toast.classList.remove('show');
  }, 3000);
}

// ========== INITIALISATION ==========
// Attendre que la page soit chargée
document.addEventListener('DOMContentLoaded', function() {
  afficherTableau(formations);

  // Branchements defensifs pour garantir les interactions principales
  const champRecherche = document.getElementById('tbSearch');
  if (champRecherche) {
    champRecherche.addEventListener('input', filtrerTableau);
  }

  const boutonAjout = document.querySelector('.topbar-actions .btn-primary');
  if (boutonAjout) {
    boutonAjout.addEventListener('click', function(event) {
      event.preventDefault();
      ouvrirFormulaireAjout();
    });
  }
  
  // Ajouter l'écouteur pour fermer la modale
  const modalOverlay = document.getElementById('modalOverlay');
  if (modalOverlay) {
    modalOverlay.addEventListener('click', fermerModalSiExterieur);
  }
});