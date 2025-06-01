<?php
require_once CRUD_PATH . 'DeliveriesCRUD.php';
require_once CRUD_PATH . 'OrdersCRUD.php';

/**
 * Service de gestion des livraisons
 * Utilise DeliveriesCRUD, OrdersCRUD et l'API ShipEngine pour les opérations
 */
class DeliveryService {
    /** @var DeliveriesCRUD $deliveriesCRUD Instance du CRUD livraisons */
    private $deliveriesCRUD;
    
    /** @var OrdersCRUD $ordersCRUD Instance du CRUD commandes */
    private $ordersCRUD;
    
    /** @var mysqli $conn Connexion à la base de données */
    private $conn;
    
    /** @var string $shipEngineApiKey Clé API ShipEngine */
    private $shipEngineApiKey;
    
    /** @var string $shipEngineApiUrl URL de base de l'API ShipEngine */
    private $shipEngineApiUrl = 'https://api.shipengine.com/v1';
    
    /** @var array $carrierMap Mapping des services de livraison vers les IDs ShipEngine */
    private $carrierMap = [
        'UPS' => 'se-123456',
        'FedEx' => 'se-234567',
        'DHL' => 'se-345678',
        'USPS' => 'se-456789',
        // Ajoutez d'autres services au besoin
    ];
    
    /** @var array $serviceMap Mapping des services de livraison vers les codes de service */
    private $serviceMap = [
        'UPS' => 'ups_ground',
        'FedEx' => 'fedex_ground',
        'DHL' => 'dhl_express',
        'USPS' => 'usps_priority',
        // Ajoutez d'autres services au besoin
    ];
    
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        $this->conn = $mysqli;
        $this->deliveriesCRUD = new DeliveriesCRUD($mysqli);
        $this->ordersCRUD = new OrdersCRUD($mysqli);
        $this->shipEngineApiKey = getenv('SHIPENGINE_API_KEY');
        
