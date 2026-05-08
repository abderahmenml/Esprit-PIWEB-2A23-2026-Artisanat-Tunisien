# Architecture et Chemins Fichiers - Système de Gestion PDF/CV

## 📊 Flux de Traitement des Uploads PDF

```
┌─────────────────────────────────────────────────────────────────┐
│                    UTILISATEUR                                  │
│          Accède à /profil → Onglet "Documents"                 │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│            FORMULAIRE HTML (views/profil/index.php)             │
│  ┌────────────────────────────────────────────────────────┐    │
│  │ Input: Titre (optionnel)                               │    │
│  │ Select: Réalisation (défaut ou custom)                 │    │
│  │ File: Portfolio PDF (accept=".pdf", max 8MB)          │    │
│  │ Button: Soumettre → POST /profil/addPortfolioFile     │    │
│  └────────────────────────────────────────────────────────┘    │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│     CONTROLLER (controllers/ProfilController.php)               │
│     Méthode: addPortfolioFile() [Ligne ~2530]                  │
│  ┌────────────────────────────────────────────────────────┐    │
│  │ 1. Vérifier Auth                                       │    │
│  │ 2. Valider titre (max 120 car, pas ctrl chars)        │    │
│  │ 3. Valider réalisation (liste pré-définie ou custom)  │    │
│  │ 4. Valider fichier:                                    │    │
│  │    - Extension: .pdf uniquement                        │    │
│  │    - Taille: ≤ 8 MB                                    │    │
│  │ 5. Générer nom sécurisé: base_timestamp_random.pdf    │    │
│  │ 6. Déplacer dans /public/uploads/portfolio/           │    │
│  │ 7. Insérer enregistrement en BD                        │    │
│  └────────────────────────────────────────────────────────┘    │
└────────────────────────────┬────────────────────────────────────┘
                             │
                    ┌────────┴────────┐
                    ▼                 ▼
           ✅ Success          ❌ Error
           Flash message       Flash error
           Redirect /profil    Redirect /profil
                │                    │
                └────────┬───────────┘
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│           DATABASE (portfolio_files)                            │
│  ┌────────────────────────────────────────────────────────┐    │
│  │ INSERT INTO portfolio_files                            │    │
│  │ (id_user, titre, realisation, file_name,              │    │
│  │  file_path, created_at) VALUES (...)                  │    │
│  └────────────────────────────────────────────────────────┘    │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│           FICHIER STOCKÉ ET INDEXÉ                              │
│  ┌────────────────────────────────────────────────────────┐    │
│  │ Chemin: /public/uploads/portfolio/document_*.pdf      │    │
│  │ URL: app_url('/public/uploads/portfolio/...')         │    │
│  │ BDD: portfolio_files.file_path                        │    │
│  │ Affichage: Tableau dans la vue profil                 │    │
│  └────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🗂️ Structure Complète des Fichiers

```
c:\xampp\htdocs\version\vie\
├── 📄 index.php                          # Point d'entrée
├── 📄 diagnostic_ollama.php             # Diagnostic service Ollama
│
├── 📁 config/
│   └── 📄 config.php                     # Configuration générale
│
├── 📁 controllers/                       # **UPLOAD LOGIC ICI**
│   ├── 📄 ProfilController.php          # ⭐ addPortfolioFile() [Ligne 2530]
│   ├── 📄 AuthController.php
│   ├── 📄 DashboardController.php
│   └── 📄 AdminController.php
│
├── 📁 models/
│   ├── 📄 ProfilModel.php               # ⭐ getPortfolioFiles() [Ligne 600]
│   ├── 📄 functions.php                 # Fonctions globales (legacy)
│   └── 📄 DashboardModel.php
│
├── 📁 api/
│   └── 📄 chatbot.php                    # Endpoint chatbot Ollama
│
├── 📁 views/
│   ├── 📁 auth/
│   │   ├── 📄 login.php
│   │   └── 📄 register.php
│   │
│   ├── 📁 profil/
│   │   ├── 📄 index.php                 # ⭐ FORMULAIRE UPLOAD [Ligne 586-650]
│   │   ├── 📄 gestion_competences.php
│   │   └── 📄 gestion_certifications.php
│   │
│   ├── 📁 admin/
│   │   ├── 📄 index.php
│   │   └── 📄 competences.php
│   │
│   └── 📁 dashboard/
│       ├── 📄 index.php
│       └── 📄 annuaire.php
│
├── 📁 public/
│   ├── 📁 css/
│   │   ├── 📄 style.css                 # Styles généraux
│   │   ├── 📄 cv-studio.css             # Styles CV editor
│   │   └── 📄 cv-print.css              # Styles impression PDF
│   │
│   ├── 📁 js/
│   │   ├── 📄 cv-builder.js             # ⭐ EDITEUR CV [500+ lignes]
│   │   ├── 📄 index.js                  # JS global du profil
│   │   ├── 📄 bio-handler.js            # Gestion bio
│   │   └── 📄 chatbot-widget.js         # Widget chatbot
│   │
│   ├── 📁 cv-intelligent/
│   │   ├── 📄 index.html                # Wizard CV (4 étapes)
│   │   ├── 📄 script.js                 # Logique wizard
│   │   └── 📄 style.css                 # Styles wizard
│   │
│   └── 📁 uploads/
│       └── 📁 portfolio/                # ⭐ STOCKAGE PDFS
│           ├── 📄 document_1702345678_1234.pdf
│           ├── 📄 vase_1702345679_5678.pdf
│           └── 📄 ...
│
├── 📁 logs/
│   └── [fichiers journaux]
│
├── 📁 migrations/
│   └── 📄 001_create_chat_messages.sql
│
├── 📄 EXPLORATION_PDF_IMPORT.md         # 📖 Cette synthèse
├── 📄 CODE_EXTRACTS_PDF_CVBUILDER.md    # 📖 Extraits de code
└── 📄 ARCHITECTURE.md                   # 📖 Ce fichier
```

---

## 🔄 Flux de Données - CV-Builder

```
┌────────────────────────────────────────┐
│   DONNÉES STOCKÉES EN BASE             │
│   ┌──────────────────────────────┐    │
│   │ user                         │    │
│   │ profil_professionnel         │    │
│   │ competences                  │    │
│   │ certification                │    │
│   │ experience                   │    │
│   │ portfolio_files              │    │
│   └──────────────────────────────┘    │
└────────────────────┬───────────────────┘
                     │ ProfilController
                     │ (buildCvSeedData)
                     ▼
