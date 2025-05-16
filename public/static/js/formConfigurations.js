// Configurations des formulaires pour la page fonctionnel.php
const formConfigurations = {
    /*   Auth ok   */ 
    registerAuth: {
        service: "Auth",
        action: "Register",
        fields: [
            { id: "registerEmail", key: "user_email" },
            { id: "registerPassword", key: "user_password" }
        ]
    },
    loginAuth: {
        service: "Auth",
        action: "Login",
        fields: [
            { id: "loginEmail", key: "user_email" },
            { id: "loginPassword", key: "user_password" }
        ]
    },
    loginAuthAdmin: {
        service: "AdminAuth",
        action: "loginAdmin",
        fields: [
            { id: "loginEmail", key: "user_email" },
            { id: "loginPassword", key: "user_password" }
        ]
    },
    resetPassword: {
        service: "Auth",
        action: "ResetPassword",
        fields: [
            { id: "resetPasswordEmail", key: "user_email" }
        ]
    },
    verifyEmail: {
        service: "Auth",
        action: "VerifyEmail",
        fields: [
            { id: "verifyEmail", key: "user_email" }
        ]
    },
    /*   User   */ 
    
    createUser: {
        service: "User",
        action: "Create",
        fields: [
            { id: "createUsername", key: "user_name" },
            { id: "createEmail", key: "user_email" },
            { id: "createUserPassword", key: "user_password" },
            { id: "createUserImage", key: "user_image_url" },
            { id: "createUserDescription", key: "user_description" }
        ]
    },
    
    deleteUser: {
        service: "User",
        action: "Delete",
        fields: [
            { id: "deleteUserId", key: "user_id" }
        ]
    },
    getUserProfile: {
        service: "User",
        action: "Get_Profile",
        fields: [
            { id: "profileUserId", key: "user_id" }
        ]
    },
    activateUser: {
        service: "User",
        action: "Activate",
        fields: [
            { id: "activateUserId", key: "user_id" }
        ]
    },
    updateUser: {
        service: "User",
        action: "Update",
        fields: [
            { id: "updateUserId", key: "user_id" },
            { id: "updateUsername", key: "user_name" },
            { id: "updateEmail", key: "user_email" },
            { id: "updateUserPassword", key: "user_password" },
            { id: "updateUserImage", key: "user_image_url" },
        ]
    },
    
    /*   Admin   */
    
    createAdmin: {
        service: "Admin",
        action: "Create",
        fields: [
            { id: "createAdminname", key: "admin_name" },
            { id: "createEmail", key: "admin_email" },
            { id: "createUserPassword", key: "admin_password" },
            { id: "createUserImage", key: "admin_image_url" },
        ]
    },
    deleteAdmin: {
        service: "Admin",
        action: "Delete",
        fields: [
            { id: "deleteAdminId", key: "delete_id" },
            { id: "codeSuperAdmin", key: "code_super_admin" }
        ]
    },
    getAllUsers: {
        service: "Admin",
        action: "Get_All_User",
        fields: [{ id: "userFilters", key: "filters" }]
    },

    /*   Product   */
    createProduct: {
        service: "Product",
        action: "Create",
        fields: [
            { id: "createProductName", key: "product_name" },
            { id: "createProductPrice", key: "product_price" },
            { id: "createProductDescription", key: "product_description" },
            { id: "createProductImage", key: "product_image_url" }
        ]
    },
    deleteProduct: {
        service: "Product",
        action: "Delete",
        fields: [
            { id: "deleteProductId", key: "product_id" }
        ]
    },
    updateProduct: {
        service: "Product",
        action: "Update",
        fields: [
            { id: "updateProductId", key: "product_id" },
            { id: "updateProductName", key: "product_name" },
            { id: "updateProductPrice", key: "product_price" },
            { id: "updateProductDescription", key: "product_description" },
            { id: "updateProductImage", key: "product_image_url" }
        ]
    },
    getCategories: {
        service: "Product",
        action: "Get_Categories",
        fields: []
    },
    updateStock: {
        service: "Product",
        action: "Update_Stock",
        fields: [
            { id: "productId", key: "product_id" },
            { id: "newStock", key: "new_stock_quantity" }
        ]
    },
    /*   Order   */
    createOrder: {
        service: "Order",
        action: "Create",
        fields: [
            { id: "orderUserId", key: "user_id" },
            { id: "orderProductId", key: "product_id" },
            { id: "orderQuantity", key: "quantity" }
        ]
    },
    cancelOrder: {
        service: "Order",
        action: "Cancel",
        fields: [
            { id: "cancelOrderId", key: "order_id" }
        ]
    },
    getAllOrders: {
        service: "Order",
        action: "Get_All",
        fields: []
    },
    updateOrderStatus: {
        service: "Order",
        action: "Update_Status",
        fields: [
            { id: "updateOrderId", key: "order_id" },
            { id: "updateOrderStatus", key: "order_status" }
        ]
    },
    /*   Return   */
    createReturn: {
        service: "Return",
        action: "Create",
        fields: [
            { id: "returnUserId", key: "user_id" },
            { id: "returnOrderId", key: "order_id" },
            { id: "returnReason", key: "reason" }
        ]
    },
    updateReturn: {
        service: "Return",
        action: "Update",
        fields: [
            { id: "updateReturnId", key: "return_id" },
            { id: "updateReturnStatus", key: "return_status" }
        ]
    },
    approveReturn: {
        service: "Return",
        action: "Approve",
        fields: [
            { id: "approveReturnId", key: "return_id" }
        ]
    },
    rejectReturn: {
        service: "Return",
        action: "Reject",
        fields: [
            { id: "rejectReturnId", key: "return_id" }
        ]
    },
    /*   Notification   */
    getUserNotifications: {
        service: "Notification",
        action: "Get",
        fields: [
            { id: "notificationUserId", key: "user_id" }
        ]
    },
    markNotificationAsRead: {
        service: "Notification",
        action: "MarkAsRead",
        fields: [
            { id: "notificationId", key: "notification_id" }
        ]
    },
    /*   Payment   */
    processPayment: {
        service: "Payment",
        action: "Process",
        fields: [
            { id: "paymentOrderId", key: "order_id" },
            { id: "paymentAmount", key: "amount" }
        ]
    },
    getPaymentStatus: {
        service: "Payment",
        action: "GetStatus",
        fields: [
            { id: "paymentId", key: "payment_id" }
        ]
    },
    /*   Shipment   */
    trackShipment: {
        service: "Shipment",
        action: "Track",
        fields: [
            { id: "trackingNumber", key: "tracking_number" }
        ]
    },
    searchProducts: {
        service: "Product",
        action: "Search",
        fields: [
            { id: "searchProductQuery", key: "query" }
        ]
    },
    /*   Shipment   */
    getServiceStatus: {
        service: "System",
        action: "Get_Service_Status",
        fields: []
    },
    /*   Review   */
    createReview: {
        service: "Review",
        action: "Create",
        fields: [
            { id: "createReviewProductId", key: "product_id" },
            { id: "createReviewContent", key: "content" },
            { id: "createReviewRating", key: "rating" }
        ]
    },
    getProductReviews: {
        service: "Review",
        action: "Get_Product",
        fields: [
            { id: "productReviewId", key: "product_id" }
        ]
    },
    getUserReviews: {
        service: "Review",
        action: "Get_User",
        fields: [
            { id: "userReviewId", key: "user_id" }
        ]
    },
    updateReview: {
        service: "Review",
        action: "Update",
        fields: [
            { id: "updateReviewId", key: "review_id" },
            { id: "updateReviewContent", key: "content" },
            { id: "updateReviewRating", key: "rating" }
        ]
    }
};