<?php
/**
 * ai_widget_front.php
 * Fragment PHP/HTML à intégrer dans front.php (CraftLink)
 * ─────────────────────────────────────────────────────────
 * Contient les éléments UI et le JavaScript pour les 5 fonctionnalités IA :
 *   1. ✨ Bouton « Générer description IA » dans le formulaire d'ajout
 *   2. 🤖 Bouton « Générer questions IA » dans le quiz builder
 *   3. 🔍 Assistant de recommandation de formations (panneau latéral)
 *   4. 🗺️ Parcours d'apprentissage personnalisé (modal)
 *   5. 📊 Résumé IA d'une formation dans la vue détail
 *
 * INTÉGRATION :
 *   - Placer ce fichier dans le même dossier que front.php
 *   - Dans front.php, ajouter : <?php include 'ai_widget_front.php'; ?>
 *     juste avant </body>
 *   - Placer ai_features.php dans le même dossier
 *   - Ajouter les styles CSS ci-dessous dans le <head> de front.php
 *
 * CSS à ajouter dans le <style> de front.php :
 * (voir bloc /* ─── AI STYLES ─── * / ci-dessous)
 */
?>

<!-- ═══════════════════════════════════════════════════════════════
     AI FEATURE 3 — Panneau recommandation formations
     ═══════════════════════════════════════════════════════════════ -->
<div id="aiRecommendPanel" class="ai-side-panel" aria-hidden="true">
  <div class="ai-panel-inner">
    <div class="ai-panel-header">
      <div class="ai-icon-row">
        <span class="ai-sparkle">✦</span>
        <h2>Recommandation IA</h2>
      </div>
      <button class="ai-close-btn" onclick="closeAiRecommend()" aria-label="Fermer">✕</button>
    </div>
    <div class="ai-panel-body">
      <p class="ai-panel-intro">Décrivez votre profil, l'IA vous suggère les formations les plus adaptées.</p>
      <div class="ai-form-group">
        <label>Vos centres d'intérêt artisanaux</label>
        <input type="text" id="aiInterets" placeholder="Ex: poterie, broderie, tissage..." class="ai-input">
      </div>
      <div class="ai-form-row">
        <div class="ai-form-group">
          <label>Niveau actuel</label>
          <select id="aiNiveauProfil" class="ai-input">
            <option value="debutant">Débutant</option>
            <option value="intermediaire">Intermédiaire</option>
            <option value="avance">Avancé</option>
          </select>
        </div>
        <div class="ai-form-group">
          <label>Budget max (TND)</label>
          <input type="number" id="aiBudget" placeholder="Ex: 300" class="ai-input" min="0">
        </div>
      </div>
      <div class="ai-form-group">
        <label>Disponibilité (heures/semaine)</label>
        <input type="number" id="aiDisponible" placeholder="Ex: 5" class="ai-input" min="1">
      </div>
      <button class="ai-primary-btn" onclick="runAiRecommend()" id="btnRunRecommend">
        <span class="ai-btn-icon">✦</span> Analyser mon profil
      </button>
      <div id="aiRecommendResult" class="ai-result-zone" style="display:none"></div>
    </div>
  </div>
</div>
<div class="ai-panel-backdrop" id="aiPanelBackdrop" onclick="closeAiRecommend()"></div>

<!-- ═══════════════════════════════════════════════════════════════
     AI FEATURE 4 — Modal parcours personnalisé
     ═══════════════════════════════════════════════════════════════ -->
<div id="aiPathModal" class="ai-modal-overlay" aria-hidden="true">
  <div class="ai-modal">
    <div class="ai-modal-header">
      <div class="ai-icon-row">
        <span class="ai-sparkle">◈</span>
        <h2>Parcours d'apprentissage </h2>
      </div>
      <button class="ai-close-btn" onclick="closeAiPath()" aria-label="Fermer">✕</button>
    </div>
    <div class="ai-modal-body">
      <p class="ai-panel-intro">Décrivez votre objectif et on conçoit un parcours progressif sur mesure.</p>
      <div class="ai-form-group">
        <label>Votre objectif *</label>
        <textarea id="aiObjectif" placeholder="Ex: Maîtriser la poterie traditionnelle tunisienne pour créer ma propre boutique..." class="ai-input ai-textarea" rows="3"></textarea>
      </div>
      <div class="ai-form-group">
        <label>Niveau de départ</label>
        <select id="aiNiveauPath" class="ai-input">
          <option value="debutant">Débutant complet</option>
          <option value="intermediaire">Quelques bases</option>
          <option value="avance">Déjà expérimenté</option>
        </select>
      </div>
      <button class="ai-primary-btn" onclick="runAiPath()" id="btnRunPath">
        <span class="ai-btn-icon">◈</span> Générer mon parcours
      </button>
      <div id="aiPathResult" class="ai-result-zone" style="display:none"></div>
    </div>
  </div>
