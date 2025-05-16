<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once SHARED_PATH . "session.php";

// Sécurité : vérifier les droits admin avant toute action
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== 'admin') {
    header('Location: /user/login');
    exit;
}

// Middleware spécifique pour la zone admin
if (strpos($_SERVER['REQUEST_URI'], '/admin/') === 0) {
    require_once SHARED_PATH . 'adminMiddleware.php';
    checkAdminAccess();
}

require_once COMPONENT_PATH . 'head.php';
require_once SHARED_PATH . 'apiRequest.php';

// Récupération des produits via API Gateway
$products = makeApiRequest('AdminProduct', 'getAllProducts');
?>

<link rel="stylesheet" href="<?= htmlspecialchars(STATIC_URL); ?>css/admin-inventory.css">

<main class="admin-inventory" role="main">
    <div class="inventory-header">
        <h1 class="text-center">Gestion des Stocks</h1>
        
        <div class="inventory-controls">
            <button id="updateStocksBtn" class="button-primary" type="button">
                Mettre à jour les stocks
            </button>
            <div class="search-container">
                <label for="searchProduct" class="visually-hidden">Rechercher un produit</label>
                <input type="search" id="searchProduct" placeholder="Rechercher un produit..." class="search-input" aria-label="Rechercher un produit">
            </div>
        </div>
    </div>

    <section class="inventory-table-container" aria-labelledby="inventoryTableTitle">
        <h2 id="inventoryTableTitle" class="visually-hidden">Tableau de gestion des stocks</h2>

        <div class="table-responsive">
            <table id="inventoryTable" class="inventory-table">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Nom du produit</th>
                        <th scope="col">Description</th>
                        <th scope="col">Prix</th>
                        <th scope="col">Stock actuel</th>
                        <th scope="col">Nouveau stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>
                            <tr data-product-id="<?= (int)$product['id']; ?>">
                                <td><?= (int)$product['id']; ?></td>
                                <td><?= htmlspecialchars($product['name']); ?></td>
                                <td class="description-cell">
                                    <?= htmlspecialchars(mb_strimwidth($product['description'], 0, 50, '...')); ?>
                                </td>
                                <td><?= number_format((float)$product['price'], 2); ?> €</td>
                                <td class="current-stock"><?= (int)$product['stock']; ?></td>
                                <td>
                                    <label for="stock-<?= (int)$product['id']; ?>" class="visually-hidden">Nouveau stock pour <?= htmlspecialchars($product['name']); ?></label>
                                    <input 
                                        type="number" 
                                        id="stock-<?= (int)$product['id']; ?>" 
                                        class="stock-input" 
                                        value="<?= (int)$product['stock']; ?>" 
                                        min="0" 
                                        aria-label="Modifier le stock pour <?= htmlspecialchars($product['name']); ?>"
                                    >
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">Aucun produit trouvé.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script src="<?= htmlspecialchars(STATIC_URL); ?>js/admin-inventory.js" defer></script>

<?php require_once COMPONENT_PATH . 'foot.php'; ?>