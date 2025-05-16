<?php
// Configuration pour protéger les routes admin
// middleware/adminMiddleware.php
function checkAdminAccess() {
    if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== 'admin') {
        header('Location: /user/login');
        exit;
    }
}

?>