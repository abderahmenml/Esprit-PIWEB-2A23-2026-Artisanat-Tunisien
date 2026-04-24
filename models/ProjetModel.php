<?php
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/SupabaseSync.php';

/**
 * Modèle Projet — CRUD sur la table `projet`
 * Hérite de Model qui fournit l'accès PDO via Singleton
 * Synchronise avec Supabase automatiquement
 */
class ProjetModel extends Model {
    private SupabaseSync $sync;

    public function __construct() {
        parent::__construct();
        $this->sync = new SupabaseSync();
    }

    // ── READ ──────────────────────────────────────────────────────────────────

    /**
     * Récupère tous les projets avec leur catégorie (JOINTURE)
     */
    public function findAll(): array {
        $sql = "SELECT projet.id_projet,
                       projet.titre,
                       projet.description,
                       projet.budget,
                       projet.statut,
                       projet.id_categorie,
                       categorie.nom AS nom_categorie
                FROM projet
                INNER JOIN categorie ON projet.id_categorie = categorie.id_categorie
                ORDER BY projet.titre ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Récupère un projet par son ID (avec jointure catégorie)
     */
    public function findById(int $id): ?array {
        $sql = "SELECT projet.id_projet,
                       projet.titre,
                       projet.description,
                       projet.budget,
                       projet.statut,
                       projet.id_categorie,
                       categorie.nom AS nom_categorie
                FROM projet
                INNER JOIN categorie ON projet.id_categorie = categorie.id_categorie
                WHERE projet.id_projet = :id
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Filtre les projets par statut (avec jointure catégorie)
     */
    public function findByStatut(string $statut): array {
        $sql = "SELECT projet.id_projet,
                       projet.titre,
                       projet.description,
                       projet.budget,
                       projet.statut,
                       categorie.nom AS nom_categorie
                FROM projet
                INNER JOIN categorie ON projet.id_categorie = categorie.id_categorie
                WHERE projet.statut = :statut
                ORDER BY projet.titre ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':statut' => $statut]);
        return $stmt->fetchAll();
    }

    /**
     * Compte le nombre de projets par statut
     */
    public function countByStatut(): array {
        $stmt = $this->pdo->query(
            "SELECT statut, COUNT(*) AS total FROM projet GROUP BY statut"
        );
        return $stmt->fetchAll();
    }

    // ── CREATE ────────────────────────────────────────────────────────────────

    /**
     * Insère un nouveau projet
     */
    public function create(array $data): bool {
        $sql = "INSERT INTO projet (titre, description, budget, statut, id_categorie)
                VALUES (:titre, :description, :budget, :statut, :id_categorie)";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            ':titre'        => $data['titre'],
            ':description'  => $data['description'],
            ':budget'       => $data['budget'],
            ':statut'       => $data['statut'] ?? 'en_attente',
            ':id_categorie' => $data['id_categorie'],
        ]);

        // Synchroniser avec Supabase
        if ($result) {
            $projectId = $this->pdo->lastInsertId();
            $this->sync->insert('projet', [
                'id_projet'    => (int)$projectId,
                'titre'        => $data['titre'],
                'description'  => $data['description'],
                'budget'       => (float)$data['budget'],
                'statut'       => $data['statut'] ?? 'en_attente',
                'id_categorie' => (int)$data['id_categorie'],
            ]);
        }

        return $result;
    }

    // ── UPDATE ────────────────────────────────────────────────────────────────

    /**
     * Met à jour un projet
     */
    public function update(int $id, array $data): bool {
        $sql = "UPDATE projet SET
                    titre        = :titre,
                    description  = :description,
                    budget       = :budget,
                    statut       = :statut,
                    id_categorie = :id_categorie
                WHERE id_projet = :id";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            ':titre'        => $data['titre'],
            ':description'  => $data['description'],
            ':budget'       => $data['budget'],
            ':statut'       => $data['statut'],
            ':id_categorie' => $data['id_categorie'],
            ':id'           => $id,
        ]);

        // Synchroniser avec Supabase
        if ($result) {
            $this->sync->update('projet', $id, [
                'titre'        => $data['titre'],
                'description'  => $data['description'],
                'budget'       => (float)$data['budget'],
                'statut'       => $data['statut'],
                'id_categorie' => (int)$data['id_categorie'],
            ], 'id_projet');
        }

        return $result;
    }

    // ── DELETE ────────────────────────────────────────────────────────────────

    /**
     * Supprime un projet par son ID
     */
    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM projet WHERE id_projet = :id");
        $result = $stmt->execute([':id' => $id]);

        // Synchroniser avec Supabase
        if ($result) {
            $this->sync->delete('projet', $id, 'id_projet');
        }

        return $result;
    }
}
