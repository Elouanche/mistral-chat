<?php 
require_once "../DIR.php";
require_once SHARED_PATH . "session.php";
require_once SHARED_PATH . 'erreur.php';
require_once SHARED_PATH . 'apiRequest.php';

$isAdmin = isset($_SESSION['admin']) && $_SESSION['admin'] == 'admin';
// Séparer les requêtes API des requêtes de pages
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Si c'est une requête API
if (strpos($path, '/api/') === 0) {
    require_once BASE_PATH . 'api/api_gateway.php';
    exit;
}

// Pour les routes normales
$routes = require_once BASE_PATH . 'routes/routes.php';
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

// Initialisation du panier - utiliser la même clé que dans CartService
$sessionKey = 'user_cart';
if (!isset($_SESSION[$sessionKey])) {
    $_SESSION[$sessionKey] = [
        'items' => [],
        'total_amount' => 0,
        'item_count' => 0
    ];
}

// Récupérer l'ID utilisateur s'il est connecté
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Si on a une action POST d'ajout au panier, utiliser l'API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $productId = $_POST['product_id'];
    $quantity = $_POST['quantity'] ?? 1;
    
    // Préparation des données pour l'API
    $data = [
        'product_id' => $productId,
        'quantity' => $quantity
    ];
    
    // Ajouter l'ID utilisateur si connecté
    if ($userId) {
        $data['user_id'] = $userId;
    }
    
    // Appel à l'API Cart pour ajouter au panier
    makeApiRequest('Cart', 'addItem', $data);
}
?>