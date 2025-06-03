<?php

require_once SHARED_PATH . 'userAcces.php';

// Initialisation des variables
$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
$checkoutType = $_GET['type'] ?? 'cart';
$planId = $_GET['plan_id'] ?? null;
$checkoutData = null;
$errorMessage = null;

logInfo('Démarrage du processus de checkout', [
    'user_id' => $userId,
    'checkout_type' => $checkoutType,
    'plan_id' => $planId
]);

try {
    // Validation du type de checkout et des paramètres requis
    if ($checkoutType === 'subscription') {
        logInfo('Traitement d\'un checkout de type abonnement');
        
        // Vérification du plan ID
        if (!$planId || !is_numeric($planId)) {
            logError('Plan ID manquant ou invalide', [
                'plan_id' => $planId,
                'is_numeric' => is_numeric($planId)
            ]);
            throw new Exception('Plan ID manquant ou invalide');
        }
        
        // Récupérer les détails du plan d'abonnement
        logInfo('Tentative de récupération des plans d\'abonnement');
        $plansResult = makeApiRequest('Subscription', 'getAvailablePlans', []);
        
        if (!isset($plansResult['status']) || !isset($plansResult['data'])) {
            logError('Réponse API invalide pour les plans', [
                'response' => $plansResult
            ]);
            throw new Exception('Erreur lors de la récupération des plans: Format de réponse invalide');
        }
        
        if ($plansResult['status'] !== 'success') {
            logError('Erreur lors de la récupération des plans', [
                'status' => $plansResult['status'],
                'message' => $plansResult['message'] ?? 'Erreur inconnue'
            ]);
            throw new Exception('Erreur lors de la récupération des plans: ' . ($plansResult['message'] ?? 'Erreur inconnue'));
        }
        
        if (!is_array($plansResult['data'])) {
            logError('Format de données des plans invalide', [
                'data_type' => gettype($plansResult['data'])
            ]);
            throw new Exception('Erreur lors de la récupération des plans: Format de données invalide');
        }
        
        $plans = $plansResult['data'];
        $selectedPlan = null;
        
        foreach ($plans as $plan) {
            if (!isset($plan['id'])) {
                logError('Plan sans ID détecté', [
                    'plan' => $plan
                ]);
                continue;
            }
            
            if ($plan['id'] == $planId) {
                $selectedPlan = $plan;
                logInfo('Plan sélectionné trouvé', [
                    'plan_id' => $plan['id'],
                    'plan_name' => $plan['name'] ?? 'N/A'
                ]);
                break;
            }
        }
        
        if (!$selectedPlan) {
            logError('Plan non trouvé', [
                'plan_id' => $planId,
                'available_plans' => array_column($plans, 'id')
            ]);
            throw new Exception('Le plan sélectionné n\'existe pas ou n\'est pas disponible');
        }
        
        $checkoutData = $selectedPlan;
        logInfo('Plan d\'abonnement validé avec succès', [
            'plan_id' => $planId,
            'plan_name' => $checkoutData['name'] ?? 'N/A',
            'plan_price' => $checkoutData['price'] ?? 0
        ]);
        
    } elseif ($checkoutType === 'cart') {
        logInfo('Traitement d\'un checkout de type panier');
        
        if (!$userId) {
            logError('Utilisateur non connecté lors du checkout de type panier', [
                'user_id' => $userId
            ]);
            throw new Exception('Utilisateur non connecté');
        }
        
        // Récupération du panier
        $params = ['user_id' => $userId];
        $cartResult = makeApiRequest('Cart', 'getCart', $params);
        if (!is_array($cartResult) || !isset($cartResult['status']) || $cartResult['status'] !== 'success' || empty($cartResult['data'])) {
            header('Location: /cart?error=invalid_cart');
            logError('Erreur lors de la récupération du panier', [
                'user_id' => $userId,
                'cart_result' => $cartResult
            ]);
            exit;
        }
        $checkoutData = $cartResult['data'];
    } else {
        throw new Exception('Type de checkout invalide');
        logError('Type de checkout invalide', [
            'checkout_type' => $checkoutType
        ]);
    }
    
} catch (Exception $e) {
    logError('Erreur durant le processus de checkout', [
        'error_message' => $e->getMessage(),
        'error_code' => $e->getCode(),
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine(),
        'checkout_type' => $checkoutType,
        'plan_id' => $planId
    ]);
    
    $errorMessage = $e->getMessage();
    
    if ($checkoutType === 'subscription') {
        header('Location: /subscription?error=checkout_failed&message=' . urlencode($errorMessage));
    } else {
        header('Location: /cart?error=checkout_failed&message=' . urlencode($errorMessage));
    }
    exit;
}

