<?php

/**
 * Classe CRUD pour la gestion des plans d'abonnement
 */
class SubscriptionPlansCRUD extends BaseCRUD {
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        parent::__construct($mysqli, 'subscription_plans', 'id');
    }
    
    /**
     * Récupère tous les plans d'abonnement actifs
     * 
     * @return array Liste des plans d'abonnement actifs
     */
    public function getActivePlans() {
        return $this->get(['*'], ['is_active' => true], ['order_by' => ['price' => 'ASC']]);
    }
    
    /**
     * Récupère un plan d'abonnement par son ID
     * 
     * @param int $planId ID du plan
     * @return array|null Données du plan ou null si non trouvé
     */
    public function getPlan($planId) {
        $results = $this->get(['*'], ['id' => $planId]);
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Crée un nouveau plan d'abonnement
     * 
     * @param array $planData Données du plan
     * @return int|bool ID du plan créé ou false en cas d'échec
     */
    public function createPlan($planData) {
        // Convertir les features en JSON si nécessaire
        if (isset($planData['features']) && is_array($planData['features'])) {
            $planData['features'] = json_encode($planData['features']);
        }
        
        return $this->insert($planData);
    }
    
    /**
     * Met à jour un plan d'abonnement
     * 
     * @param int $planId ID du plan
     * @param array $planData Données du plan à mettre à jour
     * @return bool Succès de l'opération
     */
    public function updatePlan($planId, $planData) {
        // Convertir les features en JSON si nécessaire
        if (isset($planData['features']) && is_array($planData['features'])) {
            $planData['features'] = json_encode($planData['features']);
        }
        
        return $this->update($planId, $planData);
    }
    
    /**
     * Active ou désactive un plan d'abonnement
     * 
     * @param int $planId ID du plan
     * @param bool $isActive État d'activation
     * @return bool Succès de l'opération
     */
    public function setPlanStatus($planId, $isActive) {
        return $this->update($planId, ['is_active' => $isActive ? 1 : 0]);
    }
    
    /**
     * Récupère les modèles disponibles pour un plan spécifique
     * 
     * @param int $planId ID du plan
     * @return array Liste des noms de modèles disponibles pour ce plan
     */
    public function getPlanModels($planId) {
        $plan = $this->getPlan($planId);
        if (!$plan) {
            return [];
        }
        
        $features = json_decode($plan['features'], true);
        return $features['models'] ?? [];
    }
    
    /**
     * Vérifie si un modèle est disponible pour un plan spécifique
     * 
     * @param int $planId ID du plan
     * @param string $modelName Nom du modèle à vérifier
     * @return bool True si le modèle est disponible, sinon False
     */
    public function isModelAvailableForPlan($planId, $modelName) {
        $models = $this->getPlanModels($planId);
        return in_array($modelName, $models);
    }
    
    /**
     * Récupère la limite de tokens pour un plan spécifique
     * 
     * @param int $planId ID du plan
     * @return int Limite de tokens ou 0 si le plan n'existe pas
     */
    public function getPlanTokenLimit($planId) {
        $plan = $this->getPlan($planId);
        return $plan ? (int)$plan['token_limit'] : 0;
    }
}