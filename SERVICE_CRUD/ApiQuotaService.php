<?php
require_once CRUD_PATH . '/ApiQuotasCRUD.php';
require_once CRUD_PATH . '/AiRequestsCRUD.php';
require_once CRUD_PATH . '/ApiUsageCRUD.php';

/**
 * Service pour la gestion des quotas d'utilisation de l'API
 */
class ApiQuotaService {
    /** @var ApiQuotasCRUD $quotasCRUD Instance du CRUD pour les quotas */
    private $quotasCRUD;
    
    /** @var AiRequestsCRUD $requestsCRUD Instance du CRUD pour les requêtes */
    private $requestsCRUD;
    
    /** @var ApiUsageCRUD $usageCRUD Instance du CRUD pour l'utilisation */
    private $usageCRUD;
    
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        $this->quotasCRUD = new ApiQuotasCRUD($mysqli);
        $this->requestsCRUD = new AiRequestsCRUD($mysqli);
        $this->usageCRUD = new ApiUsageCRUD($mysqli);
        logInfo("ApiQuotaService initialized");
    }
    
    /**
     * Vérifie si un utilisateur peut effectuer une requête API
     * 
     * @param array $data Données avec l'ID de l'utilisateur
     * @return array Résultat de la vérification
     */
    public function checkUserQuota($data) {
        try {
            // Vérifier les champs obligatoires
            if (!isset($data['user_id'])) {
                return [
                    'status' => 'error',
                    'message' => "L'ID de l'utilisateur est obligatoire"
                ];
            }
            
            // Récupérer les quotas de l'utilisateur
            $quota = $this->quotasCRUD->getUserQuota($data['user_id']);
            
            // Si l'utilisateur n'a pas de quotas définis, créer des quotas par défaut
            if (!$quota) {
                $defaultQuota = [
                    'user_id' => $data['user_id'],
                    'max_tokens_per_day' => 10000,
                    'max_requests_per_minute' => 10,
                    'max_monthly_cost' => 10.00,
                    'is_unlimited' => false
                ];
                
                $quotaId = $this->quotasCRUD->createQuota($defaultQuota);
                $quota = $this->quotasCRUD->getUserQuota($data['user_id']);
                
                if (!$quota) {
                    return [
                        'status' => 'error',
                        'message' => "Erreur lors de la création des quotas par défaut"
                    ];
                }
            }
            
            // Si l'utilisateur a un quota illimité, autoriser la requête
            if ($quota['is_unlimited']) {
                return [
                    'status' => 'success',
                    'data' => [
                        'can_make_request' => true,
                        'quota' => $quota
                    ]
                ];
            }
            
            // Vérifier le nombre de requêtes par minute
            $now = date('Y-m-d H:i:s');
            $oneMinuteAgo = date('Y-m-d H:i:s', strtotime('-1 minute'));
            $requestsLastMinute = $this->requestsCRUD->countUserRequests($data['user_id'], $oneMinuteAgo, $now);
            
            if ($requestsLastMinute >= $quota['max_requests_per_minute']) {
                return [
                    'status' => 'error',
                    'message' => "Limite de requêtes par minute atteinte. Veuillez réessayer dans quelques instants.",
                    'data' => [
                        'can_make_request' => false,
                        'reason' => 'rate_limit_exceeded',
                        'quota' => $quota,
                        'current_usage' => [
                            'requests_last_minute' => $requestsLastMinute
                        ]
                    ]
                ];
            }
            
            // Vérifier le nombre de tokens par jour
            $today = date('Y-m-d 00:00:00');
            $tomorrow = date('Y-m-d 23:59:59');
            $tokensToday = $this->requestsCRUD->calculateUserTokens($data['user_id'], $today, $tomorrow);
            $totalTokensToday = $tokensToday['total_prompt'] + $tokensToday['total_completion'];
            
            if ($totalTokensToday >= $quota['max_tokens_per_day']) {
                return [
                    'status' => 'error',
                    'message' => "Limite de tokens quotidienne atteinte. Veuillez réessayer demain.",
                    'data' => [
                        'can_make_request' => false,
                        'reason' => 'daily_token_limit_exceeded',
                        'quota' => $quota,
                        'current_usage' => [
                            'tokens_today' => $totalTokensToday
                        ]
                    ]
                ];
            }
            
            // Vérifier le coût mensuel
            $firstDayOfMonth = date('Y-m-01 00:00:00');
            $lastDayOfMonth = date('Y-m-t 23:59:59');
            $monthlyCost = $this->usageCRUD->calculateUserCost($data['user_id'], $firstDayOfMonth, $lastDayOfMonth);
            
            if ($monthlyCost >= $quota['max_monthly_cost']) {
                return [
                    'status' => 'error',
                    'message' => "Limite de coût mensuel atteinte. Veuillez mettre à niveau votre abonnement.",
                    'data' => [
                        'can_make_request' => false,
                        'reason' => 'monthly_cost_limit_exceeded',
                        'quota' => $quota,
                        'current_usage' => [
                            'monthly_cost' => $monthlyCost
                        ]
                    ]
                ];
            }
            
            // Toutes les vérifications sont passées, l'utilisateur peut effectuer la requête
            return [
                'status' => 'success',
                'data' => [
                    'can_make_request' => true,
                    'quota' => $quota,
                    'current_usage' => [
                        'requests_last_minute' => $requestsLastMinute,
                        'tokens_today' => $totalTokensToday,
                        'monthly_cost' => $monthlyCost
                    ]
                ]
            ];
        } catch (Exception $e) {
            logError("Erreur lors de la vérification des quotas", ['error' => $e->getMessage(), 'user_id' => $data['user_id'] ?? null]);
            return [
                'status' => 'error',
                'message' => "Erreur lors de la vérification des quotas"
            ];
        }
    }
    
    /**
     * Met à jour les quotas d'un utilisateur
     * 
     * @param array $data Données avec l'ID de l'utilisateur et les nouveaux quotas
     * @return array Résultat de l'opération
     */
    public function updateUserQuota($data) {
        try {
            // Vérifier les champs obligatoires
            if (!isset($data['user_id'])) {
                return [
                    'status' => 'error',
                    'message' => "L'ID de l'utilisateur est obligatoire"
                ];
            }
            
            // Récupérer les quotas actuels de l'utilisateur
            $currentQuota = $this->quotasCRUD->getUserQuota($data['user_id']);
            
            // Si l'utilisateur n'a pas de quotas définis, créer des quotas par défaut
            if (!$currentQuota) {
                $defaultQuota = [
                    'user_id' => $data['user_id'],
                    'max_tokens_per_day' => 10000,
                    'max_requests_per_minute' => 10,
                    'max_monthly_cost' => 10.00,
                    'is_unlimited' => false
                ];
                
                $quotaId = $this->quotasCRUD->createQuota($defaultQuota);
                $currentQuota = $this->quotasCRUD->getUserQuota($data['user_id']);
                
                if (!$currentQuota) {
                    return [
                        'status' => 'error',
                        'message' => "Erreur lors de la création des quotas par défaut"
                    ];
                }
            }
            
            // Préparer les données à mettre à jour
            $updateData = [];
            
            if (isset($data['max_tokens_per_day'])) {
                $updateData['max_tokens_per_day'] = (int)$data['max_tokens_per_day'];
            }
            
            if (isset($data['max_requests_per_minute'])) {
                $updateData['max_requests_per_minute'] = (int)$data['max_requests_per_minute'];
            }
            
            if (isset($data['max_monthly_cost'])) {
                $updateData['max_monthly_cost'] = (float)$data['max_monthly_cost'];
            }
            
            if (isset($data['is_unlimited'])) {
                $updateData['is_unlimited'] = (bool)$data['is_unlimited'] ? 1 : 0;
            }
            
            // Si aucune donnée à mettre à jour, retourner une erreur
            if (empty($updateData)) {
                return [
                    'status' => 'error',
                    'message' => "Aucune donnée à mettre à jour"
                ];
            }
            
            // Mettre à jour les quotas
            $success = $this->quotasCRUD->updateQuota($currentQuota['id'], $updateData);
            
            if (!$success) {
                return [
                    'status' => 'error',
                    'message' => "Erreur lors de la mise à jour des quotas"
                ];
            }
            
            // Récupérer les quotas mis à jour
            $updatedQuota = $this->quotasCRUD->getUserQuota($data['user_id']);
            
            return [
                'status' => 'success',
                'message' => "Quotas mis à jour avec succès",
                'data' => $updatedQuota
            ];
        } catch (Exception $e) {
            logError("Erreur lors de la mise à jour des quotas", ['error' => $e->getMessage(), 'user_id' => $data['user_id'] ?? null]);
            return [
                'status' => 'error',
                'message' => "Erreur lors de la mise à jour des quotas"
            ];
        }
    }
    
    /**
     * Récupère les quotas d'un utilisateur
     * 
     * @param array $data Données avec l'ID de l'utilisateur
     * @return array Résultat de l'opération
     */
    public function getUserQuota($data) {
        try {
            // Vérifier les champs obligatoires
            if (!isset($data['user_id'])) {
                return [
                    'status' => 'error',
                    'message' => "L'ID de l'utilisateur est obligatoire"
                ];
            }
            
            // Récupérer les quotas de l'utilisateur
            $quota = $this->quotasCRUD->getUserQuota($data['user_id']);
            
            // Si l'utilisateur n'a pas de quotas définis, créer des quotas par défaut
            if (!$quota) {
                $defaultQuota = [
                    'user_id' => $data['user_id'],
                    'max_tokens_per_day' => 10000,
                    'max_requests_per_minute' => 10,
                    'max_monthly_cost' => 10.00,
                    'is_unlimited' => false
                ];
                
                $quotaId = $this->quotasCRUD->createQuota($defaultQuota);
                $quota = $this->quotasCRUD->getUserQuota($data['user_id']);
                
                if (!$quota) {
                    return [
                        'status' => 'error',
                        'message' => "Erreur lors de la création des quotas par défaut"
                    ];
                }
            }
            
            // Récupérer l'utilisation actuelle
            $now = date('Y-m-d H:i:s');
            $oneMinuteAgo = date('Y-m-d H:i:s', strtotime('-1 minute'));
            $requestsLastMinute = $this->requestsCRUD->countUserRequests($data['user_id'], $oneMinuteAgo, $now);
            
            $today = date('Y-m-d 00:00:00');
            $tomorrow = date('Y-m-d 23:59:59');
            $tokensToday = $this->requestsCRUD->calculateUserTokens($data['user_id'], $today, $tomorrow);
            $totalTokensToday = $tokensToday['total_prompt'] + $tokensToday['total_completion'];
            
            $firstDayOfMonth = date('Y-m-01 00:00:00');
            $lastDayOfMonth = date('Y-m-t 23:59:59');
            $monthlyCost = $this->usageCRUD->calculateUserCost($data['user_id'], $firstDayOfMonth, $lastDayOfMonth);
            
            return [
                'status' => 'success',
                'data' => [
                    'quota' => $quota,
                    'current_usage' => [
                        'requests_last_minute' => $requestsLastMinute,
                        'tokens_today' => $totalTokensToday,
                        'monthly_cost' => $monthlyCost
                    ]
                ]
            ];
        } catch (Exception $e) {
            logError("Erreur lors de la récupération des quotas", ['error' => $e->getMessage(), 'user_id' => $data['user_id'] ?? null]);
            return [
                'status' => 'error',
                'message' => "Erreur lors de la récupération des quotas"
            ];
        }
    }
}