// Redirection si aucune donnée valide
if (!$checkoutData) {
    header('Location: /home?error=invalid_checkout');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php require_once COMPONENT_PATH . 'head.php'; ?>
    <title>Mistral Chat - Paiement</title>
</head>

<body>
    <?php require_once COMPONENT_PATH . 'header.php'; ?>

    <link rel="stylesheet" href="<?= htmlspecialchars(STATIC_URL); ?>css/page-checkout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <main role="main" class="checkout-page">
        <h1><?= $checkoutType === 'subscription' ? 'Finaliser votre abonnement' : 'Finaliser votre commande' ?></h1>

        <!-- Debug info (à supprimer en production) -->
        <?php if (isset($_GET['debug']) && !empty($_GET['debug'])): ?>
        <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc;">
            <strong>Debug Info:</strong> <?= htmlspecialchars($_GET['debug']) ?>
        </div>
        <?php endif; ?>

        <div class="checkout-layout">
            <!-- Récapitulatif de la commande -->
            <div class="order-summary">
                <h3><?= $checkoutType === 'subscription' ? 'Récapitulatif de l\'abonnement' : 'Récapitulatif de votre commande' ?></h3>
                
                <?php if ($checkoutType === 'subscription'): ?>
                    <div class="plan-details">
                        <h4><?= htmlspecialchars($checkoutData['name'] ?? 'Plan sans nom') ?></h4>
                        <p class="description"><?= htmlspecialchars($checkoutData['description'] ?? 'Aucune description') ?></p>
                        <div class="features">
                            <?php
                            $features = [];
                            if (isset($checkoutData['features'])) {
                                if (is_string($checkoutData['features'])) {
                                    $features = json_decode($checkoutData['features'], true) ?: [];
                                } elseif (is_array($checkoutData['features'])) {
                                    $features = $checkoutData['features'];
                                }
                            }
                            
                            if ($features && is_array($features)):
                                foreach ($features as $key => $value):
                                    if (is_array($value)):
                                        echo "<p><i class='fas fa-check'></i> " . htmlspecialchars(implode(", ", $value)) . "</p>";
                                    elseif ($value === true):
                                        echo "<p><i class='fas fa-check'></i> " . htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) . "</p>";
                                    elseif (is_string($value) || is_numeric($value)):
                                        echo "<p><i class='fas fa-check'></i> " . htmlspecialchars($key . ': ' . $value) . "</p>";
                                    endif;
                                endforeach;
                            endif;
                            ?>
                        </div>
                        <div class="price">
                            <span class="amount"><?= number_format(floatval($checkoutData['price'] ?? 0), 2) ?> €</span>
                            <span class="period">/mois</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="cart-items">
                        <?php if (!empty($checkoutData['items'])): ?>
                            <?php foreach ($checkoutData['items'] as $item): ?>
                                <article class="cart-item">
                                    <div class="cart-item-details">
                                        <h4><?= htmlspecialchars($item['name'] ?? 'Nom non défini'); ?></h4>
                                        <p>Prix unitaire : <?= number_format(floatval($item['price'] ?? 0), 2); ?> €</p>
                                        <p>Quantité : <?= intval($item['quantity'] ?? 0); ?></p>
                                        <p>Total : <?= number_format(floatval($item['price'] ?? 0) * intval($item['quantity'] ?? 0), 2); ?> €</p>
                                    </div>
                                </article>
                            <?php endforeach; ?>

                            <div class="cart-total">
                                <h3>Total : <?= number_format(floatval($checkoutData['total_amount'] ?? 0), 2); ?> €</h3>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Message d'erreur -->
                <div id="payment-message" class="hidden"></div>

                <!-- Bouton de paiement -->
                <div class="payment-actions">
                    <?php if ($checkoutType === 'subscription'): ?>
                        <a href="/subscription" class="btn btn-secondary">Retour aux abonnements</a>
                    <?php else: ?>
                        <a href="/cart" class="btn btn-secondary">Retour au panier</a>
                    <?php endif; ?>
                    
                    <button id="proceed-to-payment" class="btn btn-primary">
                        Procéder au paiement
                    </button>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Variables nécessaires pour le JavaScript
        window.userId = <?= $userId ? $userId : 'null' ?>;
        window.checkoutType = '<?= htmlspecialchars($checkoutType) ?>';
        window.planId = <?= $planId ? intval($planId) : 'null' ?>;
        window.checkoutData = <?= json_encode($checkoutData) ?>;
        
        // Debug info
        console.log('Checkout Data:', window.checkoutData);
        console.log('Plan ID:', window.planId);
        console.log('Checkout Type:', window.checkoutType);
    </script>
    <script src="<?= STATIC_URL ?>js/page-checkout.js"></script>

    <?php require_once COMPONENT_PATH . 'foot.php'; ?>