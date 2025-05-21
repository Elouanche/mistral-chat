<?php
require_once SHARED_PATH . 'admin.php';

// Fonction pour afficher un formulaire générique
function renderForm($id, $title, $fields, $submitText) {
    echo "<section aria-labelledby=\"{$id}Title\" class=\"form-section\">";
    echo "<h2 id=\"{$id}Title\">$title</h2>";
    echo "<form id=\"$id\" class=\"form-container\" novalidate>";
    
    foreach ($fields as $field) {
        $autocomplete = isset($field['autocomplete']) ? " autocomplete=\"{$field['autocomplete']}\"" : "";
        echo "<div class=\"form-group\">";
        echo "<label for=\"{$field['id']}\">{$field['label']}</label>";
        echo "<input type=\"{$field['type']}\" id=\"{$field['id']}\" placeholder=\"{$field['placeholder']}\"$autocomplete required aria-required=\"true\">";
        echo "</div>";
    }

    echo "<button type=\"submit\" class=\"button-spe\">$submitText</button>";
    echo "<p id=\"{$id}Error\" class=\"error-message\" role=\"alert\" hidden></p>";
    echo "</form>";
    echo "</section>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php require_once COMPONENT_PATH . 'head.php'; ?>
    <title>Mistral Chat - Dashbord</title>
</head>

<body>
    <?php require_once COMPONENT_PATH . 'header.php'; ?>


<link rel="stylesheet" href="<?= htmlspecialchars(STATIC_URL); ?>css/orders.css">
<link rel="stylesheet" href="<?= htmlspecialchars(STATIC_URL); ?>css/delivery.css">
<link rel="stylesheet" href="<?= htmlspecialchars(STATIC_URL); ?>css/admin-modal.css">


<main role="main">
    <div class="admin-dashboard">

        <section class="pending-orders-section" aria-labelledby="pendingOrdersTitle">
            <h2 id="pendingOrdersTitle">Commandes en cours</h2>

            <div class="bulk-actions mb-4">
                <div class="d-flex gap-3 align-items-center">
                    <button id="selectAllOrders" class="button-spe" type="button">Tout sélectionner</button>
                    <button id="deselectAllOrders" class="button-spe" type="button">Tout désélectionner</button>

                    <select id="bulkActionSelect" class="status-select mx-2">
                        <option value="">Actions groupées</option>
                        <option value="generate-invoice">Générer Factures</option>
                        <option value="Processing">Marquer En traitement</option>
                        <option value="Shipped">Marquer Expédié</option>
                        <option value="Delivered">Marquer Livré</option>
                        <option value="Cancelled">Marquer Annulé</option>
                    </select>
                    
                    <button id="applyBulkAction" class="button-spe" type="button">Appliquer</button>
                </div>
            </div>

            <div class="orders-table-container table-responsive">
                <table id="pendingOrdersTable" class="orders-table table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col" width="40"><input type="checkbox" id="selectAll"></th>
                            <th scope="col" width="60">ID</th>
                            <th scope="col" width="200">Client</th>
                            <th scope="col" width="120">Date</th>
                            <th scope="col" width="100">Montant</th>
                            <th scope="col" width="150">Statut</th>
                            <th scope="col" width="150">Paiement</th>
                            <th scope="col" width="180">Expédition</th>
                            <th scope="col" width="200">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <tr>
                            <td colspan="9" class="text-center">Chargement des commandes...</td>
                        </tr>
                    </tbody>
                </table>
                <nav aria-label="Navigation des pages de commandes" class="mt-3">
                    <ul id="ordersPagination" class="pagination justify-content-center">
                    </ul>
                </nav>
            </div>
        </section>

        <section class="delivery-management mb-4" aria-labelledby="deliveryManagementTitle">
            <h3 id="deliveryManagementTitle">Gestion des livraisons</h3>
            <p>Utilisez les boutons ci-dessous pour gérer les livraisons via ShipEngine :</p>

            <div class="delivery-actions mt-3 d-flex gap-3">
                <button id="createBulkShipments" class="button-spe" type="button">Créer expéditions en masse</button>
                <button id="generateBulkLabels" class="button-spe" type="button">Générer étiquettes en masse</button>
            </div>
        </section>

        <!-- Formulaires d'administration -->
        <?php
            renderForm('createUser', 'Créer un Utilisateur', [
                ['id' => 'createUsername', 'label' => "Nom d'utilisateur", 'type' => 'text', 'placeholder' => 'Entrer un nom', 'autocomplete' => 'username'],
                ['id' => 'createEmail', 'label' => 'Email', 'type' => 'email', 'placeholder' => 'Entrer un email', 'autocomplete' => 'email'],
                ['id' => 'createUserPassword', 'label' => 'Mot de passe', 'type' => 'password', 'placeholder' => 'Entrer un mot de passe', 'autocomplete' => 'new-password'],
            ], "Créer l'utilisateur");

            renderForm('deleteUser', 'Supprimer un Utilisateur', [
                ['id' => 'deleteUserId', 'label' => 'ID Utilisateur', 'type' => 'number', 'placeholder' => 'Entrer ID utilisateur']
            ], "Supprimer");

            renderForm('updateUser', 'Mettre à jour un Utilisateur', [
                ['id' => 'updateUserId', 'label' => 'ID Utilisateur', 'type' => 'number', 'placeholder' => 'Entrer ID utilisateur'],
                ['id' => 'updateUsername', 'label' => 'Nouveau nom', 'type' => 'text', 'placeholder' => 'Entrer nouveau nom']
            ], "Mettre à jour");

            renderForm('createProduct', 'Créer un Produit', [
                ['id' => 'createProductName', 'label' => 'Nom du produit', 'type' => 'text', 'placeholder' => 'Entrer nom produit'],
                ['id' => 'createProductPrice', 'label' => 'Prix', 'type' => 'number', 'placeholder' => 'Entrer prix'],
                ['id' => 'createProductDescription', 'label' => 'Description', 'type' => 'text', 'placeholder' => 'Entrer description']
            ], "Créer produit");

            renderForm('deleteProduct', 'Supprimer un Produit', [
                ['id' => 'deleteProductId', 'label' => 'ID Produit', 'type' => 'number', 'placeholder' => 'Entrer ID produit']
            ], "Supprimer");

            renderForm('updateProduct', 'Mettre à jour un Produit', [
                ['id' => 'updateProductId', 'label' => 'ID Produit', 'type' => 'number', 'placeholder' => 'Entrer ID produit'],
                ['id' => 'updateProductName', 'label' => 'Nouveau nom', 'type' => 'text', 'placeholder' => 'Entrer nouveau nom']
            ], "Mettre à jour");

            renderForm('createOrder', 'Créer une Commande', [
                ['id' => 'orderUserId', 'label' => 'ID Utilisateur', 'type' => 'number', 'placeholder' => 'Entrer ID utilisateur'],
                ['id' => 'orderProductId', 'label' => 'ID Produit', 'type' => 'number', 'placeholder' => 'Entrer ID produit']
            ], "Créer commande");

            renderForm('updateOrderStatus', 'Mettre à jour Statut Commande', [
                ['id' => 'updateOrderId', 'label' => 'ID Commande', 'type' => 'number', 'placeholder' => 'Entrer ID commande'],
                ['id' => 'updateOrderStatusInput', 'label' => 'Statut', 'type' => 'text', 'placeholder' => 'Entrer nouveau statut']
            ], "Mettre à jour");

            renderForm('generateInvoice', 'Créer Facture', [
                ['id' => 'invoiceOrderId', 'label' => 'ID Commande', 'type' => 'number', 'placeholder' => 'Entrer ID commande']
            ], "Créer facture");
        ?>
    </div>
</main>

<script src="<?= htmlspecialchars(STATIC_URL); ?>js/postData.js"></script>
<script src="<?= htmlspecialchars(STATIC_URL); ?>js/admin-dashboard.js" defer></script>

<?php require_once COMPONENT_PATH . 'foot.php'; ?>
