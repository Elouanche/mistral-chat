<?php

use Stripe\ApiOperations\Update;
require_once CRUD_PATH . 'PaymentsCRUD.php';
require_once CRUD_PATH . 'OrdersCRUD.php';
require_once BASE_PATH . 'vendor/autoload.php';

// Énumération des statuts de paiement (PHP 8.1+)
enum PaymentStatus: string {
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded';
}

/**
 * Service de gestion des paiements avec intégration Stripe
 * Utilise PaymentsCRUD pour les opérations de base de données
 * Gère les paiements, remboursements et webhooks Stripe
 */
class PaymentService {
    // Constantes pour les types de paiement
    private const PAYMENT_TYPE_CARD = 'card';
    private const PAYMENT_TYPE_CHECKOUT = 'checkout';
    
    // Constantes pour les préfixes d'ID Stripe
    private const STRIPE_PAYMENT_INTENT_PREFIX = 'pi_';
    private const STRIPE_CHECKOUT_SESSION_PREFIX = 'cs_';
    
    // Constantes pour les statuts Stripe
    private const STRIPE_STATUS_MAPPING = [
        'succeeded' => 'Completed',
        'requires_payment_method' => 'Pending',
        'requires_confirmation' => 'Pending',
        'requires_action' => 'Pending',
        'processing' => 'Pending',
        'canceled' => 'Cancelled',
        'paid' => 'Completed',
        'unpaid' => 'Failed',
        'complete' => 'Completed',
        'open' => 'Pending'
    ];

    /** @var string $stripePublicKey Clé publique Stripe */
    private $stripePublicKey;
    /** @var PaymentsCRUD $paymentsCRUD Instance du CRUD paiements */
    private $paymentsCRUD;
    /** @var OrdersCRUD $ordersCRUD Instance du CRUD paiements */
    private $ordersCRUD;
    
    /** @var mysqli $mysqli Instance de connexion mysqli */
    private $mysqli;
    
    /** @var \Stripe\StripeClient $stripe Instance du client Stripe */
    private $stripe;
    
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
        $this->paymentsCRUD = new PaymentsCRUD($mysqli);
        $this->ordersCRUD = new OrdersCRUD($mysqli);
        
