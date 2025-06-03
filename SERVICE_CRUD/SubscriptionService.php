<?php
require_once CRUD_PATH . 'UserSubscriptionsCRUD.php';
require_once CRUD_PATH . 'SubscriptionPlansCRUD.php';
require_once CRUD_PATH . 'ApiUsageCRUD.php';

/**
 * Service pour la gestion des abonnements
 */
class SubscriptionService {
    /** @var UserSubscriptionsCRUD $userSubscriptionsCRUD Instance du CRUD pour les abonnements utilisateurs */
    private $userSubscriptionsCRUD;
    
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
        $this->userSubscriptionsCRUD = new UserSubscriptionsCRUD($mysqli);
        $this->plansCRUD = new SubscriptionPlansCRUD($mysqli);
        $this->usageCRUD = new ApiUsageCRUD($mysqli);
        logInfo("SubscriptionService initialized");
    }
    
    /**
     * Récupère tous les plans d'abonnement actifs
     */
    public function getAvailablePlans($data) {
        try {
            $plans = $this->plansCRUD->get(['*'], ['is_active' => 1]);
            
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
     */
    public function getUserSubscription($data) {
        try {
            if (!isset($data['user_id'])) {
                return [
                    'status' => 'error',
                    'message' => "L'ID de l'utilisateur est obligatoire"
                ];
            }            // Récupérer l'abonnement actif
            $subscriptions = $this->userSubscriptionsCRUD->get(['*'], [
                'user_id' => $data['user_id'],
                'status' => 'active'
            ]);
            
            if (empty($subscriptions)) {
                return [
                    'status' => 'success',
                    'data' => null,
                    'message' => "L'utilisateur n'a pas d'abonnement actif"
                ];
            }
            
            $subscription = $subscriptions[0];
            
            // Récupérer les détails du plan
            $plan = $this->plansCRUD->find($subscription['plan_id']);
            $subscription['plan'] = $plan;
            
            // Calculer l'utilisation des tokens
            $currentMonth = date('Y-m-01');
            $nextMonth = date('Y-m-01', strtotime('+1 month'));
            
            $usage = $this->usageCRUD->get(
                ['SUM(input_tokens + output_tokens) as total_tokens'],
                [
                    'user_id' => $data['user_id'],
                    'usage_date >=' => $currentMonth,
                    'usage_date <' => $nextMonth
                ]
            );
              $tokensUsed = !empty($usage) ? (int)$usage[0]['total_tokens'] : 0;
            
            // Pour un plan gratuit, utiliser max_messages_per_day comme limite de base
            $tokensLimit = isset($plan['token_limit']) ? $plan['token_limit'] : ($plan['max_messages_per_day'] * 100);
            
            $subscription['usage'] = [
                'tokens_used' => $tokensUsed,
                'tokens_limit' => $tokensLimit,
                'tokens_remaining' => max(0, $tokensLimit - $tokensUsed),
                'max_messages_per_day' => $plan['max_messages_per_day'],
                'max_conversations' => $plan['max_conversations']
            ];
            
            return [
                'status' => 'success',
                'data' => $subscription
            ];
        } catch (Exception $e) {
            logError("Erreur lors de la récupération de l'abonnement", ['error' => $e->getMessage()]);
            return [
                'status' => 'error',
                'message' => "Erreur lors de la récupération de l'abonnement"
            ];
        }
    }
    
    /**
     * Crée un nouvel abonnement
     */
    public function createSubscription($data) {
        try {
            if (!isset($data['user_id']) || !isset($data['plan_id'])) {
                return [
                    'status' => 'error',
                    'message' => "L'ID de l'utilisateur et l'ID du plan sont obligatoires"
                ];
            }
            
            // Vérifier si le plan existe et est actif
            $plan = $this->plansCRUD->find($data['plan_id']);
            if (!$plan || !$plan['is_active']) {
                return [
                    'status' => 'error',
                    'message' => "Le plan sélectionné n'existe pas ou n'est pas actif"
                ];
            }
            
            // Désactiver les abonnements existants
            $this->userSubscriptionsCRUD->update(
                ['status' => 'cancelled'],
                [
                    'user_id' => $data['user_id'],
                    'status' => 'active'
                ]
            );            // Créer le nouvel abonnement
            $startedAt = date('Y-m-d H:i:s');
            // Par défaut, les plans gratuits n'expirent pas (NULL), les plans payants durent 30 jours
            $expiresAt = (floatval($plan['price']) > 0) 
                ? date('Y-m-d H:i:s', strtotime('+30 days'))
                : null;
            
            $subscriptionId = $this->userSubscriptionsCRUD->insert([
                'user_id' => $data['user_id'],
                'plan_id' => $data['plan_id'],
                'status' => 'active',
                'started_at' => $startedAt,
                'expires_at' => $expiresAt
            ]);
            
            return [
                'status' => 'success',
                'data' => [
                    'subscription_id' => $subscriptionId,
                    'started_at' => $startedAt,
                    'expires_at' => $expiresAt,
                    'plan' => $plan
                ],
                'message' => "Abonnement créé avec succès"
            ];
        } catch (Exception $e) {
            logError("Erreur lors de la création de l'abonnement", ['error' => $e->getMessage()]);
            return [
                'status' => 'error',
                'message' => "Erreur lors de la création de l'abonnement"
            ];
        }
    }
    
    /**
     * Annule un abonnement
     */
    public function cancelSubscription($data) {
        try {
            if (!isset($data['subscription_id']) || !isset($data['user_id'])) {
                return [
                    'status' => 'error',
                    'message' => "L'ID de l'abonnement et l'ID de l'utilisateur sont obligatoires"
                ];
            }
            
            $result = $this->userSubscriptionsCRUD->update(
                ['status' => 'cancelled'],
                [
                    'id' => $data['subscription_id'],
                    'user_id' => $data['user_id']
                ]
            );
            
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
            logError("Erreur lors de l'annulation de l'abonnement", ['error' => $e->getMessage()]);
            return [
                'status' => 'error',
                'message' => "Erreur lors de l'annulation de l'abonnement"
            ];
        }
    }

    /**
     * Vérifie si un utilisateur peut utiliser un modèle spécifique
     */
    public function canUserUseModel($userId, $modelName) {
        // Récupérer l'abonnement actif
        $subscriptions = $this->userSubscriptionsCRUD->get(['*'], [
            'user_id' => $userId,
            'status' => 'active',
            'expires_at > NOW()' => null
        ]);

        if (empty($subscriptions)) {
            // Vérifier le plan gratuit
            $freePlans = $this->plansCRUD->get(['*'], [
                'price' => 0,
                'is_active' => 1
            ]);
            
            if (empty($freePlans)) return false;
            
            // Vérifier si le modèle est disponible dans le plan gratuit
           $planModels = $this->plansCRUD->get(
                ['subscription_plans.*', 'plan_models.model_name'],
                [
                    'subscription_plans.id' => $freePlans[0]['id'],
                    'plan_models.model_name' => $modelName
                ],
                [
                    'joins' => [
                        [
                            'table' => 'plan_models',
                            'type' => 'INNER',
                            'conditions' => ['subscription_plans.id = plan_models.plan_id']
                        ]
                    ]
                ]
            );
            
            return !empty($planModels);
        }

        // Vérifier si le modèle est disponible dans le plan actif
        $planModels = $this->plansCRUD->get(['*'], [
            'plan_id' => $subscriptions[0]['plan_id'],
            'model_name' => $modelName
        ], ['joins' => [
            ['table' => 'plan_models', 'on' => 'subscription_plans.id = plan_models.plan_id']
        ]]);
        
        return !empty($planModels);
    }

    /**
     * Récupère un plan d'abonnement par son ID
     * 
     * @param array $data Les données avec l'ID du plan
     * @return array La réponse avec le statut et les données
     */
    public function getById($data) {
        try {
            if (!isset($data['id']) || !is_numeric($data['id'])) {
                return [
                    'status' => 'error',
                    'message' => "L'ID du plan est obligatoire et doit être numérique"
                ];
            }

            // Récupérer le plan et s'assurer qu'il est actif
            $plan = $this->plansCRUD->find($data['id']);
            
            // Logger la réponse pour le debug
            logInfo("Récupération du plan", ['plan_id' => $data['id'], 'plan' => $plan]);
            
            if (!$plan) {
                return [
                    'status' => 'error',
                    'message' => "Plan non trouvé",
                    'code' => 'PLAN_NOT_FOUND'
                ];
            }

            if (!$plan['is_active']) {
                return [
                    'status' => 'error',
                    'message' => "Ce plan n'est plus disponible",
                    'code' => 'PLAN_INACTIVE'
                ];
            }

            // Vérifier que les champs requis sont présents
            if (empty($plan['name']) || !isset($plan['price'])) {
                return [
                    'status' => 'error',
                    'message' => "Les données du plan sont incomplètes",
                    'code' => 'INVALID_PLAN_DATA'
                ];
            }

            return [
                'status' => 'success',
                'data' => $plan
            ];
            
        } catch (Exception $e) {
            logError("Erreur lors de la récupération du plan", [
                'error' => $e->getMessage(),
                'plan_id' => $data['id']
            ]);
            return [
                'status' => 'error',
                'message' => "Erreur lors de la récupération du plan",
                'code' => 'SERVER_ERROR'
            ];
        }
    }
}