<?php require_once "../DIR.php"   ?>
<?php
require_once SHARED_PATH . "session.php";
require_once SHARED_PATH . 'erreur.php';


$isAdmin = isset($_SESSION['admin']) && $_SESSION['admin'] == 'admin';
// Séparer les requêtes API des requêtes de pages
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Si c'est une requête API
if (strpos($path, '/api/') === 0) {
    require_once BASE_PATH . '/api/api_gateway.php';
    exit;
}

// Pour les routes normales
$routes = require_once BASE_PATH . '/routes/routes.php';
$uri = rtrim($path, '/');

try {
    if (array_key_exists($uri, $routes)) {
        $view = $routes[$uri];
        $viewPath = "../views/{$view}.php";
        
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            sendErrorResponse('MISSING_PARAMETERS', $isAdmin);
        }
    } else {
        sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
} catch (Exception $e) {
    sendErrorResponse('UNSUPPORTED_CONTENT_TYPE', $isAdmin);
}
if (isset($userId) && !empty($userId) && $isAdmin == false) {

    if (!isset($_SESSION['cart'][$userId])) {
        $_SESSION['cart'][$userId] = [];
    }
    $cart = $_SESSION['cart'][$userId];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
        $productId = $_POST['product_id'];
        $quantity = $_POST['quantity'] ?? 1;

        $cart[] = ['product_id' => $productId, 'quantity' => $quantity];
        $_SESSION['cart'][$userId] = $cart;
    }
}
?>