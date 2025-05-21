<?php

/**
 * Classe CRUD pour la gestion de l'utilisation de l'API
 */
class ApiUsageCRUD extends BaseCRUD {
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        parent::__construct($mysqli, 'api_usage', 'id');
    }
    
    /**
     * Enregistre l'utilisation de tokens pour un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param int $modelId ID du modèle utilisé
     * @param int $tokensUsed Nombre de tokens utilisés
     * @param string|null $usageDate Date d'utilisation (format Y-m-d), utilise la date du jour si null
     * @return bool Succès de l'opération
     */
    public function recordTokenUsage($userId, $modelId, $tokensUsed, $usageDate = null) {
        if ($tokensUsed <= 0) {
            return true; // Rien à enregistrer
        }
        
        $date = $usageDate ?? date('Y-m-d');
        
        // Vérifier si une entrée existe déjà pour cet utilisateur, ce modèle et cette date
        $existingRecords = $this->get(['*'], [
            'user_id' => $userId,
            'model_id' => $modelId,
            'usage_date' => $date
        ]);
        
        if (!empty($existingRecords)) {
            // Mettre à jour l'entrée existante
            $record = $existingRecords[0];
            $updateData = [
                'tokens_used' => $record['tokens_used'] + $tokensUsed,
                'request_count' => $record['request_count'] + 1
            ];
            
            return $this->update($record['id'], $updateData);
        } else {
            // Créer une nouvelle entrée
            $insertData = [
                'user_id' => $userId,
                'model_id' => $modelId,
                'tokens_used' => $tokensUsed,
                'request_count' => 1,
                'usage_date' => $date
            ];
            
            return $this->insert($insertData) !== false;
        }
    }
    
    /**
     * Récupère le nombre total de tokens utilisés par un utilisateur sur une période donnée
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $startDate Date de début (format Y-m-d)
     * @param string $endDate Date de fin (format Y-m-d)
     * @return int Nombre total de tokens utilisés
     */
    public function getUserTokensUsedInPeriod($userId, $startDate, $endDate) {
        $query = "SELECT SUM(tokens_used) as total FROM `{$this->table}` WHERE `user_id` = ? AND `usage_date` BETWEEN ? AND ?";
        $stmt = $this->mysqli->prepare($query);
        
        if (!$stmt) {
            $this->errorInfo = ['message' => $this->mysqli->error];
            return 0;
        }
        
        $stmt->bind_param('iss', $userId, $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['total'] ?? 0;
    }
    
    /**
     * Récupère le nombre total de requêtes effectuées par un utilisateur sur une période donnée
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $startDate Date de début (format Y-m-d)
     * @param string $endDate Date de fin (format Y-m-d)
     * @return int Nombre total de requêtes
     */
    public function getUserRequestCountInPeriod($userId, $startDate, $endDate) {
        $query = "SELECT SUM(request_count) as total FROM `{$this->table}` WHERE `user_id` = ? AND `usage_date` BETWEEN ? AND ?";
        $stmt = $this->mysqli->prepare($query);
        
        if (!$stmt) {
            $this->errorInfo = ['message' => $this->mysqli->error];
            return 0;
        }
        
        $stmt->bind_param('iss', $userId, $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['total'] ?? 0;
    }
    
    /**
     * Récupère l'utilisation détaillée par modèle pour un utilisateur sur une période donnée
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $startDate Date de début (format Y-m-d)
     * @param string $endDate Date de fin (format Y-m-d)
     * @return array Utilisation détaillée par modèle
     */
    public function getUserUsageByModel($userId, $startDate, $endDate) {
        $query = "SELECT `model_id`, SUM(tokens_used) as total_tokens, SUM(request_count) as total_requests 
                 FROM `{$this->table}` 
                 WHERE `user_id` = ? AND `usage_date` BETWEEN ? AND ? 
                 GROUP BY `model_id`";
        $stmt = $this->mysqli->prepare($query);
        
        if (!$stmt) {
            $this->errorInfo = ['message' => $this->mysqli->error];
            return [];
        }
        
        $stmt->bind_param('iss', $userId, $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $usage = [];
        while ($row = $result->fetch_assoc()) {
            $usage[] = $row;
        }
        
        $stmt->close();
        return $usage;
    }
    
    /**
     * Récupère l'utilisation quotidienne pour un utilisateur sur une période donnée
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $startDate Date de début (format Y-m-d)
     * @param string $endDate Date de fin (format Y-m-d)
     * @return array Utilisation quotidienne
     */
    public function getUserDailyUsage($userId, $startDate, $endDate) {
        $query = "SELECT `usage_date`, SUM(tokens_used) as total_tokens, SUM(request_count) as total_requests 
                 FROM `{$this->table}` 
                 WHERE `user_id` = ? AND `usage_date` BETWEEN ? AND ? 
                 GROUP BY `usage_date` 
                 ORDER BY `usage_date` ASC";
        $stmt = $this->mysqli->prepare($query);
        
        if (!$stmt) {
            $this->errorInfo = ['message' => $this->mysqli->error];
            return [];
        }
        
        $stmt->bind_param('iss', $userId, $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $usage = [];
        while ($row = $result->fetch_assoc()) {
            $usage[] = $row;
        }
        
        $stmt->close();
        return $usage;
    }
}