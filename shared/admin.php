<?php
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
?>