<?php
require_once SHARED_PATH . 'admin.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php require_once COMPONENT_PATH . 'head.php'; ?>
    <title>Mistral Chat - Dashbord</title>
</head>

<body>
    <?php require_once COMPONENT_PATH . 'header.php'; ?>
<main class="admin-stats">
    <h1>Statistiques</h1>
    
    <div class="stats-container" id="stats-root">
        <p>Chargement des statistiques...</p>
    </div>
</main>
<script src="<?php echo STATIC_URL; ?>js/page-admin-stats.js"></script>

<?php require_once COMPONENT_PATH . "foot.php"; ?>