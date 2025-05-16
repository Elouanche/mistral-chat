<?php


// Lance la gestion

require_once "../DIR.php";
require_once CONFIG_PATH . "log_config.php";

// Partagés
require_once SHARED_PATH . "image.php";
// require_once SHARED_PATH . "verifyJWT.php";
require_once SHARED_PATH . "erreur.php";
require_once CONFIG_PATH . "coDB.php";
require_once API_PATH . "handleService.php";
require_once CONFIG_PATH . "mail.php";
/*
// Services utilisateurs
require_once SERVICES_PATH . "auth-service/src/Auth.php";
require_once SERVICES_PATH . "user-service/src/User.php";
require_once SERVICES_PATH . "product-service/src/Product.php";
require_once SERVICES_PATH . "cart-service/src/Cart.php";
require_once SERVICES_PATH . "cart-service/src/CartItem.php";
require_once SERVICES_PATH . "cart-service/src/CartManager.php";
require_once SERVICES_PATH . "cart-service/src/SessionCart.php";
require_once SERVICES_PATH . "order-service/src/Order.php";
require_once SERVICES_PATH . "returned-service/src/Returned.php";
require_once SERVICES_PATH . "review-service/src/Review.php";
// Services système
require_once SERVICES_PATH . "payment-service/src/Payment.php";
require_once SERVICES_PATH . "notification-service/src/Notification.php";
require_once SERVICES_PATH . "delivery-service/src/Delivery.php";
require_once SERVICES_PATH . "import-service/src/Import.php";
require_once SERVICES_PATH . "analytics-service/src/Analytics.php";

// Services communs
require_once SERVICES_PATH . "errorHandling-service/src/ErrorHandling.php";
require_once SERVICES_PATH . "monitoring-service/src/Monitoring.php";
*/

require_once SERVICE_CRUD_PATH . "AuthService.php";
require_once SERVICE_CRUD_PATH . "UserService.php";
require_once SERVICE_CRUD_PATH . "ProductService.php";
require_once SERVICE_CRUD_PATH . "CartService.php";
require_once SERVICE_CRUD_PATH . "OrderService.php";
require_once SERVICE_CRUD_PATH . "ReturnService.php";
require_once SERVICE_CRUD_PATH . "PaymentService.php";
require_once SERVICE_CRUD_PATH . "NotificationService.php";
require_once SERVICE_CRUD_PATH . "DeliveryService.php";
require_once SERVICE_CRUD_PATH . "SupportService.php";
//require_once SERVICE_CRUD_PATH . "ImportService.php";
require_once SERVICE_CRUD_PATH . "AnalyticsService.php";
require_once SERVICE_CRUD_PATH . "MonitoringService.php";
require_once SERVICE_CRUD_PATH . "ErrorLoggingService.php";

// Vérifier si la constante pour l'accès sécurisé existe déjà
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

$isAdmin = isset($_SESSION['admin']) && $_SESSION['admin'] === 'admin';

define('ALLOWED_SERVICES', [
    // Services utilisateurs
    'Auth', 'User', 'Product', 'Cart', 'Order', 'Returned', 'Review',
    // Services admin
    'AdminOrder', 'AdminProduct', 'AdminReturned', 'AdminUser',
    // Services système
    'Payment', 'Notification', 'Delivery', 'Import', 'Analytics',
    // Services communs
    'ErrorHandling', 'Monitoring', 'AdminAuth'
]);

define('ALLOWED_METHODS', ['POST', 'GET', 'PUT', 'DELETE']);

// Fonction pour vérifier la session
// Fonction pour vérifier la session
function verifySession($data = []) {
    // S'assurer que la session est démarrée
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    // Vérification du session_id dans les données entrantes
    if (isset($data['session_id']) && !empty($data['session_id'])) {
        if (session_id() !== $data['session_id']) {
            logWarning("Session ID mismatch - Browser: " . session_id() . ", Request: " . $data['session_id']);
            
            // Sauvegarder les données de session actuelles si nécessaire
            $tempSessionData = $_SESSION;
            
            // Fermer proprement la session actuelle
            session_write_close();
            
            // Définir l'ID de session selon les données de la requête
            session_id($data['session_id']);
            
            // Démarrer la nouvelle session
            session_start();
            
            // Log pour débuggage
            logInfo("Session switched", [
                'old_session_id' => session_id(),
                'new_session_id' => $data['session_id'],
                'session_data' => $_SESSION
            ]);
        }
    }

    // Accès aux données de session
    $user_id = $data['user_id'] ?? $_SESSION['user_id'] ?? null;
    $session_id = session_id();
    $admin = $data['admin'] ?? $_SESSION['admin'] ?? null;

    // Vérification de l'existence du session_id
    if (!$session_id) {
        logInfo("Session not initialized, session_id missing.");
        sendErrorResponse('SESSION_EXPIRED');
    }

    return [
        'user_id' => $user_id,
        'session_id' => $session_id,
        'admin' => $admin
    ];
}

// Fonction pour analyser les données d'entrée
function parseInput() {
    global $isAdmin;
    $contentType = $_SERVER["CONTENT_TYPE"] ?? '';

    if (strpos($contentType, "application/json") !== false) {
        $input = json_decode(file_get_contents("php://input"), true);
        if (!$input) {
            sendErrorResponse('INVALID_JSON', $isAdmin);
        }
        logWarning("Requête JSON reçue", ['input' => $input]);
    } elseif (strpos($contentType, "multipart/form-data") !== false) {
        $input = $_POST;
        if (!empty($_FILES)) {
            $input['files'] = $_FILES;
        }
    } else {
        sendErrorResponse('UNSUPPORTED_CONTENT_TYPE', $isAdmin);
    }

    if (!isset($input["service"]) || !isset($input["action"])) {
        sendErrorResponse('MISSING_PARAMETERS', $isAdmin);
    }

    return $input;
}

