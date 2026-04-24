<?php
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/SupabaseSync.php';

/**
 * Modèle Categorie — CRUD sur la table `categorie`
 * Hérite de Model qui fournit l'accès PDO via Singleton
 * Synchronise avec Supabase automatiquement
 */
class CategorieModel extends Model {
    private SupabaseSync $sync;

    public function __construct() {
        parent::__construct();
        $this->sync = new SupabaseSync();
    }

    // ── READ ──────────────────────────────────────────────────────────────────

    /**
     * Récupère toutes les catégories
     */
    public function findAll(): array {
        $stmt = $this->pdo->query("SELECT * FROM categorie ORDER BY nom ASC");
        return $stmt->fetchAll();
    }

    /**
     * Récupère une catégorie par son ID
     */
    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM categorie WHERE id_categorie = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Récupère les projets d'une catégorie (JOINTURE)
     * Jointure entre `projet` et `categorie` sur id_categorie
     */
    public function findProjetsByCategorie(int $idCategorie): array {
        $sql = "SELECT projet.id_projet,
                       projet.titre,
                       projet.description,
                       projet.budget,
                       projet.statut,
                       categorie.nom AS nom_categorie
                FROM projet
                INNER JOIN categorie ON projet.id_categorie = categorie.id_categorie
                WHERE categorie.id_categorie = :id
                ORDER BY projet.titre ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $idCategorie]);
        return $stmt->fetchAll();
    }

    // ── CREATE ────────────────────────────────────────────────────────────────

    /**
     * Insère une nouvelle catégorie
     */
    public function create(array $data): bool {
        $stmt = $this->pdo->prepare(
            "INSERT INTO categorie (nom) VALUES (:nom)"
        );
        $result = $stmt->execute([':nom' => $data['nom']]);

        // Synchroniser avec Supabase
        if ($result) {
            $categoryId = $this->pdo->lastInsertId();
            $this->sync->insert('categorie', [
                'id_categorie' => (int)$categoryId,
                'nom'          => $data['nom'],
            ]);
        }

        return $result;
    }

    // ── UPDATE ────────────────────────────────────────────────────────────────

    /**
     * Met à jour une catégorie
     */
    public function update(int $id, array $data): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE categorie SET nom = :nom WHERE id_categorie = :id"
        );
        $result = $stmt->execute([':nom' => $data['nom'], ':id' => $id]);

        // Synchroniser avec Supabase
        if ($result) {
            $this->sync->update('categorie', $id, [
                'nom' => $data['nom'],
            ], 'id_categorie');
        }

        return $result;
    }

    // ── DELETE ────────────────────────────────────────────────────────────────

    /**
     * Supprime une catégorie par son ID
     */
    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare(
            "DELETE FROM categorie WHERE id_categorie = :id"
        );
        $result = $stmt->execute([':id' => $id]);

        // Synchroniser avec Supabase
        if ($result) {
            $this->sync->delete('categorie', $id, 'id_categorie');
        }

        return $result;
    }

    // ── UTILITAIRES ───────────────────────────────────────────────────────────

    /**
     * Vérifie si un nom de catégorie existe déjà
     */
    public function nomExists(string $nom, ?int $excludeId = null): bool {
        if ($excludeId !== null) {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM categorie WHERE nom = :nom AND id_categorie != :id"
            );
            $stmt->execute([':nom' => $nom, ':id' => $excludeId]);
        } else {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM categorie WHERE nom = :nom"
            );
            $stmt->execute([':nom' => $nom]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }
}