</div>

<!-- Floating AI Action Button -->
<div class="ai-fab-container">
  <div class="ai-fab-menu" id="aiFabMenu">
    <button class="ai-fab-item" onclick="openAiRecommend()" title="Recommandation de formations">
      <span>🎯</span><span class="ai-fab-label">Me recommander</span>
    </button>
    <button class="ai-fab-item" onclick="openAiPath()" title="Parcours personnalisé">
      <span>🗺️</span><span class="ai-fab-label">Mon parcours</span>
    </button>
  </div>
  <button class="ai-fab" id="aiFabMain" onclick="toggleAiFab()" title="Fonctionnalités IA" aria-label="Ouvrir les outils IA">
    <span class="ai-fab-icon">✦</span>
    <span class="ai-fab-text">Vane</span>
  </button>
</div>

<style>
/* ─── AI STYLES ─── */
:root {
  --ai-marron: #3b2314;
  --ai-caramel: #c49a6c;
  --ai-creme: #f5ecd7;
  --ai-brun: #6b3a2a;
  --ai-vert: #2e6b3e;
  --ai-gris: #6b7280;
}
.ai-side-panel {
  position: fixed;
  right: -420px;
  top: 0;
  width: 400px;
  height: 100vh;
  background: #fffcf5;
  border-left: 1px solid rgba(196,154,108,0.3);
  box-shadow: -8px 0 32px rgba(59,35,20,0.12);
  z-index: 10000;
  transition: right 0.35s cubic-bezier(.25,.8,.25,1);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
.ai-side-panel.open {
  right: 0;
}
.ai-panel-backdrop {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.25);
  z-index: 9999;
}
.ai-panel-backdrop.show {
  display: block;
}
.ai-panel-inner {
  display: flex;
  flex-direction: column;
  height: 100%;
}
.ai-panel-header, .ai-modal-header {
  padding: 18px 20px;
  border-bottom: 1px solid rgba(196,154,108,0.2);
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: linear-gradient(135deg, rgba(245,236,215,0.9) 0%, rgba(255,252,245,1) 100%);
}
.ai-icon-row {
  display: flex;
  align-items: center;
  gap: 10px;
}
.ai-sparkle {
  font-size: 22px;
  color: var(--ai-caramel);
  animation: aiPulse 2s ease-in-out infinite;
}
@keyframes aiPulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.7; transform: scale(1.15); }
}
.ai-icon-row h2 {
  font-family: 'Playfair Display', serif;
  font-size: 1.05rem;
  color: var(--ai-brun);
  margin: 0;
}
.ai-close-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.1rem;
  color: var(--ai-gris);
  padding: 4px 8px;
  border-radius: 4px;
  transition: background 0.15s;
}
.ai-close-btn:hover {
  background: rgba(196,154,108,0.15);
  color: var(--ai-marron);
}
.ai-panel-body, .ai-modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
}
.ai-panel-intro {
  font-size: 0.84rem;
  color: var(--ai-gris);
  margin-bottom: 16px;
  line-height: 1.55;
}
.ai-form-group {
  margin-bottom: 14px;
}
.ai-form-group label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--ai-marron);
  margin-bottom: 5px;
}
.ai-form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}
.ai-input {
  width: 100%;
  padding: 9px 12px;
  border: 1.5px solid rgba(196,154,108,0.4);
  border-radius: 8px;
  background: #fff;
  color: var(--ai-marron);
  font-size: 0.85rem;
  box-sizing: border-box;
  transition: border-color 0.2s;
}
.ai-input:focus {
  outline: none;
  border-color: var(--ai-caramel);
  box-shadow: 0 0 0 3px rgba(196,154,108,0.15);
}
.ai-textarea {
  resize: vertical;
  min-height: 80px;
}
.ai-primary-btn {
  width: 100%;
  padding: 11px 16px;
  background: var(--ai-marron);
  color: #f5ecd7;
  border: none;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: background 0.2s, transform 0.1s;
  margin-top: 4px;
}
.ai-primary-btn:hover:not(:disabled) {
  background: var(--ai-brun);
}
.ai-primary-btn:active:not(:disabled) {
  transform: scale(0.98);
}
.ai-primary-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.ai-btn-icon {
  font-size: 1rem;
}
.ai-result-zone {
  margin-top: 16px;
}
.ai-loading {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--ai-gris);
  font-size: 0.84rem;
  padding: 12px;
  background: rgba(245,236,215,0.6);
  border-radius: 8px;
}
.ai-spinner {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(196,154,108,0.3);
  border-top-color: var(--ai-caramel);
  border-radius: 50%;
  animation: aiSpin 0.8s linear infinite;
  flex-shrink: 0;
}
@keyframes aiSpin {
  to { transform: rotate(360deg); }
}
.ai-error {
  background: rgba(192,57,43,0.08);
  border: 1px solid rgba(192,57,43,0.25);
  color: #7b1f15;
  padding: 10px 12px;
  border-radius: 8px;
  font-size: 0.83rem;
}
.ai-recommend-card {
  background: #fff;
  border: 1px solid rgba(196,154,108,0.25);
  border-radius: 10px;
  padding: 12px;
  margin-bottom: 10px;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.ai-recommend-card:hover {
  border-color: var(--ai-caramel);
  box-shadow: 0 2px 12px rgba(196,154,108,0.15);
}
.ai-recommend-title {
  font-weight: 700;
  color: var(--ai-brun);
  font-size: 0.9rem;
  margin-bottom: 4px;
}
.ai-recommend-reason {
  font-size: 0.8rem;
  color: var(--ai-gris);
  line-height: 1.5;
  margin-bottom: 8px;
}
.ai-recommend-action {
  display: inline-block;
  padding: 5px 10px;
  background: var(--ai-creme);
  border: 1px solid rgba(196,154,108,0.4);
  border-radius: 20px;
  font-size: 0.75rem;
  color: var(--ai-marron);
  cursor: pointer;
  text-decoration: none;
  transition: background 0.15s;
}
.ai-recommend-action:hover {
  background: rgba(196,154,108,0.2);
}
.ai-conseil-box {
  background: rgba(46,107,62,0.07);
  border: 1px solid rgba(46,107,62,0.2);
  border-radius: 8px;
  padding: 12px;
  font-size: 0.83rem;
  color: #1f4d2b;
  line-height: 1.55;
  margin-top: 12px;
}
.ai-conseil-box strong {
  display: block;
  margin-bottom: 4px;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.ai-path-step {
  display: flex;
  gap: 12px;
  margin-bottom: 14px;
  position: relative;
}
.ai-path-step::before {
  content: '';
  position: absolute;
  left: 17px;
  top: 36px;
  width: 2px;
  height: calc(100% + 2px);
  background: rgba(196,154,108,0.25);
}
.ai-path-step:last-child::before {
  display: none;
}
.ai-step-num {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: var(--ai-marron);
  color: #f5ecd7;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 700;
  flex-shrink: 0;
}
.ai-step-body {
  flex: 1;
  padding-top: 4px;
}
.ai-step-title {
  font-weight: 700;
  color: var(--ai-brun);
  font-size: 0.88rem;
  margin-bottom: 3px;
}
.ai-step-formation {
  font-size: 0.78rem;
  color: var(--ai-caramel);
  font-style: italic;
  margin-bottom: 4px;
}
.ai-step-raison {
  font-size: 0.78rem;
  color: var(--ai-gris);
  line-height: 1.45;
}
.ai-path-title {
  font-family: 'Playfair Display', serif;
  font-size: 1.05rem;
  color: var(--ai-brun);
  margin-bottom: 4px;
}
.ai-path-duree {
  font-size: 0.78rem;
  color: var(--ai-gris);
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 4px;
}
.ai-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.4);
  z-index: 10001;
  display: none;
  align-items: flex-start;
  justify-content: center;
  padding: 40px 16px;
  overflow-y: auto;
}
.ai-modal-overlay.open {
  display: flex;
}
.ai-modal {
  background: #fffcf5;
  border-radius: 14px;
  width: 100%;
  max-width: 540px;
  box-shadow: 0 20px 60px rgba(59,35,20,0.2);
  overflow: hidden;
}
/* ─── FAB ─── */
.ai-fab-container {
  position: fixed;
  bottom: 28px;
  right: 28px;
  z-index: 9998;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 10px;
}
.ai-fab {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--ai-marron);
  color: #f5ecd7;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  box-shadow: 0 4px 20px rgba(59,35,20,0.3);
  transition: transform 0.2s, box-shadow 0.2s;
  font-size: 0.6rem;
  font-weight: 700;
  gap: 1px;
  letter-spacing: 0.5px;
}
.ai-fab:hover {
  transform: scale(1.08);
  box-shadow: 0 6px 24px rgba(59,35,20,0.35);
}
.ai-fab.active {
  background: var(--ai-brun);
}
.ai-fab-icon {
  font-size: 1.2rem;
  line-height: 1;
}
.ai-fab-text {
  font-size: 0.6rem;
  opacity: 0.9;
  text-transform: uppercase;
  letter-spacing: 1px;
}
.ai-fab-menu {
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: flex-end;
  overflow: hidden;
  max-height: 0;
  transition: max-height 0.3s ease;
}
.ai-fab-menu.open {
  max-height: 200px;
}
.ai-fab-item {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #fff;
  border: 1px solid rgba(196,154,108,0.3);
  border-radius: 24px;
  padding: 8px 14px;
  cursor: pointer;
  box-shadow: 0 2px 12px rgba(59,35,20,0.12);
  font-size: 0.82rem;
  color: var(--ai-marron);
  font-weight: 600;
  white-space: nowrap;
  transition: background 0.15s, box-shadow 0.15s;
}
.ai-fab-item:hover {
  background: var(--ai-creme);
  box-shadow: 0 4px 16px rgba(59,35,20,0.18);
}
/* ─── In-form AI buttons ─── */
.ai-inline-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 12px;
  background: rgba(196,154,108,0.12);
  border: 1px solid rgba(196,154,108,0.35);
  border-radius: 20px;
  color: var(--ai-marron);
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s;
  text-decoration: none;
}
.ai-inline-btn:hover:not(:disabled) {
  background: rgba(196,154,108,0.22);
}
.ai-inline-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
/* ─── Summary chip strip ─── */
.ai-summary-strip {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 10px;
}
.ai-kw-chip {
  padding: 4px 10px;
  background: rgba(245,236,215,0.9);
  border: 1px solid rgba(196,154,108,0.25);
  border-radius: 20px;
  font-size: 0.73rem;
  color: var(--ai-marron);
}
.ai-meta-list {
  list-style: none;
  padding: 0;
  margin: 8px 0 0;
  font-size: 0.81rem;
  color: var(--ai-gris);
  line-height: 1.7;
}
.ai-meta-list li::before {
  content: '→ ';
  color: var(--ai-caramel);
}
/* Mobile */
@media (max-width: 480px) {
  .ai-side-panel { width: 100%; right: -100%; }
  .ai-form-row { grid-template-columns: 1fr; }
}
</style>

