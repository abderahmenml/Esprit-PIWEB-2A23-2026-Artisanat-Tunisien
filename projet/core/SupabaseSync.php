<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Classe SupabaseSync — Synchronisation avec Supabase via API REST
 * Synchronise automatiquement: CREATE, UPDATE, DELETE entre MySQL et Supabase
 */
class SupabaseSync {
    private string $url;
    private string $apiKey;
    private bool $enabled;

    public function __construct() {
        $this->url     = SUPABASE_URL;
        $this->apiKey  = SUPABASE_ANON_KEY;
        $this->enabled = ENABLE_SYNC;
    }

    /**
     * Effectue une requête HTTP à l'API Supabase
     */
    private function request(string $method, string $table, array $data = [], array $filters = []): ?array {
        if (!$this->enabled) {
            return null;
        }

        try {
            $endpoint = "{$this->url}/rest/v1/{$table}";
            
            // Ajouter les filtres à l'URL
            if (!empty($filters)) {
                $filterQuery = [];
                foreach ($filters as $key => $value) {
                    $filterQuery[] = "{$key}=eq.{$value}";
                }
                if (!empty($filterQuery)) {
                    $endpoint .= "?" . implode("&", $filterQuery);
                }
            }

            $headers = [
                "Content-Type: application/json",
                "Authorization: Bearer {$this->apiKey}",
                "apikey: {$this->apiKey}",
                "Prefer: return=representation"
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            if (!empty($data) && in_array($method, ['POST', 'PATCH'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                return json_decode($response, true);
            }

            error_log("Supabase Error [$method $table]: HTTP $httpCode - $response");
            return null;

        } catch (Exception $e) {
            error_log("Supabase Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * INSERT — Créer un enregistrement dans Supabase
     */
    public function insert(string $table, array $data): bool {
        return $this->request('POST', $table, $data) !== null;
    }

    /**
     * UPDATE — Mettre à jour un enregistrement dans Supabase
     */
    public function update(string $table, int $id, array $data, string $idColumn = 'id'): bool {
        return $this->request('PATCH', $table, $data, [$idColumn => $id]) !== null;
    }

    /**
     * DELETE — Supprimer un enregistrement dans Supabase
     */
    public function delete(string $table, int $id, string $idColumn = 'id'): bool {
        return $this->request('DELETE', $table, [], [$idColumn => $id]) !== null;
    }

    /**
     * SELECT — Récupérer des enregistrements
     */
    public function select(string $table, array $filters = [], string $orderBy = ''): ?array {
        $endpoint = "{$this->url}/rest/v1/{$table}";
        
        $queryParams = [];
        foreach ($filters as $key => $value) {
            $queryParams[] = "{$key}=eq.{$value}";
        }
        if (!empty($orderBy)) {
            $queryParams[] = "order={$orderBy}";
        }
        
        if (!empty($queryParams)) {
            $endpoint .= "?" . implode("&", $queryParams);
        }

        try {
            $headers = [
                "Authorization: Bearer {$this->apiKey}",
                "apikey: {$this->apiKey}",
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                return json_decode($response, true);
            }

            return null;

        } catch (Exception $e) {
            error_log("Supabase Select Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifie la connexion à Supabase
     */
    public function testConnection(): bool {
        if (!$this->enabled) {
            return false;
        }
        
        $result = $this->select('user', [], 'limit=1');
        return $result !== null;
    }

    /**
     * Synchronise les données existantes (migration initiale)
     */
    public function syncUserTable(array $users): int {
        $count = 0;
        foreach ($users as $user) {
            if ($this->insert('user', $user)) {
                $count++;
            }
        }
        return $count;
    }
}
