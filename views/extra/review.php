<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once SHARED_PATH . "session.php";
require_once COMPONENT_PATH . "head.php";
?>

<main class="reviews-page"  role="main">
    <h1>Avis Clients</h1>
    
    <section class="reviews-container">
        <h2>Ce que disent nos clients</h2>
        <div class="review-list">
            <?php include COMPONENT_PATH . 'review-card.php'; ?>
        </div>
    </section>
</main>

<?php require_once COMPONENT_PATH . "foot.php"; ?>