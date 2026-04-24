
// Opens the related modal or panel.
function ouvrirFormAjoutFront()
{
  var modal = document.getElementById('modalAjoutFront');
  if (modal) {
    modal.classList.add('open');
  }
}

// Closes the related modal or panel.
function fermerFormAjoutFront()
{
  var modal = document.getElementById('modalAjoutFront');
  if (modal) {
    modal.classList.remove('open');
  }
}

// Closes the related modal or panel.
function fermerFormFrontSiExterieur(event)
{
  if (event.target && event.target.id === 'modalAjoutFront') {
    fermerFormAjoutFront();
  }
}

// Opens the related modal or panel.
function openFormationDetails(formationId)
{
  var sectionGrid = document.getElementById('formationsGridSection');
  var sectionDetail = document.getElementById('formationDetailView');

  if (!sectionGrid || !sectionDetail) {
    return;
  }

  var panels = sectionDetail.querySelectorAll('.formation-detail-card');
  var found = false;

  for (var i = 0; i < panels.length; i++) {
    var panel = panels[i];
    var panelId = parseInt(panel.getAttribute('data-detail-id'), 10);

    if (panelId === formationId) {
      panel.style.display = 'block';
      found = true;
    } else {
      panel.style.display = 'none';
    }
  }

  if (!found) {
    return;
  }

  sectionGrid.style.display = 'none';
  sectionDetail.classList.add('open');
  sectionDetail.setAttribute('data-active-id', String(formationId));
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Closes the related modal or panel.
function closeFormationDetails()
{
  var sectionGrid = document.getElementById('formationsGridSection');
  var sectionDetail = document.getElementById('formationDetailView');

  if (!sectionGrid || !sectionDetail) {
    return;
  }

  sectionGrid.style.display = '';
  sectionDetail.classList.remove('open');
  sectionDetail.removeAttribute('data-active-id');

  var panels = sectionDetail.querySelectorAll('.formation-detail-card');
  for (var i = 0; i < panels.length; i++) {
    panels[i].style.display = 'none';
  }

  var workshopSections = sectionDetail.querySelectorAll('.associated-workshops');
  for (var j = 0; j < workshopSections.length; j++) {
    workshopSections[j].classList.remove('open');
  }

  var workshopButtons = sectionDetail.querySelectorAll('.workshops-toggle-btn');
  for (var k = 0; k < workshopButtons.length; k++) {
    workshopButtons[k].textContent = 'Voir ateliers';
  }

  var quizPanels = sectionDetail.querySelectorAll('.quiz-questions-panel');
  for (var l = 0; l < quizPanels.length; l++) {
    quizPanels[l].classList.remove('open');
  }

  var quizButtons = sectionDetail.querySelectorAll('.quiz-launch-btn');
  for (var m = 0; m < quizButtons.length; m++) {
    quizButtons[m].textContent = 'Passer quizz';
  }
}

// Toggles visibility for the requested section.
function toggleAssociatedWorkshops(formationId)
{
  var container = document.getElementById('associatedWorkshops' + String(formationId));
  if (!container) {
    return;
  }

  var button = document.querySelector('.workshops-toggle-btn[data-formation-id="' + String(formationId) + '"]');
  var isOpen = container.classList.contains('open');

  if (isOpen) {
    container.classList.remove('open');
    if (button) {
      button.textContent = 'Voir ateliers';
    }
  } else {
    container.classList.add('open');
    if (button) {
      button.textContent = 'Masquer ateliers';
    }
  }
}

// Toggles visibility for the requested section.
function toggleQuizPanel(formationId)
{
  var panel = document.getElementById('quizPanel' + String(formationId));
  if (!panel) {
    return;
  }

  var button = document.querySelector('.quiz-launch-btn[data-formation-id="' + String(formationId) + '"]');
  var isOpen = panel.classList.contains('open');

  if (isOpen) {
    panel.classList.remove('open');
    if (button) {
      button.textContent = 'Passer quizz';
    }
  } else {
    panel.classList.add('open');
    if (button) {
      button.textContent = 'Masquer quizz';
    }
  }
}

document.addEventListener('DOMContentLoaded', function ()
{
  var boutonAjout = document.getElementById('btnAjouterFormationFrontPhp');
  if (boutonAjout) {
    boutonAjout.addEventListener('click', function (event) {
      event.preventDefault();
      ouvrirFormAjoutFront();
    });
  }

  var cartesFormations = document.querySelectorAll('.formation-card');
  for (var i = 0; i < cartesFormations.length; i++) {
    (function (carte) {
      var formationId = parseInt(carte.getAttribute('data-formation-id'), 10);

      carte.addEventListener('click', function (event) {
        if (event.target && event.target.closest('.formation-detail-btn')) {
          return;
        }

        if (formationId > 0) {
          openFormationDetails(formationId);
        }
      });

      carte.addEventListener('keydown', function (event) {
        if ((event.key === 'Enter' || event.key === ' ') && formationId > 0) {
          event.preventDefault();
          openFormationDetails(formationId);
        }
      });

      var boutonDetail = carte.querySelector('.formation-detail-btn');
      if (boutonDetail) {
        boutonDetail.addEventListener('click', function (event) {
          event.preventDefault();
          event.stopPropagation();

          if (formationId > 0) {
            openFormationDetails(formationId);
          }
        });
      }
    })(cartesFormations[i]);
  }

  var autoOpenId = <?php echo (string) ((int) $selectedFormationId); ?>;
  if (autoOpenId > 0) {
    openFormationDetails(autoOpenId);
  }
});

