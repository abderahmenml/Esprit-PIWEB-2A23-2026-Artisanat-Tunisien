# Exploration du Workspace - Gestion des Fichiers PDF et CV Builder

## 📋 Résumé Exécutif

Le workspace **HERFA** (plateforme de profils professionnels pour artisans) contient une fonctionnalité d'upload de fichiers PDF, **MAIS elle ne permet pas l'import automatique d'un PDF pour pré-remplir le formulaire CV**. L'upload de PDF est limité à la gestion d'un portfolio de réalisations.

---

## 1. ❌ Fonctionnalité d'Import de PDF dans le CV-Builder

**ABSENCE CONFIRMÉE** - Il n'existe **AUCUNE** fonctionnalité d'import de PDF pour auto-compléter le CV.

### Fichiers du CV-Builder:
- [public/js/cv-builder.js](public/js/cv-builder.js) - Éditeur de CV JavaScript
- [public/cv-intelligent/index.html](public/cv-intelligent/index.html) - Interface web du wizard
- [public/cv-intelligent/script.js](public/cv-intelligent/script.js) - Logique du wizard CV
- [public/css/cv-studio.css](public/css/cv-studio.css) - Styles du studio CV
- [public/css/cv-print.css](public/css/cv-print.css) - Styles d'impression
- [views/profil/index.php](views/profil/index.php) - Vue intégrée du profil

### Fonctionnalités actuelles du CV-Builder:
- ✅ Édition manuelle des infos personnelles
- ✅ Gestion des compétences (ajout/suppression)
- ✅ Gestion des expériences
- ✅ Gestion de l'éducation
- ✅ Génération via IA (Ollama) pour optimiser le contenu
- ✅ Export PDF du CV généré (via html2pdf.js)
- ❌ Import/lecture d'un PDF existant
- ❌ Extraction de texte depuis un PDF

---

## 2. 📂 Gestion des Uploads de Fichiers

### Fichiers Impliqués:

#### A. Contrôleur Principal: [controllers/ProfilController.php](controllers/ProfilController.php#L2530)

**Méthode: `addPortfolioFile()`** (ligne ~2530)

```php
public function addPortfolioFile()
{
    // Validation utilisateur
    $user_id = $this->requireAuth();
    
    // Validation du fichier
    if (!isset($_FILES['portfolio_file']) || $_FILES['portfolio_file']['error'] !== UPLOAD_ERR_OK) {
        $this->flash('error', 'Fichier invalide.');
        $this->redirect('/profil');
    }
    
    // Extracteurs du formulaire
    $titre = $_POST['titre'] ?? '';
    $realisationDefault = $_POST['realisation_default'] ?? '';
    $realisationCustom = $_POST['realisation_custom'] ?? '';
    
    // Validations
    $originalName = $_FILES['portfolio_file']['name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    
    // Vérifications strictes
    if ((int)($_FILES['portfolio_file']['size'] ?? 0) > 8 * 1024 * 1024) {
        $this->flash('error', 'Le fichier dépasse 8 Mo.');
    }
    if ($ext !== 'pdf') {
        $this->flash('error', 'Seuls les fichiers PDF sont autorisés.');
    }
    
    // Stockage
    $uploadDir = __DIR__ . '/../public/uploads/portfolio';
    $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
    $storedName = $baseName . '_' . time() . '_' . mt_rand(1000, 9999) . '.pdf';
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $storedName;
    
    if (!move_uploaded_file($_FILES['portfolio_file']['tmp_name'], $targetPath)) {
        $this->flash('error', 'Échec du téléversement.');
    }
    
    // Insertion en base de données
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
}
```

**Validations Strictes:**
- ✅ Extension: `.pdf` uniquement
- ✅ Taille maximale: 8 MB
- ✅ Authentification: Utilisateur doit être connecté
- ✅ Champs requis: `realisation`, `portfolio_file`

---

#### B. Modèle de Données: [models/ProfilModel.php](models/ProfilModel.php#L600)

**Méthode: `getPortfolioFiles()`** (ligne ~600)