<script>
/* ─── AI Helper utilities ─── */
const AI_ENDPOINT = 'ai_features.php';

async function callAiFeature(params) {
  const form = new FormData();
  Object.entries(params).forEach(([k, v]) => form.append(k, v));
  const res = await fetch(AI_ENDPOINT, { method: 'POST', body: form });
  const data = await res.json();
  if (!res.ok || data.error) throw new Error(data.error || 'Erreur serveur');
  return data;
}

function showAiLoading(el, msg) {
  el.style.display = 'block';
  el.innerHTML = `<div class="ai-loading"><div class="ai-spinner"></div><span>${msg}</span></div>`;
}

function showAiError(el, msg) {
  el.style.display = 'block';
  el.innerHTML = `<div class="ai-error">⚠ ${msg}</div>`;
}

/* ─── FAB toggle ─── */
function toggleAiFab() {
  const menu = document.getElementById('aiFabMenu');
  const fab  = document.getElementById('aiFabMain');
  menu.classList.toggle('open');
  fab.classList.toggle('active');
}

/* ─── FEATURE 3 — Recommend ─── */
function openAiRecommend() {
  document.getElementById('aiRecommendPanel').classList.add('open');
  document.getElementById('aiPanelBackdrop').classList.add('show');
  document.getElementById('aiFabMenu').classList.remove('open');
  document.getElementById('aiFabMain').classList.remove('active');
}

