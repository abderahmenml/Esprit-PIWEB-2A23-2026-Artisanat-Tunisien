-- ═══════════════════════════════════════════════════════════════════════════
-- CraftLink — Deuxième entité : Projet & Catégorie
-- Jointure : projet.id_categorie → categorie.id_categorie
-- ═══════════════════════════════════════════════════════════════════════════

-- 1. Table Catégorie
CREATE TABLE IF NOT EXISTS `categorie` (
    `id_categorie` INT(11) NOT NULL AUTO_INCREMENT,
    `nom`          VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id_categorie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Table Projet
CREATE TABLE IF NOT EXISTS `projet` (
    `id_projet`    INT(11) NOT NULL AUTO_INCREMENT,
    `titre`        VARCHAR(150) NOT NULL,
    `description`  TEXT NOT NULL,
    `budget`       FLOAT NOT NULL,
    `statut`       ENUM('en_attente','en_cours','termine') NOT NULL DEFAULT 'en_attente',
    `id_categorie` INT(11) NOT NULL,
    PRIMARY KEY (`id_projet`),
    CONSTRAINT `fk_projet_categorie`
        FOREIGN KEY (`id_categorie`)
        REFERENCES `categorie` (`id_categorie`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Données de test — Catégories
INSERT INTO `categorie` (`nom`) VALUES
('Artisanat traditionnel'),
('Technologie & Innovation'),
('Agriculture durable'),
('Mode & Textile'),
('Gastronomie');

-- 4. Données de test — Projets
INSERT INTO `projet` (`titre`, `description`, `budget`, `statut`, `id_categorie`) VALUES
('Poterie de Nabeul', 'Revitalisation de l\'artisanat de poterie.', 5000.00, 'en_cours', 1),
('App CraftMarket', 'Marketplace mobile pour artisans tunisiens.', 15000.00, 'en_attente', 2),
('Ferme bio Sfax', 'Culture biologique de légumes locaux.', 8000.00, 'en_cours', 3),
('Broderie Tunis', 'Collection de broderies traditionnelles.', 3500.00, 'termine', 4),
('Resto Terroir', 'Restaurant valorisant la cuisine régionale.', 20000.00, 'en_attente', 5),
('Tapis de Kairouan', 'Atelier de tissage de tapis berbères.', 7000.00, 'en_cours', 1);
