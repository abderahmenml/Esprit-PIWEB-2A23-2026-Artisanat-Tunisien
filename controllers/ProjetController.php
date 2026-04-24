<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/ProjetModel.php';
require_once __DIR__ . '/../models/CategorieModel.php';

/**
 * Contrôleur Projet — Gestion des projets et de la jointure avec Catégorie
 * Hérite de Controller (méthodes render, redirect, sanitize, etc.)
 */
class ProjetController extends Controller {

    private ProjetModel    $projetModel;
    private CategorieModel $categorieModel;

    public function __construct() {
        $this->projetModel    = new ProjetModel();
        $this->categorieModel = new CategorieModel();
    }

    // ── LISTE TOUS LES PROJETS (avec jointure catégorie) ─────────────────────

    /**
     * Affiche la liste de tous les projets (avec leur catégorie via INNER JOIN)
     */
    public function index(): void {
        $this->requireAuth();
        $projets = $this->projetModel->findAll();
        $this->render('projets/index', ['projets' => $projets]);
    }

    // ── RECHERCHE PAR CATÉGORIE (jointure) ───────────────────────────────────

    /**
     * Affiche le formulaire de recherche et les projets filtrés par catégorie
     */
    public function rechercherParCategorie(): void {
        $this->requireAuth();

        $categories = $this->categorieModel->findAll();
        $projets    = [];
        $idCategorie = null;

        if ($this->isPost()
            && isset($_POST['id_categorie'], $_POST['rechercher'])
            && $_POST['id_categorie'] !== ''
        ) {
            $idCategorie = (int) $_POST['id_categorie'];
            $projets = $this->categorieModel->findProjetsByCategorie($idCategorie);
        }

        $this->render('projets/recherche', [
            'categories'  => $categories,
            'projets'     => $projets,
            'idCategorie' => $idCategorie,
        ]);
    }

    // ── DÉTAIL D'UN PROJET ────────────────────────────────────────────────────

    /**
     * Affiche le détail d'un projet
     */
    public function show(): void {
        $this->requireAuth();
        $id = (int) ($_GET['id'] ?? 0);
        $projet = $this->projetModel->findById($id);

        if (!$projet) {
            $this->redirect('index.php?page=projets');
        }

        $this->render('projets/show', ['projet' => $projet]);
    }

    // ── AJOUTER UN PROJET ─────────────────────────────────────────────────────

    /**
     * Affiche le formulaire d'ajout et traite la soumission
     */
    public function ajouter(): void {
        $this->requireAuth();

        $categories = $this->categorieModel->findAll();
        $errors     = [];
        $old        = [];

        if ($this->isPost()) {
            $old = [
                'titre'        => $this->sanitize($_POST['titre']        ?? ''),
                'description'  => $this->sanitize($_POST['description']  ?? ''),
                'budget'       => $this->sanitize($_POST['budget']        ?? ''),
                'statut'       => $_POST['statut']       ?? 'en_attente',
                'id_categorie' => (int) ($_POST['id_categorie'] ?? 0),
            ];

            // Validation manuelle (sans HTML5)
            if (empty($old['titre'])) {
                $errors['titre'] = 'Le titre est obligatoire.';
            } elseif (mb_strlen($old['titre']) < 3 || mb_strlen($old['titre']) > 150) {
                $errors['titre'] = 'Le titre doit contenir entre 3 et 150 caractères.';
            }

            if (empty($old['description'])) {
                $errors['description'] = 'La description est obligatoire.';
            } elseif (mb_strlen($old['description']) < 10) {
                $errors['description'] = 'La description doit contenir au moins 10 caractères.';
            }

            if ($old['budget'] === '' || !is_numeric($old['budget'])) {
                $errors['budget'] = 'Le budget doit être un nombre valide.';
            } elseif ((float) $old['budget'] < 0) {
                $errors['budget'] = 'Le budget ne peut pas être négatif.';
            }

            if ($old['id_categorie'] === 0) {
                $errors['id_categorie'] = 'Veuillez sélectionner une catégorie.';
            } elseif (!$this->categorieModel->findById($old['id_categorie'])) {
                $errors['id_categorie'] = 'Catégorie invalide.';
            }

            $statutsValides = ['en_attente', 'en_cours', 'termine'];
            if (!in_array($old['statut'], $statutsValides, true)) {
                $errors['statut'] = 'Statut invalide.';
            }

            if (empty($errors)) {
                $created = $this->projetModel->create([
                    'titre'        => $old['titre'],
                    'description'  => $old['description'],
                    'budget'       => (float) $old['budget'],
                    'statut'       => $old['statut'],
                    'id_categorie' => $old['id_categorie'],
                ]);

                if ($created) {
                    $this->redirect('index.php?page=projets&success=ajoute');
                } else {
                    $errors['global'] = 'Une erreur est survenue. Veuillez réessayer.';
                }
            }
        }

        $this->render('projets/ajouter', [
            'categories' => $categories,
            'errors'     => $errors,
            'old'        => $old,
        ]);
    }

