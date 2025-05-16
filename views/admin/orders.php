<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Session sécurisée
require_once SHARED_PATH . 'session.php';

// Vérification d'accès admin
if (empty($_SESSION['admin']) || $_SESSION['admin'] !== 'admin') {
    header('Location: /user/login');
    exit;
}

// Middleware admin pour sécuriser les sous-pages admin
if (strpos($_SERVER['REQUEST_URI'], '/admin/') === 0) {
    require_once SHARED_PATH . 'adminMiddleware.php';
    checkAdminAccess();
}

require_once COMPONENT_PATH . 'head.php';
require_once SHARED_PATH . 'apiRequest.php';

// Récupérer toutes les commandes via API Gateway
$orders = makeApiRequest('AdminOrder', 'getAllOrders');

// Récupérer les détails des commandes
$orderDetails = [];
foreach ($orders as $order) {
    $orderDetails[$order['id']] = makeApiRequest('AdminOrder', 'getOrderDetails', [
        'order_id' => $order['id']
    ]);
}

// Statuts possibles
$possibleStatuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
?>

<main class="admin-orders" role="main">
    <div class="page-header">
        <h1>Gestion des Commandes</h1>
    </div>

    <section class="orders-controls" aria-labelledby="ordersControls">
        <h2 id="ordersControls" class="visually-hidden">Filtres de commandes</h2>

        <div class="filter-container">
            <label for="statusFilter" class="visually-hidden">Filtrer par statut</label>
            <select id="statusFilter" class="filter-select" aria-label="Filtrer les commandes par statut">
                <option value="all">Tous les statuts</option>
                <?php foreach ($possibleStatuses as $status): ?>
                    <option value="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="search-container">
            <label for="searchOrder" class="visually-hidden">Rechercher une commande</label>
            <input type="search" id="searchOrder" placeholder="Rechercher une commande..." class="search-input" aria-label="Recherche de commande">
        </div>
    </section>

    <section class="orders-table-container" aria-labelledby="ordersTableTitle">
        <h2 id="ordersTableTitle" class="visually-hidden">Liste des commandes</h2>

        <table id="ordersTable" class="orders-table">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Client</th>
                    <th scope="col">Date</th>
                    <th scope="col">Montant</th>
                    <th scope="col">Statut</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr data-order-id="<?= (int) $order['id']; ?>" data-status="<?= htmlspecialchars($order['status']); ?>">
                    <td><?= (int) $order['id']; ?></td>
                    <td><?= htmlspecialchars($order['username']); ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                    <td><?= number_format((float) $order['total_amount'], 2); ?> €</td>
                    <td>
                        <label for="status-<?= (int) $order['id']; ?>" class="visually-hidden">Changer statut</label>
                        <select id="status-<?= (int) $order['id']; ?>" class="status-select" data-original-status="<?= htmlspecialchars($order['status']); ?>">
                            <?php foreach ($possibleStatuses as $status): ?>
                                <option value="<?= htmlspecialchars($status); ?>" <?= ($status === $order['status']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($status); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <button class="view-details-btn" type="button" data-order-id="<?= (int) $order['id']; ?>">Détails</button>
                        <button class="save-status-btn" type="button" hidden>Enregistrer</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- Modal pour afficher les détails d'une commande -->
    <div id="orderDetailsModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle" hidden>
        <div class="modal-content">
            <button type="button" class="close" aria-label="Fermer">&times;</button>
            <h2 id="modalTitle">Détails de la commande #<span id="modalOrderId"></span></h2>

            <div class="order-info">
                <p><strong>Client :</strong> <span id="modalCustomer"></span></p>
                <p><strong>Date :</strong> <span id="modalDate"></span></p>
                <p><strong>Statut :</strong> <span id="modalStatus"></span></p>
                <p><strong>Adresse de livraison :</strong> <span id="modalAddress"></span></p>
            </div>

            <h3>Produits commandés</h3>
            <table id="modalProductsTable" class="modal-products-table">
                <thead>
                    <tr>
                        <th scope="col">Produit</th>
                        <th scope="col">Quantité</th>
                        <th scope="col">Prix unitaire</th>
                        <th scope="col">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Dynamique via JS -->
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>Total</strong></td>
                        <td id="modalTotal"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</main>

<link rel="stylesheet" href="<?= htmlspecialchars(STATIC_URL); ?>css/orders.css">
<link rel="stylesheet" href="<?= htmlspecialchars(STATIC_URL); ?>css/modal.css">

<!-- Données JSON pour JavaScript -->
<script id="orderDetailsData" type="application/json"><?= json_encode($orderDetails, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
<script id="ordersData" type="application/json"><?= json_encode($orders, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>

<!-- Scripts -->
<script src="<?= htmlspecialchars(STATIC_URL); ?>js/admin-orders.js" defer></script>

<?php require_once COMPONENT_PATH . 'foot.php'; ?>