function closeAiRecommend() {
  document.getElementById('aiRecommendPanel').classList.remove('open');
  document.getElementById('aiPanelBackdrop').classList.remove('show');
}

async function runAiRecommend() {
  const btn = document.getElementById('btnRunRecommend');
  const result = document.getElementById('aiRecommendResult');
  const interets   = document.getElementById('aiInterets').value.trim();
  const niveau     = document.getElementById('aiNiveauProfil').value;
  const budget     = document.getElementById('aiBudget').value.trim();
  const disponible = document.getElementById('aiDisponible').value.trim();

  btn.disabled = true;
  showAiLoading(result, "L'IA analyse votre profil...");

  try {
    const data = await callAiFeature({ ai_action: 'recommend_formations', interets, niveau, budget, disponible });
    const recs = data.recommendations || [];

    if (recs.length === 0) {
      result.innerHTML = '<div class="ai-error">Aucune formation correspondante trouvée.</div>';
    } else {
      let html = '<div style="margin-bottom:12px;font-size:0.8rem;color:var(--ai-gris);">' + recs.length + ' formation(s) recommandée(s)</div>';
      recs.forEach(r => {
        html += `<div class="ai-recommend-card">
          <div class="ai-recommend-title">#${r.id_formation}</div>
          <div class="ai-recommend-reason">${r.raison}</div>
          <a class="ai-recommend-action" onclick="closeAiRecommend();openFormationDetails(${r.id_formation})">Voir cette formation →</a>
        </div>`;
      });
      if (data.conseil) {
        html += `<div class="ai-conseil-box"><strong>💡 Conseil personnalisé</strong>${data.conseil}</div>`;
      }
      result.innerHTML = html;
    }
    result.style.display = 'block';
  } catch (e) {
    showAiError(result, e.message);
  } finally {
    btn.disabled = false;
  }
}

