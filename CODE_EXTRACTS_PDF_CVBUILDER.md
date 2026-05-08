# Extraits de Code Pertinents - Gestion PDF & CV-Builder

## 1. Méthode d'Upload PDF - ProfilController.php (Ligne ~2530)

```php
public function addPortfolioFile()
{
    $user_id = $this->requireAuth();

    if (!isset($_FILES['portfolio_file']) || $_FILES['portfolio_file']['error'] !== UPLOAD_ERR_OK) {
        $this->flash('error', 'Fichier invalide.');
        $this->redirect('/profil');
    }

    $titre = $this->normalizeText($_POST['titre'] ?? '', 120);
    if ($titre !== '' && $this->hasControlChars($titre)) {
        $this->flash('error', 'Titre invalide.');
        $this->redirect('/profil');
    }

    $defaultRealisations = $this->getDefaultPortfolioRealisations();
    $realisationDefault = trim((string)($_POST['realisation_default'] ?? ''));
    $realisationCustom = $this->normalizeText($_POST['realisation_custom'] ?? '', 120);

    if ($realisationDefault === '') {
        $this->flash('error', 'Veuillez choisir une realisation.');
        $this->redirect('/profil');
    }

    if ($realisationDefault !== '__custom__' && !in_array($realisationDefault, $defaultRealisations, true)) {
        $this->flash('error', 'Realisation invalide.');
        $this->redirect('/profil');
    }

    $realisation = $realisationDefault === '__custom__'
        ? $realisationCustom
        : $this->normalizeText($realisationDefault, 120);

    if (!$this->isWithinLength($realisation, 2, 120) || $this->hasControlChars($realisation)) {
        $this->flash('error', 'La realisation est invalide.');
        $this->redirect('/profil');
    }

    // Vérifier la colonne 'realisation' dans la BD
    try {
        $pdo = getPDO();
        $colStmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'portfolio_files' 
            AND COLUMN_NAME = 'realisation'");
        $colStmt->execute();
        $hasRealisationColumn = (int)$colStmt->fetchColumn() > 0;

        if (!$hasRealisationColumn) {
            $this->flash('error', 'Colonne realisation manquante dans portfolio_files.');
            $this->redirect('/profil');
        }
    } catch (Exception $e) {
        $this->flash('error', 'Erreur de verification de la colonne realisation.');
        $this->redirect('/profil');
    }

    // Validation du fichier
    $originalName = $_FILES['portfolio_file']['name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if ((int)($_FILES['portfolio_file']['size'] ?? 0) > 8 * 1024 * 1024) {
        $this->flash('error', 'Le fichier depasse 8 Mo.');
        $this->redirect('/profil');
    }

    if ($ext !== 'pdf') {
        $this->flash('error', 'Seuls les fichiers PDF sont autorises.');
        $this->redirect('/profil');
    }

    // Créer le répertoire d'upload s'il n'existe pas
    $uploadDir = __DIR__ . '/../public/uploads/portfolio';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Générer un nom de fichier sécurisé
    $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
    if ($baseName === '') {
        $baseName = 'document';
    }

    $storedName = $baseName . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $storedName;

    // Déplacer le fichier uploadé
    if (!move_uploaded_file($_FILES['portfolio_file']['tmp_name'], $targetPath)) {
        $this->flash('error', 'Echec du televersement.');
        $this->redirect('/profil');
    }

    // Insérer en base de données
    try {
        $stmt = $pdo->prepare('INSERT INTO portfolio_files 
            (id_user, titre, realisation, file_name, file_path, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())');
        $stmt->execute([
            $user_id,
            $titre === '' ? null : $titre,
            $realisation,
            $originalName,
            app_url('/public/uploads/portfolio/' . $storedName)
        ]);

        $this->refreshInsightCache($user_id);
        $this->flash('success', 'Document ajoute.');
    } catch (Exception $e) {
        $this->flash('error', 'Erreur lors de l\'enregistrement.');
    }

    $this->redirect('/profil');
}
```

---

## 2. Méthode de Récupération Portfolio - ProfilModel.php (Ligne ~600)

```php
/**
 * PORTFOLIO FILES
 */
public function getPortfolioFiles(int $userId): array
{
    if (!$this->hasTable('portfolio_files')) {
        return [];
    }

    $stmt = $this->pdo->prepare("
        SELECT id_portfolio_file, titre, realisation, file_name, file_path, created_at
        FROM portfolio_files
        WHERE id_user = ?
        ORDER BY created_at DESC, id_portfolio_file DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
```

