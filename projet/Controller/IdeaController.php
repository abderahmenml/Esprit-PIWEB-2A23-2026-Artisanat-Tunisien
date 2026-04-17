<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Model/Idea.php';

class IdeaController 
{
    public function listProjects()
    {
        $sql = 'SELECT p.id_projet, p.titre, p.budget_min, p.status, p.date_creation, p.categorie, p.description, p.id_user, u.nom, u.prenom FROM projet p LEFT JOIN user u ON u.id_user = p.id_user ORDER BY p.id_projet DESC';
        $db = config::getConnexion();
        $list = [];
        try {
            $result = $db->query($sql);
            if ($result) {
                $list = $result->fetchAll();
            }
        } catch (Exception $e) {
            $list = [];
        }
        return $list;
    }

    public function showProject($id)
    {
        $sql = 'SELECT id_projet, titre, budget_min, status, categorie, description, id_user FROM projet WHERE id_projet = :id AND id_user = :id_user';
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $project = null;
        try {
            $query->execute([
                'id' => $id,
                'id_user' => getCurrentUserId()
            ]);
            $project = $query->fetch();
        } catch (Exception $e) {
            $project = null;
        }
        return $project;
    }

    public function showProjectAny($id)
    {
        $sql = 'SELECT id_projet, titre, budget_min, status, categorie, description, id_user FROM projet WHERE id_projet = :id';
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $project = null;
        try {
            $query->execute(['id' => $id]);
            $project = $query->fetch();
        } catch (Exception $e) {
            $project = null;
        }
        return $project;
    }

    public function getSkillsForProject($projectId)
    {
        $sql = 'SELECT s.nom, s.`level` AS skill_level FROM required_skills rs JOIN skills s ON s.id = rs.id_skill WHERE rs.id_projet = :id';
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $rows = [];
        try {
            $query->execute(['id' => $projectId]);
            $rows = $query->fetchAll();
        } catch (Exception $e) {
            $rows = [];
        }
        return $rows;
    }

    public function getMaterialsForProject($projectId)
    {
        $sql = 'SELECT m.nom_materiel, rm.quantite, rm.prix_unitaire FROM required_mater rm JOIN materiel m ON m.id_materiel = rm.id_materiel WHERE rm.id_projet = :id';
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $rows = [];
        try {
            $query->execute(['id' => $projectId]);
            $rows = $query->fetchAll();
        } catch (Exception $e) {
            $rows = [];
        }
        return $rows;
    }

    public function addIdea($idea,$skillsNames,$skillsLevels,$materialsNames,$materialsQty,$materialsPrice) {
        $db = config::getConnexion();

        $currentUserId = getCurrentUserId();
        if ($currentUserId <= 0) {
            return;
        }

        try {
            $db->beginTransaction();

            // Step 1: save the main project.
            $projectId = $this->saveProject($db, $idea);

            // Step 2: save skills linked to the project.
            $this->saveSkills($db, $projectId, $skillsNames, $skillsLevels);

            // Step 3: save materials linked to the project.
            $this->saveMaterials($db, $projectId, $materialsNames, $materialsQty, $materialsPrice);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
        }
    }

    private function saveProject($db, $idea)
    {
        $query = $db->prepare(
            'INSERT INTO projet (titre, budget_min, status, date_creation, categorie, description, id_user) VALUES (:titre, :budget_min, :status, CURDATE(), :categorie, :description, :id_user)'
        );

        $statusValue = null;
        if ($idea->getStatus() !== '') {
            $statusValue = $idea->getStatus();
        }

        $categoryValue = null;
        if ($idea->getCategory() !== '') {
            $categoryValue = $idea->getCategory();
        }

        $descriptionValue = null;
        if ($idea->getDescription() !== '') {
            $descriptionValue = $idea->getDescription();
        }

        $query->execute([
            'titre' => $idea->getTitle(),
            'budget_min' => $idea->getBudgetMin(),
            'status' => $statusValue,
            'categorie' => $categoryValue,
            'description' => $descriptionValue,
            'id_user' => getCurrentUserId()
        ]);

        return (int)$db->lastInsertId();
    }

