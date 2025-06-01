<?php
require_once CRUD_PATH . 'ReturnedsCRUD.php';

/**
 * Service de gestion des retours de produits
 * Utilise ReturnedsCRUD pour les opérations de base de données
 */
class ReturnService {
    /** @var ReturnedsCRUD $returnedsCRUD Instance du CRUD retours */
    private $returnedsCRUD;
    
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        $this->returnedsCRUD = new ReturnedsCRUD($mysqli);
    }
    
    /**
     * Crée une nouvelle demande de retour
     * 
     * @param array $data Données du retour (order_id, user_id, reason, items)
     * @return array Statut de l'opération
     */
    public function createReturn($data) {
        logInfo("Creating return request", [
            'order_id' => $data['order_id'] ?? null,
            'user_id' => $data['user_id'] ?? null
        ]);
        
        $orderId = $data['order_id'] ?? null;
        $userId = $data['user_id'] ?? null;
        $reason = $data['reason'] ?? null;
        $items = $data['items'] ?? [];
        
        if (!$orderId || !$userId || !$reason || empty($items)) {
            return ['status' => 'error', 'message' => 'Order ID, user ID, reason and items are required'];
        }
        
        // Vérification des éléments à retourner
        foreach ($items as $item) {
            if (!isset($item['order_item_id']) || !isset($item['quantity']) || !isset($item['reason'])) {
                return ['status' => 'error', 'message' => 'Invalid return item data'];
            }
        }
        
        // Création de la demande de retour
        $returnData = [
            'order_id' => $orderId,
            'user_id' => $userId,
            'reason' => $reason,
            'status' => 'Pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Ajout des détails des éléments si le champ existe dans la table
        if (!empty($items)) {
            $returnData['items_details'] = json_encode($items);
        }
        
        // Insertion de la demande de retour
        $returnId = $this->returnedsCRUD->insert($returnData);
        
        if ($returnId) {
            // Notification au service de commande
            return [
                'status' => 'pending',
                'service' => 'Order',
                'action' => 'updateOrderStatus',
                'data' => [
                    'message' => 'Return request created',
                    'order_id' => $orderId,
                    'status' => 'Return Requested',
                    'return_id' => $returnId
                ]
            ];
        }
        
        return ['status' => 'error', 'message' => 'Failed to create return request'];
    }
    
    /**
     * Met à jour le statut d'une demande de retour
     * 
     * @param array $data Données du retour (return_id, status, admin_notes)
     * @return array Statut de l'opération
     */
    public function updateReturnStatus($data) {
        logInfo("Updating return status", [
            'return_id' => $data['return_id'] ?? null,
            'status' => $data['status'] ?? null
        ]);
        
        $returnId = $data['return_id'] ?? null;
        $status = $data['status'] ?? null;
        $adminNotes = $data['admin_notes'] ?? null;
        
        if (!$returnId || !$status) {
            return ['status' => 'error', 'message' => 'Return ID and status are required'];
        }
        
        // Vérifier si la demande de retour existe
        $return = $this->returnedsCRUD->find($returnId);
        
        if (!$return) {
            return ['status' => 'error', 'message' => 'Return request not found'];
        }
        
        // Vérifier si le statut est valide
        $validStatuses = ['Pending', 'Approved', 'Rejected', 'Completed'];
        if (!in_array($status, $validStatuses)) {
            return ['status' => 'error', 'message' => 'Invalid return status'];
        }
        
        // Préparation des données pour la mise à jour
        $updateData = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($adminNotes) {
            $updateData['admin_notes'] = $adminNotes;
        }
        
        // Mise à jour de la demande de retour
        $result = $this->returnedsCRUD->update($updateData, ['id' => $returnId]);
        
        if ($result) {
            // Si le retour est approuvé, notifier le service de remboursement
            if ($status === 'Approved') {
                return [
                    'status' => 'pending',
                    'service' => 'Payment',
                    'action' => 'refundPayment',
                    'data' => [
                        'message' => 'Return approved, processing refund',
                        'order_id' => $return['order_id'],
                        'user_id' => $return['user_id'],
                        'return_id' => $returnId,
                        'reason' => 'Return approved: ' . ($return['reason'] ?? 'Customer request')
                    ]
                ];
            }
            
            // Si le retour est rejeté ou complété, notifier l'utilisateur
            if ($status === 'Rejected' || $status === 'Completed') {
                return [
                    'status' => 'pending',
                    'service' => 'Notification',
                    'action' => 'sendEmail',
                    'data' => [
                        'message' => 'Return status updated',
                        'email_data' => [
                            'type' => 'return_status_update',
                            'user_id' => $return['user_id'],
                            'return_id' => $returnId,
                            'status' => $status,
                            'admin_notes' => $adminNotes
                        ]
                    ]
                ];
            }
            
            return ['status' => 'success', 'message' => 'Return status updated successfully'];
        }
        
        return ['status' => 'error', 'message' => 'Failed to update return status'];
    }
    
    /**
     * Récupère les détails d'une demande de retour
     * 
     * @param array $data Données du retour (return_id)
     * @return array Détails de la demande de retour
     */
    public function getReturn($data) {
        logInfo("Getting return details", ['return_id' => $data['return_id'] ?? null]);
        
        $returnId = $data['return_id'] ?? null;
        
        if (!$returnId) {
            return ['status' => 'error', 'message' => 'Return ID is required'];
        }
        
        // Récupération de la demande de retour
        $return = $this->returnedsCRUD->find($returnId);
        
        if (!$return) {
            return ['status' => 'error', 'message' => 'Return request not found'];
        }
        
        // Décodage des détails des éléments si nécessaire
        if (isset($return['items_details']) && $this->isJson($return['items_details'])) {
            $return['items_details'] = json_decode($return['items_details'], true);
        }
        
        return [
            'status' => 'success', 
            'message' => 'Return request retrieved successfully', 
            'data' => $return
        ];
    }
    
    /**
     * Récupère les demandes de retour d'un utilisateur
     * 
     * @param array $data Données de l'utilisateur (user_id, page, limit)
     * @return array Liste des demandes de retour
     */
    public function getUserReturns($data) {
        $userId = $data['user_id'] ?? null;
        $page = isset($data['page']) ? (int)$data['page'] : 1;
        $limit = isset($data['limit']) ? (int)$data['limit'] : 10;
        
        if (!$userId) {
            return ['status' => 'error', 'message' => 'User ID is required'];
        }
        
        // Calcul de l'offset pour la pagination
        $offset = ($page - 1) * $limit;
        
        // Récupération des demandes de retour
        $returns = $this->returnedsCRUD->get(
            ['*'],
            ['user_id' => $userId],
            ['orderBy' => 'created_at', 'orderDirection' => 'DESC', 'limit' => $limit, 'offset' => $offset]
        );
        
        // Décodage des détails des éléments si nécessaire
        foreach ($returns as &$return) {
            if (isset($return['items_details']) && $this->isJson($return['items_details'])) {
                $return['items_details'] = json_decode($return['items_details'], true);
            }
        }
        
        // Comptage du nombre total de demandes de retour pour la pagination
        $total = $this->returnedsCRUD->count(['user_id' => $userId]);
        
        return [
            'status' => 'success', 
            'message' => 'User returns retrieved successfully', 
            'data' => [
                'returns' => $returns,
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
     * Liste toutes les demandes de retour avec pagination et filtres
     * 
     * @param array $data Données de pagination et filtres (page, limit, status)
     * @return array Liste des demandes de retour
     */
    public function listReturns($data) {
        logInfo("Listing returns", [
            'page' => $data['page'] ?? 1,
            'limit' => $data['limit'] ?? 10
        ]);
        
        $page = isset($data['page']) ? (int)$data['page'] : 1;
        $limit = isset($data['limit']) ? (int)$data['limit'] : 10;
        $status = $data['status'] ?? null;
        
        // Calcul de l'offset pour la pagination
        $offset = ($page - 1) * $limit;
        
        // Préparation des filtres
        $filters = [];
        
        if ($status) {
            $filters['status'] = $status;
        }
        
        // Récupération des demandes de retour
        $returns = $this->returnedsCRUD->get(
            ['*'],
            $filters,
            ['orderBy' => 'created_at', 'orderDirection' => 'DESC', 'limit' => $limit, 'offset' => $offset]
        );
        
        // Décodage des détails des éléments si nécessaire
        foreach ($returns as &$return) {
            if (isset($return['items_details']) && $this->isJson($return['items_details'])) {
                $return['items_details'] = json_decode($return['items_details'], true);
            }
        }
        
        // Comptage du nombre total de demandes de retour pour la pagination
        $total = $this->returnedsCRUD->count($filters);
        
        return [
            'status' => 'success', 
            'message' => 'Returns retrieved successfully', 
            'data' => [
                'returns' => $returns,
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