-- ═══════════════════════════════════════════════════════════════════════════
-- CraftLink — SQL pour Supabase (PostgreSQL)
-- Tables synchronisées avec la base MySQL locale
-- ═══════════════════════════════════════════════════════════════════════════

-- 1. Table User
CREATE TABLE IF NOT EXISTS public.user (
    id_user SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    date_creation DATE DEFAULT CURRENT_DATE,
    etat_compte VARCHAR(20) DEFAULT 'actif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Table Categorie
CREATE TABLE IF NOT EXISTS public.categorie (
    id_categorie SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Table Projet
CREATE TABLE IF NOT EXISTS public.projet (
    id_projet SERIAL PRIMARY KEY,
    titre VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    budget FLOAT NOT NULL,
    statut VARCHAR(50) NOT NULL DEFAULT 'en_attente',
    id_categorie INT NOT NULL REFERENCES public.categorie(id_categorie) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Table Pending Users
CREATE TABLE IF NOT EXISTS public.pending_users (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Table Password Resets
CREATE TABLE IF NOT EXISTS public.password_resets (
    id SERIAL PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    code VARCHAR(10) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ═══════════════════════════════════════════════════════════════════════════
-- INDEX pour performance
-- ═══════════════════════════════════════════════════════════════════════════
CREATE INDEX IF NOT EXISTS idx_user_email ON public.user(email);
CREATE INDEX IF NOT EXISTS idx_user_role ON public.user(role);
CREATE INDEX IF NOT EXISTS idx_pending_users_email ON public.pending_users(email);
CREATE INDEX IF NOT EXISTS idx_pending_users_token ON public.pending_users(token);
CREATE INDEX IF NOT EXISTS idx_password_resets_email ON public.password_resets(email);
CREATE INDEX IF NOT EXISTS idx_projet_id_categorie ON public.projet(id_categorie);

-- ═══════════════════════════════════════════════════════════════════════════
-- Données de test (optionnel)
-- ═══════════════════════════════════════════════════════════════════════════

-- Catégories
INSERT INTO public.categorie (nom) VALUES
('Artisanat traditionnel'),
('Technologie & Innovation'),
('Agriculture durable'),
('Mode & Textile'),
('Gastronomie')
ON CONFLICT DO NOTHING;

-- Projets de test
INSERT INTO public.projet (titre, description, budget, statut, id_categorie) VALUES
('Poterie de Nabeul', 'Revitalisation de l''artisanat de poterie.', 5000.00, 'en_cours', 1),
('App CraftMarket', 'Marketplace mobile pour artisans tunisiens.', 15000.00, 'en_attente', 2),
('Ferme bio Sfax', 'Culture biologique de légumes locaux.', 8000.00, 'en_cours', 3),
('Broderie Tunis', 'Collection de broderies traditionnelles.', 3500.00, 'termine', 4),
('Resto Terroir', 'Restaurant valorisant la cuisine régionale.', 20000.00, 'en_attente', 5),
('Tapis de Kairouan', 'Atelier de tissage de tapis berbères.', 7000.00, 'en_cours', 1)
ON CONFLICT DO NOTHING;

-- ═══════════════════════════════════════════════════════════════════════════
-- Permissions Row Level Security (RLS) - Optionnel
-- ═══════════════════════════════════════════════════════════════════════════

-- Activer RLS (sécurité)
-- ALTER TABLE public.user ENABLE ROW LEVEL SECURITY;
-- ALTER TABLE public.projet ENABLE ROW LEVEL SECURITY;
-- ALTER TABLE public.categorie ENABLE ROW LEVEL SECURITY;