    private function saveSkills($db, $projectId, $skillsNames, $skillsLevels)
    {
        $insertSkill = $db->prepare('INSERT INTO skills (`nom`, `level`) VALUES (:nom, :level)');
        $linkSkill = $db->prepare('INSERT INTO required_skills (id_projet, id_skill) VALUES (:id_projet, :id_skill)');

        $skillsCount = count($skillsNames);
        if (count($skillsLevels) > $skillsCount) {
            $skillsCount = count($skillsLevels);
        }

        for ($i = 0; $i < $skillsCount; $i++) {
            $skillName = '';
            if (isset($skillsNames[$i])) {
                $skillName = trim($skillsNames[$i]);
            }

            $skillLevel = '';
            if (isset($skillsLevels[$i])) {
                $skillLevel = trim($skillsLevels[$i]);
            }

            if ($skillName === '' && $skillLevel === '') {
                continue;
            }

            $insertSkill->execute([
                'nom' => $skillName,
                'level' => $skillLevel
            ]);

            $skillId = (int)$db->lastInsertId();
            $linkSkill->execute([
                'id_projet' => $projectId,
                'id_skill' => $skillId
            ]);
        }
    }

    private function saveMaterials($db, $projectId, $materialsNames, $materialsQty, $materialsPrice)
    {
        $insertMaterial = $db->prepare('INSERT INTO materiel (nom_materiel, description) VALUES (:nom, :description)');
        $linkMaterial = $db->prepare(
            'INSERT INTO required_mater (id_projet, id_materiel, quantite, prix_unitaire) VALUES (:id_projet, :id_materiel, :quantite, :prix_unitaire)'
        );

        $materialsCount = count($materialsNames);
        if (count($materialsQty) > $materialsCount) {
            $materialsCount = count($materialsQty);
        }
        if (count($materialsPrice) > $materialsCount) {
            $materialsCount = count($materialsPrice);
        }

        for ($i = 0; $i < $materialsCount; $i++) {
            $materialName = '';
            if (isset($materialsNames[$i])) {
                $materialName = trim($materialsNames[$i]);
            }

            $qtyRaw = '';
            if (isset($materialsQty[$i])) {
                $qtyRaw = trim($materialsQty[$i]);
            }

            $priceRaw = '';
            if (isset($materialsPrice[$i])) {
                $priceRaw = trim($materialsPrice[$i]);
            }

            if ($materialName === '') {
                continue;
            }

            $insertMaterial->execute([
                'nom' => $materialName,
                'description' => null
            ]);

            $materialId = (int)$db->lastInsertId();
            $qtyValue = null;
            if ($qtyRaw !== '') {
                $qtyValue = (int)$qtyRaw;
            }

            $priceValue = null;
            if ($priceRaw !== '') {
                $priceValue = (float)$priceRaw;
            }

            $linkMaterial->execute([
                'id_projet' => $projectId,
                'id_materiel' => $materialId,
                'quantite' => $qtyValue,
                'prix_unitaire' => $priceValue
            ]);
        }
    }

    public function updateIdea($idea, $id)
    {
        $db = config::getConnexion();
        try {
            $this->updateProject($db, $idea, $id);
        } catch (Exception $e) {
        }
    }

    public function updateIdeaWithDetails($idea, $id, $skillsNames, $skillsLevels, $materialsNames, $materialsQty, $materialsPrice)
    {
        $db = config::getConnexion();

        $currentUserId = getCurrentUserId();
        if ($currentUserId <= 0) {
            return;
        }

        try {
            if (!$this->isOwner($db, $id)) {
                return;
            }

            $db->beginTransaction();
            $this->updateProject($db, $idea, $id);
            $this->replaceSkills($db, $id, $skillsNames, $skillsLevels);
            $this->replaceMaterials($db, $id, $materialsNames, $materialsQty, $materialsPrice);
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
        }
    }

    public function updateIdeaAny($idea, $id)
    {
        $db = config::getConnexion();
        try {
            $this->updateProjectAny($db, $idea, $id);
        } catch (Exception $e) {
        }
    }

