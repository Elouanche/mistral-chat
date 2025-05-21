<!DOCTYPE html>
<html lang="fr">
<head>
    <?php require_once COMPONENT_PATH . 'head.php'; ?>
    <title>Mistral Chat - review</title>
</head>

<body>
    <?php require_once COMPONENT_PATH . 'header.php'; ?>

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