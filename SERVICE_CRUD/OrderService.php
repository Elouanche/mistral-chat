<?php

use Stripe\Price;
use Stripe\Service\ProductService;

require_once CRUD_PATH . '/OrdersCRUD.php';
require_once CRUD_PATH . '/OrderItemsCRUD.php';
require_once CRUD_PATH . '/ProductsCRUD.php';
require_once CRUD_PATH . '/CartCRUD.php';

/**
 * Service de gestion des commandes
 * Utilise OrdersCRUD et OrderItemsCRUD pour les opérations de base de données
 */
class OrderService {
    /** @var OrdersCRUD $ordersCRUD Instance du CRUD commandes */
    private $ordersCRUD;
    
    /** @var OrderItemsCRUD $orderItemsCRUD Instance du CRUD éléments de commande */
    private $orderItemsCRUD;

    /** @var ProductsCRUD $productCrud Instance du CRUD produits */
    private $productCrud;
    
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        $this->ordersCRUD = new OrdersCRUD($mysqli);
        $this->orderItemsCRUD = new OrderItemsCRUD($mysqli);
        $this->productCrud = new ProductsCRUD($mysqli);
    }
    
    /**
     * Crée une nouvelle commande
     * 
     * @param array $data Données de la commande (user_id, items, shipping_info)
     * @return array Statut de l'opération
     * 
     */
    public function createOrder($data) {
        logInfo("Creating order", ['user_id' => $data['user_id'] ?? null]);
        $userId = $data['user_id'] ?? null;
        $items = $data['items'] ?? [];
        $shippingInfo = $data['shipping_info'] ?? [];
        
        if (!$userId || empty($items)) {
            return ['status' => 'error', 'message' => 'User ID and items are required'];
        }
        
        // Vérification des informations d'expédition
        if (empty($shippingInfo) || 
            !isset($shippingInfo['street']) || 
            !isset($shippingInfo['city']) || 
            !isset($shippingInfo['state']) || 
            !isset($shippingInfo['postal_code']) || 
            !isset($shippingInfo['country'])) {
            return ['status' => 'error', 'message' => 'Complete shipping information is required'];
        }
        
        // Calcul du montant total et préparation des line_items pour Stripe
        $totalAmount = 0;
        $lineItems = [];
        

            foreach ($items as $item) {
                if (!isset($item['product_id']) || !isset($item['quantity'])) {
                    logError("Invalid item data", ['item' => $item]);
                    return ['status' => 'error', 'message' => 'Invalid item data'];
                }
                
                // Récupérer les informations du produit
                $product = $this->productCrud->find($item['product_id']);
                if (!$product) {
                    logError("Product not found", ['product_id' => $item['product_id']]);

                    return ['status' => 'error', 'message' => 'Product not found'];
                }
                
                $totalAmount += $product['price'] * $item['quantity'];
                
                // Préparer le format pour Stripe Checkout
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'eur',
                        'unit_amount' => round($product['price'] * 100), // Convertir en centimes
                        'product_data' => [
                            'name' => $product['name'],
                        ],
                    ],
                    'quantity' => $item['quantity']
                ];
            }
            
            // Démarrer une transaction
            $this->ordersCRUD->beginTransaction();
            
            // Création de la commande
            $orderData = [
                'user_id' => $userId,
                'status' => 'Pending',
                'total_amount' => $totalAmount,
                'shipping_street' => $shippingInfo['street'],
                'shipping_city' => $shippingInfo['city'],
                'shipping_state' => $shippingInfo['state'],
                'shipping_postal_code' => $shippingInfo['postal_code'],
                'shipping_country' => $shippingInfo['country']
            ];
            
            $orderId = $this->ordersCRUD->insert($orderData);
            
            if (!$orderId) {
                throw new Exception('Failed to create order');
            }
            
            // Ajout des éléments de commande
            foreach ($items as $item) {
                $product = $this->productCrud->find($item['product_id']);
                $orderItemData = [
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $product['price']
                ];
                
                if (!$this->orderItemsCRUD->insert($orderItemData)) {
                    logError("Failed to add order item", ['order_id' => $orderId, 'item' => $item]);
                    $this->ordersCRUD->rollback();
                    return ['status' => 'error', 'message' => 'Failed to add order items'];
                }
            }
            
            // Valider la transaction
            $this->ordersCRUD->commit();
            
            // Créer la session Stripe Checkout
            return [
                'status' => 'pending',
                'service' => 'Payment',
                'action' => 'createCheckoutSession',
                'data' => [
                    'order_id' => $orderId,
                    'user_id' => $userId,
                    'line_items' => $lineItems
                ]
            ];
            
    }
    
    /**
     * Récupère les détails d'une commande
     * 
     * @param array $data Données de la commande (order_id)
     * @return array Détails de la commande
     */
    public function getOrder($data) {
        logInfo("Getting order details", ['order_id' => $data['order_id'] ?? null]);
        $orderId = $data['order_id'] ?? null;
        
        if (!$orderId) {
            return ['status' => 'error', 'message' => 'Order ID is required'];
        }
        
        // Récupération de la commande
        $order = $this->ordersCRUD->find($orderId);
        
        if (!$order) {
            return ['status' => 'error', 'message' => 'Order not found'];
        }
        
        // Récupération des éléments de la commande
        $orderItems = $this->orderItemsCRUD->get(['*'], ['order_id' => $orderId]);
        
        // Ajout des éléments à la commande
        $order['items'] = $orderItems;
        
        return ['status' => 'success', 'message' => 'Order retrieved successfully', 'data' => $order];
    }
    
    /**
     * Met à jour le statut d'une commande
     * 
     * @param array $data Données de la commande (order_id, status)
     * @return array Statut de l'opération
     */
    public function updateOrderStatus($data) {
        logInfo("Updating order status", [
            'order_id' => $data['order_id'] ?? null,
            'status' => $data['status'] ?? null
        ]);
        
        $orderIds = is_array($data['order_id']) ? $data['order_id'] : [$data['order_id']];
        $status = $data['status'] ?? null;
        
        if (empty($orderIds) || !$status) {
            return ['status' => 'error', 'message' => 'Order IDs and status are required'];
        }
        
        // Vérifier si le statut est valide
        $validStatuses = ['Pending', 'Paid', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
        if (!in_array($status, $validStatuses)) {
            return ['status' => 'error', 'message' => 'Invalid order status'];
        }
        
        // Démarrer une transaction pour les mises à jour multiples
        $this->ordersCRUD->beginTransaction();
        
        try {
            $success = true;
            $errors = [];
            
            foreach ($orderIds as $orderId) {
                // Vérifier si la commande existe
                $order = $this->ordersCRUD->find($orderId);
                if (!$order) {
                    $errors[] = "Order #$orderId not found";
                    continue;
                }
                
                // Mise à jour du statut
                $result = $this->ordersCRUD->update(['status' => $status], ['id' => $orderId]);
                if (!$result) {
                    $errors[] = "Failed to update order #$orderId";
                    $success = false;
                }
            }
            
            if ($success) {
                $this->ordersCRUD->commit();
                return [
                    'status' => 'success', 
                    'message' => 'Orders status updated successfully',
                    'errors' => $errors
                ];
            } else {
                $this->ordersCRUD->rollback();
                return [
                    'status' => 'error', 
                    'message' => 'Some orders failed to update',
                    'errors' => $errors
                ];
            }
            
        } catch (Exception $e) {
            $this->ordersCRUD->rollback();
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Annule une commande
     * 
     * @param array $data Données de la commande (order_id)
     * @return array Statut de l'opération
     */
    public function cancelOrder($data) {
        logInfo("Cancelling order", ['order_id' => $data['order_id'] ?? null]);
        $orderId = $data['order_id'] ?? null;
        
        if (!$orderId) {
            return ['status' => 'error', 'message' => 'Order ID is required'];
        }
        
        // Vérifier si la commande existe
        $order = $this->ordersCRUD->find($orderId);
        
        if (!$order) {
            return ['status' => 'error', 'message' => 'Order not found'];
        }
        
        // Vérifier si la commande peut être annulée
        if ($order['status'] === 'Shipped' || $order['status'] === 'Delivered') {
            return ['status' => 'error', 'message' => 'Cannot cancel an order that has been shipped or delivered'];
        }
        
        // Mise à jour du statut
        $result = $this->ordersCRUD->update(['status' => 'Cancelled'], ['id' => $orderId]);
        
        if ($result) {
            // Notifier le service de notification
            return [
                'status' => 'pending',
                'service' => 'Notification',
                'action' => 'sendEmail',
                'data' => [
                    'message' => 'Order cancelled, sending notification',
                    'email_data' => [
                        'type' => 'order_cancelled',
                        'order_id' => $orderId,
                        'user_id' => $order['user_id']
                    ]
                ]
            ];
        }
        
        return ['status' => 'error', 'message' => 'Failed to cancel order'];
    }
    
    /**
     * Liste les commandes d'un utilisateur
     * 
     * @param array $data Données de l'utilisateur (user_id, page, limit)
     * @return array Liste des commandes
     */
    public function getUserOrders($data) {
        $userId = $data['user_id'] ?? null;
        $page = isset($data['page']) ? (int)$data['page'] : 1;
        $limit = isset($data['limit']) ? (int)$data['limit'] : 10;
        
        if (!$userId) {
            return ['status' => 'error', 'message' => 'User ID is required'];
        }
        
        // Calcul de l'offset pour la pagination
        $offset = ($page - 1) * $limit;
        
        // Récupération des commandes
        $orders = $this->ordersCRUD->get(
            ['*'],
            ['user_id' => $userId],
            ['orderBy' => 'created_at', 'orderDirection' => 'DESC', 'limit' => $limit, 'offset' => $offset]
        );
        
        // Récupération du nombre total de commandes pour la pagination
        $total = $this->ordersCRUD->count(['user_id' => $userId]);
        
        // Pour chaque commande, récupérer ses éléments
        foreach ($orders as &$order) {
            $order['items'] = $this->orderItemsCRUD->get(['*'], ['order_id' => $order['id']]);
        }
        
        return [
            'status' => 'success', 
            'message' => 'User orders retrieved successfully', 
            'data' => [
                'orders' => $orders,
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
     * Liste toutes les commandes avec pagination et filtres
     * 
     * @param array $data Données de pagination et filtres (page, limit, filters)
     * @return array Liste des commandes
     */
    public function listOrders($data) {
        $page = isset($data['page']) ? (int)$data['page'] : 1;
        $limit = isset($data['limit']) ? (int)$data['limit'] : 10;
        $filters = $data['filters'] ?? [];
        
        // Calcul de l'offset pour la pagination
        $offset = ($page - 1) * $limit;
        
        // Options pour la requête
        $options = ['limit' => $limit, 'offset' => $offset, 'orderBy' => 'created_at', 'orderDirection' => 'DESC'];
        
        // Récupération des commandes
        $orders = $this->ordersCRUD->get(['*'], $filters, $options);
        
        // Récupération du nombre total de commandes pour la pagination
        $total = $this->ordersCRUD->count($filters);
        
        // Pour chaque commande, récupérer ses éléments
        foreach ($orders as &$order) {
            $order['items'] = $this->orderItemsCRUD->get(['*'], ['order_id' => $order['id']]);
        }
        
        return [
            'status' => 'success', 
            'message' => 'Orders retrieved successfully', 
            'data' => [
                'orders' => $orders,
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
     * @param array $data Tableau contenant :
     *   - 'order_id' (array) : Liste des identifiants des commandes à facturer
     * @return array Retourne un tableau avec :
     *   - 'status' (string) : 'success' en cas de réussite, 'error' en cas d'échec
     *   - 'message' (string) : Détail du résultat de l'opération
     *   - 'files' (array, optionnel) : Liste des fichiers PDF générés (si applicable)
     */
    public function generateInvoice($data) {
        logInfo("Generating invoice", ['order_id' => $data['order_id'] ?? null]);
      
        if (!isset($data['order_id'])) {
           return ['status' => 'error','message' => 'ID de commande manquant'];
        }
        $orderIds = is_array($data['order_id']) ? $data['order_id'] : [$data['order_id']];
        
        $pdf = new FPDF();
        $pdf->SetAutoPageBreak(true, 20);
        require_once BASE_PATH . '/env_helper.php';
        // Charger les variables d'environnement
        $companyInfo = [
            'name' =>get_env_variable('COMPANY_NAME') ?: 'Lorempsum SARL',
            'address' =>get_env_variable('COMPANY_ADDRESS') ?: '10 rue de Exemple',
            'city' =>get_env_variable('COMPANY_CITY') ?: 'Paris',
            'postal_code' =>get_env_variable('COMPANY_POSTAL_CODE') ?: '75000',
            'country' =>get_env_variable('COMPANY_COUNTRY') ?: 'France',
            'siret' =>get_env_variable('COMPANY_SIRET') ?: '123 456 789 00010',
            'ape' =>get_env_variable('COMPANY_APE') ?: '62.01Z',
            'vat' =>get_env_variable('COMPANY_VAT') ?: 'FR12 345678901',
            'support_email' =>get_env_variable('COMPANY_SUPPORT_EMAIL') ?: 'support@lorempsum.com',
            'payment_terms' =>get_env_variable('COMPANY_PAYMENT_TERMS') ?: '30 jours fin de mois',
            'late_fees' =>get_env_variable('COMPANY_LATE_FEES') ?: 'taux légal en vigueur',
            'collection_fees' =>get_env_variable('COMPANY_COLLECTION_FEES') ?: '40',
            'vat_rate' => (float)(get_env_variable('COMPANY_VAT_RATE') ?: '20')
        ];
    
        foreach ($orderIds as $orderId) {
            if (!is_numeric($orderId)) {
               return ['status' => 'error','message' => "ID de commande invalide : {$orderId}"];
            }
            $orderDetails = $this->getOrder($orderId);
            if (empty($orderDetails)) {
                return ['status' => 'error','message' => "Commande introuvable : {$orderId}"];
            }
            $shipping = $orderDetails[0];
            if (empty($shipping['shipping_street']) || empty($shipping['shipping_city']) ||
                empty($shipping['shipping_postal_code']) || empty($shipping['shipping_country'])) {
                return ['status' => 'error','message' => "Adresse de livraison manquante pour la commande : {$orderId}"];
            }
    
            $pdf->AddPage();
    
            // ------------------------
            // En‑tête avec logo + entreprise
            // ------------------------
            $logoPath = PUBLIC_PATH.'static/asset/logo.png';
            if (file_exists($logoPath)) {
                $pdf->Image($logoPath, 20, 20, 20);
            }
            $pdf->SetFont('Arial','B',20);
            $pdf->SetXY(45, 20);
            $pdf->Cell(0, 10, iconv('UTF-8', 'windows-1252', $companyInfo['name']), 0, 1, 'L');
            // Adresse siège
            $pdf->SetFont('Arial','',10);
            $pdf->SetX(45);
            $pdf->Cell(0, 5, iconv('UTF-8', 'windows-1252', 
                "{$companyInfo['address']}, {$companyInfo['postal_code']} {$companyInfo['city']}"), 0, 1, 'L');
            $pdf->SetX(45);
            $pdf->Cell(0, 5, iconv('UTF-8', 'windows-1252', 
                "SIRET : {$companyInfo['siret']} - APE : {$companyInfo['ape']}"), 0, 1, 'L');
            $pdf->SetX(45);
            $pdf->Cell(0, 5, iconv('UTF-8', 'windows-1252', 
                "TVA intracom : {$companyInfo['vat']}"), 0, 1, 'L');
    
            // ------------------------
            // Numéro et dates
            // ------------------------
            $pdf->Ln(5);
            $pdf->SetFont('Arial','B',14);
            $pdf->Cell(0, 8, iconv('UTF-8', 'windows-1252', "FACTURE N° " . str_pad($orderId, 6, '0', STR_PAD_LEFT)), 0, 1, 'R');
            $pdf->SetFont('Arial','',10);
            $pdf->Cell(0, 5, iconv('UTF-8', 'windows-1252', 'Date d\'émission : ' . date('d/m/Y')), 0, 1, 'R');
            $pdf->Cell(0, 5, iconv('UTF-8', 'windows-1252', 'Date de la prestation : ' . date('d/m/Y', strtotime($shipping['created_at']))), 0, 1, 'R');
    
            // ------------------------
            // Client
            // ------------------------
            $pdf->Ln(8);
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(0, 6, iconv('UTF-8', 'windows-1252', 'Facturé à :'), 0, 1, 'L');
            $pdf->SetFont('Arial','',11);
            $clientInfo = iconv('UTF-8', 'windows-1252', 
                $shipping['username'] . "\n" . 
                $shipping['email'] . "\n" .
                $shipping['shipping_street'] . "\n" .
                $shipping['shipping_city'] . " " . 
                $shipping['shipping_postal_code'] . "\n" .
                $shipping['shipping_country']
            );
            $pdf->MultiCell(0, 6, $clientInfo);
    
            // ------------------------
            // Livraison
            // ------------------------
            $pdf->Ln(4);
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(0, 6, iconv('UTF-8', 'windows-1252', 'Livré à :'), 0, 1, 'L');
            $pdf->SetFont('Arial','',11);
            $shippingInfo = iconv('UTF-8', 'windows-1252',
                "{$shipping['shipping_street']}, {$shipping['shipping_city']} {$shipping['shipping_postal_code']}, {$shipping['shipping_country']}"
            );
            $pdf->MultiCell(0, 6, $shippingInfo);
    
            // ------------------------
            // Tableau des produits
            // ------------------------
            $pdf->Ln(6);
            $pdf->SetFont('Arial','B',12);
            $pdf->SetFillColor(230);
            $headers = ['Produit','Quantité','PU HT','Total HT'];
            $w = [80,30,40,40];
            for ($i=0;$i<count($headers);$i++) {
                $pdf->Cell($w[$i],8,iconv('UTF-8', 'windows-1252', $headers[$i]),1,0,'C',true);
            }
            $pdf->Ln();
            $pdf->SetFont('Arial','',11);
            $pdf->SetFillColor(255);
            $totalTTC = 0;
            $tvaRate = $companyInfo['vat_rate'];

            foreach ($orderDetails as $item) {
                $lineTTC = $item['price'] * $item['quantity'];
                $totalTTC += $lineTTC;
                // Calcul du prix HT et de la ligne HT
                $priceHT = $item['price'] / (1 + ($tvaRate/100));
                $lineHT = $priceHT * $item['quantity'];
                
                $pdf->Cell($w[0],6,iconv('UTF-8', 'windows-1252', $item['product_name']),1);
                $pdf->Cell($w[1],6,$item['quantity'],1,0,'C');
                $pdf->Cell($w[2],6,iconv('UTF-8', 'windows-1252', number_format($priceHT,2,',',' ').' €'),1,0,'R');
                $pdf->Cell($w[3],6,iconv('UTF-8', 'windows-1252', number_format($lineHT,2,',',' ').' €'),1,0,'R');
                $pdf->Ln();
            }
    
            // ------------------------
            // Totaux avec TVA depuis les variables d'environnement
            // ------------------------
            $totalHT = $totalTTC / (1 + ($tvaRate/100));
            $tva = $totalTTC - $totalHT;
            $pdf->Ln(3);
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(array_sum($w)-40, 6, iconv('UTF-8', 'windows-1252', 'Total HT'), 1);
            $pdf->Cell(40, 6, iconv('UTF-8', 'windows-1252', number_format($totalHT,2,',',' ').' €'), 1, 0, 'R');
            $pdf->Ln();
            $pdf->Cell(array_sum($w)-40, 6, iconv('UTF-8', 'windows-1252', "TVA ({$tvaRate}%)"), 1);
            $pdf->Cell(40, 6, iconv('UTF-8', 'windows-1252', number_format($tva,2,',',' ').' €'), 1, 0, 'R');
            $pdf->Ln();
            $pdf->Cell(array_sum($w)-40, 8, iconv('UTF-8', 'windows-1252', 'Total TTC'), 1);
            $pdf->Cell(40, 8, iconv('UTF-8', 'windows-1252', number_format($totalTTC,2,',',' ').' €'), 1, 0, 'R');

            // ------------------------
            // Conditions & mentions légales
            // ------------------------
            $pdf->Ln(12);
            $pdf->SetFont('Arial','',9);
            $pdf->MultiCell(0, 5, iconv('UTF-8', 'windows-1252',
                "Conditions de paiement : {$companyInfo['payment_terms']}. ".
                "Pénalités de retard : {$companyInfo['late_fees']}, exigibles sans autre formalité. ".
                "Indemnité forfaitaire pour frais de recouvrement : {$companyInfo['collection_fees']} €."
            ));

            // ------------------------
            // Support & remerciement
            // ------------------------
            $pdf->Ln(6);
            $pdf->SetFont('Arial','I',10);
            $pdf->Cell(0, 5, iconv('UTF-8', 'windows-1252', 
                "Pour toute question (remboursement, renvoi, etc.) : {$companyInfo['support_email']}"), 0, 1, 'L');
            $pdf->Ln(4);
            $pdf->Cell(0, 5, iconv('UTF-8', 'windows-1252',
                'Merci pour votre confiance et votre achat chez Lorempsum !'), 0, 1, 'C');
        }
    
        // Sauvegarde et sortie
        $dir = BASE_PATH.'/facture/';
        if (!is_dir($dir)) mkdir($dir,0777,true);
        $file = $dir.'facture_'.implode('_',$orderIds).'.pdf';
        $pdf->Output('F',$file);
    
        header('Content-Type: application/pdf');
        header("Content-Disposition:inline;filename=\"".basename($file)."\"");
        header('Content-Length:'.filesize($file));
        readfile($file);
        return ['status' => 'success','message' => 'Fichier PDF généré avec succès'];
    }
    
    /**
     * Supprime une ou plusieurs commandes
     * 
     * @param array $data Données contenant les IDs des commandes à supprimer
     * @return array Statut de l'opération
     */
    public function deleteOrders($data) {
        logInfo("Deleting orders", ['order_ids' => $data['order_id'] ?? null]);
        
        $orderIds = is_array($data['order_id']) ? $data['order_id'] : [$data['order_id']];
        
        if (empty($orderIds)) {
            return ['status' => 'error', 'message' => 'Order IDs are required'];
        }
        
        $this->ordersCRUD->beginTransaction();
        
        try {
            $success = true;
            $errors = [];
            
            foreach ($orderIds as $orderId) {
                // Vérifier si la commande existe et peut être supprimée
                $order = $this->ordersCRUD->find($orderId);
                if (!$order) {
                    $errors[] = "Order #$orderId not found";
                    continue;
                }
                
                // Ne pas supprimer les commandes expédiées ou livrées
                if (in_array($order['status'], ['Shipped', 'Delivered'])) {
                    $errors[] = "Cannot delete order #$orderId (status: {$order['status']})";
                    $success = false;
                    continue;
                }
                
                // Supprimer d'abord les éléments de la commande
                $this->orderItemsCRUD->delete(['order_id' => $orderId]);
                
                // Puis supprimer la commande
                if (!$this->ordersCRUD->delete(['id' => $orderId])) {
                    $errors[] = "Failed to delete order #$orderId";
                    $success = false;
                }
            }
            
            if ($success) {
                $this->ordersCRUD->commit();
                return [
                    'status' => 'success',
                    'message' => 'Orders deleted successfully',
                    'warnings' => $errors
                ];
            } else {
                $this->ordersCRUD->rollback();
                return [
                    'status' => 'error',
                    'message' => 'Some orders could not be deleted',
                    'errors' => $errors
                ];
            }
            
        } catch (Exception $e) {
            $this->ordersCRUD->rollback();
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}