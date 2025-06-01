<?php

require_once SHARED_PATH . 'userAcces.php';
require_once SECURISER_PATH . 'stripe_api_keys.php';

// Initialisation des variables
$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
$checkoutType = $_GET['type'] ?? 'cart'; // 'cart' ou 'subscription'
$planId = $_GET['plan_id'] ?? null;

$total = 0;
$itemCount = 0;
$checkoutData = null;

// Récupération des données selon le type de checkout
if ($checkoutType === 'subscription' && $planId) {
    // Récupérer les détails du plan d'abonnement
    $planResult = makeApiRequest('SubscriptionPlans', 'getById', ['id' => $planId]);
    if (is_array($planResult) && isset($planResult['status']) && $planResult['status'] === 'success') {
        $checkoutData = $planResult['data'];
        $total = $checkoutData['price'];
    } else {
        header('Location: /subscription?error=invalid_plan');
        exit;
    }
} else {
    // Récupération du panier pour un achat normal
    $params = [];
    if ($userId) {
        $params['user_id'] = $userId;
    }
    $cartResult = makeApiRequest('Cart', 'getCart', $params);
    if (is_array($cartResult) && isset($cartResult['status']) && $cartResult['status'] === 'success') {
        $checkoutData = $cartResult['data'];
        $total = $checkoutData['total_amount'] ?? 0;
        $itemCount = $checkoutData['item_count'] ?? 0;
    } else {
        logError("Erreur lors de la récupération du panier", $cartResult);
    }
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
    <script src="https://js.stripe.com/v3/"></script>
</head>

<body>
    <?php require_once COMPONENT_PATH . 'header.php'; ?>

    <link rel="stylesheet" href="<?= htmlspecialchars(STATIC_URL); ?>css/page-checkout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <main role="main" class="checkout-page">
        <h1><?= $checkoutType === 'subscription' ? 'Finaliser votre abonnement' : 'Finaliser votre commande' ?></h1>

        <div class="checkout-layout">
            <form id="payment-form" class="checkout-form">
                <input type="hidden" name="checkout_type" value="<?= htmlspecialchars($checkoutType) ?>">
                <?php if ($checkoutType === 'subscription'): ?>
                    <input type="hidden" name="plan_id" value="<?= htmlspecialchars($planId) ?>">
                <?php else: ?>
                    <input type="hidden" name="cart-data" value='<?= json_encode($checkoutData['items'] ?? []); ?>'>
                <?php endif; ?>

                <div id="payment-element"></div>

                <button id="submit" class="btn btn-primary">
                    <div class="spinner hidden" id="spinner"></div>
                    <span id="button-text">Payer maintenant</span>
                </button>
                <div id="payment-message" class="hidden"></div>
            </form>

            <aside class="order-summary">
                <h3><?= $checkoutType === 'subscription' ? 'Récapitulatif de l\'abonnement' : 'Récapitulatif de votre commande' ?></h3>
                
                <?php if ($checkoutType === 'subscription'): ?>
                    <div class="plan-details">
                        <h4><?= htmlspecialchars($checkoutData['name']) ?></h4>
                        <p class="description"><?= htmlspecialchars($checkoutData['description'] ?? '') ?></p>
                        <div class="features">
                            <?php
                            $features = json_decode($checkoutData['features'], true);
                            if ($features && is_array($features)):
                                foreach ($features as $key => $value):
                                    if (is_array($value)):
                                        echo "<p><i class='fas fa-check'></i> " . implode(", ", $value) . "</p>";
                                    elseif ($value === true):
                                        echo "<p><i class='fas fa-check'></i> " . ucfirst(str_replace('_', ' ', $key)) . "</p>";
                                    endif;
                                endforeach;
                            endif;
                            ?>
                        </div>
                        <div class="price">
                            <span class="amount"><?= number_format($checkoutData['price'], 2) ?> €</span>
                            <span class="period">/mois</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div id="cart-items" class="cart-items">
                        <?php if (!empty($checkoutData['items'])): ?>
                            <?php foreach ($checkoutData['items'] as $item): ?>
                                <?php
                                    if (!isset($item['product_id'], $item['quantity'], $item['price'])) continue;

                                    $itemTotal = round($item['price'] * $item['quantity'], 2);
                                ?>
                                <article class="cart-item" data-product-id="<?= (int)$item['product_id']; ?>">
                                    <?php if (!empty($item['product_image'])): ?>
                                        <img 
                                            src="<?= htmlspecialchars(PRODUCT_IMAGES_URL . $item['product_image']); ?>" 
                                            alt="<?= htmlspecialchars($item['product_name']); ?>" 
                                            class="cart-item-image" 
                                            loading="lazy"
                                            width="100"
                                            height="100"
                                        >
                                    <?php else: ?>
                                        <div class="cart-item-image-placeholder">
                                            <span class="no-image">Pas d'image</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="cart-item-details">
                                        <div class="product-header-checkout">
                                            <h3><?= htmlspecialchars($item['name'] ?? 'Nom non défini'); ?></h3>
                                            <p class="price">Prix : <?= number_format($item['price'], 2); ?> €</p>
                                        </div>
                                        <p class="quantity-info">Quantité : <?= (int)$item['quantity']; ?></p>
                                        <p>Total : <?= number_format($itemTotal, 2); ?> €</p>
                                    </div>
                                </article>
                            <?php endforeach; ?>

                            <div class="cart-total">
                                <h3>Total : <span id="cart-total-price"><?= number_format($total, 2); ?> €</span></h3>
                            </div>
                        <?php else: ?>
                            <p>Votre panier est vide.</p>
                        <?php endif; ?>

                        <a href="/cart" class="btn btn-secondary" aria-label="Retour au panier">Retour au panier</a>
                    </div>
                <?php endif; ?>
            </aside>
        </div>
    </main>

    <script>
        // Définir les variables nécessaires pour le JavaScript
        const stripePublicKey = '<?php echo $stripe_public_key; ?>';
        const checkoutType = '<?php echo $checkoutType; ?>';
        const planId = '<?php echo $planId; ?>';
        const total = <?php echo $total; ?>;
        const userEmail = '<?php echo $userEmail; ?>';
    </script>
    <script src="<?php echo STATIC_URL; ?>js/page-checkout.js"></script>
    
    <?php require_once COMPONENT_PATH . 'foot.php'; ?>