        if (empty($this->shipEngineApiKey)) {
            logError("Construction: ShipEngine API key manquante");
        }
    }
    
    /**
     * Crée une nouvelle livraison pour une commande
     * 
     * @param array $data Données de la livraison (order_id, delivery_service, etc.)
     * @return array Statut de l'opération
     */
    public function createDelivery($data) {
        logInfo("Creating delivery", [
            'order_id' => $data['order_id'] ?? null,
            'delivery_service' => $data['delivery_service'] ?? null
        ]);
        
        $orderId = $data['order_id'] ?? null;
        $deliveryService = $data['delivery_service'] ?? null;
        
        if (!$orderId) {
            logError("createDelivery: order_id manquant", $data);
            return ['status' => 'error', 'message' => 'Order ID is required'];
        }
        
        if (!$deliveryService) {
            logError("createDelivery: delivery_service manquant", $data);
            return ['status' => 'error', 'message' => 'Delivery service is required'];
        }
        
        // Vérifier si la commande existe
        $order = $this->ordersCRUD->find($orderId);
        
        if (!$order) {
            logError("createDelivery: commande introuvable", ['order_id' => $orderId]);
            return ['status' => 'error', 'message' => 'Order not found'];
        }
        
        // Vérifier si la commande est déjà expédiée
        if ($order['status'] !== 'Processing') {
            logWarning("createDelivery: commande pas prête pour l'expédition", 
                ['order_id' => $orderId, 'status' => $order['status']]);
            return ['status' => 'error', 'message' => 'Order is not ready for shipping'];
        }
        
        // Démarrer une transaction
        $this->conn->begin_transaction();
        
        // Création de la livraison
        $deliveryData = [
            'order_id' => $orderId,
            'delivery_service' => $deliveryService,
            'status' => 'In Transit',
            'tracking_number' => $this->generateTrackingNumber($deliveryService)
        ];
        
       
            $shipmentResponse = $this->createShipEngineShipment(['order_data' => $order]);
            
            if (isset($shipmentResponse['status']) && $shipmentResponse['status'] === 'error') {
                $this->conn->rollback();
                logError("createDelivery: erreur ShipEngine", 
                    ['error' => $shipmentResponse['message']]);
                    return ['status' => 'error', 'message' => $shipmentResponse['message']];

            }
            
            // Créer l'étiquette
            $labelResponse = $this->createShipEngineLabel(['shipment_id' => $shipmentResponse['shipment_id']]);
            
            if (isset($labelResponse['status']) && $labelResponse['status'] === 'error') {
                $this->conn->rollback();
                logError("createDelivery: erreur ShipEngine", 
                    ['error' => $labelResponse['message']]);
                return ['status' => 'error', 'message' => $labelResponse['message']];
            }
            
            // Ajouter les informations ShipEngine aux données de livraison
            $deliveryData['shipengine_shipment_id'] = $shipmentResponse['shipment_id'];
            $deliveryData['shipengine_label_id'] = $labelResponse['label_id'];
            $deliveryData['carrier_id'] = $labelResponse['carrier_id'];
            $deliveryData['label_download_url'] = $labelResponse['label_download']['pdf'];
            $deliveryData['tracking_number'] = $labelResponse['tracking_number'];
            
        
        
        $deliveryId = $this->deliveriesCRUD->insert($deliveryData);
        
        if (!$deliveryId) {
            logError("createDelivery: échec de création de la livraison", $deliveryData);
            $this->conn->rollback();
            return ['status' => 'error', 'message' => 'Failed to create delivery'];
        }
        
        // Mettre à jour le statut de la commande
        $result = $this->ordersCRUD->update(['status' => 'Shipped'], ['id' => $orderId]);
        
        if (!$result) {
            logError("createDelivery: échec de mise à jour du statut de commande", 
                ['order_id' => $orderId]);
            $this->conn->rollback();
            return ['status' => 'error', 'message' => 'Failed to update order status'];
        }
        
        // Valider la transaction
        $this->conn->commit();
        
        // Notification au service de notification pour informer le client
        return [
            'status' => 'pending',
            'service' => 'Notification',
            'action' => 'sendEmail',
            'data' => [
                'message' => 'Order shipped, sending notification',
                'email_data' => [
                    'type' => 'order_shipped',
                    'order_id' => $orderId,
                    'tracking_number' => $deliveryData['tracking_number'],
                    'delivery_service' => $deliveryService,
                    'user_id' => $order['user_id']
                ]
            ]
        ];
    }
    
    /**
     * Crée une expédition via ShipEngine
     * 
     * @param array $data Contient les données de la commande (order_data)
     * @return array Réponse de l'API
     * @throws Exception
     */
    private function createShipEngineShipment($data) {
        $orderData = $data['order_data'] ?? null;
        
        if (!$orderData) {
            logError("createShipEngineShipment: données de commande manquantes");
            return ['status' => 'error', 'message' => 'Order data is required'];
        }
        
        $serviceCode = $this->getServiceCode(['delivery_service' => $orderData['delivery_service'] ?? '']);
        $carrierId = $this->getCarrierId(['delivery_service' => $orderData['delivery_service'] ?? '']);
        
        $shipmentData = [
            'carrier_id' => $carrierId,
            'service_code' => $serviceCode,
            'ship_to' => [
                'name' => $orderData['shipping_name'],
                'phone' => $orderData['shipping_phone'] ?? '',
                'address_line1' => $orderData['shipping_street'],
                'city_locality' => $orderData['shipping_city'],
                'state_province' => $orderData['shipping_state'],
                'postal_code' => $orderData['shipping_zip'],
                'country_code' => $orderData['shipping_country'],
            ],
            'ship_from' => $this->getShipFromAddress([]),
            'packages' => [
                [
                    'weight' => [
                        'value' => $orderData['total_weight'] ?? 1,
                        'unit' => 'kilogram'
                    ]
                ]
            ]
        ];

        $response = $this->makeShipEngineRequest(['method' => 'POST', 'endpoint' => '/shipments', 'data' => $shipmentData]);
        
        if (isset($response['status']) && $response['status'] === 'error') {
            return $response;
        }
        
        if (!isset($response['shipment_id'])) {
            return ['status' => 'error', 'message' => "Échec de création de l'expédition ShipEngine"];
        }
        
        return $response;
    }

    /**
     * Crée une étiquette d'expédition via ShipEngine
     * 
     * @param array $data Contient l'ID de l'expédition (shipment_id)
     * @return array Réponse de l'API
     * @throws Exception
     */
    private function createShipEngineLabel($data) {
        $shipmentId = $data['shipment_id'] ?? null;
        
        if (!$shipmentId) {
            logError("createShipEngineLabel: shipment_id manquant");
            return ['status' => 'error', 'message' => 'Shipment ID is required'];
        }
        
        $labelData = [
            'shipment_id' => $shipmentId,
            'test_label' => false
        ];

        $response = $this->makeShipEngineRequest(['method' => 'POST', 'endpoint' => '/labels', 'data' => $labelData]);
        
        if (isset($response['status']) && $response['status'] === 'error') {
            return $response;
        }
        
        if (!isset($response['label_id'])) {
            return ['status' => 'error', 'message' => "Échec de création de l'étiquette ShipEngine"];
        }
        
        return $response;
    }

    /**
     * Effectue les requêtes vers l'API ShipEngine
     * 
     * @param array $data Contient method, endpoint et data pour la requête
     * @return array Réponse décodée
     */
    private function makeShipEngineRequest($data) {
        $method = $data['method'] ?? 'GET';
        $endpoint = $data['endpoint'] ?? '';
        $requestData = $data['data'] ?? null;
        
        if (empty($this->shipEngineApiKey)) {
            logError("makeShipEngineRequest: clé API manquante", 
                ['endpoint' => $endpoint, 'method' => $method]);
            return ['status' => 'error', 'message' => 'ShipEngine API key is missing'];
        }
        
        $ch = curl_init($this->shipEngineApiUrl . $endpoint);
        
        $headers = [
            'Content-Type: application/json',
            'API-Key: ' . $this->shipEngineApiKey
        ];

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($requestData) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = 'Erreur Curl: ' . curl_error($ch);
            curl_close($ch);
            logError("makeShipEngineRequest: erreur cURL", 
                ['endpoint' => $endpoint, 'error' => $error]);
            return ['status' => 'error', 'message' => $error];
        }
        
        curl_close($ch);
        
        $responseData = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMessage = $responseData['message'] ?? 'Erreur inconnue';
            logError("makeShipEngineRequest: erreur API", 
                ['endpoint' => $endpoint, 'http_code' => $httpCode, 'message' => $errorMessage]);
            return ['status' => 'error', 'message' => $errorMessage];
        }
        
        return $responseData;
    }

    /**
     * Retourne l'adresse d'expédition par défaut
     * 
     * @param array $data Paramètres optionnels pour personnaliser l'adresse
     * @return array
     */
    private function getShipFromAddress($data) {
        // On pourrait utiliser $data pour personnaliser l'adresse si nécessaire
        return [
            'company_name' => $data['company_name'] ?? 'Votre Entreprise',
            'name' => $data['name'] ?? 'Service Expédition',
            'phone' => $data['phone'] ?? '0123456789',
            'address_line1' => $data['address_line1'] ?? '123 Rue Commerce',
            'city_locality' => $data['city'] ?? 'Paris',
            'state_province' => $data['state'] ?? 'IDF',
            'postal_code' => $data['postal_code'] ?? '75000',
            'country_code' => $data['country'] ?? 'FR',
        ];
    }
    
    /**
     * Génère un numéro de suivi unique
     * 
     * @param string $deliveryService Service de livraison
     * @return string Numéro de suivi
     */
    private function generateTrackingNumber($data) {
        $deliveryService = is_array($data) ? ($data['delivery_service'] ?? '') : $data;
        $prefix = '';
        
        // Préfixes en fonction du service de livraison
        switch ($deliveryService) {
            case 'UPS':
                $prefix = 'UPS';
                break;
            case 'FedEx':
                $prefix = 'FDX';
                break;
            case 'DHL':
                $prefix = 'DHL';
                break;
            case 'USPS':
                $prefix = 'USP';
                break;
            default:
                $prefix = 'TRK';
        }
        
        // Génération du numéro de suivi avec préfixe, timestamp et nombre aléatoire
        return $prefix . date('Ymd') . rand(10000, 99999);
    }
    
    /**
     * Met à jour le statut d'une livraison
     * 
     * @param array $data Données de la livraison (tracking_number, status)
     * @return array Statut de l'opération
     */
    public function updateDeliveryStatus($data) {
        logInfo("Updating delivery status", [
            'tracking_number' => $data['tracking_number'] ?? null,
            'status' => $data['status'] ?? null
        ]);
        
        $trackingNumber = $data['tracking_number'] ?? null;
        $status = $data['status'] ?? null;
        
        if (!$trackingNumber || !$status) {
            logError("updateDeliveryStatus: données manquantes", $data);
            return ['status' => 'error', 'message' => 'Tracking number and status are required'];
        }
        
        // Rechercher la livraison par numéro de suivi
        $deliveries = $this->deliveriesCRUD->get(['*'], ['tracking_number' => $trackingNumber]);
        
        if (empty($deliveries)) {
            logError("updateDeliveryStatus: livraison introuvable", 
                ['tracking_number' => $trackingNumber]);
            return ['status' => 'error', 'message' => 'Delivery not found'];
        }
        
        $delivery = $deliveries[0];
        
        // Vérifier si le statut est valide
        $validStatuses = ['In Transit', 'Out for Delivery', 'Delivered', 'Delayed', 'Failed Delivery', 'Returned'];
        if (!in_array($status, $validStatuses)) {
            logError("updateDeliveryStatus: statut invalide", 
                ['status' => $status, 'tracking_number' => $trackingNumber]);
            return ['status' => 'error', 'message' => 'Invalid delivery status'];
        }
        
        // Démarrer une transaction
        $this->conn->begin_transaction();
        
        // Mise à jour du statut de la livraison
        $result = $this->deliveriesCRUD->update(['status' => $status], ['tracking_number' => $trackingNumber]);
        
        if (!$result) {
            logError("updateDeliveryStatus: échec de mise à jour du statut", 
                ['tracking_number' => $trackingNumber, 'status' => $status]);
            $this->conn->rollback();
            return ['status' => 'error', 'message' => 'Failed to update delivery status'];
        }
        
        // Si la livraison est "Delivered", mettre à jour le statut de la commande
        if ($status === 'Delivered') {
            $orderUpdateResult = $this->ordersCRUD->update(
                ['status' => 'Delivered'], 
                ['id' => $delivery['order_id']]
            );
            
            if (!$orderUpdateResult) {
                logWarning("updateDeliveryStatus: échec de mise à jour du statut de commande", 
                    ['order_id' => $delivery['order_id']]);
                $this->conn->rollback();
                return ['status' => 'error', 'message' => 'Failed to update order status'];
            }
            
            // Récupérer les détails de la commande pour la notification
            $order = $this->ordersCRUD->find($delivery['order_id']);
            
            // Valider la transaction
            $this->conn->commit();
            
            if ($order) {
                // Notifier le service de notification
                return [
                    'status' => 'pending',
                    'service' => 'Notification',
                    'action' => 'sendEmail',
                    'data' => [
                        'message' => 'Order delivered, sending notification',
                        'email_data' => [
                            'type' => 'order_delivered',
                            'order_id' => $delivery['order_id'],
                            'tracking_number' => $trackingNumber,
                            'user_id' => $order['user_id']
                        ]
                    ]
                ];
            }
        } else {
            // Valider la transaction
            $this->conn->commit();
        }
        
        return ['status' => 'success', 'message' => 'Delivery status updated successfully'];
    }
    
    /**
     * Récupère les détails d'une livraison
     * 
     * @param array $data Données de la livraison (tracking_number ou order_id)
     * @return array Détails de la livraison
     */
    public function getDelivery($data) {
        logInfo("Getting delivery details", [
            'tracking_number' => $data['tracking_number'] ?? null,
            'order_id' => $data['order_id'] ?? null
        ]);
        
        if (isset($data['tracking_number'])) {
            $deliveries = $this->deliveriesCRUD->get(['*'], ['tracking_number' => $data['tracking_number']]);
        } elseif (isset($data['order_id'])) {
            $deliveries = $this->deliveriesCRUD->get(['*'], ['order_id' => $data['order_id']]);
        } else {
            logError("getDelivery: paramètres manquants", $data);
            return ['status' => 'error', 'message' => 'Tracking number or order ID is required'];
        }
        
        if (empty($deliveries)) {
            logWarning("getDelivery: livraison introuvable", $data);
            return ['status' => 'error', 'message' => 'Delivery not found'];
        }
        
        // Obtenir les détails de la commande associée
        $delivery = $deliveries[0];
        $order = $this->ordersCRUD->find($delivery['order_id']);
        
        if (!$order) {
            logWarning("getDelivery: commande associée introuvable", 
                ['order_id' => $delivery['order_id']]);
        }
        
        // Ajouter les détails de la commande à la livraison
        $delivery['order'] = $order;
        
        return ['status' => 'success', 'message' => 'Delivery retrieved successfully', 'data' => $delivery];
    }
    
    /**
     * Liste les livraisons avec pagination et filtres
     * 
     * @param array $data Données de pagination et filtres (page, limit, filters)
     * @return array Liste des livraisons
     */
    public function listDeliveries($data) {
        logInfo("Listing deliveries", [
            'page' => $data['page'] ?? 1,
            'limit' => $data['limit'] ?? 10
        ]);
        
        $page = isset($data['page']) ? (int)$data['page'] : 1;
        $limit = isset($data['limit']) ? (int)$data['limit'] : 10;
        $filters = $data['filters'] ?? [];
        
        // Calcul de l'offset pour la pagination
        $offset = ($page - 1) * $limit;
        
        // Options pour la requête
        $options = [
            'limit' => $limit, 
            'offset' => $offset, 
            'orderBy' => 'created_at', 
            'orderDirection' => 'DESC'
        ];
        
        // Récupération des livraisons
        $deliveries = $this->deliveriesCRUD->get(['*'], $filters, $options);
        
        // Récupération du nombre total de livraisons pour la pagination
        $total = $this->deliveriesCRUD->count($filters);
        
        // Pour chaque livraison, récupérer les détails de la commande associée
        foreach ($deliveries as &$delivery) {
            $order = $this->ordersCRUD->find($delivery['order_id']);
            if ($order) {
                $delivery['order'] = $order;
            } else {
                logWarning("listDeliveries: commande associée introuvable", 
                    ['order_id' => $delivery['order_id']]);
                $delivery['order'] = null;
            }
        }
        
        return [
            'status' => 'success', 
            'message' => 'Deliveries retrieved successfully', 
            'data' => [
                'deliveries' => $deliveries,
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
     * Génère une étiquette d'expédition via l'API ShipEngine
     * 
     * @param array $data Données pour générer l'étiquette
     * @return array Réponse avec l'URL de l'étiquette
     */
    public function generateShippingLabel($data) {
        $orderId = $data['order_id'] ?? null;
        $deliveryService = $data['delivery_service'] ?? null;
        
        if (!$orderId) {
            logError("generateShippingLabel: order_id manquant", $data);
            return ['status' => 'error', 'message' => 'Order ID is required'];
        }
        
        if (!$deliveryService) {
            logError("generateShippingLabel: delivery_service manquant", $data);
            return ['status' => 'error', 'message' => 'Delivery service is required'];
        }
        
        // Vérifier si la commande existe
        $order = $this->ordersCRUD->find($orderId);
        
        if (!$order) {
            logError("generateShippingLabel: commande introuvable", ['order_id' => $orderId]);
            return ['status' => 'error', 'message' => 'Order not found'];
        }
        
        // Vérifier si une livraison existe déjà pour cette commande
        $existingDeliveries = $this->deliveriesCRUD->get(['*'], ['order_id' => $orderId]);
        if (!empty($existingDeliveries)) {
            logWarning("generateShippingLabel: livraison existante", 
                ['order_id' => $orderId, 'delivery_id' => $existingDeliveries[0]['id']]);
            return ['status' => 'error', 'message' => 'Delivery already exists for this order'];
        }
        
        // Démarrer une transaction
        $this->conn->begin_transaction();
        
     
            // Créer l'expédition via ShipEngine
            $shipmentResponse = $this->createShipEngineShipment(['order_data' => $order]);
            
            if (isset($shipmentResponse['status']) && $shipmentResponse['status'] === 'error') {
                logError("generateShippingLabel: erreur ShipEngine", 
                    ['error' => $shipmentResponse['message']]);
                $this->conn->rollback();
                return ['status' => 'error', 'message' => $shipmentResponse['message']];
            }
            
            // Créer l'étiquette
            $labelResponse = $this->createShipEngineLabel(['shipment_id' => $shipmentResponse['shipment_id']]);
            
            if (isset($labelResponse['status']) && $labelResponse['status'] === 'error') {
                logError("generateShippingLabel: erreur ShipEngine", 
                    ['error' => $labelResponse['message']]);
                $this->conn->rollback();
                return ['status' => 'error', 'message' => $labelResponse['message']];
            }
            
            // Données de livraison
            $deliveryData = [
                'order_id' => $orderId,
                'delivery_service' => $deliveryService,
                'status' => 'In Transit',
                'tracking_number' => $labelResponse['tracking_number'],
                'shipengine_shipment_id' => $shipmentResponse['shipment_id'],
                'shipengine_label_id' => $labelResponse['label_id'],
                'carrier_id' => $labelResponse['carrier_id'],
                'label_download_url' => $labelResponse['label_download']['pdf']
            ];
            
            // Insérer la livraison
            $deliveryId = $this->deliveriesCRUD->insert($deliveryData);
            
            if (!$deliveryId) {
                logError("generateShippingLabel: échec d'insertion de la livraison", $deliveryData);
                $this->conn->rollback();
                return ['status' => 'error', 'message' => 'Failed to create delivery record'];
            }
            
            // Mettre à jour le statut de la commande
            $updateResult = $this->ordersCRUD->update(['status' => 'Shipped'], ['id' => $orderId]);
            
            if (!$updateResult) {
                logWarning("generateShippingLabel: échec de mise à jour du statut de commande", 
                    ['order_id' => $orderId]);
                $this->conn->rollback();
                return ['status' => 'error', 'message' => 'Failed to update order status'];
            }
            
            // Valider la transaction
            $this->conn->commit();
            
            return [
                'status' => 'success',
                'message' => 'Shipping label generated successfully',
                'data' => [
                    'delivery_id' => $deliveryId,
                    'tracking_number' => $labelResponse['tracking_number'],
                    'label_url' => $labelResponse['label_download']['pdf']
                ]
            ];
      
    }
    
    /**
     * Obtient l'ID du transporteur pour ShipEngine
     * 
     * @param array $data Contient le nom du service de livraison
     * @return string ID du transporteur
     */
    private function getCarrierId($data) {
        $deliveryService = $data['delivery_service'] ?? '';
        return $this->carrierMap[$deliveryService] ?? 'se-default';
    }
    
    /**
     * Obtient le code de service pour ShipEngine
     * 
     * @param array $data Contient le nom du service de livraison
     * @return string Code du service
     */
    private function getServiceCode($data) {
        $deliveryService = $data['delivery_service'] ?? '';
        return $this->serviceMap[$deliveryService] ?? 'standard';
    }
    
    /**
     * Calcule les frais d'expédition estimés via l'API ShipEngine
     * 
     * @param array $data - Contient les détails d'expédition
     * @return array - Estimation des frais
     */
    public function calculateShippingCost($data) {
        if (empty($data)) {
            logError("calculateShippingCost: données manquantes");
            return ['status' => 'error', 'message' => 'Shipping details are required'];
        }
        
        // Validation des données requises
        $requiredFields = ['destination', 'weight', 'delivery_service'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                logError("calculateShippingCost: champ requis manquant", ['field' => $field]);
                return ['status' => 'error', 'message' => "Field {$field} is required"];
            }
        }
        
        
            // Préparation des données pour l'API
            $rateData = [
                'carrier_id' => $this->getCarrierId(['delivery_service' => $data['delivery_service']]),
                'service_code' => $this->getServiceCode(['delivery_service' => $data['delivery_service']]),
                'ship_from' => $this->getShipFromAddress([]),
                'ship_to' => [
                    'country_code' => $data['destination']['country'],
                    'postal_code' => $data['destination']['postal_code'],
                    'city_locality' => $data['destination']['city'] ?? '',
                    'state_province' => $data['destination']['state'] ?? '',
                ],
                'packages' => [
                    [
                        'weight' => [
                            'value' => floatval($data['weight']),
                            'unit' => $data['weight_unit'] ?? 'kilogram'
                        ]
                    ]
                ]
            ];

            // Ajout des dimensions si disponibles
            if (isset($data['dimensions'])) {
                $rateData['packages'][0]['dimensions'] = [
                    'length' => floatval($data['dimensions']['length']),
                    'width' => floatval($data['dimensions']['width']),
                    'height' => floatval($data['dimensions']['height']),
                    'unit' => $data['dimensions']['unit'] ?? 'centimeter'
                ];
            }

            $response = $this->makeShipEngineRequest([
                'method' => 'POST',
                'endpoint' => '/rates/estimate',
                'data' => $rateData
            ]);

            if (isset($response['status']) && $response['status'] === 'error') {
                logError("calculateShippingCost: erreur ShipEngine", 
                    ['error' => $response['message']]);
                return ['status' => 'error', 'message' => $response['message']];
            }

            return [
                'status' => 'success',
                'message' => 'Shipping rates calculated successfully',
                'data' => $response
            ];

      
    }
    
    /**
     * Annule une livraison
     * 
     * @param array $data Données de la livraison (tracking_number)
     * @return array Statut de l'opération
     */
    public function cancelDelivery($data) {
        logInfo("Cancelling delivery", ['tracking_number' => $data['tracking_number'] ?? null]);
        
            $trackingNumber = $data['tracking_number'] ?? null;
            
            if (!$trackingNumber) {
                logError("cancelDelivery: tracking_number manquant", $data);
                return ['status' => 'error', 'message' => 'Tracking number is required'];


            }
            
            // Démarrer une transaction
            $this->conn->begin_transaction();
            
            // Récupérer la livraison
            $deliveries = $this->deliveriesCRUD->get(['*'], ['tracking_number' => $trackingNumber]);
            
            if (empty($deliveries)) {
                logError("cancelDelivery: livraison introuvable", 
                    ['tracking_number' => $trackingNumber]);
                return ['status' => 'error', 'message' => 'Delivery not found'];
            }
            
            $delivery = $deliveries[0];
            
            if ($delivery['status'] === 'Delivered') {
                logWarning("cancelDelivery: livraison déjà livrée", 
                    ['tracking_number' => $trackingNumber]);
                return ['status' => 'error', 'message' => 'Delivery already delivered'];
            }
            
            // Annuler l'étiquette ShipEngine si elle existe
            if (!empty($delivery['shipengine_label_id'])) {
                $response = $this->makeShipEngineRequest([
                    'method' => 'PUT',
                    'endpoint' => "/labels/{$delivery['shipengine_label_id']}/void"
                ]);
                
                if (isset($response['status']) && $response['status'] === 'error') {
                    logError("cancelDelivery: erreur annulation étiquette ShipEngine", 
                        ['error' => $response['message']]);
                    $this->conn->rollback();
                    return ['status' => 'error', 'message' => $response['message']];
                }
            }
            
            // Mettre à jour le statut de la livraison
            $updateResult = $this->deliveriesCRUD->update(
                ['status' => 'Cancelled'],
                ['tracking_number' => $trackingNumber]
            );
            
            if (!$updateResult) {
                logError("cancelDelivery: échec de mise à jour du statut de livraison", 
                    ['tracking_number' => $trackingNumber]);
                $this->conn->rollback();
                return ['status' => 'error', 'message' => 'Failed to update delivery status'];
            }
            
            // Mettre à jour le statut de la commande
            $orderUpdateResult = $this->ordersCRUD->update(
                ['status' => 'Processing'],
                ['id' => $delivery['order_id']]
            );
            
            if (!$orderUpdateResult) {
                logWarning("cancelDelivery: échec de mise à jour du statut de commande", 
                    ['order_id' => $delivery['order_id']]);
                $this->conn->rollback();
                return ['status' => 'error', 'message' => 'Failed to update order status'];
            }
            
            $this->conn->commit();
            
            return [
                'status' => 'success',
                'message' => 'Delivery cancelled successfully'
            ];
            
       
    }
    
    /**
     * Récupère les statistiques des livraisons
     * 
     * @param array $data Paramètres de filtrage (date_start, date_end)
     * @return array Statistiques des livraisons
     */
    public function getDeliveryStats($data) {
        
        $dateStart = $data['date_start'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateEnd = $data['date_end'] ?? date('Y-m-d');
        
        // Validation des dates
        if (!strtotime($dateStart) || !strtotime($dateEnd)) {
           logError("getDeliveryStats: dates invalides", 
                ['date_start' => $dateStart, 'date_end' => $dateEnd]);
            return ['status' => 'error', 'message' => 'Invalid date range'];
        }
        
        $filters = [
            'created_at' => [
                'operator' => 'BETWEEN',
                'value' => [$dateStart . ' 00:00:00', $dateEnd . ' 23:59:59']
            ]
        ];
        
        // Ajout des filtres supplémentaires
        if (isset($data['delivery_service'])) {
            $filters['delivery_service'] = $data['delivery_service'];
        }
        
        $deliveries = $this->deliveriesCRUD->get(['*'], $filters);
        
        if ($deliveries === false) {
            logError("getDeliveryStats: échec de récupération des livraisons", 
                ['filters' => $filters]);
            return ['status' => 'error', 'message' => 'Failed to retrieve deliveries'];
        }
        
        $stats = [
            'total' => count($deliveries),
            'by_status' => [],
            'by_service' => [],
            'by_date' => [],
            'average_delivery_time' => 0,
            'total_value' => 0
        ];
        
        $totalDeliveryTime = 0;
        $deliveredCount = 0;
        
        foreach ($deliveries as $delivery) {
            // Statistiques par statut
            $status = $delivery['status'];
            $stats['by_status'][$status] = ($stats['by_status'][$status] ?? 0) + 1;
            
            // Statistiques par service
            $service = $delivery['delivery_service'];
            $stats['by_service'][$service] = ($stats['by_service'][$service] ?? 0) + 1;
            
            // Statistiques par date
            $date = date('Y-m-d', strtotime($delivery['created_at']));
            $stats['by_date'][$date] = ($stats['by_date'][$date] ?? 0) + 1;
            
            // Calculer le temps moyen de livraison
            if ($status === 'Delivered' && !empty($delivery['delivered_at'])) {
                $deliveryTime = strtotime($delivery['delivered_at']) - strtotime($delivery['created_at']);
                $totalDeliveryTime += $deliveryTime;
                $deliveredCount++;
            }
        }
        
        // Calculer la moyenne du temps de livraison
        if ($deliveredCount > 0) {
            $stats['average_delivery_time'] = round($totalDeliveryTime / $deliveredCount / 3600, 1); // en heures
        }
        
        // Trier les statistiques
        ksort($stats['by_date']);
        arsort($stats['by_status']);
        arsort($stats['by_service']);
        
        return [
            'status' => 'success',
            'message' => 'Delivery statistics retrieved successfully',
            'data' => $stats
        ];
            
        
    }
    
    /**
     * Calcule les frais d'expédition via l'API ShipEngine
     */
    private function calculateShippingRates($data) {
        if (!isset($data['service_code'], $data['carrier_id'], $data['packages'])) {
            logError("calculateShippingRates: données manquantes", $data);
            return ['status' => 'error', 'message' => 'Service code, carrier ID and packages are required'];
        }

        $rateRequest = [
            'carrier_id' => $data['carrier_id'],
            'service_code' => $data['service_code'],
            'ship_from' => $data['ship_from'] ?? $this->getShipFromAddress([]),
            'ship_to' => $data['ship_to'],
            'packages' => $data['packages']
        ];

        $response = $this->makeShipEngineRequest([
            'method' => 'POST',
            'endpoint' => '/rates/estimate',
            'data' => $rateRequest
        ]);

        if (isset($response['status']) && $response['status'] === 'error') {
            logError("calculateShippingRates: erreur ShipEngine", 
                ['error' => $response['message']]);
            return ['status' => 'error', 'message' => $response['message']];
        }

        return [
            'status' => 'success',
            'message' => 'Shipping rates calculated successfully',
            'data' => $response
        ];
    }
}