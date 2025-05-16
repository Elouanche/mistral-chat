<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

function renderForm($id, $title, $fields, $submitText) {
    echo "<form id=\"$id\" class=\"form-container\">";
    echo "<h2>$title</h2>";
    foreach ($fields as $field) {
        echo "<div class=\"form-group\">";
        echo "<label for=\"{$field['id']}\">{$field['label']}</label>";
        echo "<input type=\"{$field['type']}\" id=\"{$field['id']}\" placeholder=\"{$field['placeholder']}\" required>";
        echo "</div>";
    }
    echo "<button class='button-spe' type=\"submit\">$submitText</button>";
    echo "<p id=\"{$id}Error\" class=\"error\"></p>";
    echo "</form>";
}

require_once SHARED_PATH . "session.php";
require_once COMPONENT_PATH . "head.php";
?>

<main role="main" class="main-content">
    <div class="zone-test">
        
        <?php
        if (!$isAdmin) {
            //Auth acces/admin/user----------------------
            // Formulaire d'inscription
            renderForm("registerAuth", "Register Auth", [
                ['id' => 'registerEmail', 'label' => 'Email', 'type' => 'email', 'placeholder' => 'Enter email'],
                ['id' => 'registerPassword', 'label' => 'Password', 'type' => 'password', 'placeholder' => 'Enter password'],
            ], "Register");

            // Formulaire de connexion (authentification)
            renderForm("loginAuth", "Login Auth", [
                ['id' => 'loginEmail', 'label' => 'Email', 'type' => 'email', 'placeholder' => 'Enter your email'],
                ['id' => 'loginPassword', 'label' => 'Password', 'type' => 'password', 'placeholder' => 'Enter password'],
            ], "Login");
            // Formulaire de connexion (authentification)
            renderForm("loginAuthAdmin", "Login Auth Admin", [
                ['id' => 'loginEmail', 'label' => 'Email', 'type' => 'email', 'placeholder' => 'Enter your email'],
                ['id' => 'loginPassword', 'label' => 'Password', 'type' => 'password', 'placeholder' => 'Enter password'],
            ], "Login admin");

            // Formulaire de réinitialisation du mot de passe
            renderForm("resetPassword", "Reset Password", [
                ['id' => 'resetPasswordEmail', 'label' => 'Email', 'type' => 'email', 'placeholder' => 'Enter email for reset link'],
            ], "Reset Password");

            
            // Formulaire de vérification d'email pour activer un compte (gestion utilisateur)
            renderForm("verifyEmail", "Verify Email", [
                ['id' => 'verifyEmail', 'label' => 'Email', 'type' => 'email', 'placeholder' => 'Enter email to verify'],
            ], "Verify Email");

            //User acces/admin/user---------------------------
            renderForm("deleteUser", "Delete User", [
                ['id' => 'deleteUserId', 'label' => 'User ID', 'type' => 'number', 'placeholder' => 'Enter user ID'],
            ], "Delete User");

            renderForm("updateUser", "Update User", [
                ['id' => 'updateUserId', 'label' => 'User ID', 'type' => 'number', 'placeholder' => 'Enter user ID'],
                ['id' => 'updateUsername', 'label' => 'New Username', 'type' => 'text', 'placeholder' => 'Enter new username'],
                ['id' => 'updateEmail', 'label' => 'New Email', 'type' => 'email', 'placeholder' => 'Enter new email'],
                ['id' => 'updateUserPassword', 'label' => 'New Password', 'type' => 'password', 'placeholder' => 'Enter new password'],
            ], "Update User");

            // Récupération du profil utilisateur
            renderForm("getUserProfile", "Get User Profile", [
                ['id' => 'profileUserId', 'label' => 'User ID', 'type' => 'number', 'placeholder' => 'Enter user ID to retrieve profile'],
            ], "Get User Profile");

            // Activation de l'utilisateur
            renderForm("activateUser", "Activate User", [
                ['id' => 'activateUserId', 'label' => 'User ID', 'type' => 'number', 'placeholder' => 'Enter user ID to activate'],
            ], "Activate User");
            // Order Management
            renderForm("createOrder", "Create Order", [
                ['id' => 'orderUserId', 'label' => 'User ID', 'type' => 'number', 'placeholder' => 'Enter user ID'],
                ['id' => 'orderProductId', 'label' => 'Product ID', 'type' => 'number', 'placeholder' => 'Enter product ID'],
                ['id' => 'orderQuantity', 'label' => 'Quantity', 'type' => 'number', 'placeholder' => 'Enter quantity'],
            ], "Create Order");
        
            renderForm("createReturn", "Create Return", [
                ['id' => 'returnUserId', 'label' => 'User ID', 'type' => 'number', 'placeholder' => 'Enter user ID'],
                ['id' => 'returnOrderId', 'label' => 'Order ID', 'type' => 'number', 'placeholder' => 'Enter order ID'],
                ['id' => 'returnReason', 'label' => 'Reason', 'type' => 'text', 'placeholder' => 'Enter return reason'],
            ], "Create Return");
             // Formulaire de création d'avis
            renderForm("createReview", "Create Review", [
                ['id' => 'createReviewProductId', 'label' => 'Product ID', 'type' => 'number', 'placeholder' => 'Enter product ID'],
                ['id' => 'createReviewContent', 'label' => 'Review Content', 'type' => 'text', 'placeholder' => 'Enter your review'],
                ['id' => 'createReviewRating', 'label' => 'Rating', 'type' => 'number', 'placeholder' => 'Enter rating (1-5)', 'min' => 1, 'max' => 5],
            ], "Submit Review");
            // Formulaire de mise à jour d'un avis
            renderForm("updateReview", "Update Review", [
                ['id' => 'updateReviewId', 'label' => 'Review ID', 'type' => 'number', 'placeholder' => 'Enter review ID'],
                ['id' => 'updateReviewContent', 'label' => 'New Content', 'type' => 'text', 'placeholder' => 'Enter new content for the review'],
                ['id' => 'updateReviewRating', 'label' => 'New Rating', 'type' => 'number', 'placeholder' => 'Enter new rating (1-5)', 'min' => 1, 'max' => 5],
            ], "Update Review");
        }


        //Common
        renderForm("updateReturn", "Update Return", [
            ['id' => 'updateReturnId', 'label' => 'Return ID', 'type' => 'number', 'placeholder' => 'Enter return ID'],
            ['id' => 'updateReturnStatus', 'label' => 'Status', 'type' => 'text', 'placeholder' => 'Enter new status'],
        ], "Update Return");
         // Notifications
        renderForm("getUserNotifications", "Get User Notifications", [
            ['id' => 'notificationUserId', 'label' => 'User ID', 'type' => 'number', 'placeholder' => 'Enter user ID'],
        ], "Get Notifications");

        renderForm("markNotificationAsRead", "Mark Notification as Read", [
            ['id' => 'notificationId', 'label' => 'Notification ID', 'type' => 'number', 'placeholder' => 'Enter notification ID'],
        ], "Mark as Read");
        // Service de paiement
        renderForm("processPayment", "Process Payment", [
            ['id' => 'paymentOrderId', 'label' => 'Order ID', 'type' => 'number', 'placeholder' => 'Enter order ID'],
            ['id' => 'paymentAmount', 'label' => 'Amount', 'type' => 'number', 'placeholder' => 'Enter payment amount'],
        ], "Process Payment");

        // Livraison
        renderForm("trackShipment", "Track Shipment", [
            ['id' => 'trackingNumber', 'label' => 'Tracking Number', 'type' => 'text', 'placeholder' => 'Enter tracking number'],
        ], "Track");
        renderForm("getPaymentStatus", "Get Payment Status", [
            ['id' => 'paymentId', 'label' => 'Payment ID', 'type' => 'number', 'placeholder' => 'Enter payment ID'],
        ], "Get Status");
        renderForm("cancelOrder", "Cancel Order", [
            ['id' => 'cancelOrderId', 'label' => 'Order ID', 'type' => 'number', 'placeholder' => 'Enter order ID'],
        ], "Cancel Order");
        // Formulaire pour récupérer les avis d'un produit
        renderForm("getProductReviews", "Get Product Reviews", [
            ['id' => 'productReviewId', 'label' => 'Product ID', 'type' => 'number', 'placeholder' => 'Enter product ID to get reviews'],
        ], "Get Reviews");
        

        
        if ($isAdmin) {
            // Liste des commandes admin
            //User acces/super-admin---------------------------
            renderForm("createAdmin", "Create Admin", [
                ['id' => 'superAdminCode', 'label' => 'SuperAdmin', 'type' => 'password', 'placeholder' => 'super password'],
                ['id' => 'createAdminname', 'label' => 'Username', 'type' => 'text', 'placeholder' => 'Enter username'],
                ['id' => 'createEmail', 'label' => 'Email', 'type' => 'email', 'placeholder' => 'Enter email'],
                ['id' => 'createAdminPassword', 'label' => 'Password', 'type' => 'password', 'placeholder' => 'Enter password'],
            ], "Create Admin");

            // Product acces/admin---------------------------
            renderForm("createProduct", "Create Product", [
                ['id' => 'createProductName', 'label' => 'Product Name', 'type' => 'text', 'placeholder' => 'Enter product name'],
                ['id' => 'createProductPrice', 'label' => 'Price', 'type' => 'number', 'placeholder' => 'Enter product price'],
                ['id' => 'createProductDescription', 'label' => 'Description', 'type' => 'text', 'placeholder' => 'Enter product description'],
                ['id' => 'createProductImage', 'label' => 'Image URL', 'type' => 'text', 'placeholder' => 'Enter product image URL'],
            ], "Create Product");

            renderForm("deleteProduct", "Delete Product", [
                ['id' => 'deleteProductId', 'label' => 'Product ID', 'type' => 'number', 'placeholder' => 'Enter product ID'],
            ], "Delete Product");

            renderForm("updateProduct", "Update Product", [
                ['id' => 'updateProductId', 'label' => 'Product ID', 'type' => 'number', 'placeholder' => 'Enter product ID'],
                ['id' => 'updateProductName', 'label' => 'New Product Name', 'type' => 'text', 'placeholder' => 'Enter new product name'],
                ['id' => 'updateProductPrice', 'label' => 'New Price', 'type' => 'number', 'placeholder' => 'Enter new price'],
                ['id' => 'updateProductDescription', 'label' => 'New Description', 'type' => 'text', 'placeholder' => 'Enter new description'],
                ['id' => 'updateProductImage', 'label' => 'New Image URL', 'type' => 'text', 'placeholder' => 'Enter new image URL'],
            ], "Update Product");

            renderForm("getAllProduct", "Get All Product", [
                ['id' => 'productFilters', 'label' => 'Filters (optional)', 'type' => 'text', 'placeholder' => 'Enter filters JSON'],
            ], "Get Users");

            // Recherche des produits
            renderForm("searchProducts", "Search Products", [
                ['id' => 'searchProductQuery', 'label' => 'Search Query', 'type' => 'text', 'placeholder' => 'Enter search query'],
            ], "Search Products");
            
            renderForm("getAllOrders", "Get All Orders", [], "Get All Orders");
            
            renderForm("getAllUsers", "Get All Users", [
                ['id' => 'userFilters', 'label' => 'Filters (optional)', 'type' => 'text', 'placeholder' => 'Enter filters JSON'],
            ], "Get Users");

            renderForm("updateStock", "Update Stock", [
                ['id' => 'productId', 'label' => 'Product ID', 'type' => 'number', 'placeholder' => 'Enter product ID'],
                ['id' => 'newStock', 'label' => 'New Stock Quantity', 'type' => 'number', 'placeholder' => 'Enter new stock quantity'],
            ], "Update Stock");

            renderForm("getServiceStatus", "Get Service Status", [], "Check Status");
            // Mise à jour du statut de la commande
            renderForm("updateOrderStatus", "Update Order Status", [
                ['id' => 'updateOrderId', 'label' => 'Order ID', 'type' => 'number', 'placeholder' => 'Enter order ID'],
                ['id' => 'updateOrderStatus', 'label' => 'Order Status', 'type' => 'text', 'placeholder' => 'Enter new status'],
            ], "Update Order Status");

            // Processus de retour de produit
            renderForm("processReturn", "Process Return", [
                ['id' => 'returnProductId', 'label' => 'Product ID', 'type' => 'number', 'placeholder' => 'Enter product ID'],
                ['id' => 'returnStatus', 'label' => 'Return Status', 'type' => 'text', 'placeholder' => 'Enter return status'],
            ], "Process Return");

            // Approbation ou rejet du retour
            renderForm("approveReturn", "Approve Return", [
                ['id' => 'approveReturnId', 'label' => 'Return ID', 'type' => 'number', 'placeholder' => 'Enter return ID'],
            ], "Approve Return");

            renderForm("rejectReturn", "Reject Return", [
                ['id' => 'rejectReturnId', 'label' => 'Return ID', 'type' => 'number', 'placeholder' => 'Enter return ID'],
            ], "Reject Return");
            // Formulaire pour récupérer les avis d'un utilisateur
            renderForm("getUserReviews", "Get User Reviews", [
                ['id' => 'userReviewId', 'label' => 'User ID', 'type' => 'number', 'placeholder' => 'Enter your user ID to see reviews'],
            ], "Get User Reviews");


            
        }

        // Autres
        renderForm("getCategories", "Get Categories", [], "Get Categories");

        ?>
    </div>
</main>
<script src="<?php echo STATIC_URL; ?>js/postData.js"></script>
<script src="<?php echo STATIC_URL; ?>js/formConfigurations.js"></script>
<script src="<?php echo STATIC_URL; ?>js/prepaForms.js"></script>
<?php require_once COMPONENT_PATH . "foot.php"; ?>