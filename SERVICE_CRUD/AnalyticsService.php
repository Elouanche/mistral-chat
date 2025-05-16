<?php
require_once CRUD_PATH . '/AnalyticsCRUD.php';

/**
 * Service de gestion des analyses
 * Utilise AnalyticsCRUD pour les opérations de base de données
 */
class AnalyticsService {
    /** @var AnalyticsCRUD $analyticsCRUD Instance du CRUD analytics */
    private $analyticsCRUD;
    
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        $this->analyticsCRUD = new AnalyticsCRUD($mysqli);
    }
    
    /**
     * Enregistre une nouvelle entrée d'analyse
     * 
     * @param array $data Données d'analyse (user_id, page_viewed, action, etc.)
     * @return array Statut de l'opération
     */
    public function recordActivity($data) {
        logInfo("Recording activity", ['data' => $data]);
        $userId = $data['user_id'] ?? null;
        $pageViewed = $data['page_viewed'] ?? null;
        $action = $data['action'] ?? null;
        $ipAddress = $data['ip_address'] ?? null;
        $userAgent = $data['user_agent'] ?? null;
        $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
        
        // Préparation des données pour l'insertion
        $analyticsData = [
            'timestamp' => $timestamp,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent
        ];
        
        if ($userId) {
            $analyticsData['user_id'] = $userId;
        }
        
        if ($pageViewed) {
            $analyticsData['page_viewed'] = $pageViewed;
        }
        
        if ($action) {
            $analyticsData['action'] = $action;
        }
        
        // Insertion de l'entrée d'analyse
        $analyticsId = $this->analyticsCRUD->insert($analyticsData);
        
        if ($analyticsId) {
            return [
                'status' => 'success', 
                'message' => 'Activity recorded successfully', 
                'data' => ['analytics_id' => $analyticsId]
            ];
        }
        
        return ['status' => 'error', 'message' => 'Failed to record activity'];
    }
    
    /**
     * Récupère les statistiques de visite de page
     * 
     * @param array $data Données de filtrage (start_date, end_date, page)
     * @return array Statistiques de visite
     */
    public function getPageViewStats($data) {
        logInfo("Retrieving page view stats", ['data' => $data]);
        $startDate = $data['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $data['end_date'] ?? date('Y-m-d');
        $page = $data['page'] ?? null;
        
        // Préparation des filtres
        $filters = [
            'timestamp' => [
                'operator' => 'BETWEEN',
                'value' => [$startDate . ' 00:00:00', $endDate . ' 23:59:59']
            ]
        ];
        
        if ($page) {
            $filters['page_viewed'] = $page;
        }
        
        // Récupération des données
        $analytics = $this->analyticsCRUD->get(['*'], $filters);
        
        // Traitement des données pour les statistiques
        $pageViews = [];
        $uniqueVisitors = [];
        $ipAddresses = [];
        
        foreach ($analytics as $entry) {
            $date = date('Y-m-d', strtotime($entry['timestamp']));
            $page = $entry['page_viewed'];
            $ip = $entry['ip_address'];
            
            // Comptage des vues de page
            if (!isset($pageViews[$date][$page])) {
                $pageViews[$date][$page] = 0;
            }
            $pageViews[$date][$page]++;
            
            // Comptage des visiteurs uniques
            if (!isset($ipAddresses[$date][$ip])) {
                $ipAddresses[$date][$ip] = true;
                if (!isset($uniqueVisitors[$date])) {
                    $uniqueVisitors[$date] = 0;
                }
                $uniqueVisitors[$date]++;
            }
        }
        
        return [
            'status' => 'success', 
            'message' => 'Page view statistics retrieved successfully', 
            'data' => [
                'page_views' => $pageViews,
                'unique_visitors' => $uniqueVisitors,
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate
                ]
            ]
        ];
    }
    
    /**
     * Récupère les statistiques d'action utilisateur
     * 
     * @param array $data Données de filtrage (start_date, end_date, action)
     * @return array Statistiques d'action
     */
    public function getActionStats($data) {
        logInfo("Retrieving action stats", ['data' => $data]);
        $startDate = $data['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $data['end_date'] ?? date('Y-m-d');
        $action = $data['action'] ?? null;
        
        // Préparation des filtres
        $filters = [
            'timestamp' => [
                'operator' => 'BETWEEN',
                'value' => [$startDate . ' 00:00:00', $endDate . ' 23:59:59']
            ]
        ];
        
        if ($action) {
            $filters['action'] = $action;
        }
        
        // Récupération des données
        $analytics = $this->analyticsCRUD->get(['*'], $filters);
        
        // Traitement des données pour les statistiques
        $actionCounts = [];
        
        foreach ($analytics as $entry) {
            $date = date('Y-m-d', strtotime($entry['timestamp']));
            $actionType = $entry['action'];
            
            if (!isset($actionCounts[$date][$actionType])) {
                $actionCounts[$date][$actionType] = 0;
            }
            $actionCounts[$date][$actionType]++;
        }
        
        return [
            'status' => 'success', 
            'message' => 'Action statistics retrieved successfully', 
            'data' => [
                'action_counts' => $actionCounts,
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate
                ]
            ]
        ];
    }
    
    /**
     * Récupère les statistiques d'utilisateur
     * 
     * @param array $data Données de filtrage (user_id, start_date, end_date)
     * @return array Statistiques d'utilisateur
     */
    public function getUserStats($data) {
        logInfo("Retrieving user stats", ['data' => $data]);
        $userId = $data['user_id'] ?? null;
        $startDate = $data['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $data['end_date'] ?? date('Y-m-d');
        
        if (!$userId) {
            return ['status' => 'error', 'message' => 'User ID is required'];
        }
        
        // Préparation des filtres
        $filters = [
            'user_id' => $userId,
            'timestamp' => [
                'operator' => 'BETWEEN',
                'value' => [$startDate . ' 00:00:00', $endDate . ' 23:59:59']
            ]
        ];
        
        // Récupération des données
        $analytics = $this->analyticsCRUD->get(['*'], $filters);
        
        // Traitement des données pour les statistiques
        $pageViews = [];
        $actions = [];
        $sessionDates = [];
        
        foreach ($analytics as $entry) {
            $date = date('Y-m-d', strtotime($entry['timestamp']));
            $page = $entry['page_viewed'] ?? null;
            $action = $entry['action'] ?? null;
            
            // Enregistrement des dates de session
            $sessionDates[] = $date;
            
            // Comptage des vues de page
            if ($page) {
                if (!isset($pageViews[$page])) {
                    $pageViews[$page] = 0;
                }
                $pageViews[$page]++;
            }
            
            // Comptage des actions
            if ($action) {
                if (!isset($actions[$action])) {
                    $actions[$action] = 0;
                }
                $actions[$action]++;
            }
        }
        
        // Calcul des sessions uniques (jours distincts)
        $uniqueSessions = count(array_unique($sessionDates));
        
        return [
            'status' => 'success', 
            'message' => 'User statistics retrieved successfully', 
            'data' => [
                'user_id' => $userId,
                'page_views' => $pageViews,
                'actions' => $actions,
                'unique_sessions' => $uniqueSessions,
                'total_activities' => count($analytics),
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate
                ]
            ]
        ];
    }
    
    /**
     * Génère un rapport d'analyse pour une période donnée
     * 
     * @param array $data Données du rapport (start_date, end_date, report_type)
     * @return array Rapport d'analyse
     */
    public function generateReport($data) {
        logInfo("Generating report", ['data' => $data]);
        $startDate = $data['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $data['end_date'] ?? date('Y-m-d');
        $reportType = $data['report_type'] ?? 'general';
        
        // Préparation des filtres
        $filters = [
            'timestamp' => [
                'operator' => 'BETWEEN',
                'value' => [$startDate . ' 00:00:00', $endDate . ' 23:59:59']
            ]
        ];
        
        // Récupération des données
        $analytics = $this->analyticsCRUD->get(['*'], $filters);
        
        // Traitement des données selon le type de rapport
        $report = [];
        
        switch ($reportType) {
            case 'page_views':
                $report = $this->processPageViewReport($analytics);
                break;
            case 'user_activity':
                $report = $this->processUserActivityReport($analytics);
                break;
            case 'conversion':
                $report = $this->processConversionReport($analytics);
                break;
            default:
                $report = $this->processGeneralReport($analytics);
        }
        
        return [
            'status' => 'success', 
            'message' => 'Report generated successfully', 
            'data' => [
                'report_type' => $reportType,
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate
                ],
                'report_data' => $report
            ]
        ];
    }
    
    /**
     * Traite les données pour un rapport de vues de page
     * 
     * @param array $analytics Données d'analyse
     * @return array Rapport traité
     */
    private function processPageViewReport($analytics) {
        logInfo("Processing page view report", ['analytics' => $analytics]);
        $pageViews = [];
        $topPages = [];
        
        foreach ($analytics as $entry) {
            $page = $entry['page_viewed'] ?? null;
            
            if ($page) {
                if (!isset($pageViews[$page])) {
                    $pageViews[$page] = 0;
                }
                $pageViews[$page]++;
            }
        }
        
        // Tri des pages par nombre de vues
        arsort($pageViews);
        
        // Récupération des 10 pages les plus vues
        $topPages = array_slice($pageViews, 0, 10, true);
        
        return [
            'total_page_views' => array_sum($pageViews),
            'unique_pages_viewed' => count($pageViews),
            'top_pages' => $topPages
        ];
    }
    
    /**
     * Traite les données pour un rapport d'activité utilisateur
     * 
     * @param array $analytics Données d'analyse
     * @return array Rapport traité
     */
    private function processUserActivityReport($analytics) {
        logInfo("Processing user activity report", ['analytics' => $analytics]);
        $userActivities = [];
        $topUsers = [];
        
        foreach ($analytics as $entry) {
            $userId = $entry['user_id'] ?? null;
            
            if ($userId) {
                if (!isset($userActivities[$userId])) {
                    $userActivities[$userId] = 0;
                }
                $userActivities[$userId]++;
            }
        }
        
        // Tri des utilisateurs par nombre d'activités
        arsort($userActivities);
        
        // Récupération des 10 utilisateurs les plus actifs
        $topUsers = array_slice($userActivities, 0, 10, true);
        
        return [
            'total_logged_activities' => count($analytics),
            'unique_active_users' => count($userActivities),
            'top_active_users' => $topUsers
        ];
    }
    
    /**
     * Traite les données pour un rapport de conversion
     * 
     * @param array $analytics Données d'analyse
     * @return array Rapport traité
     */
    private function processConversionReport($analytics) {
        logInfo("Processing conversion report", ['analytics' => $analytics]);
        $pageViews = 0;
        $addToCart = 0;
        $checkout = 0;
        $purchase = 0;
        
        foreach ($analytics as $entry) {
            $action = $entry['action'] ?? null;
            $page = $entry['page_viewed'] ?? null;
            
            if ($page) {
                $pageViews++;
            }
            
            if ($action) {
                switch ($action) {
                    case 'add_to_cart':
                        $addToCart++;
                        break;
                    case 'checkout':
                        $checkout++;
                        break;
                    case 'purchase':
                        $purchase++;
                        break;
                }
            }
        }
        
        // Calcul des taux de conversion
        $cartRate = $pageViews > 0 ? ($addToCart / $pageViews) * 100 : 0;
        $checkoutRate = $addToCart > 0 ? ($checkout / $addToCart) * 100 : 0;
        $purchaseRate = $checkout > 0 ? ($purchase / $checkout) * 100 : 0;
        $overallConversion = $pageViews > 0 ? ($purchase / $pageViews) * 100 : 0;
        
        return [
            'page_views' => $pageViews,
            'add_to_cart' => $addToCart,
            'checkout' => $checkout,
            'purchase' => $purchase,
            'conversion_rates' => [
                'cart_rate' => round($cartRate, 2),
                'checkout_rate' => round($checkoutRate, 2),
                'purchase_rate' => round($purchaseRate, 2),
                'overall_conversion' => round($overallConversion, 2)
            ]
        ];
    }
    
    /**
     * Traite les données pour un rapport général
     * 
     * @param array $analytics Données d'analyse
     * @return array Rapport traité
     */
    private function processGeneralReport($analytics) {
        logInfo("Processing general report", ['analytics' => $analytics]);
        $pageViews = 0;
        $uniqueUsers = [];
        $uniqueIPs = [];
        $actionCounts = [];
        
        foreach ($analytics as $entry) {
            $userId = $entry['user_id'] ?? null;
            $ip = $entry['ip_address'] ?? null;
            $page = $entry['page_viewed'] ?? null;
            $action = $entry['action'] ?? null;
            
            if ($page) {
                $pageViews++;
            }
            
            if ($userId) {
                $uniqueUsers[$userId] = true;
            }
            
            if ($ip) {
                $uniqueIPs[$ip] = true;
            }
            
            if ($action) {
                if (!isset($actionCounts[$action])) {
                    $actionCounts[$action] = 0;
                }
                $actionCounts[$action]++;
            }
        }
        
        // Tri des actions par nombre d'occurrences
        arsort($actionCounts);
        
        return [
            'total_activities' => count($analytics),
            'page_views' => $pageViews,
            'unique_users' => count($uniqueUsers),
            'unique_visitors' => count($uniqueIPs),
            'top_actions' => array_slice($actionCounts, 0, 5, true)
        ];
    }
}