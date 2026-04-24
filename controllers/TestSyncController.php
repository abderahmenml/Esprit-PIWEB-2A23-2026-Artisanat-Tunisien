<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/CategorieModel.php';
require_once __DIR__ . '/../models/ProjetModel.php';
require_once __DIR__ . '/../core/SupabaseSync.php';

class TestSyncController extends Controller {
    public function index(): void {
        $output = [];
        $output[] = "===================================================================";
        $output[] = "TEST DE SYNCHRONISATION SUPABASE - CraftLink";
        $output[] = "===================================================================";
        $output[] = "";

        $output[] = "TEST 1: Configuration Supabase";
        $output[] = "  URL: " . SUPABASE_URL;
        $output[] = "  Sync enabled: " . (ENABLE_SYNC ? "OUI" : "NON");
        $output[] = "  Anon Key: " . substr(SUPABASE_ANON_KEY, 0, 20) . "...";
        $output[] = "";

        $output[] = "TEST 2: Connexion MySQL";
        try {
            $db = Database::getInstance();
            $db->getConnection();
            $output[] = "  Connexion reussie";
        } catch (Exception $e) {
            $output[] = "  ERREUR: " . $e->getMessage();
        }
        $output[] = "";

        $output[] = "TEST 3: Connexion Supabase";
        $sync = new SupabaseSync();
        if ($sync->testConnection()) {
            $output[] = "  Connexion reussie";
        } else {
            $output[] = "  ERREUR: Connexion echouee";
            $output[] = "  Note: Assurez-vous que les tables existent dans Supabase";
        }
        $output[] = "";

        $output[] = "TEST 4: Compter les utilisateurs";
        try {
            $userModel = new UserModel();
            $output[] = "  Utilisateurs dans MySQL: " . $userModel->count();
        } catch (Exception $e) {
            $output[] = "  ERREUR: " . $e->getMessage();
        }
        $output[] = "";

        $output[] = "TEST 5: Compter les categories";
        try {
            $catModel = new CategorieModel();
            $cats = $catModel->findAll();
            $output[] = "  Categories dans MySQL: " . count($cats);
            foreach ($cats as $cat) {
                $output[] = "    - " . $cat['nom'];
            }
        } catch (Exception $e) {
            $output[] = "  ERREUR: " . $e->getMessage();
        }
        $output[] = "";

        $output[] = "TEST 6: Compter les projets";
        try {
            $projModel = new ProjetModel();
            $output[] = "  Projets dans MySQL: " . count($projModel->findAll());
        } catch (Exception $e) {
            $output[] = "  ERREUR: " . $e->getMessage();
        }
        $output[] = "";

        $output[] = "TEST 7: Operation CREATE (test optionnel)";
        $output[] = "  Pour tester, rendez-vous sur: http://localhost/craftlink/admin";
        $output[] = "  Creez un nouvel utilisateur et verifiez dans Supabase";
        $output[] = "";
        $output[] = "Acces via: http://localhost/craftlink/index.php?page=test_sync";

        $this->render('tools/test_sync', ['output' => $output]);
    }
}
