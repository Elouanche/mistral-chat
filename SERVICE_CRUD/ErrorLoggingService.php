<?php
require_once CRUD_PATH . '/ErrorLogsCRUD.php';

/**
 * Service de gestion des erreurs
 * Utilise ErrorLogsCRUD pour les opérations de base de données
 */
class ErrorLoggingService {
    /** @var ErrorLogsCRUD $errorLogsCRUD Instance du CRUD erreurs */
    private $errorLogsCRUD;
    
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        $this->errorLogsCRUD = new ErrorLogsCRUD($mysqli);
    }
    
    /**
     * Enregistre une nouvelle erreur
     * 
     * @param array $data Données de l'erreur (service, level, message, context)
     * @return array Statut de l'opération
     */
    public function logError($data) {
        logInfo("Logging error", [
            'service' => $data['service'] ?? $data['error_data']['service'] ?? 'unknown',
            'level' => $data['level'] ?? $data['error_data']['level'] ?? 'error'
        ]);
        
        $service = $data['service'] ?? $data['error_data']['service'] ?? 'unknown';
        $level = $data['level'] ?? $data['error_data']['level'] ?? 'error';
        $message = $data['message'] ?? $data['error_data']['message'] ?? null;
        $context = $data['context'] ?? $data['error_data']['details'] ?? null;
        $timestamp = $data['timestamp'] ?? $data['error_data']['timestamp'] ?? date('Y-m-d H:i:s');
        
        if (!$message) {
            return ['status' => 'error', 'message' => 'Error message is required'];
        }
        
        // Préparation des données pour l'insertion
        $errorData = [
            'service' => $service,
            'level' => $level,
            'message' => $message,
            'timestamp' => $timestamp
        ];
        
        if ($context) {
            $errorData['context'] = is_array($context) ? json_encode($context) : $context;
        }
        
        // Insertion de l'erreur
        $errorId = $this->errorLogsCRUD->insert($errorData);
        
        if ($errorId) {
            // Si l'erreur est critique, notifier le service de surveillance
            if ($level === 'critical' || $level === 'emergency') {
                return [
                    'status' => 'pending',
                    'service' => 'Monitoring',
                    'action' => 'recordServiceStatus',
                    'data' => [
                        'message' => 'Critical error detected',
                        'service' => $service,
                        'status' => 'critical',
                        'details' => [
                            'error_id' => $errorId,
                            'error_message' => $message,
                            'error_level' => $level
                        ]
                    ]
                ];
            }
            
            return [
                'status' => 'success', 
                'message' => 'Error logged successfully', 
                'data' => ['error_id' => $errorId]
            ];
        }
        
        return ['status' => 'error', 'message' => 'Failed to log error'];
    }
    
    /**
     * Récupère les erreurs avec pagination et filtres
     * 
     * @param array $data Données de filtrage (service, level, start_date, end_date, page, limit)
     * @return array Liste des erreurs
     */
    public function getErrors($data) {
        logInfo("Getting errors", [
            'service' => $data['service'] ?? null,
            'level' => $data['level'] ?? null
        ]);
        
        $service = $data['service'] ?? null;
        $level = $data['level'] ?? null;
        $startDate = $data['start_date'] ?? null;
        $endDate = $data['end_date'] ?? null;
        $page = isset($data['page']) ? (int)$data['page'] : 1;
        $limit = isset($data['limit']) ? (int)$data['limit'] : 50;
        
        // Calcul de l'offset pour la pagination
        $offset = ($page - 1) * $limit;
        
        // Préparation des filtres
        $filters = [];
        
        if ($service) {
            $filters['service'] = $service;
        }
        
        if ($level) {
            $filters['level'] = $level;
        }
        
        if ($startDate && $endDate) {
            $filters['timestamp'] = [
                'operator' => 'BETWEEN',
                'value' => [$startDate . ' 00:00:00', $endDate . ' 23:59:59']
            ];
        } elseif ($startDate) {
            $filters['timestamp'] = [
                'operator' => '>=',
                'value' => $startDate . ' 00:00:00'
            ];
        } elseif ($endDate) {
            $filters['timestamp'] = [
                'operator' => '<=',
                'value' => $endDate . ' 23:59:59'
            ];
        }
        
        // Options pour la requête
        $options = [
            'orderBy' => 'timestamp',
            'orderDirection' => 'DESC',
            'limit' => $limit,
            'offset' => $offset
        ];
        
        // Récupération des erreurs
        $errors = $this->errorLogsCRUD->get(['*'], $filters, $options);
        
        // Décodage du contexte JSON si nécessaire
        foreach ($errors as &$error) {
            if (isset($error['context']) && $this->isJson($error['context'])) {
                $error['context'] = json_decode($error['context'], true);
            }
        }
        
        // Comptage du nombre total d'erreurs pour la pagination
        $total = $this->errorLogsCRUD->count($filters);
        
        return [
            'status' => 'success', 
            'message' => 'Errors retrieved successfully', 
            'data' => [
                'errors' => $errors,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ];
    }
    
    /**
     * Récupère une erreur spécifique
     * 
     * @param array $data Données de l'erreur (error_id)
     * @return array Détails de l'erreur
     */
    public function getError($data) {
        logInfo("Getting specific error", ['error_id' => $data['error_id'] ?? null]);
        
        $errorId = $data['error_id'] ?? null;
        
        if (!$errorId) {
            return ['status' => 'error', 'message' => 'Error ID is required'];
        }
        
        // Récupération de l'erreur
        $error = $this->errorLogsCRUD->find($errorId);
        
        if (!$error) {
            return ['status' => 'error', 'message' => 'Error not found'];
        }
        
        // Décodage du contexte JSON si nécessaire
        if (isset($error['context']) && $this->isJson($error['context'])) {
            $error['context'] = json_decode($error['context'], true);
        }
        
        return [
            'status' => 'success', 
            'message' => 'Error retrieved successfully', 
            'data' => $error
        ];
    }
    
    /**
     * Supprime une erreur
     * 
     * @param array $data Données de l'erreur (error_id)
     * @return array Statut de l'opération
     */
    public function deleteError($data) {
        logInfo("Deleting error", ['error_id' => $data['error_id'] ?? null]);
        
        $errorId = $data['error_id'] ?? null;
        
        if (!$errorId) {
            return ['status' => 'error', 'message' => 'Error ID is required'];
        }
        
        // Vérifier si l'erreur existe
        $error = $this->errorLogsCRUD->find($errorId);
        
        if (!$error) {
            return ['status' => 'error', 'message' => 'Error not found'];
        }
        
        // Suppression de l'erreur
        $result = $this->errorLogsCRUD->delete(['id' => $errorId]);
        
        if ($result) {
            return ['status' => 'success', 'message' => 'Error deleted successfully'];
        }
        
        return ['status' => 'error', 'message' => 'Failed to delete error'];
    }
    
    /**
     * Supprime les erreurs plus anciennes qu'une date donnée
     * 
     * @param array $data Données de suppression (older_than)
     * @return array Statut de l'opération
     */
    public function purgeOldErrors($data) {
        logInfo("Purging old errors", ['older_than' => $data['older_than'] ?? '30 days']);
        
        $olderThan = $data['older_than'] ?? '30 days';
        
        // Calcul de la date limite
        $limitDate = date('Y-m-d H:i:s', strtotime('-' . $olderThan));
        
        // Suppression des erreurs
        $result = $this->errorLogsCRUD->delete([
            'timestamp' => [
                'operator' => '<',
                'value' => $limitDate
            ]
        ]);
        
        return [
            'status' => 'success', 
            'message' => 'Old errors purged successfully', 
            'data' => ['purged_before' => $limitDate]
        ];
    }
    
    /**
     * Vérifie si une chaîne est au format JSON
     * 
     * @param string $string Chaîne à vérifier
     * @return bool True si la chaîne est au format JSON
     */
    private function isJson($string) {
        if (!is_string($string)) {
            return false;
        }
        
        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }
}