    // ── MODIFIER UN PROJET ────────────────────────────────────────────────────

    /**
     * Affiche le formulaire de modification et traite la mise à jour
     */
    public function modifier(): void {
        $this->requireAuth();

        $id = (int) ($_GET['id'] ?? 0);
        $projet = $this->projetModel->findById($id);

        if (!$projet) {
            $this->redirect('index.php?page=projets');
        }

        $categories = $this->categorieModel->findAll();
        $errors     = [];

        if ($this->isPost()) {
            $data = [
                'titre'        => $this->sanitize($_POST['titre']        ?? ''),
                'description'  => $this->sanitize($_POST['description']  ?? ''),
                'budget'       => $this->sanitize($_POST['budget']        ?? ''),
                'statut'       => $_POST['statut']       ?? 'en_attente',
                'id_categorie' => (int) ($_POST['id_categorie'] ?? 0),
            ];

            // Validation manuelle (sans HTML5)
            if (empty($data['titre'])) {
                $errors['titre'] = 'Le titre est obligatoire.';
            } elseif (mb_strlen($data['titre']) < 3 || mb_strlen($data['titre']) > 150) {
                $errors['titre'] = 'Le titre doit contenir entre 3 et 150 caractères.';
            }

            if (empty($data['description'])) {
                $errors['description'] = 'La description est obligatoire.';
            } elseif (mb_strlen($data['description']) < 10) {
                $errors['description'] = 'La description doit contenir au moins 10 caractères.';
            }

            if ($data['budget'] === '' || !is_numeric($data['budget'])) {
                $errors['budget'] = 'Le budget doit être un nombre valide.';
            } elseif ((float) $data['budget'] < 0) {
                $errors['budget'] = 'Le budget ne peut pas être négatif.';
            }

            if ($data['id_categorie'] === 0) {
                $errors['id_categorie'] = 'Veuillez sélectionner une catégorie.';
            }

            if (empty($errors)) {
                $updated = $this->projetModel->update($id, [
                    'titre'        => $data['titre'],
                    'description'  => $data['description'],
                    'budget'       => (float) $data['budget'],
                    'statut'       => $data['statut'],
                    'id_categorie' => $data['id_categorie'],
                ]);

                if ($updated) {
                    $this->redirect('index.php?page=projets&success=modifie');
                } else {
                    $errors['global'] = 'Aucune modification effectuée.';
                }
            }

            // Mettre à jour $projet avec les valeurs soumises pour réafficher le formulaire
            $projet = array_merge($projet, $data);
        }

        $this->render('projets/modifier', [
            'projet'     => $projet,
            'categories' => $categories,
            'errors'     => $errors,
        ]);
    }

    // ── SUPPRIMER UN PROJET ───────────────────────────────────────────────────

    /**
     * Supprime un projet (via GET avec confirmation côté vue)
     */
    public function supprimer(): void {
        $this->requireAuth();
        $id = (int) ($_GET['id'] ?? 0);

        if ($id > 0) {
            $this->projetModel->delete($id);
        }

        $this->redirect('index.php?page=projets&success=supprime');
    }
}