    private function updateProject($db, $idea, $id)
    {
        $query = $db->prepare(
            'UPDATE projet SET titre = :titre, budget_min = :budget_min, status = :status, categorie = :categorie, description = :description WHERE id_projet = :id AND id_user = :id_user'
        );

        $statusValue = null;
        if ($idea->getStatus() !== '') {
            $statusValue = $idea->getStatus();
        }

        $categoryValue = null;
        if ($idea->getCategory() !== '') {
            $categoryValue = $idea->getCategory();
        }

        $descriptionValue = null;
        if ($idea->getDescription() !== '') {
            $descriptionValue = $idea->getDescription();
        }

        $query->execute([
            'titre' => $idea->getTitle(),
            'budget_min' => $idea->getBudgetMin(),
            'status' => $statusValue,
            'categorie' => $categoryValue,
            'description' => $descriptionValue,
            'id' => $id,
            'id_user' => getCurrentUserId()
        ]);
    }

    private function updateProjectAny($db, $idea, $id)
    {
        $query = $db->prepare(
            'UPDATE projet SET titre = :titre, budget_min = :budget_min, status = :status, categorie = :categorie, description = :description WHERE id_projet = :id'
        );

        $statusValue = null;
        if ($idea->getStatus() !== '') {
            $statusValue = $idea->getStatus();
        }

        $categoryValue = null;
        if ($idea->getCategory() !== '') {
            $categoryValue = $idea->getCategory();
        }

        $descriptionValue = null;
        if ($idea->getDescription() !== '') {
            $descriptionValue = $idea->getDescription();
        }

        $query->execute([
            'titre' => $idea->getTitle(),
            'budget_min' => $idea->getBudgetMin(),
            'status' => $statusValue,
            'categorie' => $categoryValue,
            'description' => $descriptionValue,
            'id' => $id
        ]);
    }

    private function replaceSkills($db, $projectId, $skillsNames, $skillsLevels)
    {
        $db->prepare('DELETE FROM required_skills WHERE id_projet = :id')->execute(['id' => $projectId]);
        $this->saveSkills($db, $projectId, $skillsNames, $skillsLevels);
    }

    private function replaceMaterials($db, $projectId, $materialsNames, $materialsQty, $materialsPrice)
    {
        $db->prepare('DELETE FROM required_mater WHERE id_projet = :id')->execute(['id' => $projectId]);
        $this->saveMaterials($db, $projectId, $materialsNames, $materialsQty, $materialsPrice);
    }

    private function isOwner($db, $id)
    {
        $query = $db->prepare('SELECT id_user FROM projet WHERE id_projet = :id');
        $query->execute(['id' => $id]);
        $row = $query->fetch();
        if (!$row) {
            return false;
        }
        return (int)$row['id_user'] === (int)getCurrentUserId();
    }

    public function deleteIdea($id)
    {
        $db = config::getConnexion();
        try {
            if (!$this->isOwner($db, $id)) {
                return;
            }
            $db->beginTransaction();
            $db->prepare('DELETE FROM required_skills WHERE id_projet = :id')->execute(['id' => $id]);
            $db->prepare('DELETE FROM required_mater WHERE id_projet = :id')->execute(['id' => $id]);
            $db->prepare('DELETE FROM projet WHERE id_projet = :id')->execute(['id' => $id]);
            $db->commit();
        } catch (PDOException $e) {
            $db->rollBack();
        }
    }

    public function deleteIdeaAny($id)
    {
        $db = config::getConnexion();
        try {
            $db->beginTransaction();
            $db->prepare('DELETE FROM required_skills WHERE id_projet = :id')->execute(['id' => $id]);
            $db->prepare('DELETE FROM required_mater WHERE id_projet = :id')->execute(['id' => $id]);
            $db->prepare('DELETE FROM projet WHERE id_projet = :id')->execute(['id' => $id]);
            $db->commit();
        } catch (PDOException $e) {
            $db->rollBack();
        }
    }
}
?>