/* ─── FEATURE 4 — Learning path ─── */
function openAiPath() {
  document.getElementById('aiPathModal').classList.add('open');
  document.getElementById('aiFabMenu').classList.remove('open');
  document.getElementById('aiFabMain').classList.remove('active');
}

function closeAiPath() {
  document.getElementById('aiPathModal').classList.remove('open');
}

async function runAiPath() {
  const btn    = document.getElementById('btnRunPath');
  const result = document.getElementById('aiPathResult');
  const objectif   = document.getElementById('aiObjectif').value.trim();
  const niveauPath = document.getElementById('aiNiveauPath').value;

  if (!objectif) {
    showAiError(result, 'Veuillez décrire votre objectif.');
    result.style.display = 'block';
    return;
  }

  btn.disabled = true;
  showAiLoading(result, "L'IA conçoit votre parcours...");

  try {
    const data = await callAiFeature({ ai_action: 'learning_path', objectif, niveau_actuel: niveauPath });
    const etapes = data.etapes || [];

    let html = `<div class="ai-path-title">${data.titre_parcours || 'Mon parcours personnalisé'}</div>
      <div class="ai-path-duree">🕐 ${data.duree_totale || ''}</div>`;

    etapes.forEach((e, i) => {
      html += `<div class="ai-path-step">
        <div class="ai-step-num">${e.ordre || (i+1)}</div>
        <div class="ai-step-body">
          <div class="ai-step-title">${e.titre}</div>
          <div class="ai-step-formation">📚 ${e.nom_formation}</div>
          <div class="ai-step-raison">${e.raison}<br><em>${e.objectif_etape || ''}</em></div>
        </div>
      </div>`;
    });

    if (data.conseils) {
      html += `<div class="ai-conseil-box"><strong>💡 Conseils pour réussir</strong>${data.conseils}</div>`;
    }

    result.innerHTML = html;
    result.style.display = 'block';
  } catch (e) {
    showAiError(result, e.message);
  } finally {
    btn.disabled = false;
  }
}

