<?php


// Vérifier la connexion utilisateur
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

// Préparation des paramètres pour l'API
$params = [];
if ($userId) {
    $params['user_id'] = $userId;
}

// Obtenir le panier via l'API
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
    <title>Mistral Chat - Cart</title>
</head>

<body>
    <?php require_once COMPONENT_PATH . 'header.php'; ?>

<link rel="stylesheet" href="<?= htmlspecialchars(STATIC_URL); ?>css/page-cart.css">


<main class="cart-page" role="main">
    <section class="cart-container">
        <div class="cart-header">
            <h1 class="cart-title">
                <?= $isLoggedIn && isset($_SESSION['user_username']) ? 'Panier de ' . htmlspecialchars($_SESSION['user_username']) : 'Votre Panier'; ?>
                
            </h1>
        </div>

        <div id="cart-items" class="cart-items">
            <?php if (!empty($cartItems)): ?>
                <?php foreach ($cartItems as $item): ?>
                    <article class="cart-item" data-product-id="<?= (int) $item['product_id']; ?>">
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
                            <div class="product-header">
                                <h4><?= htmlspecialchars($item['product_name']); ?></h4>
                                <p class="item-total">Total: <?= number_format($item['item_total'], 2); ?>€</p>
                            </div>
                            <!--<p class="item-description"><?php /*  htmlspecialchars($item['product_description']); */ ?></p>-->
                            <div class="price-quantity">
                                <p class="price">Unit : <?= number_format($item['price'], 2); ?>€</p>
                                <div class="quantity-controls">
                                    <button 
                                        class="quantity-btn minus" 
                                        onclick="decreaseQuantity(<?= (int) $item['product_id']; ?>)"
                                        <?= $item['quantity'] <= 1 ? 'disabled' : ''; ?>
                                        aria-label="Diminuer la quantité"
                                    >-</button>
                                    <span class="quantity"><?= (int) $item['quantity']; ?></span>
                                    <button 
                                        class="quantity-btn plus"
                                        onclick="increaseQuantity(<?= (int) $item['product_id']; ?>)"
                                        aria-label="Augmenter la quantité"
                                    >+</button>
                                </div>
                               
                                
                            </div>
                            <button 
                                class="remove-btn" 
                                onclick="removeFromCart(<?= (int) $item['product_id']; ?>)"
                                aria-label="Supprimer <?= htmlspecialchars($item['product_name']); ?>"
                            >
                                Supprimer
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>

                <div class="cart-actions">
                   
                    <button onclick="clearCart()" class="btn btn-secondary" aria-label="Vider tout le panier">
                        Vider le panier
                    </button>
                    <div class="cart-total">
                        <h3>Total: <span><?= number_format($total, 2); ?>€</span></h3>
                    </div>
                    
                    
                        <a  class="btn btn-primary" href="/checkout"  aria-label="Procéder au paiement">
                            Passer à la caisse
                        </a>
                    
                </div>
            <?php else: ?>
                <div class="empty-cart">
                    <p class="empty-cart-message" role="status">Votre panier est vide.</p>
                    <a href="/products" class="btn btn-primary">Continuer vos achats</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<!-- Scripts -->
<script>
// Définir globalement si l'utilisateur est connecté pour que le JS puisse l'utiliser
const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
const userId = <?= $userId ? $userId : 'null' ?>;
</script>

<script src="<?= htmlspecialchars(STATIC_URL); ?>js/page-cart.js" defer></script>

<?php require_once COMPONENT_PATH . 'foot.php'; ?>