        if (!defined('SECURE_ACCESS')) {
            define('SECURE_ACCESS', true);
        }
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(dirname(dirname(__DIR__))));
        }
        
        // Initialisation de Stripe
        require BASE_PATH . "/securiser/stripe_api_keys.php";
        
        if (!isset($stripe_private_key) || !isset($stripe_public_key)) {
            logError("Configuration Stripe incomplète");

        }
        
        $this->stripePublicKey = $stripe_public_key;
        \Stripe\Stripe::setApiKey($stripe_private_key);
        $this->stripe = new \Stripe\StripeClient($stripe_private_key);
    }
    
    /**
     * Crée une intention de paiement Stripe pour une commande
     * 
     * @param array $data - Données du paiement (amount, currency, order_id, user_id)
     * @return array - Statut et informations sur l'intention de paiement
     */
    public function createPaymentIntent($data) {
        logInfo("Début de création d'une intention de paiement", ['order_id' => $data['order_id'] ?? null]);
        
        if (!isset($data['amount']) || !isset($data['order_id']) || !isset($data['user_id'])) {
            logError("Données manquantes pour la création du payment intent", $data);
            return [
                'status' => 'error',
                'message' => 'Données manquantes pour créer une intention de paiement'
            ];
        }

        $amountInCents = (int) round($data['amount'] * 100);
        logDebug("Montant converti pour Stripe", ['amount' => $data['amount'], 'cents' => $amountInCents]);

        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $amountInCents,
                'currency' => 'eur',
                'payment_method_types' => [self::PAYMENT_TYPE_CARD],
                'metadata' => [
                    'order_id' => $data['order_id'],
                    'user_id' => $data['user_id']
                ]
            ]);

            logInfo("Intention de paiement créée avec succès", ['id' => $paymentIntent->id]);

            $paymentData = [
                'order_id' => $data['order_id'],
                'user_id' => $data['user_id'],
                'payment_method' => self::PAYMENT_TYPE_CARD,
                'amount' => $data['amount'],
                'status' => PaymentStatus::PENDING->value,
                'stripe_payment_id' => $paymentIntent->id,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $paymentId = $this->paymentsCRUD->insert($paymentData);
            
            if (!$paymentId) {
                logError("Échec d'enregistrement du paiement en base de données", $paymentData);
                return [
                    'status' => 'error',
                    'message' => "Échec d'enregistrement du paiement en base de données"
                ];
            }

            logDebug("Paiement enregistré en base de données", ['payment_id' => $paymentId]);

            return [
                'status' => 'success',
                'client_secret' => $paymentIntent->client_secret,
                'id' => $paymentIntent->id,
                'payment_id' => $paymentId
            ];

        } catch (\Stripe\Exception\ApiErrorException $e) {
            logError("Erreur Stripe lors de la création du payment intent", [
                'message' => $e->getMessage(),
                'code' => $e->getStripeCode()
            ]);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Crée une session Stripe Checkout pour le paiement de la commande
     * 
     * @param array $data - order_id, user_id, et line_items
     * @return array - Détails de la session (url, id)
     */
    public function createCheckoutSession($data) {
        if (!isset($data['order_id']) || !isset($data['user_id']) || !isset($data['line_items'])) {
            logError("Données manquantes pour la création de session checkout", $data);
            return [
                'status' => 'error',
                'message' => 'Données manquantes pour créer une session Stripe Checkout'
            ];
        }
            
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";

        // Créer la session de paiement
        $session = $this->stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'success_url' => $protocol . '://' . $_SERVER['HTTP_HOST'] . '/success?type=order&order_id=' . $data['order_id'],
            'cancel_url' => $protocol . '://' . $_SERVER['HTTP_HOST'] . '/cancel?order_id=' . $data['order_id'],
            'line_items' => $data['line_items'],
            'metadata' => [
                'order_id' => $data['order_id'],
                'user_id' => $data['user_id']
            ]
        ]);

        if (!$session) {
            logError("Échec de création de la session checkout", $data);
            return [
                'status' => 'error',
                'message' => "Échec de création de la session checkout"
            ];
        }
    
        // Création de l'enregistrement dans la base de données
        $paymentData = [
            'order_id' => $data['order_id'],
            'user_id' => $data['user_id'],
            'payment_method' => self::PAYMENT_TYPE_CHECKOUT,
            'amount' => $this->calculateTotalAmount($data),
            'status' => PaymentStatus::PENDING->value,
            'stripe_payment_id' => $session->id,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $paymentId = $this->paymentsCRUD->insert($paymentData);
        
        if (!$paymentId) {
            logError("Échec d'enregistrement du paiement en base de données", $paymentData);
            return [
                'status' => 'error',
                'message' => "Échec d'enregistrement du paiement en base de données"
            ];
        }

        return [
            'status' => 'success',
            'redirect' => $session->url,
        ];
    }
    
    /**
     * Traite un paiement Stripe en validant, capturant et enregistrant en base
     * 
     * @param array $data - Données de transaction
     * @return array - Statut du paiement
     */
    /*
    public function processPayment($data) {
        $orderId     = $data['order_id'] ?? null;
        $totalAmount = $data['total_amount'] ?? null;
        $userId      = $data['user_id'] ?? null;
        $currency    = $data['currency'] ?? 'eur';

        logInfo("Processing payment", [
            'order_id' => $orderId,
            'amount' => $totalAmount
        ]);

        // Vérification des données essentielles
        if (!$orderId || !$totalAmount || !$userId) {
            logError("Données manquantes pour le traitement du paiement", $data);
            return [
                'status' => 'error',
                'message' => 'Données manquantes : order_id, total_amount et user_id sont requis'
            ];
        }

        try {
            $this->mysqli->begin_transaction();

            // Créer une intention de paiement Stripe si non fournie
            if (empty($data['stripe_payment_id'])) {
                $paymentIntentResult = $this->createPaymentIntent([
                    'amount'   => $totalAmount,
                    'order_id' => $orderId,
                    'user_id'  => $userId,
                    'currency' => $currency
                ]);

                if ($paymentIntentResult['status'] !== 'success') {
                    return $paymentIntentResult;
                }

                $data['stripe_payment_id'] = $paymentIntentResult['id'];
            }

            // Créer l'enregistrement de paiement
            $paymentData = [
                'order_id'         => $orderId,
                'user_id'          => $userId,
                'payment_method'   => 'card',
                'amount'           => $totalAmount,
                'status'           => PaymentStatus::PENDING->value,
                'stripe_payment_id'=> $data['stripe_payment_id'],
                'created_at'       => date('Y-m-d H:i:s')
            ];

            $paymentId = $this->paymentsCRUD->insert($paymentData);

            if (!$paymentId) {
                throw new Exception('Échec de la création de l’enregistrement de paiement');
            }

            $this->mysqli->commit();
            return [
                'status'      => 'success',
                'redirect'    => $paymentIntentResult['client_secret'] ?? null,
                'payment_id'  => $paymentId,
                'message'     => 'Traitement du paiement initialisé'
            ];

        } catch (Exception $e) {
            $this->mysqli->rollback();
            logError("Erreur lors du traitement du paiement", [
                'error' => $e->getMessage(),
                'data'  => $data
            ]);
            return [
                'status'  => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
        */

    
    /**
     * Récupère le statut et l'ID Stripe d'un paiement
     * 
     * @param array $data - Données pour récupérer le statut (payment_id requis)
     * @return array|false - Données du paiement ou false
     */
    public function getPaymentStatus($data) {
        $paymentId = $data['payment_id'] ?? null;
        if (!$paymentId) {
            return ['status' => 'error', 'message' => 'Payment ID required'];
        }
        
        $payment = $this->paymentsCRUD->find($paymentId);
        if (!$payment) {
            return ['status' => 'error', 'message' => 'Payment not found'];
        }
        
        if (!empty($payment['stripe_payment_id'])) {
            $stripeStatus = $this->syncStripeStatus(['payment' => $payment]);
            if ($stripeStatus['status'] === 'success') {
                $payment = $stripeStatus['data'];
            }
        }
        
        return ['status' => 'success', 'data' => $payment];
    }
    
    /**
     * Synchronise le statut d'un paiement avec Stripe
     * 
     * @param array $data - Données du paiement
     * @return array - Statut mis à jour
     */
    private function syncStripeStatus($data) {
        $payment = $data['payment'] ?? $data;
        logDebug("Début de synchronisation du statut Stripe", ['payment_id' => $payment['id'] ?? null]);

        if (!isset($payment['stripe_payment_id']) || empty($payment['stripe_payment_id'])) {
            logWarning("ID Stripe manquant pour la synchronisation");
            return ['status' => 'error', 'message' => 'ID Stripe manquant'];
        }

        $stripeId = $payment['stripe_payment_id'];
        $stripeStatus = null;

        if (str_starts_with($stripeId, self::STRIPE_CHECKOUT_SESSION_PREFIX)) {
            logDebug("Synchronisation d'une session Checkout", ['session_id' => $stripeId]);
            $stripeStatus = $this->fetchCheckoutSessionStatus(['stripe_payment_id' => $stripeId]);
        } elseif (str_starts_with($stripeId, self::STRIPE_PAYMENT_INTENT_PREFIX)) {
            logDebug("Synchronisation d'un PaymentIntent", ['intent_id' => $stripeId]);
            $stripeStatus = $this->fetchPaymentStatus(['stripe_payment_id' => $stripeId]);
        } else {
            logWarning("Format d'ID Stripe non reconnu", ['stripe_id' => $stripeId]);
            return ['status' => 'error', 'message' => 'Format d\'ID Stripe non reconnu'];
        }

        if ($stripeStatus['status'] === 'success') {
            $status = $stripeStatus['db_status'];
            logInfo("Statut Stripe synchronisé", ['status' => $status]);
            $this->paymentsCRUD->update(
                ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')],
                ['id' => $payment['id']]
            );
            
            $payment['payment_status'] = $status;
            return ['status' => 'success', 'data' => $payment];
        } else {
            logWarning("Erreur lors de la synchronisation avec Stripe");
            return $stripeStatus; // Retourne l'erreur Stripe
        }
    }
    
    /**
     * Convertit les statuts Stripe en statuts internes
     * 
     * @param array $data - Données contenant le statut Stripe
     * @return string - Statut interne
     */
    private function mapStripeStatus($data): string {
        $stripeStatus = $data['stripe_status'];
        return self::STRIPE_STATUS_MAPPING[$stripeStatus] ?? 'Pending';
    }
    
    /**
     * Vérifie et met à jour le statut d'un paiement directement via l'API Stripe
     * 
     * @param array $data - Données contenant l'ID du paiement Stripe à vérifier
     * @return array - Résultat de la vérification
     */
    public function checkPaymentStatus($data) {
        $stripePaymentId = $data['stripe_payment_id'] ?? null;
        if (!$stripePaymentId) {
            return ['status' => 'error', 'message' => 'ID de paiement Stripe requis'];
        }
        
        // Vérifier si c'est une session checkout ou un payment intent
        if (str_starts_with($stripePaymentId, self::STRIPE_CHECKOUT_SESSION_PREFIX)) {
            // C'est une session checkout
            $stripeStatus = $this->fetchCheckoutSessionStatus($stripePaymentId);
        } elseif (str_starts_with($stripePaymentId, self::STRIPE_PAYMENT_INTENT_PREFIX)) {
            // C'est un payment intent
            $stripeStatus = $this->fetchPaymentStatus($stripePaymentId);
        } else {
            return ['status' => 'error', 'message' => 'Format d\'ID Stripe non reconnu'];
        }
        
        if ($stripeStatus['status'] === 'success') {
            // Rechercher le paiement dans la base de données
            $payment = $this->paymentsCRUD->get(['*'], ['stripe_payment_id' => $stripePaymentId])[0] ?? null;
            
            if ($payment) {
                // Mise à jour du statut du paiement dans la base de données
                $this->paymentsCRUD->update(
                    ['status' => $stripeStatus['db_status'], 'updated_at' => date('Y-m-d H:i:s')],
                    ['id' => $payment['id']]
                );
                
                if ($stripeStatus['db_status'] === PaymentStatus::COMPLETED->value) {
                    return [
                        'status' => 'pending',
                        'service' => 'Order',
                        'action' => 'updateOrderStatus',
                        'data' => [
                            'order_id' => $payment['order_id'],
                            'status' => 'Paid'
                        ]
                    ];
                } elseif ($stripeStatus['db_status'] === PaymentStatus::FAILED->value) {
                    // Gérer l'échec du paiement
                    logWarning("Échec du paiement: " . $stripePaymentId);
                }
                
                return [
                    'status' => 'success',
                    'message' => "Statut du paiement vérifié et mis à jour",
                    'payment_status' => $stripeStatus['db_status'],
                    'payment_id' => $payment['id'],
                    'order_id' => $payment['order_id']
                ];
            } else {
                return ['status' => 'error', 'message' => 'Paiement non trouvé dans la base de données'];
            }
        } else {
            return $stripeStatus; // Retourne l'erreur Stripe
        }
    }
    
    /**
     * Vérifie le statut d'un remboursement via l'API Stripe
     * 
     * @param array $data - Données contenant l'ID du remboursement Stripe
     * @return array - Statut du remboursement
     */
    public function checkRefundStatus($data) {
        $refundId = $data['refund_id'] ?? null;
        if (!$refundId) {
            return ['status' => 'error', 'message' => 'ID de remboursement requis'];
        }
        
        $refund = $this->stripe->refunds->retrieve($refundId);
        
        $refundStatus = $refund->status;
        $dbStatus = $refundStatus === 'succeeded' ? PaymentStatus::REFUNDED->value : PaymentStatus::PROCESSING->value;
        
        // Rechercher le paiement associé au remboursement
        $payment = $this->paymentsCRUD->get(['*'], ['refund_id' => $refundId])[0] ?? null;
        
        if ($payment) {
            // Mise à jour du statut du paiement
            $this->paymentsCRUD->update(
                ['status' => $dbStatus, 'updated_at' => date('Y-m-d H:i:s')],
                ['id' => $payment['id']]
            );
            
            // Si remboursement total et réussi, retourner le statut 'pending'
            if ($dbStatus === PaymentStatus::REFUNDED->value && $payment['refunded_amount'] == $payment['amount']) {
                return [
                    'status' => 'pending',
                    'service' => 'Order',
                    'action' => 'updateOrderStatus',
                    'data' => [
                        'order_id' => $payment['order_id'],
                        'status' => 'Cancelled'
                    ]
                ];
            }
            
            return [
                'status' => 'success',
                'message' => 'Statut du remboursement vérifié',
                'refund_status' => $refundStatus,
                'payment_id' => $payment['id']
            ];
        } else {
            return ['status' => 'error', 'message' => 'Paiement associé au remboursement non trouvé'];
        }
    }
    
    /**
     * Rembourse un paiement existant via Stripe
     * 
     * @param array $data - ID du paiement à rembourser
     * @return array - Statut du remboursement
     */
    public function refundPayment($data) {
        logInfo("Processing refund", [
            'payment_id' => $data['payment_id'] ?? null,
            'amount' => $data['amount'] ?? null
        ]);

        $paymentId = $data['payment_id'] ?? null;
        $amount = $data['amount'] ?? null; // Montant optionnel pour remboursement partiel
        $reason = $data['reason'] ?? 'Customer request';
        
        if (!$paymentId) {
            logWarning("ID de paiement manquant pour le remboursement");
            return ['status' => 'error', 'message' => 'Payment ID is required'];
        }
        
        // Récupération du paiement
        $payment = $this->paymentsCRUD->find($paymentId);
        
        if (!$payment) {
            return ['status' => 'error', 'message' => 'Payment not found'];
        }
        
        if (!$payment['stripe_payment_id']) {
            return ['status' => 'error', 'message' => 'Aucun ID Stripe trouvé pour ce paiement'];
        }
        
        // Vérification que le paiement peut être remboursé
        if ($payment['status'] !== PaymentStatus::COMPLETED->value) {
            return ['status' => 'error', 'message' => 'Only completed payments can be refunded'];
        }
        
        // Si le montant n'est pas spécifié, rembourser le montant total
        if (!$amount) {
            $amount = $payment['amount'];
        }
        
        // Vérification que le montant du remboursement ne dépasse pas le montant du paiement
        if ($amount > $payment['amount']) {
            return ['status' => 'error', 'message' => 'Refund amount cannot exceed payment amount'];
        }
        
        // Création du remboursement dans Stripe
        $refund = $this->stripe->refunds->create([
            'payment_intent' => $payment['stripe_payment_id'],
            'amount' => round($amount * 100), // Convertir en centimes pour Stripe
            'reason' => 'requested_by_customer'
        ]);
        
        if (!$refund || !isset($refund->id)) {
            logWarning("Échec de création du remboursement Stripe");
            return ['status' => 'error', 'message' => 'Échec de création du remboursement'];
        }
        
        // Mise à jour du statut du paiement dans la BDD
        $this->paymentsCRUD->update([
            'status' => $amount == $payment['amount'] ? PaymentStatus::REFUNDED->value : PaymentStatus::PARTIALLY_REFUNDED->value,
            'refund_id' => $refund->id,
            'refunded_amount' => $amount,
            'refund_reason' => $reason,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $payment['id']]);
        
        // Mise à jour du statut de la commande si remboursement total
        if ($amount == $payment['amount']) {
            return [
                'status' => 'pending',
                'service' => 'Order',
                'action' => 'updateOrderStatus',
                'data' => [
                    'order_id' => $payment['order_id'],
                    'status' => 'Cancelled'
                ]
            ];
        }

        return [
            'status' => 'success', 
            'message' => 'Remboursement effectué avec succès',
            'refund_id' => $refund->id
        ];
    }
    

    /**
     * Gère l'annulation d'un paiement et supprime la commande associée
     * 
     * @param array $data Données de l'annulation
     * @return array Statut de l'opération
     */
    public function handleCancelledPayment($data) {
        if (!isset($data['order_id'])) {
            return ['status' => 'error', 'message' => 'ID de commande requis'];
        }

        $result = $this->paymentsCRUD->update(
            ['status' => PaymentStatus::CANCELLED->value],
            ['order_id' => $data['order_id']]
        );

        if (!$result) {
            return ['status' => 'error', 'message' => 'Échec de mise à jour du paiement'];
        }

        return [
            'status' => 'pending',
            'service' => 'Order',
            'action' => 'cancelOrder',
            'data' => [
                'order_id' => $data['order_id']
            ]
        ];
    }
    
    /**
     * Récupère l'historique des paiements d'un utilisateur
     * 
     * @param array $data Données de l'utilisateur (user_id, page, limit)
     * @return array Historique des paiements
     */
    public function getUserPayments($data) {
        logInfo("Listing payments", [
            'page' => $data['page'] ?? 1,
            'limit' => $data['limit'] ?? 10
        ]);

        $userId = $data['user_id'] ?? null;
        $page = isset($data['page']) ? (int)$data['page'] : 1;
        $limit = isset($data['limit']) ? (int)$data['limit'] : 10;
        
        if (!$userId) {
            return ['status' => 'error', 'message' => 'User ID is required'];
        }
        
        // Calcul de l'offset pour la pagination
        $offset = ($page - 1) * $limit;
        
        // Récupération des paiements
        $payments = $this->paymentsCRUD->get(
            ['*'],
            ['user_id' => $userId],
            ['orderBy' => 'created_at', 'orderDirection' => 'DESC', 'limit' => $limit, 'offset' => $offset]
        );
        
        // Mise à jour des statuts avec Stripe pour les paiements récents
        foreach ($payments as &$payment) {
            if (!empty($payment['stripe_payment_id'])) {
                if (str_starts_with($payment['stripe_payment_id'], self::STRIPE_PAYMENT_INTENT_PREFIX)) {
                    $stripeStatus = $this->fetchPaymentStatus($payment['stripe_payment_id']);
                    if ($stripeStatus['status'] === 'success') {
                        $payment['status'] = $stripeStatus['db_status'];
                        // Mettre à jour en base de données
                        $this->paymentsCRUD->update(
                            ['status' => $stripeStatus['db_status'], 'updated_at' => date('Y-m-d H:i:s')],
                            ['id' => $payment['id']]
                        );
                    }
                } elseif (str_starts_with($payment['stripe_payment_id'], self::STRIPE_CHECKOUT_SESSION_PREFIX)) {
                    $stripeStatus = $this->fetchCheckoutSessionStatus($payment['stripe_payment_id']);
                    if ($stripeStatus['status'] === 'success') {
                        $payment['status'] = $stripeStatus['db_status'];
                        // Mettre à jour en base de données
                        $this->paymentsCRUD->update(
                            ['status' => $stripeStatus['db_status'], 'updated_at' => date('Y-m-d H:i:s')],
                            ['id' => $payment['id']]
                        );
                    }
                }
            }
        }
        
        // Comptage du nombre total de paiements pour la pagination
        $total = $this->paymentsCRUD->count(['user_id' => $userId]);
        
        return [
            'status' => 'success', 
            'message' => 'User payments retrieved successfully', 
            'data' => [
                'payments' => $payments,
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
     * Crée un enregistrement de paiement dans la base de données
     * @param array $data Données du paiement à enregistrer
     * @return array Statut de l'opération
     */
    public function createPaymentRecord($data) {
        logInfo("Getting payment details", ['payment_id' => $data['payment_id'] ?? null]);

        // Validation des données
        $required = ['order_id', 'user_id', 'payment_method', 'amount', 'status'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return [
                    'status' => 'error',
                    'message' => "Champ requis manquant: $field"
                ];
            }
        }
        
        // Préparation des données pour l'insertion
        $paymentData = [
            'order_id' => $data['order_id'],
            'user_id' => $data['user_id'],
            'payment_method' => $data['payment_method'],
            'amount' => $data['amount'],
            'status' => $data['status'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        if (isset($data['stripe_payment_id'])) {
            $paymentData['stripe_payment_id'] = $data['stripe_payment_id'];
        }
        
        // Insertion dans la base de données
        $paymentId = $this->paymentsCRUD->insert($paymentData);
        
        if (!$paymentId) {
            return [
                'status' => 'error',
                'message' => "Erreur lors de l'insertion du paiement"
            ];
        }
        
        return [
            'status' => 'success',
            'message' => 'Enregistrement de paiement créé',
            'payment_id' => $paymentId
        ];
    }
    
    /**
     * Supprime un paiement de la base de données.
     *
     * @param array $data - Données contenant l'identifiant de la commande associée au paiement.
     * @return array - Statut de l'opération et message.
     */
    public function deletePayment($data) {
        $orderId = $data['order_id'] ?? null;
        
        if (!$orderId) {
            return ['status' => 'error', 'message' => 'ID de commande requis'];
        }
        
        // Supprimer le paiement associé à la commande
        $result = $this->paymentsCRUD->delete(['order_id' => $orderId]);
        
        if ($result) {
            return [
                'status' => 'success',
                'message' => "Paiement supprimé pour la commande ID: $orderId"
            ];
        } else {
            return [
                'status' => 'error',
                'message' => "Aucun paiement trouvé pour la commande ID: $orderId"
            ];
        }
    }
    
    /**
     * Vérifie l'état d'un paiement via Stripe et met à jour la base de données si nécessaire.
     *
     * @param array $data - Données contenant l'identifiant de la commande associée au paiement.
     * @return array - Statut du paiement et message.
     */
    public function processPayment($data) {
        $orderId = $data['order_id'] ?? null;
        if (!$orderId) {
            return ['status' => 'error', 'message' => 'ID de commande requis'];
        }
        

        $payment = $this->paymentsCRUD->get(
            ['*'], 
            ['order_id' => $orderId ]
        )[0] ?? null;
        
        if (!$payment) {
            return ['status' => 'error', 'message' => 'Paiement non trouvé'];
        }

        $stripeStatus = $this->syncStripeStatus(['payment' => $payment]);
        if ($stripeStatus['status'] == 'success') {
            return [
                'status' => 'success',
                'message' => 'Statut Stripe synchronisé avec succès',
                'data' => [

                    'payment_status' => $stripeStatus['data']['payment_status'],
                    'payment_id' => $payment['id'],
                    'order_id' => $payment['order_id']
                ]
                
            ];
            
        } else {
            return [
                'status' => 'error',
                'message' => 'Erreur lors de la synchronisation du statut Stripe',
                'details' => $stripeStatus['message'] ?? null
            ];
        }
       

       
    }
    
    /**
     * Récupère le statut d'un PaymentIntent Stripe (paiement)
     * et met à jour la base si besoin.
     *
     * @param array $data - Données contenant l'ID du paiement Stripe
     * @return array
     */
    private function fetchPaymentStatus($data): array {
        $stripePaymentId = $data['stripe_payment_id'] ?? null;
        
        if (!$stripePaymentId) {
            return ['status' => 'error', 'message' => 'ID de paiement Stripe requis'];
        }
        
        $pi = $this->stripe->paymentIntents->retrieve($stripePaymentId);
        
        if (!$pi) {
            logWarning("Échec de récupération du PaymentIntent: " . $stripePaymentId);
            return ['status' => 'error', 'message' => 'Échec de récupération du PaymentIntent'];
        }
        
        $stripeStatus = $pi->status;
        $dbStatus = self::STRIPE_STATUS_MAPPING[$stripeStatus] ?? 'Pending';
        
        // Mettre à jour en base de données
        $payment = $this->paymentsCRUD->get(['id'], ['stripe_payment_id' => $stripePaymentId])[0] ?? null;
        if ($payment) {
            $this->paymentsCRUD->update(
                ['status' => $dbStatus, 'updated_at' => date('Y-m-d H:i:s')],
                ['id' => $payment['id']]
            );
        }
        
        return [
            'status' => 'success', 
            'stripe_status' => $stripeStatus,
            'db_status' => $dbStatus
        ];
    }
    
    /**
     * Récupère le statut d'une session Checkout Stripe
     * et met à jour la base si besoin.
     *
     * @param array $data - Données contenant l'ID de la session Checkout
     * @return array
     */
    private function fetchCheckoutSessionStatus($data): array {
        $sessionId = $data['stripe_payment_id'] ?? null;
        
        if (!$sessionId) {
            return ['status' => 'error', 'message' => 'ID de session Stripe requis'];
        }
        
        $session = $this->stripe->checkout->sessions->retrieve($sessionId);
        
        if (!$session) {
            logWarning("Échec de récupération de la session Checkout: " . $sessionId);
            return ['status' => 'error', 'message' => 'Échec de récupération de la session Checkout'];
        }
        
        $stripeStatus = $session->payment_status;
        $dbStatus = self::STRIPE_STATUS_MAPPING[$stripeStatus] ?? 'Pending';
        
        // Si la session est expirée, on considère le paiement comme échoué
        $dbStatus = $session->status === 'expired' && $stripeStatus === 'unpaid' ? PaymentStatus::FAILED->value : $dbStatus;
        
        // Mettre à jour en base de données
        $payment = $this->paymentsCRUD->get(['id'], ['stripe_payment_id' => $sessionId])[0] ?? null;
        if ($payment) {
            $this->paymentsCRUD->update(
                ['status' => $dbStatus, 'updated_at' => date('Y-m-d H:i:s')],
                ['id' => $payment['id']]
            );
        }
        
        return [
            'status' => 'success', 
            'stripe_status' => $session->payment_status,
            'db_status' => $dbStatus
        ];
    }

    /**
     * Calcule le montant total à partir des lignes de commande
     * 
     * @param array $lineItems - Lignes de commande (items)
     * @return float - Montant total
     */
    private function calculateTotalAmount($data) {
        $lineItems = $data['line_items'] ?? null;
        if (!$lineItems) {
            logWarning("Aucun élément de ligne trouvé pour le calcul du montant total");
            return 0;
        }
        $total = 0;
        foreach ($lineItems as $item) {
            $total += $item['price_data']['unit_amount'] * $item['quantity'];
        }
        return round($total, 2); // Arrondir à deux décimales
    }
}