/* ─── FEATURE 1 — Generate description (called from form) ─── */
async function aiGenerateDescription(titreInputId, descTextareaId, niveauSelectId, dureeInputId, btnEl) {
  const titre  = document.getElementById(titreInputId)?.value.trim() || '';
  const niveau = document.getElementById(niveauSelectId)?.value || 'debutant';
  const duree  = document.getElementById(dureeInputId)?.value.trim() || '';

  if (!titre) { alert('Saisissez d\'abord le titre de la formation.'); return; }

  btnEl.disabled = true;
  const origText = btnEl.innerHTML;
  btnEl.innerHTML = '<span class="ai-spinner" style="display:inline-block;width:14px;height:14px;border:2px solid rgba(196,154,108,0.3);border-top-color:var(--ai-caramel);border-radius:50%;animation:aiSpin 0.8s linear infinite;vertical-align:middle;margin-right:6px;"></span>Génération…';

  try {
    const data = await callAiFeature({ ai_action: 'generate_description', titre, niveau, duree });
    const textarea = document.getElementById(descTextareaId);
    if (textarea) textarea.value = data.description || '';
  } catch (e) {
    alert('Erreur IA : ' + e.message);
  } finally {
    btnEl.disabled = false;
    btnEl.innerHTML = origText;
  }
}

/* ─── FEATURE 2 — Generate quiz questions (called from quiz builder) ─── */
async function aiGenerateQuizQuestions(titreInputId, descTextareaId, niveauSelectId, nbQuestions, btnEl) {
  const titre       = document.getElementById(titreInputId)?.value.trim() || '';
  const description = document.getElementById(descTextareaId)?.value.trim() || '';
  const niveau      = document.getElementById(niveauSelectId)?.value || 'debutant';

  if (!titre) { alert('Saisissez d\'abord le titre de la formation.'); return; }

  btnEl.disabled = true;
  const origText = btnEl.innerHTML;
  btnEl.innerHTML = '<span style="display:inline-block;width:12px;height:12px;border:2px solid rgba(196,154,108,0.3);border-top-color:var(--ai-caramel);border-radius:50%;animation:aiSpin 0.8s linear infinite;vertical-align:middle;margin-right:6px;"></span>Génération des questions…';

  try {
    const data = await callAiFeature({ ai_action: 'generate_quiz_questions', titre, description, niveau, nb: nbQuestions });
    const questions = data.questions || [];

    const container = document.getElementById('quizBuilderQuestions');
    if (!container) throw new Error('Quiz builder introuvable.');

    questions.forEach(q => {
      const nextIdx = parseInt(container.getAttribute('data-next-question-index') || '0', 10);
      const qIdx = String(nextIdx);
      container.setAttribute('data-next-question-index', String(nextIdx + 1));

      const isVF = q.type === 'vrai_faux';
      const qType = (q.type === 'choix_unique' || q.type === 'choix_multiple' || q.type === 'vrai_faux') ? q.type : 'choix_unique';
      const points = q.points || 1;
      const reponses = q.reponses || [];

      let answersHtml = '';
      reponses.forEach((r, ai) => {
        const escapedText = r.texte.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;');
        answersHtml += `<div class="quiz-builder-answer-row">
          <input type="text" name="quiz_answer_text[${qIdx}][${ai}]" value="${escapedText}" placeholder="Texte de la réponse">
          <label><input type="checkbox" name="quiz_answer_correct[${qIdx}][]" value="${ai}" ${r.est_correcte ? 'checked' : ''}>Bonne réponse</label>
        </div>`;
      });

      const tfCorrect = reponses.length > 0 && reponses[0].est_correcte ? 'true' : 'false';

      const qHtml = `<div class="quiz-builder-question" data-question-index="${qIdx}" data-next-answer-index="${reponses.length + 1}">
        <div class="quiz-builder-head">
          <strong>Question <span class="quiz-builder-number">0</span></strong>
          <button type="button" class="btn-cancel quiz-remove-btn" onclick="removeQuizBuilderQuestion(this)">Supprimer</button>
        </div>
        <div class="form-group">
          <label>Texte de la question *</label>
          <input type="text" name="quiz_question_text[${qIdx}]" value="${q.enonce.replace(/"/g,'&quot;')}" placeholder="Texte de la question">
        </div>
        <div class="front-form-row-3">
          <div class="form-group">
            <label>Type</label>
            <select class="quiz-builder-type-select" data-question-index="${qIdx}" name="quiz_question_type[${qIdx}]" onchange="onQuizBuilderTypeChange(this,'${qIdx}')">
              <option value="choix_unique" ${qType==='choix_unique'?'selected':''}>Choix unique</option>
              <option value="choix_multiple" ${qType==='choix_multiple'?'selected':''}>Choix multiple</option>
              <option value="vrai_faux" ${qType==='vrai_faux'?'selected':''}>Vrai / Faux</option>
            </select>
          </div>
          <div class="form-group">
            <label>Points</label>
            <input type="number" min="1" step="1" name="quiz_question_points[${qIdx}]" value="${points}">
          </div>
          <div class="form-group ${isVF ? '' : 'quiz-builder-hidden'}" id="quizTrueFalseBox${qIdx}">
            <label>Bonne réponse</label>
            <select name="quiz_tf_correct[${qIdx}]">
              <option value="true" ${tfCorrect==='true'?'selected':''}>True</option>
              <option value="false" ${tfCorrect==='false'?'selected':''}>False</option>
            </select>
          </div>
        </div>
        <div class="form-group ${isVF ? 'quiz-builder-hidden' : ''}" id="quizAnswersBox${qIdx}">
          <label>Réponses *</label>
          <div class="quiz-builder-answer-list" id="quizAnswerList${qIdx}">${answersHtml}</div>
          <button type="button" class="btn-inscrit vert" onclick="addQuizBuilderAnswer('${qIdx}')">+ Ajouter une réponse</button>
        </div>
      </div>`;

      container.insertAdjacentHTML('beforeend', qHtml);
    });

    if (typeof refreshQuizBuilderQuestionLabels === 'function') refreshQuizBuilderQuestionLabels();

    alert(questions.length + ' question(s) générée(s) par l\'IA et ajoutée(s) au quiz !');
  } catch (e) {
    alert('Erreur IA : ' + e.message);
  } finally {
    btnEl.disabled = false;
    btnEl.innerHTML = origText;
  }
}

