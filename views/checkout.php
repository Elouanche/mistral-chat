<?php



// Vérification de l'utilisateur
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

// Préparation des paramètres pour l'API
$params = [];
if ($userId) {
    $params['user_id'] = $userId;
}
$cartResult = makeApiRequest('Cart', 'getCart', $params);
$cartData = [];
$cartItems = [];
$total = 0;
$itemCount = 0;

// Traitement de la réponse API
if (is_array($cartResult) && isset($cartResult['status']) && $cartResult['status'] === 'success') {
    $cartData = $cartResult['data'];
    $cartItems = $cartData['items'] ?? [];
    $total = $cartData['total_amount'] ?? 0;
    $itemCount = $cartData['item_count'] ?? 0;
} else {
    logError("Erreur lors de la récupération du panier", $cartResult);
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php require_once COMPONENT_PATH . 'head.php'; ?>
    <title>Mistral Chat - Checkout</title>
</head>

<body>
    <?php require_once COMPONENT_PATH . 'header.php'; ?>

<!-- Liens CSS -->
<link rel="stylesheet" href="<?= htmlspecialchars(STATIC_URL); ?>css/checkout-page.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">


<main role="main" class="checkout-page">
    <h1>Finaliser votre commande</h1>

    <div class="checkout-layout">
        <form method="POST" class="checkout-form" id="checkoutForm" aria-label="Formulaire de validation de commande">
            <input type="hidden" name="cart-data" id="cart-data" value='<?= json_encode($cartItems); ?>'>

            <?php if (!$isLoggedIn): ?>
                <fieldset>
                    <legend>Vos informations</legend>
                    <div class="form-group">
                        <label for="UserEmail">Email</label>
                        <input type="email" id="UserEmail" name="email" required aria-required="true">
                    </div>
                    <div class="form-group">
                        <label for="phone">Numéro de téléphone</label>
                        <input type="tel" id="phone" name="phone" required aria-required="true">
                    </div>
                </fieldset>
            <?php endif; ?>

            <fieldset>
                <legend>Informations de livraison</legend>
                <?php
                $fields = [
                    ['label' => 'Nom complet', 'id' => 'full_name'],
                    ['label' => 'Adresse', 'id' => 'shipping_street'],
                    ['label' => 'Ville', 'id' => 'shipping_city'],
                    ['label' => 'Région', 'id' => 'shipping_state'],
                    ['label' => 'Code Postal', 'id' => 'shipping_postal_code'],
                    ['label' => 'Pays', 'id' => 'shipping_country', 'value' => 'FR']
                ];

                foreach ($fields as $field):
                    $value = $field['value'] ?? '';
                ?>
                    <div class="form-group">
                        <label for="<?= htmlspecialchars($field['id']); ?>"><?= htmlspecialchars($field['label']); ?></label>
                        <input
                            type="text"
                            id="<?= htmlspecialchars($field['id']); ?>"
                            name="<?= htmlspecialchars($field['id']); ?>"
                            value="<?= htmlspecialchars($value); ?>"
                            required
                            aria-required="true"
                        >
                    </div>
                <?php endforeach; ?>
            </fieldset>

            <button type="submit" class="btn btn-primary" aria-label="Confirmer votre commande">
                <i class="fas fa-credit-card"></i> Confirmer la commande
            </button>
        </form>

        <aside class="cart-container order-summary" aria-label="Résumé de votre commande">
            <h3>Récapitulatif de votre commande</h3>
            <div id="cart-items" class="cart-items">
                <?php if (!empty($cartItems)): ?>
                    <?php foreach ($cartItems as $item): ?>
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
        </aside>
    </div>
</main>

<!-- Scripts -->
<script>
    window.cartData = <?= json_encode($cartItems, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    window.checkoutUserId = <?= $userId !== null ? (int)$userId : 'null'; ?>;
</script>

<script src="<?= htmlspecialchars(STATIC_URL); ?>js/page-checkout.js"></script>

<?php require_once COMPONENT_PATH . 'foot.php'; ?>