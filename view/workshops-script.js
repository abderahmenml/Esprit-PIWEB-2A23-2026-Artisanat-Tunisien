// FILTRAGE ATELIERS
// Filters workshop cards based on the selected category.
function filtrerAteliers(btn, type) {
  const boutons = document.querySelectorAll('.filter-btn');
  for (let i = 0; i < boutons.length; i++) {
    boutons[i].classList.remove('active');
  }
  btn.classList.add('active');

  const cards = document.querySelectorAll('#workshopsGrid .card');

  for (let i = 0; i < cards.length; i++) {
    const card = cards[i];
    if (type === 'tous') {
      card.style.display = '';
      continue;
    }

    if (type === '2h') {
      const duree = card.getAttribute('data-duree');
      if (duree === '2h') {
        card.style.display = '';
      } else {
        card.style.display = 'none';
      }
    }
    else if (type === 'journee') {
      const duree = card.getAttribute('data-duree');
      if (duree === 'journee') {
        card.style.display = '';
      } else {
        card.style.display = 'none';
      }
    }
    else if (type === 'famille') {
      const famille = card.getAttribute('data-type');
      if (famille === 'famille') {
        card.style.display = '';
      } else {
        card.style.display = 'none';
      }
    }
    else if (type === 'certificat') {
      const cert = card.getAttribute('data-certificat');
      if (cert === 'oui') {
        card.style.display = '';
      } else {
        card.style.display = 'none';
      }
    } else {
      card.style.display = 'none';
    }
  }
}

// RECHERCHE TEXTE
// Filters workshop cards using the current free-text query.
function rechercherAteliers() {
  const q = document.getElementById('searchWorkshop').value.trim().toLowerCase();
  const cards = document.querySelectorAll('#workshopsGrid .card');
  if (q === '') {
    for (let i = 0; i < cards.length; i++) {
      cards[i].style.display = '';
    }
    return;
  }

  for (let i = 0; i < cards.length; i++) {
    const card = cards[i];

    let titre = '';
    const titreElement = card.querySelector('h3');
    if (titreElement) {
      titre = titreElement.textContent.toLowerCase();
    }

    let desc = '';
    const descElement = card.querySelector('p');
    if (descElement) {
      desc = descElement.textContent.toLowerCase();
    }

    let lieu = '';
    const lieuElement = card.querySelector('.tag-lieu');
    if (lieuElement) {
      lieu = lieuElement.textContent.toLowerCase();
    }

    const fullText = titre + ' ' + desc + ' ' + lieu;
    if (fullText.includes(q)) {
      card.style.display = '';
    } else {
      card.style.display = 'none';
    }
  }
}

// AJOUT WORKSHOP (FRONT)
// Builds a two-letter avatar from the artisan name.
function getInitialesWorkshop(nomArtisan) {
  const nom = nomArtisan.trim();
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

// Opens the related modal or panel.
function ouvrirModalAjoutWorkshop() {
  const modal = document.getElementById('modalAjoutWorkshopFront');
  if (!modal) {
    return;
  }

  document.getElementById('ajw_titre').value = '';
  document.getElementById('ajw_artisan').value = '';
  document.getElementById('ajw_lieu').value = '';
  document.getElementById('ajw_prix').value = '';
  document.getElementById('ajw_duree').value = '2h';
  document.getElementById('ajw_type').value = 'famille';
  document.getElementById('ajw_certif').value = 'non';
  document.getElementById('ajw_places').value = '';
  document.getElementById('ajw_description').value = '';

  modal.classList.add('open');
}

// Closes the related modal or panel.
function fermerModalAjoutWorkshop() {
  const modal = document.getElementById('modalAjoutWorkshopFront');
  if (modal) {
    modal.classList.remove('open');
  }
}

// Saves the provided data to persistent storage.
async function enregistrerWorkshopDansBase(donneesWorkshop) {
  const corps = new URLSearchParams();
  corps.set('titre', donneesWorkshop.titre);
  corps.set('artisan', donneesWorkshop.artisan);
  corps.set('lieu', donneesWorkshop.lieu);
  corps.set('prix', String(donneesWorkshop.prix));
  corps.set('duree', donneesWorkshop.duree);
  corps.set('type', donneesWorkshop.type);
  corps.set('certif', donneesWorkshop.certif);
  corps.set('places', String(donneesWorkshop.places));
  corps.set('description', donneesWorkshop.description);

  let reponse = null;
  let texteReponse = '';

  try {
    reponse = await fetch('../model/workshop_add.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      body: corps.toString()
    });

    texteReponse = await reponse.text();
  } catch (erreurReseau) {
    alert('Erreur réseau pendant l\'enregistrement du workshop.');
    return false;
  }

  if (!reponse.ok) {
    if (texteReponse === '') {
      alert('Erreur serveur pendant l\'enregistrement du workshop.');
    } else {
      alert(texteReponse);
    }
    return false;
  }

  if (texteReponse.indexOf('OK') !== 0) {
    if (texteReponse === '') {
      alert('Réponse inattendue du serveur.');
    } else {
      alert(texteReponse);
    }
    return false;
  }

  return true;
}