---

## 3. Réalisations par Défaut - ProfilController.php (Ligne ~408)

```php
private function getDefaultPortfolioRealisations(): array
{
    return [
        'Vase Amazigh',
        'Service a Tajine',
        'Carreaux Zellige',
        'Fontaine en ceramique',
        'Collection Printemps',
        'Motifs Islamiques'
    ];
}
```

---

## 4. Formulaire d'Upload - views/profil/index.php (Ligne 586-650)

```html
<div class="card" style="margin-top:1.5rem;">
    <div class="section-title">Documents (PDF)</div>
    
    <form action="<?= htmlspecialchars(app_url('/profil/addPortfolioFile')) ?>" 
          method="post" 
          enctype="multipart/form-data" 
          style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;background:#f8f5f0;padding:1.2rem 1rem;border-radius:1rem;margin-bottom:1rem;">
        
        <!-- Titre optionnel -->
        <div style="flex:2;">
            <label style="font-weight:600;color:var(--marron);">Titre (optionnel)</label>
            <input type="text" 
                   name="titre" 
                   maxlength="120" 
                   placeholder="ex : Catalogue 2026" 
                   style="width:100%;padding:.5rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
        </div>
        
        <!-- Réalisation -->
        <div style="flex:2;min-width:240px;">
            <label style="font-weight:600;color:var(--marron);">Realisation</label>
            <select name="realisation_default" 
                    data-realisation-select 
                    required 
                    style="width:100%;padding:.5rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
                <option value="">Choisir une realisation</option>
                <?php foreach (($defaultRealisations ?? []) as $realisationOption): ?>
                    <option value="<?= htmlspecialchars((string)$realisationOption) ?>">
                        <?= htmlspecialchars((string)$realisationOption) ?>
                    </option>
                <?php endforeach; ?>
                <option value="__custom__">Autre (nouvelle realisation)</option>
            </select>
            <input type="text" 
                   name="realisation_custom" 
                   maxlength="120" 
                   data-realisation-custom 
                   placeholder="Saisissez une nouvelle realisation" 
                   style="display:none;width:100%;padding:.5rem;margin-top:.5rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
        </div>
        
        <!-- Fichier PDF -->
        <div style="flex:2;">
            <label style="font-weight:600;color:var(--marron);">Fichier PDF</label>
            <input type="file" 
                   name="portfolio_file" 
                   accept=".pdf" 
                   required 
                   style="width:100%;padding:.45rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
        </div>
        
        <!-- Bouton Submit -->
        <button type="submit" 
                style="background:var(--marron);color:#fff;padding:.7rem 1.5rem;border:none;border-radius:.5rem;font-weight:600;font-size:1rem;box-shadow:0 2px 8px #8b5a3a22;transition:.2s;">
            + Ajouter
        </button>
    </form>
    
    <!-- Tableau des documents -->
    <table style="width:100%;border-collapse:separate;border-spacing:0 .75rem;">
        <thead>
            <tr style="color:var(--marron);font-size:.95rem;background:#f8f5f0;">
                <th>TITRE</th><th>REALISATION</th><th>FICHIER</th><th>DATE</th>
            </tr>
        </thead>
        <tbody id="portfolio-docs-list">
            <?php if (!empty($portfolioFiles)): ?>
                <?php foreach ($portfolioFiles as $index => $file): ?>
                    <tr data-id="<?= (int)$index + 1 ?>" 
                        data-created-at="<?= htmlspecialchars((string)($file['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <td class="doc-title">
                            <?= htmlspecialchars($file['titre'] ?: $file['file_name']) ?>
                        </td>
                        <td class="doc-realisation">
                            <?= htmlspecialchars((string)($file['realisation'] ?? '-')) ?>
                        </td>
                        <td class="doc-file">
                            <a href="<?= htmlspecialchars($file['file_path']) ?>" 
                               target="_blank" 
                               style="color:var(--marron);text-decoration:none;">
                                Voir
                            </a>
                        </td>
                        <td class="doc-date">
                            <?= htmlspecialchars(date('d/m/Y', strtotime($file['created_at'] ?? 'now'))) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align:center; padding:1rem;">Aucun fichier ajouté</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
```

---

## 5. Partie CV-Builder - views/profil/index.php (Section CV Studio)

