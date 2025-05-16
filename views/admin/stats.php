<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once SHARED_PATH . "session.php";
require_once COMPONENT_PATH . "head.php";

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== 'admin') {
    header('Location: /user/login');
    exit;
}

if (strpos($_SERVER['REQUEST_URI'], '/admin/') === 0) {
    require_once SHARED_PATH .'adminMiddleware.php';
    checkAdminAccess();
}
?>

<main class="admin-stats">
    <h1>Statistiques</h1>
    
    <div class="stats-container" id="stats-root">
        <p>Chargement des statistiques...</p>
    </div>
</main>
<script src="<?php echo STATIC_URL; ?>js/admin-stats.js"></script>

<?php require_once COMPONENT_PATH . "foot.php"; ?>