// Adds a new item to the current dataset or form.
async function ajouterWorkshopDepuisFront() {
  const titre = document.getElementById('ajw_titre').value.trim();
  const artisan = document.getElementById('ajw_artisan').value.trim();
  const lieu = document.getElementById('ajw_lieu').value.trim();
  const prixTexte = document.getElementById('ajw_prix').value.trim();
  const duree = document.getElementById('ajw_duree').value;
  const type = document.getElementById('ajw_type').value;
  const certif = document.getElementById('ajw_certif').value;
  const placesTexte = document.getElementById('ajw_places').value.trim();
  const description = document.getElementById('ajw_description').value.trim();

  if (titre.length < 3 || artisan.length < 2 || lieu.length < 2 || description.length < 10) {
    alert('Veuillez remplir tous les champs obligatoires (description min. 10 caractères).');
    return;
  }

  const prix = parseFloat(prixTexte);
  if (isNaN(prix) || prix < 0) {
    alert('Le prix doit être un nombre valide (>= 0).');
    return;
  }

  const places = parseInt(placesTexte, 10);
  if (isNaN(places) || places <= 0) {
    alert('Le nombre de places doit être un entier > 0.');
    return;
  }

  const donneesWorkshop = {
    titre: titre,
    artisan: artisan,
    lieu: lieu,
    prix: prix,
    duree: duree,
    type: type,
    certif: certif,
    places: places,
    description: description
  };

  const enregistre = await enregistrerWorkshopDansBase(donneesWorkshop);
  if (!enregistre) {
    return;
  }

  let badgeTexte = '🧰 Atelier pratique';
  if (type === 'famille') {
    badgeTexte = '👨‍👩‍👧‍👦 En famille';
  }
  if (certif === 'oui') {
    badgeTexte = '🏺 Certifiant';
  }

  let dureeTexte = '⏱️ 2h-3h';
  if (duree === 'journee') {
    dureeTexte = '⏱️ Journée (4h+)';
  }

  let certifTag = '';
  if (certif === 'oui') {
    certifTag = '<span class="tag tag-certif">📜 Certificat</span>';
  }

  let boutonClasse = 'btn-inscrit';
  if (certif === 'oui') {
    boutonClasse = 'btn-inscrit vert';
  }

  let notePrix = 'matériel inclus';
  if (certif === 'oui') {
    notePrix = 'certificat inclus';
  }

  const initiales = getInitialesWorkshop(artisan);
  const titreSecurise = titre.replace(/'/g, "\\'");

  const card = document.createElement('div');
  card.className = 'card';
  card.setAttribute('data-duree', duree);
  card.setAttribute('data-type', type);
  card.setAttribute('data-certificat', certif);

  card.innerHTML = `
    <div class="card-badge">${badgeTexte}</div>
    <div class="card-body">
      <div class="card-meta">
        <span class="tag tag-duree">${dureeTexte}</span>
        <span class="tag tag-lieu">${lieu}</span>
        ${certifTag}
      </div>
      <h3>${titre}</h3>
      <p>${description}</p>
      <div class="card-info">
        <span>🔥 ${places} places max</span>
        <span>🎨 Tout niveau</span>
      </div>
    </div>
    <div class="card-mentor">
      <div class="mentor-avatar" style="background:var(--marron)">${initiales}</div>
      <div class="mentor-info">
        <strong>${artisan}</strong>
        <small>${lieu}</small>
      </div>
    </div>
    <div class="card-footer">
      <div class="price">${prix} TND<small>${notePrix}</small></div>
      <button class="${boutonClasse}" onclick="ouvrirModalAtelier('${titreSecurise}')">Réserver</button>
    </div>
  `;

  const grille = document.getElementById('workshopsGrid');
  if (grille) {
    grille.insertBefore(card, grille.firstChild);
  }

  const boutons = document.querySelectorAll('.filter-btn');
  for (let i = 0; i < boutons.length; i++) {
    boutons[i].classList.remove('active');
  }
  if (boutons.length > 0) {
    boutons[0].classList.add('active');
  }
  const cartes = document.querySelectorAll('#workshopsGrid .card');
  for (let i = 0; i < cartes.length; i++) {
    cartes[i].style.display = '';
  }

  fermerModalAjoutWorkshop();
  alert('Workshop ajouté avec succès dans la base de données.');
}

const modalAjoutWorkshop = document.getElementById('modalAjoutWorkshopFront');
if (modalAjoutWorkshop) {
  modalAjoutWorkshop.addEventListener('click', function(e) {
    if (e.target === this) {
      fermerModalAjoutWorkshop();
    }
  });
}

// MODAL WORKSHOP
let currentAtelier = '';

// Opens the related modal or panel.
function ouvrirModalAtelier(nom) {
  currentAtelier = nom;
  document.getElementById('modalAtelierNom').textContent = nom;
  
  const successBanner = document.getElementById('successBannerWorkshop');
  if (successBanner) successBanner.classList.remove('show');
  
  document.getElementById('prenomW').value = '';
  document.getElementById('nomW').value = '';
  document.getElementById('emailW').value = '';
  document.getElementById('telW').value = '';
  document.getElementById('nbParticipants').value = '1';
  document.getElementById('messageW').value = '';

  const fields = ['prenomW', 'nomW', 'emailW', 'telW'];
  for (let i = 0; i < fields.length; i++) {
    const id = fields[i];
    const input = document.getElementById(id);
    if (input) input.classList.remove('error');
  }

  const errorMsgs = ['err-prenomW', 'err-nomW', 'err-emailW', 'err-telW', 'err-nbParticipants'];
  for (let i = 0; i < errorMsgs.length; i++) {
    const id = errorMsgs[i];
    const errDiv = document.getElementById(id);
    if (errDiv) errDiv.classList.remove('show');
  }

  document.getElementById('modalWorkshopOverlay').classList.add('open');
}

// Closes the related modal or panel.
function fermerModalAtelier() {
  document.getElementById('modalWorkshopOverlay').classList.remove('open');
  currentAtelier = '';
}

// Fermeture en cliquant à l'extérieur
const modalWorkshopOverlay = document.getElementById('modalWorkshopOverlay');
if (modalWorkshopOverlay) {
  modalWorkshopOverlay.addEventListener('click', function(e) {
    if (e.target === this) {
      fermerModalAtelier();
    }
  });
}

// VALIDATIONS
// Validates the email format for workshop reservation.
function validerEmail(email) {
  if (email === '') {
    return false;
  }

  if (email.indexOf('@') === -1) {
    return false;
  }

  if (email.indexOf('.') === -1) {
    return false;
  }

  return true;
}

// Validates input values and returns whether they are valid.
function validerTelephone(tel) {
  let digits = '';
  for (let i = 0; i < tel.length; i++) {
    const c = tel[i];
    if (c >= '0' && c <= '9') {
      digits = digits + c;
    }
  }

  if (digits.length === 8) {
    return true;
  }

  return false;
}

// Displays content in the interface based on current data.
function afficherErreurWorkshop(champId, estErreur) {
  let errorDivId = '';
  let inputField = null;
  
  if (champId === 'prenomW') { errorDivId = 'err-prenomW'; inputField = document.getElementById('prenomW'); }
  if (champId === 'nomW') { errorDivId = 'err-nomW'; inputField = document.getElementById('nomW'); }
  if (champId === 'emailW') { errorDivId = 'err-emailW'; inputField = document.getElementById('emailW'); }
  if (champId === 'telW') { errorDivId = 'err-telW'; inputField = document.getElementById('telW'); }
  if (champId === 'nbParticipants') { errorDivId = 'err-nbParticipants'; inputField = document.getElementById('nbParticipants'); }
  
  const errorDiv = document.getElementById(errorDivId);
  if (estErreur) {
    if (errorDiv) errorDiv.classList.add('show');
    if (inputField && champId !== 'nbParticipants') inputField.classList.add('error');
  } else {
    if (errorDiv) errorDiv.classList.remove('show');
    if (inputField && champId !== 'nbParticipants') inputField.classList.remove('error');
  }
}

// Validates and submits the current form data.
function soumettreReservation() {
  let valide = true;
  
  const prenom = document.getElementById('prenomW').value.trim();
  if (prenom.length < 2) {
    afficherErreurWorkshop('prenomW', true);
    valide = false;
  } else { afficherErreurWorkshop('prenomW', false); }
  
  const nom = document.getElementById('nomW').value.trim();
  if (nom.length < 2) {
    afficherErreurWorkshop('nomW', true);
    valide = false;
  } else { afficherErreurWorkshop('nomW', false); }
  
  const email = document.getElementById('emailW').value.trim();
  if (!validerEmail(email)) {
    afficherErreurWorkshop('emailW', true);
    valide = false;
  } else { afficherErreurWorkshop('emailW', false); }
  
  const tel = document.getElementById('telW').value.trim();
  if (!validerTelephone(tel)) {
    afficherErreurWorkshop('telW', true);
    valide = false;
  } else { afficherErreurWorkshop('telW', false); }
  
  const nbParticipants = document.getElementById('nbParticipants').value;
  if (!nbParticipants || nbParticipants === '') {
    afficherErreurWorkshop('nbParticipants', true);
    valide = false;
  } else { afficherErreurWorkshop('nbParticipants', false); }
  
  if (valide) {
    const message = document.getElementById('messageW').value;

    let messageAffiche = message;
    if (messageAffiche === '') {
      messageAffiche = '(aucun)';
    }

    console.log('=== RÉSERVATION ATELIER ===');
    console.log('Atelier:', currentAtelier);
    console.log('Participant(s):', prenom, nom);
    console.log('Email:', email);
    console.log('Tél:', tel);
    console.log('Nb personnes:', nbParticipants);
    console.log('Message:', messageAffiche);
    console.log('===========================');

    const successBanner = document.getElementById('successBannerWorkshop');
    successBanner.classList.add('show');

    setTimeout(function() {
      fermerModalAtelier();
      let suffixeParticipants = '';
      if (parseInt(nbParticipants, 10) > 1) {
        suffixeParticipants = 's';
      }
      alert(`✨ Merci ${prenom} !\n\nVotre réservation pour l'atelier "${currentAtelier}" (${nbParticipants} participant${suffixeParticipants}) est confirmée.\n\nUn email récapitulatif a été envoyé à ${email}.`);
    }, 1500);
  }
}

// Recherche avec la touche Entrée
const searchWorkshop = document.getElementById('searchWorkshop');
if (searchWorkshop) {
  searchWorkshop.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
      rechercherAteliers();
    }
  });
}

// Initialisation
window.addEventListener('DOMContentLoaded', function() {
  const boutonAjout = document.getElementById('btnAjouterWorkshopFront');
  if (boutonAjout) {
    boutonAjout.addEventListener('click', function(event) {
      event.preventDefault();
      ouvrirModalAjoutWorkshop();
    });
  }

  const cards = document.querySelectorAll('#workshopsGrid .card');
  for (let i = 0; i < cards.length; i++) {
    cards[i].style.display = '';
  }
});