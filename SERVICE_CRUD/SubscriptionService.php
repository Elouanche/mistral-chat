<?php
require_once CRUD_PATH . '/SubscriptionsCRUD.php';
require_once CRUD_PATH . '/SubscriptionPlansCRUD.php';
require_once CRUD_PATH . '/ApiUsageCRUD.php';

/**
 * Service pour la gestion des abonnements
 */
class SubscriptionService {
    /** @var SubscriptionsCRUD $subscriptionsCRUD Instance du CRUD pour les abonnements */
    private $subscriptionsCRUD;
    
    /** @var SubscriptionPlansCRUD $plansCRUD Instance du CRUD pour les plans d'abonnement */
    private $plansCRUD;
    
    /** @var ApiUsageCRUD $usageCRUD Instance du CRUD pour l'utilisation de l'API */
    private $usageCRUD;
    
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        $this->subscriptionsCRUD = new SubscriptionsCRUD($mysqli);
        $this->plansCRUD = new SubscriptionPlansCRUD($mysqli);
        $this->usageCRUD = new ApiUsageCRUD($mysqli);
        logInfo("SubscriptionService initialized");
    }
    
    /**
     * Récupère tous les plans d'abonnement actifs
     * 
     * @return array Résultat de l'opération
     */
    public function getAvailablePlans() {
        try {
            $plans = $this->plansCRUD->getActivePlans();
            
            return [
                'status' => 'success',
                'data' => $plans
            ];
        } catch (Exception $e) {
            logError("Erreur lors de la récupération des plans d'abonnement", ['error' => $e->getMessage()]);
            return [
                'status' => 'error',
                'message' => "Erreur lors de la récupération des plans d'abonnement"
            ];
        }
    }
    
    /**
     * Récupère l'abonnement actif d'un utilisateur
     * 
     * @param array $data Données avec l'ID de l'utilisateur
     * @return array Résultat de l'opération
     */
    public function getUserSubscription($data) {
        try {
            // Vérifier les champs obligatoires
            if (!isset($data['user_id'])) {
                return [
                    'status' => 'error',
                    'message' => "L'ID de l'utilisateur est obligatoire"
                ];
            }
            
            $subscription = $this->subscriptionsCRUD->getActiveSubscription($data['user_id']);
            
            if (!$subscription) {
                return [
                    'status' => 'success',
                    'data' => null,
                    'message' => "L'utilisateur n'a pas d'abonnement actif"
                ];
            }
            
            // Récupérer les détails du plan
            $plan = $this->plansCRUD->getPlan($subscription['plan_id']);
            $subscription['plan'] = $plan;
            
            // Récupérer l'utilisation actuelle
            $currentMonth = date('Y-m-01');
            $nextMonth = date('Y-m-01', strtotime('+1 month'));
            $usage = $this->usageCRUD->getUserTokensUsedInPeriod($data['user_id'], $currentMonth, $nextMonth);
            
            $subscription['usage'] = [
                'tokens_used' => $usage,
                'tokens_limit' => $plan['token_limit'],
                'tokens_remaining' => max(0, $plan['token_limit'] - $usage)
            ];
            
            return [
                'status' => 'success',
                'data' => $subscription
            ];
        } catch (Exception $e) {
            logError("Erreur lors de la récupération de l'abonnement", ['error' => $e->getMessage(), 'user_id' => $data['user_id'] ?? null]);
            return [
                'status' => 'error',
                'message' => "Erreur lors de la récupération de l'abonnement"
            ];
        }
    }
    
    /**
     * Crée un nouvel abonnement
     * 
     * @param array $data Données de l'abonnement
     * @return array Résultat de l'opération
     */
    public function createSubscription($data) {
        try {
            // Vérifier les champs obligatoires
            if (!isset($data['user_id']) || !isset($data['plan_id'])) {
                return [
                    'status' => 'error',
                    'message' => "L'ID de l'utilisateur et l'ID du plan sont obligatoires"
                ];
            }
            
            // Vérifier si le plan existe et est actif
            $plan = $this->plansCRUD->getPlan($data['plan_id']);
            if (!$plan || !$plan['is_active']) {
                return [
                    'status' => 'error',
                    'message' => "Le plan sélectionné n'existe pas ou n'est pas actif"
                ];
            }
            
            // Vérifier si l'utilisateur a déjà un abonnement actif
            $existingSubscription = $this->subscriptionsCRUD->getActiveSubscription($data['user_id']);
            if ($existingSubscription) {
                // Annuler l'abonnement existant
                $this->subscriptionsCRUD->cancelSubscription($existingSubscription['id']);
            }
            
            // Calculer les dates de début et de fin
            $startDate = $data['start_date'] ?? date('Y-m-d');
            $endDate = date('Y-m-d', strtotime($startDate . ' + ' . $plan['duration_days'] . ' days'));
            
            // Créer le nouvel abonnement
            $subscriptionData = [
                'user_id' => $data['user_id'],
                'plan_id' => $data['plan_id'],
                'status' => 'active',
                'start_date' => $startDate,
                'end_date' => $endDate
            ];
            
            $subscriptionId = $this->subscriptionsCRUD->createSubscription($subscriptionData);
            
            if (!$subscriptionId) {
                return [
                    'status' => 'error',
                    'message' => "Erreur lors de la création de l'abonnement"
                ];
            }
            
            return [
                'status' => 'success',
                'data' => [
                    'subscription_id' => $subscriptionId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'plan' => $plan
                ],
                'message' => "Abonnement créé avec succès"
            ];
        } catch (Exception $e) {
            logError("Erreur lors de la création de l'abonnement", ['error' => $e->getMessage(), 'user_id' => $data['user_id'] ?? null]);
            return [
                'status' => 'error',
                'message' => "Erreur lors de la création de l'abonnement"
            ];
        }
    }
    
    /**
     * Annule un abonnement
     * 
     * @param array $data Données avec l'ID de l'abonnement et l'ID de l'utilisateur
     * @return array Résultat de l'opération
     */
    public function cancelSubscription($data) {
        try {
            // Vérifier les champs obligatoires
            if (!isset($data['subscription_id']) || !isset($data['user_id'])) {
                return [
                    'status' => 'error',
                    'message' => "L'ID de l'abonnement et l'ID de l'utilisateur sont obligatoires"
                ];
            }
            
            $result = $this->subscriptionsCRUD->cancelSubscription($data['subscription_id'], $data['user_id']);
            
            if (!$result) {
                return [
                    'status' => 'error',
                    'message' => "Erreur lors de l'annulation de l'abonnement ou abonnement non trouvé"
                ];
            }
            
            return [
                'status' => 'success',
                'message' => "Abonnement annulé avec succès"
            ];
        } catch (Exception $e) {
            logError("Erreur lors de l'annulation de l'abonnement", ['error' => $e->getMessage(), 'subscription_id' => $data['subscription_id'] ?? null]);
            return [
                'status' => 'error',
                'message' => "Erreur lors de l'annulation de l'abonnement"
            ];
        }
    }
    
    /**
     * Vérifie si un utilisateur peut utiliser un modèle spécifique
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $modelName Nom du modèle
     * @return bool True si l'utilisateur peut utiliser le modèle, sinon False
     */
    public function canUserUseModel($userId, $modelName) {
        $subscription = $this->subscriptionsCRUD->getActiveSubscription($userId);
        if (!$subscription) {
            // Si pas d'abonnement, vérifier si le modèle est disponible dans le plan gratuit
            $freePlans = $this->plansCRUD->get(['*'], ['price' => 0, 'is_active' => true]);
            if (empty($freePlans)) {
                return false;
            }
            
            return $this->plansCRUD->isModelAvailableForPlan($freePlans[0]['id'], $modelName);
        }
        
        return $this->plansCRUD->isModelAvailableForPlan($subscription['plan_id'], $modelName);
    }
    
    /**
     * Vérifie si un utilisateur a dépassé sa limite de tokens
     * 
     * @param int $userId ID de l'utilisateur
     * @return bool True si l'utilisateur a dépassé sa limite, sinon False
     */
    public function hasUserExceededTokenLimit($userId) {
        $subscription = $this->subscriptionsCRUD->getActiveSubscription($userId);
        if (!$subscription) {
            // Si pas d'abonnement, vérifier la limite du plan gratuit
            $freePlans = $this->plansCRUD->get(['*'], ['price' => 0, 'is_active' => true]);
            if (empty($freePlans)) {
                return true; // Pas de plan gratuit, donc limite dépassée
            }
            
            $tokenLimit = $freePlans[0]['token_limit'];
        } else {
            $tokenLimit = $this->plansCRUD->getPlanTokenLimit($subscription['plan_id']);
        }
        
        // Récupérer l'utilisation actuelle
        $currentMonth = date('Y-m-01');
        $nextMonth = date('Y-m-01', strtotime('+1 month'));
        $tokensUsed = $this->usageCRUD->getUserTokensUsedInPeriod($userId, $currentMonth, $nextMonth);
        
        return $tokensUsed >= $tokenLimit;
    }
    
    /**
     * Récupère l'historique des abonnements d'un utilisateur
     * 
     * @param array $data Données avec l'ID de l'utilisateur
     * @return array Résultat de l'opération
     */
    public function getUserSubscriptionHistory($data) {
        try {
            // Vérifier les champs obligatoires
            if (!isset($data['user_id'])) {
                return [
                    'status' => 'error',
                    'message' => "L'ID de l'utilisateur est obligatoire"
                ];
            }
            
            $subscriptions = $this->subscriptionsCRUD->getUserSubscriptions($data['user_id']);
            
            // Ajouter les détails du plan pour chaque abonnement
            foreach ($subscriptions as &$subscription) {
                $plan = $this->plansCRUD->getPlan($subscription['plan_id']);
                $subscription['plan'] = $plan;
            }
            
            return [
                'status' => 'success',
                'data' => $subscriptions
            ];
        } catch (Exception $e) {
            logError("Erreur lors de la récupération de l'historique des abonnements", ['error' => $e->getMessage(), 'user_id' => $data['user_id'] ?? null]);
            return [
                'status' => 'error',
                'message' => "Erreur lors de la récupération de l'historique des abonnements"
            ];
        }
    }
}