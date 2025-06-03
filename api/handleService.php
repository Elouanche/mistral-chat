<?php
// Path: api/handleService.php
require_once "../DIR.php";
require_once CONFIG_PATH . "log_config.php";
require_once SECURISER_PATH . "oauth_config.php";

// Import des services
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
require_once SERVICE_CRUD_PATH . "AnalyticsService.php";
require_once SERVICE_CRUD_PATH . "MonitoringService.php";
require_once SERVICE_CRUD_PATH . "ErrorLoggingService.php";



require_once SERVICE_CRUD_PATH . "MistralApiService.php";
require_once SERVICE_CRUD_PATH . "ApiQuotaService.php"; 
require_once SERVICE_CRUD_PATH . "AiConversationService.php";
require_once SERVICE_CRUD_PATH . "SubscriptionService.php";



global $isAdmin;
function handleAuthService($conn, $action, $data) {
    $auth = new AuthService($conn);
    
    switch ($action) {
        case 'Register':
            return $auth->register($data);
        case 'Login':
            return $auth->login($data);
        case 'ResetPassword':
            return $auth->resetPassword($data);
        case 'VerifyEmail':
            return $auth->verifyEmail($data);
        case 'Logout':
            return $auth->logout($data);
        case 'verifyAdminCode':
            return $auth->verifyAdminCode($data);
        case 'GoogleAuth':
            return $auth->initiateGoogleAuth();
        case 'GoogleCallback':
            return $auth->handleGoogleCallback($data);
        case 'GoogleLogin':
            return $auth->handleGoogleLogin($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            return ['status' => 'error', 'message' => 'Invalid action'];
    }
}

function handleUserService($conn, $action, $data) {
    global $isAdmin;
    $user = new UserService($conn);
    
    
    switch ($action) {
        case 'update':
            return $user->updateUser($data);
        case 'getProfile':
            return $user->getUser($data);
        case 'updatePassword':
            return $user->updateUser($data);
        case 'delete':
            return $user->deleteUser($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            return ['status' => 'error', 'message' => 'Invalid action'];
    }
}

function handleProductService($conn, $action, $data) {
    global $isAdmin;
    $product = new ProductService($conn);
    
    switch ($action) {
        case 'getProducts':
            return $product->listProducts($data);
        case 'getProduct':
            return $product->getProduct($data);
        case 'searchProducts':
            return $product->listProducts(['filters' => $data]);
        case 'createProduct':
            return $product->createProduct($data);
        case 'updateProduct':
            return $product->updateProduct($data);
        case 'deleteProduct':
            return $product->deleteProduct($data);
        case 'updateStock':
            return $product->updateStock($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            return ['status' => 'error', 'message' => 'Invalid action'];
    }
}

function handleCartService($conn, $action, $data) {
    global $isAdmin;
    $cart = new CartService($conn);

    switch ($action) {
        case 'addItem':
            return $cart->addToCart($data);
        case 'updateQuantity':
            return $cart->updateCartItem($data);
        case 'removeItem':
            return $cart->removeFromCart($data);
        case 'getCart':
            return $cart->getCart($data);
        case 'clearCart':
            return $cart->clearCart($data);
        case 'checkout':
            return $cart->checkoutCart($data);
        case 'increaseQuantity':
            return $cart->increaseQuantity($data);
        case 'decreaseQuantity':
            return $cart->decreaseQuantity($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            return ['status' => 'error', 'message' => 'Invalid action'];
    }
}

function handleOrderService($conn, $action, $data) {
    global $isAdmin;
    $order = new OrderService($conn);
    
    switch ($action) {
        case 'createOrder':
            return $order->createOrder($data);
        case 'getOrder':
            return $order->getOrder($data);
        case 'getUserOrders':
            return $order->getUserOrders($data);
        case 'cancelOrder':
            return $order->cancelOrder($data);
        case 'updateOrderStatus':
            return $order->updateOrderStatus($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
}


/*
function handleReviewService($conn, $action, $data) {
    global $isAdmin;
    $review = new Review($conn);
    
    switch ($action) {
        case 'createReview':
            return $review->createReview($data);
        case 'getProductReviews':
            return $review->getProductReviews($data);
        case 'getUserReviews':
            return $review->getUserReviews($data);
        case 'updateReview':
            return $review->updateReview($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
}
*/
function handleAdminOrderService($conn, $action, $data) {
    global $isAdmin;
    if (!$isAdmin) sendErrorResponse('UNAUTHORIZED', $isAdmin);

    $adminOrder = new OrderService($conn);

    switch ($action) {
        case 'listOrders':
            return $adminOrder->listOrders($data);
        case 'generateInvoice':
            return $adminOrder->generateInvoice($data);
        case 'updateOrderStatus':
            return $adminOrder->updateOrderStatus($data);
        
        case 'getUserOrders':   
            return $adminOrder->getUserOrders($data);
        case 'deleteOrders':
            return $adminOrder->deleteOrders($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
}

function handleAdminProductService($conn, $action, $data) {
    global $isAdmin;
    if (!$isAdmin) sendErrorResponse('UNAUTHORIZED', $isAdmin);

    $adminProduct = new ProductService($conn);

    switch ($action) {
        case 'createProduct':
            return $adminProduct->createProduct($data);
        case 'updateProduct':
            return $adminProduct->updateProduct($data);
        case 'deleteProduct':
            return $adminProduct->deleteProduct($data);
        case 'updateStock':
            return $adminProduct->updateStock($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
}

function handleAdminReturnedService($conn, $action, $data) {
    global $isAdmin;
    if (!$isAdmin) sendErrorResponse('UNAUTHORIZED', $isAdmin);

    $returned = new ReturnService($conn);

    switch ($action) {
        case 'createReturn':
            return $returned->createReturn($data);
        case 'getReturn':
            return $returned->getReturn($data);
        case 'getUserReturns':
            return $returned->getUserReturns($data);
        case 'updateReturnStatus':
            return $returned->updateReturnStatus($data);
        case 'listReturns':
            return $returned->listReturns($data);
        
            
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
}

function handleAdminUserService($conn, $action, $data) {
    global $isAdmin;
    if (!$isAdmin) sendErrorResponse('UNAUTHORIZED', $isAdmin);

    $adminUser = new UserService($conn);

    switch ($action) {
        case 'createUser':
            return $adminUser->createUser($data);
        case 'updateUser':
            return $adminUser->updateUser($data);
        case 'deleteUser':
            return $adminUser->deleteUser($data);
        case 'getUser':
            return $adminUser->getUser($data);
        case 'listUsers':
            return $adminUser->listUsers($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            return ['status' => 'error', 'message' => 'Invalid action'];
    }
}

function handlePaymentService($conn, $action, $data) {
    global $isAdmin;
    $payment = new PaymentService($conn);
    
    switch ($action) {
        case 'processPayment':
            return $payment->processPayment($data);
        case 'getPaymentStatus':
            return $payment->getPaymentStatus($data);
        case 'refundPayment':
            if (!$isAdmin) sendErrorResponse('UNAUTHORIZED', $isAdmin);
            return $payment->refundPayment($data);
        case 'createPaymentIntent':
            return $payment->createPaymentIntent($data);
        case 'createCheckoutSession':
            return $payment->createCheckoutSession($data);
        
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            return ['status' => 'error', 'message' => 'Invalid action'];
    }
}

function handleNotificationService($conn, $action, $data) {
    global $isAdmin;
    $notification = new NotificationService($conn);
    
    switch ($action) {
        case 'sendEmail':
            return $notification->sendEmail($data);
        case 'getUserNotifications':
            return $notification->getUserNotifications($data);
        case 'markAsRead':
            return $notification->markAsRead($data);
        case 'markAllAsRead':
            return $notification->markAllAsRead($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            return ['status' => 'error', 'message' => 'Invalid action'];
    }
}

function handleDeliveryService($conn, $action, $data) {
    global $isAdmin;
    if (!$isAdmin) sendErrorResponse('UNAUTHORIZED', $isAdmin);
    $delivery = new DeliveryService($conn);
    
    switch ($action) {
        case 'trackShipment':
            return $delivery->getDelivery($data);
        case 'updateShipment':
            return $delivery->updateDeliveryStatus($data);
        case 'createShipment':
            return $delivery->createDelivery($data);
        case 'getShippingLabel':
            return $delivery->generateShippingLabel($data);
        case 'calculateShipping':
            return $delivery->calculateShippingCost($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            return ['status' => 'error', 'message' => 'Invalid action'];
    }
}



function handleAnalyticsService($conn, $action, $data) {
    global $isAdmin;
    if (!$isAdmin) sendErrorResponse('UNAUTHORIZED', $isAdmin);
    
    $analytics = new AnalyticsService($conn);
    
    switch ($action) {
        case 'recordActivity':
            return $analytics->recordActivity($data);
        case 'getPageViewStats':
            return $analytics->getPageViewStats($data);
        case 'getUserStats':
            return $analytics->getUserStats($data);
        case 'generateReport':
            return $analytics->generateReport($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
}

function handleErrorService($conn, $action, $data) {
    global $isAdmin;
    if (!$isAdmin) sendErrorResponse('UNAUTHORIZED', $isAdmin);
    
    $errorHandling = new ErrorLoggingService($conn);
    
    switch ($action) {
        case 'logError':
            return $errorHandling->logError($data);
        case 'deleteErrors':
            return $errorHandling->getErrors($data);
        case 'getError':
            return $errorHandling->getError($data);
        case 'deleteError':
            return $errorHandling->deleteError($data);
        case 'purgeOldErrors':
            return $errorHandling->purgeOldErrors($data);
        
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            return ['status' => 'error', 'message' => 'Invalid action'];
    }
}

function handleMonitoringService($conn, $action, $data) {
    global $isAdmin;
    if (!$isAdmin) sendErrorResponse('UNAUTHORIZED', $isAdmin);
    $monitoring = new MonitoringService($conn);
    
    switch ($action) {
        case 'recordServiceStatus':
            return $monitoring->recordServiceStatus($data);
        case 'getServiceStatus':
            return $monitoring->getServiceStatus($data);
        case 'getAllServicesStatus':
            return $monitoring->getAllServicesStatus($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
}


function handleSupportService($conn, $action, $data) {
    global $isAdmin;
    $support = new SupportService($conn);
    
    switch ($action) {
        case 'createConversation':
            return $support->createConversation($data);
        case 'addMessage':
            return $support->addMessage($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            return ['status' => 'error', 'message' => 'Invalid action'];
    }
}




function handleMistralApiService($conn, $action, $data) {
    $mistralApi = new MistralApiService($conn);
    
    switch ($action) {
        case 'sendChatRequest':
            return $mistralApi->sendChatRequest($data);
        case 'getModels':
            return $mistralApi->getModels();
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            return ['status' => 'error', 'message' => 'Invalid action'];
    }
}

function handleApiQuotaService($conn, $action, $data) {
    $apiQuota = new ApiQuotaService($conn);
    
    switch ($action) {
        case 'checkUserQuota':
            return $apiQuota->checkUserQuota($data);
        case 'updateUserQuota':
            return $apiQuota->updateUserQuota($data);
        case 'getUserQuota':
            return $apiQuota->getUserQuota($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            return ['status' => 'error', 'message' => 'Invalid action'];
    }
}

function handleAiConversationService($conn, $action, $data) {
    $aiConversation = new AiConversationService($conn);
    
    switch ($action) {
        case 'getUserConversations':
            return $aiConversation->getUserConversations($data);
        case 'getConversation':
            return $aiConversation->getConversation($data);
        case 'createConversation':
            return $aiConversation->createConversation($data);
        case 'updateConversation':
            return $aiConversation->updateConversation($data);
        case 'archiveConversation':
            return $aiConversation->archiveConversation($data);
        case 'deleteConversation':
            return $aiConversation->deleteConversation($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            return ['status' => 'error', 'message' => 'Invalid action'];
    }
}

function handleSubscriptionService($conn, $action, $data) {
    $subscription = new SubscriptionService($conn);
    
    switch ($action) {
        case 'getById':
            return $subscription->getById($data);
            
        case 'getAvailablePlans':
            return $subscription->getAvailablePlans($data);
            
        case 'getUserSubscription':
            return $subscription->getUserSubscription($data);
            
        case 'createSubscription':
            return $subscription->createSubscription($data);
            
        case 'cancelSubscription':
            return $subscription->cancelSubscription($data);
            
        case 'canUserUseModel':
            if (!isset($data['user_id']) || !isset($data['model_name'])) {
                return [
                    'status' => 'error',
                    'message' => "L'ID de l'utilisateur et le nom du modèle sont obligatoires"
                ];
            }
            $result = $subscription->canUserUseModel($data['user_id'], $data['model_name']);
            return [
                'status' => 'success',
                'data' => ['can_use' => $result]
            ];
        
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            return ['status' => 'error', 'message' => 'Invalid action'];    }
}