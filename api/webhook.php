<?php
// webhook.php - À placer à la racine de votre projet ou dans un dossier accessible

require_once __DIR__ . '/config/init.php'; // Adapter selon votre structure de projet
require_once CONFIG_PATH . 'coDB.php';
require_once SERVICES_PATH . 'payment-service/src/Payment.php';
require_once SERVICES_PATH . 'order-service/src/Order.php';

// Récupérer le payload et la signature
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Charger la clé secrète pour les webhooks
require BASE_PATH . "/securiser/stripe_webhook_secret.php";
if (!isset($stripe_webhook_secret)) {
    http_response_code(500);
    exit('Configuration error: webhook secret not defined');
}

try {
    // Vérifier la signature Stripe
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $stripe_webhook_secret
    );
    
    // Connexion à la base de données
    $conn = coDB();
    $paymentService = new Payment($conn);
    $orderService = new Order($conn);
    
    // Traiter l'événement selon son type
    switch ($event->type) {
        case 'checkout.session.completed':
            handleCheckoutSessionCompleted($event->data->object, $paymentService, $orderService);
            break;
            
        case 'payment_intent.succeeded':
            handlePaymentIntentSucceeded($event->data->object, $paymentService, $orderService);
            break;
            
        case 'payment_intent.payment_failed':
            handlePaymentFailed($event->data->object, $paymentService);
            break;
            
        case 'charge.refunded':
            handleRefund($event->data->object, $paymentService);
            break;
            
        default:
            // Événement ignoré
            http_response_code(200);
            exit();
    }
    
    // Répondre avec un succès
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    
} catch(\UnexpectedValueException $e) {
    // Signature invalide
    logError('Webhook error: ' . $e->getMessage());
    http_response_code(400);
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    // Erreur de vérification de signature
    logError('Webhook signature error: ' . $e->getMessage());
    http_response_code(400);
    exit();
} catch(\Exception $e) {
    // Erreur interne
    logError('Webhook processing error: ' . $e->getMessage());
    http_response_code(500);
    exit();
}

/**
 * Traite un paiement réussi via Checkout
 */
function handleCheckoutSessionCompleted($session, $paymentService, $orderService) {
    $conn = $paymentService->getConnection();
    $orderId = $session->metadata->order_id ?? null;
    $userId = $session->metadata->user_id ?? null;
    
    if (!$orderId || !$userId) {
        logError('Webhook: missing order or user ID in checkout session');
        return;
    }
    
    try {
        $conn->begin_transaction();
        
        // Mettre à jour le statut du paiement
        $paymentService->updatePaymentStatus([
            'order_id' => $orderId,
            'status' => 'Completed'
        ]);
        
        // Mettre à jour le statut de la commande
        $orderService->updateOrderStatus([
            'order_id' => [$orderId],
            'status' => 'Paid'
        ]);
        
        // Récupérer l'email de l'utilisateur
        $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user) {
            // Envoyer l'email de confirmation
            require_once SERVICES_PATH . 'notification-service/src/Notification.php';
            $notificationService = new Notification($conn);
            $notificationService->sendEmail([
                'email_data' => [
                    'type' => 'order_confirmation',
                    'to' => $user['email'],
                    'subject' => 'Confirmation de commande',
                    'order_id' => $orderId,
                    'base_url' => getBaseUrl(),
                ]
            ]);
        }
        
        $conn->commit();
        logInfo("Webhook: Paiement validé pour commande #$orderId (user #$userId)");
        
    } catch (Exception $e) {
        $conn->rollback();
        logError('Webhook error processing payment: ' . $e->getMessage());
    }
}

/**
 * Traite un paiement réussi via PaymentIntent
 */
function handlePaymentIntentSucceeded($paymentIntent, $paymentService, $orderService) {
    $orderId = $paymentIntent->metadata->order_id ?? null;
    $userId = $paymentIntent->metadata->user_id ?? null;
    
    if (!$orderId || !$userId) {
        logError('Webhook: missing order or user ID in payment intent');
        return;
    }
    
    try {
        // Mettre à jour le statut du paiement
        $paymentService->updatePaymentStatus([
            'order_id' => $orderId,
            'status' => 'Completed'
        ]);
        
        // Mettre à jour le statut de la commande
        $orderService->updateOrderStatus([
            'order_id' => [$orderId],
            'status' => 'Paid'
        ]);
        
        logInfo("Webhook: PaymentIntent réussi pour commande #$orderId");
        
    } catch (Exception $e) {
        logError('Webhook error processing payment intent: ' . $e->getMessage());
    }
}

/**
 * Traite un échec de paiement
 */
function handlePaymentFailed($paymentIntent, $paymentService) {
    $orderId = $paymentIntent->metadata->order_id ?? null;
    
    if (!$orderId) {
        logError('Webhook: missing order ID in failed payment');
        return;
    }
    
    try {
        // Mettre à jour le statut du paiement
        $paymentService->updatePaymentStatus([
            'order_id' => $orderId,
            'status' => 'Failed'
        ]);
        
        logInfo("Webhook: Échec de paiement pour commande #$orderId");
        
    } catch (Exception $e) {
        logError('Webhook error processing failed payment: ' . $e->getMessage());
    }
}

/**
 * Traite un remboursement
 */
function handleRefund($charge, $paymentService) {
    // Retrouver le payment_intent_id à partir de la charge
    $paymentIntentId = $charge->payment_intent;
    
    if (!$paymentIntentId) {
        logError('Webhook: missing payment intent ID in refund');
        return;
    }
    
    try {
        $conn = $paymentService->getConnection();
        
        // Retrouver l'ID de commande à partir du payment_intent_id
        $stmt = $conn->prepare("SELECT id, order_id FROM payments WHERE stripe_payment_id = ?");
        $stmt->bind_param("s", $paymentIntentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $payment = $result->fetch_assoc();
        
        if ($payment) {
            // Mettre à jour le statut du paiement
            $paymentService->updatePaymentStatus([
                'order_id' => $payment['order_id'],
                'status' => 'Refunded'
            ]);
            
            logInfo("Webhook: Remboursement traité pour commande #{$payment['order_id']}");
        } else {
            logError("Webhook: Paiement non trouvé pour remboursement (payment_intent: $paymentIntentId)");
        }
        
    } catch (Exception $e) {
        logError('Webhook error processing refund: ' . $e->getMessage());
    }
}

/**
 * Récupère l'URL de base du site
 */
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    return $protocol . '://' . $_SERVER['HTTP_HOST'];
}