┌────────────────────────────────────────┐
│   JSON SEED DATA                       │
│   (views/profil/index.php, Ligne 82)   │
│   ┌──────────────────────────────┐    │
│   │ <script type="application/json"   │
│   │  id="cv-ai-seed">             │    │
│   │  {                             │    │
│   │    "personal": {...},          │    │
│   │    "skills": [...],            │    │
│   │    "experiences": [...],       │    │
│   │    "education": [...],         │    │
│   │    "scores": {...},            │    │
│   │    "template": "moderne",      │    │
│   │    "primaryColor": "#2E6B3E"   │    │
│   │  }                             │    │
│   │ </script>                      │    │
│   └──────────────────────────────┘    │
└────────────────────┬───────────────────┘
                     │ JavaScript
                     │ cv-builder.js (parseSeed)
                     ▼
┌────────────────────────────────────────┐
│   STATE OBJECT (JavaScript)            │
│   ┌──────────────────────────────┐    │
│   │ const state = {               │    │
│   │   personal: {...},            │    │
│   │   skills: [...],              │    │
│   │   experiences: [...],         │    │
│   │   education: [...],           │    │
│   │   template: 'moderne',        │    │
│   │   language: 'fr',             │    │
│   │   model: 'mistral'            │    │
│   │ }                             │    │
│   └──────────────────────────────┘    │
└────────────────────┬───────────────────┘
                     │ Input events
                     │ collectInfos()
                     ▼