// Fonction pour extraire service/action/donnée
function takeData($data) {
    $service = $data["service"] ?? null;
    $action = $data["action"] ?? null;
    $data = $data["data"] ?? null;
    return [$service, $action, $data];
}

// Fonction de routage
function routeRequest($conn, $service, $action, $data) {
    global $isAdmin;
    logInfo("Request routing started", ['service' => $service, 'action' => $action]);

  

    $serviceHandlers = [
        // Services utilisateurs
        'Auth' => function() use ($conn, $action, $data) {
            return handleAuthService($conn, $action, $data);
        },
        'User' => function() use ($conn, $action, $data) {
            return handleUserService($conn, $action, $data);
        },
        'Product' => function() use ($conn, $action, $data) {
            return handleProductService($conn, $action, $data);
        },
        'Cart' => function() use ($conn, $action, $data) {
            return handleCartService($conn, $action, $data);
        },
        'Order' => function() use ($conn, $action, $data) {
            return handleOrderService($conn, $action, $data);
        },
        'Returned' => function() use ($conn, $action, $data) {
            return handleReturnedService($conn, $action, $data);
        },
        'Review' => function() use ($conn, $action, $data) {
            return handleReviewService($conn, $action, $data);
        },
        
        // Services admin
        'AdminOrder' => function() use ($conn, $action, $data) {
            return handleAdminOrderService($conn, $action, $data);
        },
        'AdminProduct' => function() use ($conn, $action, $data) {
            return handleAdminProductService($conn, $action, $data);
        },
        'AdminReturned' => function() use ($conn, $action, $data) {
            return handleAdminReturnedService($conn, $action, $data);
        },
        'AdminUser' => function() use ($conn, $action, $data) {
            return handleAdminUserService($conn, $action, $data);
        },
        
        // Services système
        'Payment' => function() use ($conn, $action, $data) {
            return handlePaymentService($conn, $action, $data);
        },
        'Notification' => function() use ($conn, $action, $data) {
            return handleNotificationService($conn, $action, $data);
        },
        'Delivery' => function() use ($conn, $action, $data) {
            return handleDeliveryService($conn, $action, $data);
        },
        'Import' => function() use ($conn, $action, $data) {
            return handleImportService($conn, $action, $data);
        },
        'Analytics' => function() use ($conn, $action, $data) {
            return handleAnalyticsService($conn, $action, $data);
        },
        
        // Services communs
        'ErrorHandling' => function() use ($conn, $action, $data) {
            return handleErrorService($conn, $action, $data);
        },
        'Monitoring' => function() use ($conn, $action, $data) {
            return handleMonitoringService($conn, $action, $data);
        }
    ];

    if (!isset($serviceHandlers[$service])) {
        sendErrorResponse('SERVICE_NOT_FOUND', $isAdmin);
    }
    
    $result = $serviceHandlers[$service]();
    logInfo("Request routing completed", ['service' => $service, 'action' => $action, 'status' => 'success']);
    logWarning("Réponse envoyée", ['response' => $result]);
    return $result;
}

// Fonction pour vérifier la propriété de la ressource
function checkOwnership($requestUserId, $resourceUserId) {
    global $isAdmin;
    if ($requestUserId !== $resourceUserId && !$isAdmin) {
        sendErrorResponse('UNAUTHORIZED', $isAdmin);
    }
}

// Fonction principale pour gérer la requête
function handleRequest() {
    global $isAdmin;

    if (!in_array($_SERVER["REQUEST_METHOD"], ALLOWED_METHODS)) {
        logError("Requête refusée : méthode non autorisée", ["method" => $_SERVER["REQUEST_METHOD"]]);
        sendErrorResponse('METHOD_NOT_ALLOWED', $isAdmin);
        return;
    }

    $conn = coDB();

    try {
        $data = parseInput();
        if (empty($data)) {
            throw new Exception("Données d'entrée vides");
        }

        list($service, $action, $payload) = takeData($data);

        $userData = verifySession($data);

        if (!isset($data['user_id']) && isset($userData['user_id'])) {
            $data['user_id'] = $userData['user_id'];
        }
        if (!isset($payload['user_id']) && isset($userData['user_id'])) {
            $payload['user_id'] = $userData['user_id'];
        }
        if (!isset($data['session_id']) && isset($userData['session_id'])) {
            $data['session_id'] = $userData['session_id'];
        }
        if (!$service || !$action) {
            logError("Service/action manquants", $data);
            sendErrorResponse('MISSING_SERVICE_ACTION', $isAdmin);
        }

        if (!in_array($service, ALLOWED_SERVICES)) {
            logWarning("Service non autorisé", ["service" => $service]);
            sendErrorResponse('SERVICE_NOT_ALLOWED', $isAdmin);
        }

        $response = routeRequest($conn, $service, $action, $payload);

        if (isset($response["status"]) && $response["status"] === 'pending') {
            list($service, $action, $payload) = takeData($response);
            $response = routeRequest($conn, $service, $action, $payload);
        }

        $responseData = [
            'status' => $response['status'] ?? 'success',
            'message' => $response['message'] ?? '',
            'redirect' => $response['redirect'] ?? $response['data']['redirect'] ?? null,
            'data' => $response['data'] ?? $response
        ];

        header('Content-Type: application/json');
        echo json_encode($responseData);

    } catch (Exception $e) {
        logError("Erreur serveur", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        sendErrorResponse('INTERNAL_ERROR', $isAdmin);
    } finally {
        if ($conn instanceof mysqli) {
            $conn->close();
        }
    }
}

handleRequest();
?>