```php
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

**Table Structure:**
```sql
portfolio_files (
    id_portfolio_file INT PRIMARY KEY,
    id_user INT,
    titre VARCHAR(255),
    realisation VARCHAR(255),
    file_name VARCHAR(255),
    file_path VARCHAR(255),
    created_at TIMESTAMP
)
```

---

#### C. Vue Formulaire: [views/profil/index.php](views/profil/index.php#L586)

**Section: "Documents (PDF)"** (ligne 586-650)

```html
<form action="<?= app_url('/profil/addPortfolioFile') ?>" method="post" enctype="multipart/form-data">
    <!-- Titre optionnel -->
    <input type="text" name="titre" maxlength="120" placeholder="ex : Catalogue 2026">
    
    <!-- Réalisation (select ou custom) -->
    <select name="realisation_default" required>
        <option value="">Choisir une réalisation</option>
        <option value="Vase Amazigh">Vase Amazigh</option>
        <option value="Service a Tajine">Service a Tajine</option>
        <option value="Carreaux Zellige">Carreaux Zellige</option>
        <option value="Fontaine en ceramique">Fontaine en ceramique</option>
        <option value="Collection Printemps">Collection Printemps</option>
        <option value="Motifs Islamiques">Motifs Islamiques</option>
        <option value="__custom__">Autre (nouvelle réalisation)</option>
    </select>
    <input type="text" name="realisation_custom" maxlength="120" 
           placeholder="Saisissez une nouvelle réalisation" style="display:none;">
    
    <!-- Upload du PDF -->
    <input type="file" name="portfolio_file" accept=".pdf" required>
    
    <button type="submit">+ Ajouter</button>
</form>
```

**Réalisations par défaut** (ligne ~408 dans ProfilController):
- Vase Amazigh
- Service a Tajine
- Carreaux Zellige
- Fontaine en ceramique
- Collection Printemps
- Motifs Islamiques

---

## 3. 🔗 Endpoint PHP pour les Uploads

### Route Principale
```
POST /profil/addPortfolioFile
```

**Accès:** [controllers/ProfilController.php](controllers/ProfilController.php#L2530)

**Paramètres POST:**
| Paramètre | Type | Requis | Validation |
|-----------|------|--------|-----------|
| `titre` | string | Non | Max 120 caractères |
| `realisation_default` | string | Oui | Valeur pré-définie ou '__custom__' |
| `realisation_custom` | string | Si custom | Max 120 caractères |
| `portfolio_file` | file | Oui | PDF uniquement, max 8 MB |

**Réponses:**
- ✅ Success: Flash message "Document ajouté" + Redirect to `/profil`
- ❌ Error: Flash message + Redirect to `/profil`

**Erreurs Possibles:**
1. "Fichier invalide" - Pas de fichier ou erreur d'upload
2. "Titre invalide" - Caractères de contrôle détectés
3. "Veuillez choisir une réalisation" - Champ vide
4. "Réalisation invalide" - Valeur non autorisée
5. "La réalisation est invalide" - Hors limites length/contrôle
6. "Le fichier dépasse 8 Mo" - Taille excédentaire
7. "Seuls les fichiers PDF sont autorisés" - Format non PDF
8. "Échec du téléversement" - Erreur move_uploaded_file
9. "Erreur lors de l'enregistrement" - Erreur BDD

---

## 4. 📁 Structure du CV-Builder

### 4.1 Fichiers HTML/CSS/JS

```
public/
├── cv-intelligent/
│   ├── index.html          # Interface wizard 4 étapes
│   ├── script.js           # Logique du wizard
│   └── style.css           # Styles du wizard
├── js/
│   ├── cv-builder.js       # Éditeur CV principal (500+ lignes)
│   ├── index.js            # JS global du profil
│   └── bio-handler.js      # Gestion de la bio
├── css/
│   ├── cv-studio.css       # Styles du studio CV
│   ├── cv-print.css        # Styles d'impression PDF
│   └── style.css           # Styles généraux
└── uploads/
    └── portfolio/          # Dossier de stockage des PDFs