```html
<div class="card cv-section">
    <div class="card-header">
        <h2>Générateur de CV Intelligent</h2>
        <span class="cv-ia-badge" title="PDF Export">📄 PDF</span>
    </div>
    
    <p class="cv-ia-subtitle">
        Générateur de CV intelligent alimenté par une IA locale. 
        Personnalisez vos informations, générez avec IA, optimisez et téléchargez en PDF professionnel.
    </p>
    
    <div class="cv-ia-actions-row">
        <button type="button" class="cv-ia-btn cv-ia-btn-generate" id="cv-ia-generate">
            ✨ Générer avec IA
        </button>
        <button type="button" class="cv-ia-btn cv-ia-btn-optimize" id="cv-ia-optimize">
            🔧 Optimiser
        </button>
        <button type="button" class="cv-ia-btn cv-ia-btn-download" id="cv-ia-download">
            📥 Télécharger (PDF)
        </button>
    </div>
    
    <!-- Le reste de l'éditeur CV... -->
</div>
```

---

## 6. Export PDF via html2pdf - cv-builder.js (Ligne ~380)

```javascript
if (downloadBtn) {
    downloadBtn.addEventListener('click', function () {
        var element = byId('cv-preview');
        if (!element) {
            notify('CV non trouvé', 'error');
            return;
        }

        var fileName = ((state.personal && state.personal.fullName) || 'cv').trim()
            .replace(/\s+/g, '_') + '_' + new Date().toISOString().split('T')[0] + '.pdf';
        
        // Try html2pdf if available
        if (window.html2pdf && typeof window.html2pdf === 'function') {
            try {
                notify('Génération du PDF en cours...', 'info');
                document.body.classList.add('cv-pdf-export');
                
                var pdfWorker = window.html2pdf().set({
                    margin: [6, 6, 6, 6],
                    filename: fileName,
                    image: { 
                        type: 'jpeg', 
                        quality: 0.95 
                    },
                    html2canvas: { 
                        scale: 1.6,
                        useCORS: true,
                        allowTaint: true,
                        backgroundColor: '#FFFFFF',
                        letterRendering: true,
                        logging: false
                    },
                    jsPDF: { 
                        unit: 'mm',
                        format: 'a4',
                        orientation: 'portrait',
                        compress: true
                    }
                }).from(element).save();

                if (pdfWorker && typeof pdfWorker.then === 'function') {
                    pdfWorker.then(function () {
                        document.body.classList.remove('cv-pdf-export');
                    }).catch(function () {
                        document.body.classList.remove('cv-pdf-export');
                    });
                } else {
                    setTimeout(function () {
                        document.body.classList.remove('cv-pdf-export');
                    }, 2000);
                }
                
                notify('PDF téléchargé: ' + fileName, 'success');
                return;
            } catch (err) {
                document.body.classList.remove('cv-pdf-export');
                notify('Erreur PDF html2pdf: ' + err.message, 'error');
                console.error('html2pdf error:', err);
            }
        }

        // Fallback: Browser Print to PDF
        notify('html2pdf non disponible, utilisation de l\'impression', 'info');
        document.body.classList.add('cv-pdf-export');
        window.focus();
        window.print();
        setTimeout(function () {
            document.body.classList.remove('cv-pdf-export');
        }, 1000);
    });
}
```

---

## 7. Structure de données du CV-Builder State (cv-builder.js, Ligne ~200)

```javascript
const state = {
    personal: seed.personal || { 
        fullName: '', 
        title: '', 
        email: '', 
        phone: '', 
        city: '', 
        summary: '' 
    },
    skills: safeArray(seed.skills),
    experiences: safeArray(seed.experiences),
    education: safeArray(seed.education),
    qualities: safeArray(seed.qualities || []),
    scores: seed.scores || { 
        ats: 60, 
        impact: 55, 
        readability: 75 
    },
    recommendations: safeArray(seed.recommendations || seed.tips || []),
    aiAdvice: safeArray(seed.aiAdvice || seed.advice || seed.tips || seed.recommendations || []),
    slogan: seed.slogan || '',
    language: seed.language || 'fr',
    model: seed.model || 'mistral',
    template: seed.template || 'moderne',
    primaryColor: seed.primaryColor || '#2E6B3E'
};
```

---

## 8. Schema JSON du Seed Data - views/profil/index.php (Ligne ~12-48)

