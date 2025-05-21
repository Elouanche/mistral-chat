<?php 


// Récupérer l'ID de commande
$orderId = $_GET['order_id'] ?? null;

if ($orderId) {
    // 1. Vérifier l'état du paiement
    $data = ['order_id' => $orderId];
    $paymentStatus = makeApiRequest('Payment', 'processPayment', $data);

    // Vérifier si le paiement existe et son statut
    $paymentExists = isset($paymentStatus['status']) && $paymentStatus['status'] === 'success';
    $paymentFailed = !$paymentExists || 
                     (isset($paymentStatus['payment_status']) && $paymentStatus['payment_status'] === 'Failed');

    if ($paymentFailed) {
        // 2. Récupérer les détails de la commande
        $orderResult = makeApiRequest('Order', 'getOrder', ['order_id' => $orderId]);

        if (isset($orderResult['status']) && $orderResult['status'] === 'success') {
            // Vérifier si les items existent
            $items = isset($orderResult['data']['items']) ? $orderResult['data']['items'] : [];
            
            // 3. Recréer le panier avec les articles de la commande
            $cartData = [
                'user_id' => $_SESSION['user_id'] ?? null,
                'items' => $items
            ];
            $cartResult = makeApiRequest('Cart', 'createCart', $cartData);

            // 4. Pour chaque article, l'ajouter au panier
            if (isset($cartResult['status']) && $cartResult['status'] === 'success' && !empty($items)) {
                foreach ($items as $item) {
                    if (isset($item['product_id']) && isset($item['quantity'])) {
                        $addItemData = [
                            'cart_id' => $cartResult['data']['cart_id'] ?? null,
                            'user_id' => $_SESSION['user_id'] ?? null,
                            'product_id' => $item['product_id'],
                            'quantity' => $item['quantity']
                        ];
                        makeApiRequest('Cart', 'addToCart', $addItemData);
                    }
                }
            }

            // 5. Supprimer la commande et le paiement
            makeApiRequest('Order', 'cancelOrder', ['order_id' => $orderId]);
            makeApiRequest('Payment', 'deletePayment', ['order_id' => $orderId]);
        }
    } elseif ($paymentExists && isset($paymentStatus['payment_status']) && $paymentStatus['payment_status'] === 'Completed') {
        // Si le paiement a été validé, rediriger vers la page de succès
        header('Location: /success?order_id=' . $orderId);
        exit;
    }
} else {
    header('Location: /');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php require_once COMPONENT_PATH . 'head.php'; ?>
    <title>Mistral Chat - Cancel</title>
</head>

<body>
    <?php require_once COMPONENT_PATH . 'header.php'; ?>

<main class="cancel-page">
    <h1>Paiement annulé</h1>
    
    <section>
        <p>Votre paiement a été annulé et la commande a été supprimée. Votre panier a été recréé avec les articles de la commande.</p>
        <a href="/cart" class="button">Retour au panier</a>
    </section>
</main>

<?php require_once COMPONENT_PATH . "foot.php"; ?>