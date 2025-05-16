<?php
require_once CRUD_PATH . '/MonitoringCRUD.php';

/**
 * Service de surveillance du système
 * Utilise MonitoringCRUD pour les opérations de base de données
 */
class MonitoringService {
    /** @var MonitoringCRUD $monitoringCRUD Instance du CRUD monitoring */
    private $monitoringCRUD;
    
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        $this->monitoringCRUD = new MonitoringCRUD($mysqli);
    }
    
    /**
     * Enregistre une nouvelle entrée de surveillance
     * 
     * @param array $data Données de surveillance (service, status, response_time, etc.)
     * @return array Statut de l'opération
     */
    public function recordServiceStatus($data) {
        logInfo("Recording service status", [
            'service' => $data['service'] ?? null,
            'status' => $data['status'] ?? null
        ]);
        
        $service = $data['service'] ?? null;
        $status = $data['status'] ?? null;
        $responseTime = $data['response_time'] ?? null;
        $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
        $details = $data['details'] ?? null;
        
        if (!$service || !$status) {
            return ['status' => 'error', 'message' => 'Service name and status are required'];
        }
        
        // Préparation des données pour l'insertion
        $monitoringData = [
            'service' => $service,
            'status' => $status,
            'timestamp' => $timestamp
        ];
        
        if ($responseTime !== null) {
            $monitoringData['response_time'] = $responseTime;
        }
        
        if ($details !== null) {
            $monitoringData['details'] = is_array($details) ? json_encode($details) : $details;
        }
        
        // Insertion de l'entrée de surveillance
        $monitoringId = $this->monitoringCRUD->insert($monitoringData);
        
        if ($monitoringId) {
            // Si le statut est 'error' ou 'critical', notifier le service d'erreur
            if ($status === 'error' || $status === 'critical') {
                return [
                    'status' => 'pending',
                    'service' => 'ErrorLogging',
                    'action' => 'logError',
                    'data' => [
                        'message' => 'Service monitoring detected an issue',
                        'error_data' => [
                            'service' => $service,
                            'status' => $status,
                            'details' => $details,
                            'timestamp' => $timestamp
                        ]
                    ]
                ];
            }
            
            return [
                'status' => 'success', 
                'message' => 'Service status recorded successfully', 
                'data' => ['monitoring_id' => $monitoringId]
            ];
        }
        
        return ['status' => 'error', 'message' => 'Failed to record service status'];
    }
    
    /**
     * Récupère le statut actuel d'un service
     * 
     * @param array $data Données du service (service)
     * @return array Statut du service
     */
    public function getServiceStatus($data) {
        logInfo("Getting service status", ['service' => $data['service'] ?? null]);
        
        $service = $data['service'] ?? null;
        
        if (!$service) {
            return ['status' => 'error', 'message' => 'Service name is required'];
        }
        
        // Récupération de la dernière entrée pour ce service
        $entries = $this->monitoringCRUD->get(
            ['*'],
            ['service' => $service],
            ['orderBy' => 'timestamp', 'orderDirection' => 'DESC', 'limit' => 1]
        );
        
        if (empty($entries)) {
            return ['status' => 'error', 'message' => 'No monitoring data found for this service'];
        }
        
        $serviceStatus = $entries[0];
        
        // Décodage des détails si nécessaire
        if (isset($serviceStatus['details']) && $this->isJson($serviceStatus['details'])) {
            $serviceStatus['details'] = json_decode($serviceStatus['details'], true);
        }
        
        return [
            'status' => 'success', 
            'message' => 'Service status retrieved successfully', 
            'data' => $serviceStatus
        ];
    }
    
    /**
     * Récupère le statut de tous les services
     * 
     * @param array $data Données de filtrage (optionnel)
     * @return array Statut de tous les services
     */
    public function getAllServicesStatus($data) {
        logInfo("Getting all services status");
        
        // Récupération de tous les services distincts
        $services = $this->monitoringCRUD->query(
            "SELECT DISTINCT service FROM monitoring",
            [],
            ''
        );
        
        if (empty($services)) {
            return ['status' => 'error', 'message' => 'No monitoring data found'];
        }
        
        $allStatuses = [];
        
        // Pour chaque service, récupérer son dernier statut
        foreach ($services as $serviceEntry) {
            $serviceName = $serviceEntry['service'];
            $status = $this->getServiceStatus(['service' => $serviceName]);
            
            if ($status['status'] === 'success') {
                $allStatuses[$serviceName] = $status['data'];
            }
        }
        
        return [
            'status' => 'success', 
            'message' => 'All services status retrieved successfully', 
            'data' => $allStatuses
        ];
    }
    
    /**
     * Récupère l'historique de surveillance d'un service
     * 
     * @param array $data Données du service (service, start_date, end_date, limit)
     * @return array Historique de surveillance
     */
    public function getServiceHistory($data) {
        logInfo("Getting service history", [
            'service' => $data['service'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null
        ]);
        
        $service = $data['service'] ?? null;
        $startDate = $data['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
        $endDate = $data['end_date'] ?? date('Y-m-d');
        $limit = isset($data['limit']) ? (int)$data['limit'] : 100;
        
        if (!$service) {
            return ['status' => 'error', 'message' => 'Service name is required'];
        }
        
        // Préparation des filtres
        $filters = [
            'service' => $service,
            'timestamp' => [
                'operator' => 'BETWEEN',
                'value' => [$startDate . ' 00:00:00', $endDate . ' 23:59:59']
            ]
        ];
        
        // Récupération des entrées
        $history = $this->monitoringCRUD->get(
            ['*'],
            $filters,
            ['orderBy' => 'timestamp', 'orderDirection' => 'DESC', 'limit' => $limit]
        );
        
        // Décodage des détails si nécessaire
        foreach ($history as &$entry) {
            if (isset($entry['details']) && $this->isJson($entry['details'])) {
                $entry['details'] = json_decode($entry['details'], true);
            }
        }
        
        return [
            'status' => 'success', 
            'message' => 'Service history retrieved successfully', 
            'data' => [
                'service' => $service,
                'history' => $history,
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate
                ]
            ]
        ];
    }
    
    /**
     * Calcule les statistiques de performance d'un service
     * 
     * @param array $data Données du service (service, start_date, end_date)
     * @return array Statistiques de performance
     */
    public function calculateServicePerformance($data) {
        logInfo("Calculating service performance", [
            'service' => $data['service'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null
        ]);
        
        $service = $data['service'] ?? null;
        $startDate = $data['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $data['end_date'] ?? date('Y-m-d');
        
        if (!$service) {
            return ['status' => 'error', 'message' => 'Service name is required'];
        }
        
        // Préparation des filtres
        $filters = [
            'service' => $service,
            'timestamp' => [
                'operator' => 'BETWEEN',
                'value' => [$startDate . ' 00:00:00', $endDate . ' 23:59:59']
            ]
        ];
        
        // Récupération des entrées
        $entries = $this->monitoringCRUD->get(['*'], $filters);
        
        if (empty($entries)) {
            return ['status' => 'error', 'message' => 'No monitoring data found for this service in the specified date range'];
        }
        
        // Calcul des statistiques
        $totalEntries = count($entries);
        $statusCounts = [];
        $responseTimes = [];
        $downtime = 0;
        $lastStatus = null;
        $lastTimestamp = null;
        
        foreach ($entries as $entry) {
            // Comptage des statuts
            if (!isset($statusCounts[$entry['status']])) {
                $statusCounts[$entry['status']] = 0;
            }
            $statusCounts[$entry['status']]++;
            
            // Collecte des temps de réponse
            if (isset($entry['response_time'])) {
                $responseTimes[] = $entry['response_time'];
            }
            
            // Calcul du temps d'arrêt
            if ($lastStatus === 'error' || $lastStatus === 'critical') {
                if ($lastTimestamp) {
                    $currentTimestamp = strtotime($entry['timestamp']);
                    $lastTimestampValue = strtotime($lastTimestamp);
                    $downtime += ($currentTimestamp - $lastTimestampValue);
                }
            }
            
            $lastStatus = $entry['status'];
            $lastTimestamp = $entry['timestamp'];
        }
        
        // Calcul des statistiques de temps de réponse
        $avgResponseTime = !empty($responseTimes) ? array_sum($responseTimes) / count($responseTimes) : 0;
        $minResponseTime = !empty($responseTimes) ? min($responseTimes) : 0;
        $maxResponseTime = !empty($responseTimes) ? max($responseTimes) : 0;
        
        // Calcul de la disponibilité
        $upCount = ($statusCounts['ok'] ?? 0) + ($statusCounts['warning'] ?? 0);
        $availability = $totalEntries > 0 ? ($upCount / $totalEntries) * 100 : 0;
        
        return [
            'status' => 'success', 
            'message' => 'Service performance calculated successfully', 
            'data' => [
                'service' => $service,
                'total_checks' => $totalEntries,
                'status_distribution' => $statusCounts,
                'response_time' => [
                    'average' => round($avgResponseTime, 2),
                    'min' => $minResponseTime,
                    'max' => $maxResponseTime
                ],
                'availability' => round($availability, 2),
                'downtime_seconds' => $downtime,
                'downtime_formatted' => $this->formatDowntime($downtime),
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate
                ]
            ]
        ];
    }
    
    /**
     * Formate le temps d'arrêt en une chaîne lisible
     * 
     * @param int $seconds Nombre de secondes
     * @return string Temps d'arrêt formaté
     */
    private function formatDowntime($seconds) {
        if ($seconds < 60) {
            return $seconds . ' secondes';
        }
        
        $minutes = floor($seconds / 60);
        if ($minutes < 60) {
            return $minutes . ' minutes';
        }
        
        $hours = floor($minutes / 60);
        if ($hours < 24) {
            return $hours . ' heures';
        }
        
        $days = floor($hours / 24);
        return $days . ' jours';
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