```php
$cvSeedData = [
    'personal' => [
        'fullName' => trim((string)(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''))),
        'title' => (string)($specialite ?? ''),
        'email' => (string)($email ?? ''),
        'phone' => (string)($telephone ?? ''),
        'city' => (string)($ville ?? ''),
        'summary' => (string)($cvSummary ?? '')
    ],
    'skills' => array_map(static function (array $item): array {
        return [
            'name' => (string)($item['nom_competence'] ?? ''),
            'level' => (int)($item['niveau'] ?? 50)
        ];
    }, (array)($competences ?? [])),
    'experiences' => array_map(static function (array $item): array {
        return [
            'role' => (string)($item['poste'] ?? ''),
            'company' => (string)($item['entreprise'] ?? ''),
            'start' => (string)($item['date_debut'] ?? ''),
            'end' => (string)($item['date_fin'] ?? ''),
            'description' => (string)($item['description'] ?? '')
        ];
    }, (array)($experiences ?? [])),
    'education' => array_map(static function (array $item): array {
        return [
            'degree' => (string)($item['nom_certification'] ?? 'Certification'),
            'school' => 'Certification professionnelle',
            'start' => '',
            'end' => '',
            'description' => 'Niveau: ' . (int)($item['niveau'] ?? 0) . '%'
        ];
    }, (array)($certifications ?? [])),
    'scores' => [
        'ats' => (int)($profileScore ?? 0),
        'impact' => (int)($popularityScore ?? 0),
        'readability' => 78
    ],
    'tips' => [],
    'language' => 'fr',
    'template' => 'moderne',
    'primaryColor' => '#2E6B3E'
];
```

---

## 9. Appel Ollama pour Génération CV - cv-builder.js (Ligne ~260)

```javascript
function callOllamaGenerate(state, action) {
    var status = byId('cv-ia-status');
    if (!status) {
        return;
    }

    status.textContent = 'Connexion à Ollama...';
    status.className = 'cv-status loading';

    collectInfos(state);

    var formData = new FormData();
    formData.append('prompt', '');
    formData.append('language', state.language || 'fr');
    formData.append('model', state.model || 'mistral');
    if (action === 'optimize') {
        formData.append('cv', JSON.stringify(state));
    }

    var endpoint = action === 'optimize' ? '/profil/optimizeCvAi' : '/profil/generateCvAi';

    fetch(getAppUrl(endpoint), {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(function (resp) {
            if (resp.status === 401) {
                notify('Veuillez vous connecter', 'error');
                window.location.href = getAppUrl('/auth/login');
                return Promise.reject('Unauthorized');
            }
            if (!resp.ok) {
                throw new Error('HTTP ' + resp.status);
            }
            return resp.json();
        })
        .then(function (result) {
            if (result.success && result.data) {
                Object.assign(state, result.data);
                if (!Array.isArray(state.aiAdvice) || !state.aiAdvice.length) {
                    state.aiAdvice = safeArray(state.recommendations || state.tips || []);
                }
                refresh(state);

                status.textContent = '✅ ' + result.message + ' (' + (result.provider || 'local') + ')';
                status.className = 'cv-status success';
                notify(result.message, 'success');

                setTimeout(function () {
                    status.textContent = '';
                    status.className = '';
                }, 4000);
            } else {
                throw new Error(result.message || 'Réponse invalide');
            }
        })
        .catch(function (err) {
            console.error('Error:', err);
            status.textContent = '❌ Erreur: ' + (err.message || 'Ollama indisponible');
            status.className = 'cv-status error';
            notify('Erreur Ollama: ' + (err.message || 'Vérifiez que Ollama est lancé sur localhost:11434'), 'error');

            setTimeout(function () {
                status.textContent = '';
                status.className = '';
            }, 5000);
        });
}
```

---

## 10. Base de Données - Structure de portfolio_files

```sql
CREATE TABLE portfolio_files (
    id_portfolio_file INT PRIMARY KEY AUTO_INCREMENT,
    id_user INT NOT NULL,
    titre VARCHAR(255),
    realisation VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES user(id_user) ON DELETE CASCADE
);
```

**Colonnes:**
- `id_portfolio_file` - Identifiant unique
- `id_user` - Référence utilisateur
- `titre` - Titre optionnel du document
- `realisation` - Type de réalisation (Vase Amazigh, etc.)
- `file_name` - Nom original du fichier uploadé
- `file_path` - Chemin de stockage (accessible via HTTP)
- `created_at` - Date d'ajout du document