┌────────────────────────────────────────┐
│   PREVIEW RENDU EN TEMPS RÉEL          │
│   ┌──────────────────────────────┐    │
│   │ <div id="cv-preview">         │    │
│   │   <div class="cv-header">    │    │
│   │   <div class="cv-section">   │    │
│   │   ...                         │    │
│   │ </div>                        │    │
│   └──────────────────────────────┘    │
└────────────────────┬───────────────────┘
                     │
            ┌────────┴─────────┐
            │                  │
      Export PDF      Generation IA
            │                  │
            ▼                  ▼
       html2pdf()      POST /profil/
                       generateCvAi
            │                  │
            ▼                  ▼
       CV PDF file      Ollama API
       (local)          (localhost:11434)
```

---

## 📦 Stack Technique

### Backend
| Composant | Version | Usage |
|-----------|---------|-------|
| **PHP** | 7.4+ | Controllers, Models, Upload handling |
| **PDO** | Native | Database abstraction |
| **MySQL** | 5.7+ | Stockage données |
| **cURL** | Native | Appels Ollama API |
| **Ollama** | Latest | Génération IA CV |

### Frontend
| Composant | Usage |
|-----------|-------|
| **Vanilla JavaScript** | Pas de framework |
| **html2pdf.js** | Export PDF du CV |
| **CSS Grid/Flexbox** | Layout responsive |
| **LocalStorage** | Persistance wizard CV intelligent |

### Base de Données
```sql
-- Tableau principal pour les uploads
CREATE TABLE portfolio_files (
    id_portfolio_file INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    titre VARCHAR(255),
    realisation VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES user(id_user) ON DELETE CASCADE,
    INDEX idx_user (id_user),
    INDEX idx_created (created_at)
);
```

---

## 🔐 Sécurité

### Validations Implémentées (addPortfolioFile)

1. **Authentication**
   ```php
   $user_id = $this->requireAuth(); // Doit être connecté
   ```

2. **Extension**
   ```php
   if ($ext !== 'pdf') {
       $this->flash('error', 'Seuls les fichiers PDF sont autorisés.');
   }
   ```

3. **Taille**
   ```php
   if ((int)($_FILES['portfolio_file']['size'] ?? 0) > 8 * 1024 * 1024) {
       $this->flash('error', 'Le fichier dépasse 8 Mo.');
   }
   ```

4. **Nom Fichier**
   ```php
   $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
   $storedName = $baseName . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
   ```

5. **Caractères de Contrôle**
   ```php
   if ($this->hasControlChars($titre)) {
       $this->flash('error', 'Titre invalide.');
   }
   ```

6. **Énumération**
   ```php
   if (!in_array($realisationDefault, $defaultRealisations, true) 
       && $realisationDefault !== '__custom__') {
       $this->flash('error', 'Réalisation invalide.');
   }
   ```

---

## 🎯 Endpoints et Routes

### Upload PDF
```
POST /profil/addPortfolioFile
├─ Authentification: Required
├─ Paramètres: titre, realisation_default, realisation_custom, portfolio_file
├─ Validation: Extension (.pdf), Size (≤8MB), Titre, Réalisation
├─ Stockage: /public/uploads/portfolio/
└─ Response: Redirect /profil avec flash message
```

### Génération CV via IA
```
POST /profil/generateCvAi
├─ Authentification: Required
├─ Provider: Ollama (localhost:11434)
├─ Model: mistral (configurable)
└─ Response: JSON {success, message, data}
```

### Optimisation CV
```
POST /profil/optimizeCvAi
├─ Authentification: Required
├─ Payload: CV state object en JSON
├─ Traitement: Envoyer à Ollama pour optimisation
└─ Response: JSON {success, message, data}
```

---

## 📈 Cas d'Usage Actuels

### 1. Upload & Stockage Portfolio
```
Utilisateur → Remplit formulaire PDF → Envoie → Fichier stocké + BD
```
**Fichiers impliqués:**
- [views/profil/index.php](views/profil/index.php#L586) - Formulaire
- [controllers/ProfilController.php](controllers/ProfilController.php#L2530) - Traitement
- [models/ProfilModel.php](models/ProfilModel.php#L600) - Lecture

### 2. Édition CV en Temps Réel
```
Utilisateur → Remplit CV → JavaScript refresh preview
```
**Fichiers impliqués:**
- [public/js/cv-builder.js](public/js/cv-builder.js) - Éditeur
- [views/profil/index.php](views/profil/index.php#L450) - HTML structure

### 3. Export PDF CV
```
Utilisateur → Clique "Télécharger" → html2pdf() → CV.pdf
```
**Fichiers impliqués:**
- [public/js/cv-builder.js](public/js/cv-builder.js#L380) - Export logic
- html2pdf.js (CDN)

### 4. Génération IA
```
Utilisateur → Clique "Générer" → POST Ollama → Résultat intégré
```
**Fichiers impliqués:**
- [public/js/cv-builder.js](public/js/cv-builder.js#L260) - Fetch
- [controllers/ProfilController.php](controllers/ProfilController.php#L2000+) - Handler

---

## ❌ Ce qui MANQUE pour l'Import PDF

Pour ajouter une fonctionnalité d'import de CV depuis PDF, il faudrait:

### Étape 1: Upload PDF pour parsing
```php
// Nouveau endpoint: POST /profil/importCvPdf
if ($_FILES['cv_pdf']) {
    // Valider
    // Stocker temporairement
    // Parser le PDF
}
```

### Étape 2: Extraction de Texte
```php
// Option A: pdftotext system command
// Option B: PDF parsing library (pdf-parser, smalot/pdfparser)
// Option C: JavaScript client-side (pdfjs-dist)
```

### Étape 3: Parsing Structuré
```php
// Regex ou NLP pour identifier:
// - Nom, prénom, email, téléphone
// - Sections: Skills, Experiences, Education
// - Texte pour chaque section
```

### Étape 4: Populate State
```javascript
// Remplir le state JavaScript avec les données extraites
state.personal.fullName = "Extracted Name";
state.skills = [...extracted skills];
state.experiences = [...extracted exp];
state.education = [...extracted edu];
```

### Étape 5: Affichage & Édition
```
Utilisateur voit le CV pré-rempli → Peut éditer → Sauvegarder
```

**Fichiers à créer/modifier:**
- [ ] `/controllers/new CvImportController.php`
- [ ] `/views/profil/import-cv-modal.php`
- [ ] `/public/js/cv-import-handler.js`
- [ ] Bibliothèque PDF parsing (npm/composer)

---

## 📝 Résumé Fichiers Pertinents

| Fichier | Ligne | Fonction |
|---------|-------|----------|
| [ProfilController.php](controllers/ProfilController.php) | ~2530 | `addPortfolioFile()` - Upload PDF |
| [ProfilModel.php](models/ProfilModel.php) | ~600 | `getPortfolioFiles()` - Lecture BDD |
| [views/profil/index.php](views/profil/index.php) | 586-650 | Formulaire upload PDF |
| [views/profil/index.php](views/profil/index.php) | 450-500 | Section CV Studio |
| [cv-builder.js](public/js/cv-builder.js) | ~260 | `callOllamaGenerate()` |
| [cv-builder.js](public/js/cv-builder.js) | ~380 | Export PDF |
| [cv-builder.js](public/js/cv-builder.js) | ~200 | State object definition |

---

## 🔗 Chemins Clés

```
Routes:
  POST /profil/addPortfolioFile
  POST /profil/generateCvAi
  POST /profil/optimizeCvAi
  GET  /profil  (page principale)

Fichiers:
  /public/uploads/portfolio/          (Stockage PDFs)
  /controllers/ProfilController.php   (Logique backend)
  /models/ProfilModel.php             (Accès BDD)
  /views/profil/index.php             (Affichage)
  /public/js/cv-builder.js            (Logique frontend)

BDD:
  Table: portfolio_files
  Colonnes: id_portfolio_file, id_user, titre, realisation, 
            file_name, file_path, created_at
```

---

**Généré:** Exploration complète du workspace HERFA - Système de gestion PDF & CV-Builder
