<?php

use PHPUnit\Framework\SyntheticError;

ini_set('display_errors', 1); 
error_reporting(E_ALL);  
require_once SHARED_PATH . "session.php"; 
require_once COMPONENT_PATH . "head.php";  
require_once SHARED_PATH . 'apiRequest.php';

// Récupération des paramètres
$type = $_GET['type'] ?? 'order'; // Type de succès (order, email, etc.)
$orderId = $_GET['order_id'] ?? null;

if ($orderId) {
    
    // 1. Vérifier le statut du paiement
    $data = ['order_id' => $orderId];
    $paymentDetails = makeApiRequest('Payment', 'processPayment', $data);
    
    if ($paymentDetails['status'] === 'success') {
        // La réponse de l'API contient les détails du paiement
        $paymentStatus = $paymentDetails['data']['payment_status'] ?? null;
        
        if ($paymentStatus === 'Completed') {
            // 2. Mettre à jour le statut de la commande
            $orderData = ['order_id' => $orderId, 'status' => 'Paid'];
            $orderResult = makeApiRequest('Order', 'updateOrderStatus', $orderData);

            if ($orderResult['status'] === 'success') {
                // 3. Envoyer l'email de confirmation
                $emailData = [
                    'type' => 'order_confirmation',
                    'order_id' => $orderId,
                    'to' => $_SESSION['user_email'],
                    'username' => $_SESSION['user_username'],
                    'base_url' => ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
                ];
                $emailResult = makeApiRequest('Notification', 'sendEmail', ['email_data' => $emailData]);

                
                // 4. Vider le panier
                $cartData = [
                    'user_id' => $_SESSION['user_id'] ?? null,
                    'cart_id' => $_SESSION['cart_id'] ?? null
                ];
                makeApiRequest('Cart', 'clearCart', $cartData);
                
            } 
        }else {
            // Si le paiement n'est pas complété, rediriger vers la page d'annulation
            logError("Erreur de paiement : " . $paymentDetails, $paymentStatus);
            header('Location: /cancel?order_id=' . $orderId);
            exit;
        }

    } else {
        logError("Erreur lors du traitement du paiement2222", $paymentDetails);
        header('Location: /cancel?order_id=' . $orderId);
        exit;
    }
}

$userEmail = $_SESSION['user_email'] ?? null;
$message = $_GET['message'] ?? null; // Message personnalisé optionnel

// Configuration des messages selon le type
$titles = [
    'order' => 'Commande validée !',
    'email' => 'Email envoyé avec succès !',
    'contact' => 'Message envoyé avec succès !',
    'subscription' => 'Inscription réussie !',
    'payment' => 'Paiement effectué avec succès !',
    'default' => 'Opération réussie !'
];

$messages = [
    'order' => 'Merci pour votre achat ! Votre commande a été traitée avec succès. Un email de confirmation vous a été envoyé avec les détails de votre commande.',
    'email' => 'Votre email a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.',
    'contact' => 'Votre message a bien été envoyé. Notre équipe vous contactera prochainement.',
    'subscription' => 'Votre inscription a été confirmée. Vous recevrez nos prochaines communications.',
    'payment' => 'Votre paiement a été traité avec succès. Merci pour votre confiance.',
    'default' => 'Votre opération a été effectuée avec succès.'
];

// Sélection du titre et du message appropriés
$title = $titles[$type] ?? $titles['default']; 
$displayMessage = $message ?? ($messages[$type] ?? $messages['default']);

// Détermine le bouton de retour selon le type
$returnButtons = [
    'order' => '<a href="/" class="btn">Retour à l\'accueil</a>',
    'email' => '<a href="/contact" class="btn">Retour au contact</a>',
    'contact' => '<a href="/" class="btn">Retour à l\'accueil</a>',
    'subscription' => '<a href="/" class="btn">Retour à l\'accueil</a>',
    'payment' => '<a href="/user/account" class="btn">Mon compte</a>',
    'default' => '<a href="/" class="btn">Retour à l\'accueil</a>'
];

$returnButton = $returnButtons[$type] ?? $returnButtons['default'];
?>
<style>


</style>

<main role="main" class="success-page" data-type="<?= htmlspecialchars($type) ?>" data-order-id="<?= htmlspecialchars($orderId ?? '') ?>" data-user-email="<?= htmlspecialchars($userEmail ?? '') ?>">
    <h1><?= htmlspecialchars($title) ?></h1>
    
    <section>
        <p><?= htmlspecialchars($displayMessage) ?></p>
        <?= $returnButton ?>
    </section>
</main>


<script src="<?= htmlspecialchars(STATIC_URL); ?>js/page-success.js" defer></script>


<?php require_once COMPONENT_PATH . "foot.php"; ?>