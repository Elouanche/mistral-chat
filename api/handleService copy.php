<?php
// Path: api/handleService.php
require_once "../DIR.php";
require_once CONFIG_PATH . "log_config.php";

function handleAuthService($conn, $action, $data) {
    global $isAdmin;
    $auth = new Auth($conn);
    
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
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
}

function handleUserService($conn, $action, $data) {
    global $isAdmin;
    $user = new User($conn);
    
    switch ($action) {
        case 'update':
            return $user->update($data);
        case 'getProfile':
            return $user->getProfile($data);
        case 'updatePassword':
            return $user->updatePassword($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
}

function handleProductService($conn, $action, $data) {
    global $isAdmin;
    $product = new Product($conn);
    
    switch ($action) {
        case 'getProducts':
            return $product->getProducts($data);
        case 'getProduct':
            return $product->getProduct($data);
        case 'getProductsById':
            return $product->getProductsById($data);
        case 'searchProducts':
            return $product->searchProducts($data);
        case 'saveProduct':
            return $product->saveProduct($data);
        case 'deleteProduct':
            return $product->deleteProduct($data);
        case 'updateStock':
            return $product->updateStocks($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
}

function handleCartService($conn, $action, $data) {
    global $isAdmin;
    $cartManager = new CartManager($conn);

    switch ($action) {
        case 'updateQuantity':
            return $cartManager->updateQuantity($data);
        case 'removeItem':
            return $cartManager->removeItem($data);
        case 'getCart':
            return $cartManager->getCart($data);
        case 'clearCart':
            return $cartManager->clearCart($data);
        case 'increaseQuantity':
            return $cartManager->increaseQuantity($data);
        case 'decreaseQuantity':
            return $cartManager->decreaseQuantity($data);
        default:
            return ['status' => 'error', 'message' => 'Unknown action'];
    }
}

function handleOrderService($conn, $action, $data) { 
    global $isAdmin;
    $order = new Order($conn);
    
    switch ($action) {
        case 'createOrder':
            return $order->createOrder($data);
        case 'getOrder':
            return $order->getOrder($data);
        case 'getUserOrders':
            return $order->getUserOrders($data);
        case 'cancelOrder':
            return $order->cancelOrder($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
}

function handleReturnedService($conn, $action, $data) {
    global $isAdmin;
    $returned = new Returned($conn);

    switch ($action) {
        case 'createReturn':
            return $returned->createReturned($data);
        case 'getReturned':
            return $returned->getReturned($data);
        case 'getUserReturneds':
            return $returned->getUserReturneds($data);
        case 'updateReturned':
            return $returned->updateReturned($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
}

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

function handleAdminOrderService($conn, $action, $data) {
    global $isAdmin;
    if (!$isAdmin) sendErrorResponse('UNAUTHORIZED', $isAdmin);

    $adminOrder = new Order($conn);

    switch ($action) {
        case 'getAllOrders':
            return $adminOrder->getAllOrders($data);
        case 'generateInvoice':
            return $adminOrder->generateInvoice($data);
        case 'updateOrderStatus':
            return $adminOrder->updateOrderStatus($data);
        case 'getOrderStatistics':
            return $adminOrder->getOrderStatistics($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
}

function handleAdminProductService($conn, $action, $data) {
    global $isAdmin;
    if (!$isAdmin) sendErrorResponse('UNAUTHORIZED', $isAdmin);

    $adminProduct = new Product($conn);

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

    $adminReturned = new Returned($conn);

    switch ($action) {
        case 'getAllReturneds':
            return $adminReturned->getAllReturneds($data);
        case 'processReturned':
            return $adminReturned->processReturned($data);
        case 'approveReturned':
            return $adminReturned->approveReturned($data);
        case 'rejectReturned':
            return $adminReturned->rejectReturned($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
}

function handleAdminUserService($conn, $action, $data) {
    global $isAdmin;
    if (!$isAdmin) sendErrorResponse('UNAUTHORIZED', $isAdmin);

    $adminUser = new User($conn);

    switch ($action) {
        case 'getAllUsers':
            return $adminUser->getAllUsers($data);
        case 'updateUser':
            return $adminUser->updateUser($data);
        case 'deleteUser':
            return $adminUser->deleteUser($data);
        case 'getUserStatistics':
            return $adminUser->getUserStatistics();
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
}

function handlePaymentService($conn, $action, $data) {
    global $isAdmin;
    $payment = new Payment($conn);
    
    switch ($action) {
        case 'processPayment':
            return $payment->processPayment($data);
        case 'getPaymentStatus':
            return $payment->getPaymentStatus($data);
        case 'refundPayment':
            if (!$isAdmin) sendErrorResponse('UNAUTHORIZED', $isAdmin);
            return $payment->refundPayment($data);
        case 'updatePaymentStatus':
            return $payment->updatePaymentStatus($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
}

function handleNotificationService($conn, $action, $data) {
    global $isAdmin;
    $notification = new Notification($conn);
    
    switch ($action) {
        case 'sendEmail':
            return $notification->sendEmail($data);
        case 'getUserNotifications':
            return $notification->getUserNotifications($data);
        case 'markAsRead':
            return $notification->markAsRead($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
}

function handleDeliveryService($conn, $action, $data) {
    global $isAdmin;
    if (!$isAdmin) sendErrorResponse('UNAUTHORIZED', $isAdmin);
    $delivery = new Delivery($conn);
    
    switch ($action) {
        case 'trackShipment':
            return $delivery->trackShipment($data);
        case 'updateShipment':
            return $delivery->updateShipment($data);
        case 'createShipment':
            return $delivery->createShipment($data);
        case 'getShippingLabel':
            return $delivery->getShippingLabel($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
}

function handleImportService($conn, $action, $data) {
    global $isAdmin;
    if (!$isAdmin) sendErrorResponse('UNAUTHORIZED', $isAdmin);
    
    $import = new Import($conn);
    
    switch ($action) {
        case 'importReviews':
            return $import->importReviews($data);
        case 'importStock':
            return $import->importStock($data);
        case 'getImportHistory':
            return $import->getImportHistory($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
}

function handleAnalyticsService($conn, $action, $data) {
    global $isAdmin;
    if (!$isAdmin) sendErrorResponse('UNAUTHORIZED', $isAdmin);
    
    $analytics = new Analytics($conn);
    
    switch ($action) {
        case 'getSalesMetrics':
            return $analytics->getSalesMetrics($data);
        case 'getUserMetrics':
            return $analytics->getUserMetrics($data);
        case 'getProductMetrics':
            return $analytics->getProductMetrics($data);
        case 'getConversionMetrics':
            return $analytics->getConversionMetrics($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
}

function handleErrorService($conn, $action, $data) {
    global $isAdmin;
    if (!$isAdmin) sendErrorResponse('UNAUTHORIZED', $isAdmin);
    
    $errorHandling = new ErrorHandling($conn);
    
    switch ($action) {
        case 'getErrorLogs':
            return $errorHandling->getErrorLogs($data);
        case 'clearErrorLogs':
            return $errorHandling->clearErrorLogs($data);
        case 'getErrorStatistics':
            return $errorHandling->getErrorStatistics($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
}

function handleMonitoringService($conn, $action, $data) {
    global $isAdmin;
    if (!$isAdmin) sendErrorResponse('UNAUTHORIZED', $isAdmin);
    
    $monitoring = new Monitoring($conn);
    
    switch ($action) {
        case 'getServiceStatus':
            return $monitoring->getServiceStatus();
        case 'getPerformanceMetrics':
            return $monitoring->getPerformanceMetrics();
        case 'getSystemHealth':
            return $monitoring->getSystemHealth();
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
}

function handleAdminStatisticsService($conn, $action, $data) {
    global $isAdmin;
    if (!$isAdmin) sendErrorResponse('UNAUTHORIZED', $isAdmin);

    $adminStats = new Statistics($conn);

    switch ($action) {
        case 'getUserStats':
            return $adminStats->getUserStatistics($data);
        case 'getProductStats':
            return $adminStats->getProductStatistics($data);
        case 'getOrderStats':
            return $adminStats->getOrderStatistics($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
}

function handleAdminAuthService($conn, $action, $data) {
    global $isAdmin;
    $adminAuth = new Auth($conn);
    
    switch ($action) {
        case 'loginAdmin':
            return $adminAuth->login($data);
        case 'logoutAdmin':
            if (!$isAdmin) sendErrorResponse('UNAUTHORIZED', $isAdmin);
            return $adminAuth->logout();
        case 'verifyAdminCode':
            return $adminAuth->verifyAdminCode($data);
        default:
            logError("Invalid $action action requested", ['action' => $action]);
            sendErrorResponse('INVALID_ACTION', $isAdmin);
    }
}

?>