/* ─── FEATURE 5 — Formation summary in detail view ─── */
async function aiSummarizeFormation(formationId, titre, description, niveau, duree, containerEl) {
  showAiLoading(containerEl, "L'IA analyse cette formation...");
  containerEl.style.display = 'block';

  try {
    const data = await callAiFeature({ ai_action: 'summarize_formation', titre, description, niveau, duree });

    let html = '';
    if (data.resume_court) {
      html += `<p style="font-style:italic;color:var(--ai-gris);font-size:0.85rem;margin-bottom:10px;">"${data.resume_court}"</p>`;
    }
    if (data.mots_cles && data.mots_cles.length > 0) {
      html += '<div class="ai-summary-strip">';
      data.mots_cles.forEach(k => { html += `<span class="ai-kw-chip">${k}</span>`; });
      html += '</div>';
    }
    if (data.public_cible) {
      html += `<p style="font-size:0.8rem;color:var(--ai-gris);margin-top:10px;"><strong style="color:var(--ai-marron);">Public :</strong> ${data.public_cible}</p>`;
    }
    if (data.prerequis) {
      html += `<p style="font-size:0.8rem;color:var(--ai-gris);"><strong style="color:var(--ai-marron);">Prérequis :</strong> ${data.prerequis}</p>`;
    }
    if (data.debouches && data.debouches.length > 0) {
      html += '<ul class="ai-meta-list">';
      data.debouches.forEach(d => { html += `<li>${d}</li>`; });
      html += '</ul>';
    }
    if (data.points_cles && data.points_cles.length > 0) {
      html += '<ul class="ai-meta-list" style="margin-top:6px;">';
      data.points_cles.forEach(p => { html += `<li>${p}</li>`; });
      html += '</ul>';
    }

    containerEl.innerHTML = html;
  } catch (e) {
    showAiError(containerEl, e.message);
  }
}
</script>