```

### 4.2 Vue Profil Intégrée

[views/profil/index.php](views/profil/index.php) - Page principale (1000+ lignes)

**Sections:**
1. **CV AI Studio** - Éditeur avec preview en temps réel
2. **Documents (PDF)** - Gestion du portfolio
3. **Compétences** - Gestion des skills
4. **Expériences** - Parcours professionnel
5. **Avis** - Reviews des clients

### 4.3 Génération PDF

**Méthode d'export:** html2pdf.js

```javascript
// Dans cv-builder.js (~ligne 380)
if (window.html2pdf && typeof window.html2pdf === 'function') {
    var pdfWorker = window.html2pdf().set({
        margin: [6, 6, 6, 6],
        filename: fileName,
        image: { type: 'jpeg', quality: 0.95 },
        html2canvas: { scale: 1.6, useCORS: true },
        jsPDF: { 
            unit: 'mm',
            format: 'a4',
            orientation: 'portrait',
            compress: true
        }
    }).from(element).save();
}
```

---

## 5. 🎯 Cas d'Usage Actuel

### Utilisateur Standard:

1. **Accède à** `/profil`
2. **Onglet "Documents"** - Voit le formulaire d'upload
3. **Remplit:**
   - Titre optionnel (ex: "Catalogue 2026")
   - Choisit une réalisation
   - Upload un PDF (max 8 MB)
4. **PDF est stocké** dans `public/uploads/portfolio/`
5. **Enregistrement BD** dans table `portfolio_files`
6. **Affichage** dans tableau récapitulatif

### Résultats de Recherche & Affichage:

Les PDFs uploadés sont:
- ✅ Listés dans un tableau avec: titre, réalisation, date, lien de visionnage
- ✅ Liés au profil utilisateur
- ✅ Accessibles publiquement via URL directe
- ❌ Non analysés automatiquement
- ❌ Non indexés pour la recherche de texte

---

## 6. 🔍 Analyse des Fichiers Clés

### cv-builder.js (500+ lignes)

**Fonctionnalités:**
- Parse des données seed depuis JSON (ligne ~50)
- Rendu du preview CV (ligne ~80-150)
- Gestion d'événements (ajout/suppression skills, exp, edu)
- Export PDF via html2pdf
- Appels Ollama pour génération/optimisation IA

**Pas de:**
- Lecture de fichiers PDF
- Extraction OCR
- Parsing de structure CV

---

### cv-intelligent/script.js (400+ lignes)

**Wizard 4 étapes:**
1. Sélection métier cible
2. Top 3 skills
3. Expérience textuelle
4. Niveau d'éducation

**Résultats générés:**
- CV Title (métier ciblé)
- Objective (basé sur métier)
- Suggested Skills
- Relevant Interests
- Job Matching Score

**Pas de:**
- Import PDF
- Extraction d'informations

---

## 7. 📊 Tableau de Synthèse

| Aspect | Existant | Détails |
|--------|----------|---------|
| **Upload PDF** | ✅ OUI | Portfolio de réalisations uniquement |
| **Import pour CV** | ❌ NON | Pas de parseur PDF |
| **Édition manuelle CV** | ✅ OUI | Fields texte + IA |
| **Export PDF CV** | ✅ OUI | Via html2pdf.js |
| **Wizard d'orientation** | ✅ OUI | 4 étapes métier |
| **IA Ollama** | ✅ OUI | Génération + optimisation |
| **Gestion Portfolio** | ✅ OUI | Réalisations + documents |
| **BD Portfolio** | ✅ OUI | Table `portfolio_files` |

---

## 8. 🚀 Recommandations pour Ajouter l'Import PDF

Si vous voulez **IMPLÉMENTER** une fonctionnalité d'import CV depuis PDF:

### Option 1: PDFBox JavaScript (Client-side)
```javascript
// Nécessite: pdfjs-dist library
const pdf = await pdfjsLib.getDocument(fileBlob);
const page = await pdf.getPage(1);
const text = await page.getTextContent();
```
**Pros:** Pas de serveur  
**Cons:** Extraction textile only, pas de structure

### Option 2: Backend PHP avec pdftotext
```php
$text = shell_exec('pdftotext ' . escapeshellarg($path) . ' -');
// Parser $text avec regex pour extraire sections
```
**Pros:** Plus précis  
**Cons:** Dépendance système

### Option 3: API Tierce (Parsing Professionnel)
- Dataleon / Adobe API
- CloudConvert
- Rossum

**Pros:** Extraction structurée, métadonnées  
**Cons:** Coût API

---

## 📍 Chemins Clés du Projet

```
/controllers/ProfilController.php          # Route principale
/models/ProfilModel.php                    # Gestion BDD
/views/profil/index.php                    # Vue portfolio
/public/uploads/portfolio/                 # Stockage PDFs
/public/js/cv-builder.js                   # Éditeur CV
/public/cv-intelligent/                    # Wizard CV
```

---

## ✅ Conclusion

**Le workspace HERFA possède:**
- ✅ Un système robuste d'upload de PDF pour le portfolio
- ✅ Un CV builder complet avec édition manuelle et IA
- ✅ Export PDF professionnel du CV

**Mais il MANQUE:**
- ❌ Parsing et extraction de texte depuis PDF
- ❌ Import/auto-remplissage depuis CV PDF
- ❌ Reconnaissance OCR

L'upload PDF est dédié exclusivement au portfolio (réalisations/projets), et non à l'auto-complétion du profil.
