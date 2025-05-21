<?php

/**
 * Classe CRUD pour la gestion des abonnements
 */
class SubscriptionsCRUD extends BaseCRUD {
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        parent::__construct($mysqli, 'subscriptions', 'id');
    }
    
    /**
     * Récupère l'abonnement actif d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @return array|null Données de l'abonnement ou null si aucun abonnement actif
     */
    public function getActiveSubscription($userId) {
        $filters = [
            'user_id' => $userId,
            'status' => 'active',
            'end_date >= CURDATE()' => null // Condition pour vérifier que l'abonnement n'est pas expiré
        ];
        
        $options = ['order_by' => ['end_date' => 'DESC'], 'limit' => 1];
        
        $results = $this->get(['*'], $filters, $options);
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Récupère tous les abonnements d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param array $options Options supplémentaires (pagination, tri)
     * @return array Liste des abonnements
     */
    public function getUserSubscriptions($userId, $options = []) {
        $filters = ['user_id' => $userId];
        $defaultOptions = ['order_by' => ['created_at' => 'DESC']];
        $mergedOptions = array_merge($defaultOptions, $options);
        
        return $this->get(['*'], $filters, $mergedOptions);
    }
    
    /**
     * Crée un nouvel abonnement
     * 
     * @param array $subscriptionData Données de l'abonnement
     * @return int|bool ID de l'abonnement créé ou false en cas d'échec
     */
    public function createSubscription($subscriptionData) {
        return $this->insert($subscriptionData);
    }
    
    /**
     * Met à jour un abonnement
     * 
     * @param int $subscriptionId ID de l'abonnement
     * @param array $updateData Données à mettre à jour
     * @return bool Succès de l'opération
     */
    public function updateSubscription($subscriptionId, $updateData) {
        return $this->update($subscriptionId, $updateData);
    }
    
    /**
     * Annule un abonnement
     * 
     * @param int $subscriptionId ID de l'abonnement
     * @param int|null $userId ID de l'utilisateur (pour vérification d'accès)
     * @return bool Succès de l'opération
     */
    public function cancelSubscription($subscriptionId, $userId = null) {
        // Vérifier que l'abonnement existe et appartient à l'utilisateur si spécifié
        if ($userId !== null) {
            $subscription = $this->get(['*'], ['id' => $subscriptionId, 'user_id' => $userId]);
            if (empty($subscription)) {
                return false;
            }
        }
        
        return $this->update($subscriptionId, ['status' => 'cancelled']);
    }
    
    /**
     * Vérifie si un utilisateur a un abonnement actif
     * 
     * @param int $userId ID de l'utilisateur
     * @return bool True si l'utilisateur a un abonnement actif, sinon False
     */
    public function hasActiveSubscription($userId) {
        return $this->getActiveSubscription($userId) !== null;
    }
    
    /**
     * Récupère les abonnements expirés qui sont encore marqués comme actifs
     * 
     * @return array Liste des abonnements expirés
     */
    public function getExpiredActiveSubscriptions() {
        $query = "SELECT * FROM `{$this->table}` WHERE `status` = 'active' AND `end_date` < CURDATE()";
        $result = $this->mysqli->query($query);
        
        if (!$result) {
            $this->errorInfo = ['message' => $this->mysqli->error];
            return [];
        }
        
        $subscriptions = [];
        while ($row = $result->fetch_assoc()) {
            $subscriptions[] = $row;
        }
        
        return $subscriptions;
    }
    
    /**
     * Marque les abonnements expirés comme tels
     * 
     * @return int Nombre d'abonnements mis à jour
     */
    public function markExpiredSubscriptions() {
        $query = "UPDATE `{$this->table}` SET `status` = 'expired' WHERE `status` = 'active' AND `end_date` < CURDATE()";
        $result = $this->mysqli->query($query);
        
        if (!$result) {
            $this->errorInfo = ['message' => $this->mysqli->error];
            return 0;
        }
        
        return $this->mysqli->affected_rows;
    }
}