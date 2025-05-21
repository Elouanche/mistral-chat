<?php

/**
 * Classe CRUD pour la gestion des requêtes API envoyées à l'IA
 */
class AiRequestsCRUD extends BaseCRUD {
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        parent::__construct($mysqli, 'ai_requests', 'id');
    }
    
    /**
     * Crée une nouvelle requête API
     * 
     * @param array $requestData Données de la requête
     * @return int|bool ID de la requête créée ou false en cas d'échec
     */
    public function createRequest($requestData) {
        return $this->insert($requestData);
    }
    
    /**
     * Met à jour une requête API
     * 
     * @param int $requestId ID de la requête
     * @param array $updateData Données à mettre à jour
     * @return bool Succès de l'opération
     */
    public function updateRequest($requestId, $updateData) {
        return $this->update($requestId, $updateData);
    }
    
    /**
     * Récupère les requêtes d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param array $options Options supplémentaires (pagination, tri)
     * @return array Liste des requêtes
     */
    public function getUserRequests($userId, $options = []) {
        $filters = ['user_id' => $userId];
        $defaultOptions = ['order_by' => ['request_timestamp' => 'DESC']];
        $mergedOptions = array_merge($defaultOptions, $options);
        
        return $this->get(['*'], $filters, $mergedOptions);
    }
    
    /**
     * Récupère les requêtes d'une conversation
     * 
     * @param int $conversationId ID de la conversation
     * @param array $options Options supplémentaires (pagination, tri)
     * @return array Liste des requêtes
     */
    public function getConversationRequests($conversationId, $options = []) {
        $filters = ['conversation_id' => $conversationId];
        $defaultOptions = ['order_by' => ['request_timestamp' => 'ASC']];
        $mergedOptions = array_merge($defaultOptions, $options);
        
        return $this->get(['*'], $filters, $mergedOptions);
    }
    
    /**
     * Récupère une requête spécifique
     * 
     * @param int $requestId ID de la requête
     * @return array|null Données de la requête ou null si non trouvée
     */
    public function getRequest($requestId) {
        $results = $this->get(['*'], ['id' => $requestId]);
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Compte le nombre de requêtes d'un utilisateur sur une période donnée
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $startDate Date de début (format Y-m-d H:i:s)
     * @param string $endDate Date de fin (format Y-m-d H:i:s)
     * @return int Nombre de requêtes
     */
    public function countUserRequests($userId, $startDate, $endDate) {
        $query = "SELECT COUNT(*) as count FROM `{$this->table}` WHERE `user_id` = ? AND `request_timestamp` BETWEEN ? AND ?";
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
        
        return $row['count'] ?? 0;
    }
    
    /**
     * Calcule le nombre total de tokens utilisés par un utilisateur sur une période donnée
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $startDate Date de début (format Y-m-d H:i:s)
     * @param string $endDate Date de fin (format Y-m-d H:i:s)
     * @return array Nombre de tokens d'entrée et de sortie
     */
    public function calculateUserTokens($userId, $startDate, $endDate) {
        $query = "SELECT SUM(tokens_prompt) as total_prompt, SUM(tokens_completion) as total_completion 
                 FROM `{$this->table}` 
                 WHERE `user_id` = ? AND `request_timestamp` BETWEEN ? AND ?";
        $stmt = $this->mysqli->prepare($query);
        
        if (!$stmt) {
            $this->errorInfo = ['message' => $this->mysqli->error];
            return ['total_prompt' => 0, 'total_completion' => 0];
        }
        
        $stmt->bind_param('iss', $userId, $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return [
            'total_prompt' => $row['total_prompt'] ?? 0,
            'total_completion' => $row['total_completion'] ?? 0
        ];
    }
}