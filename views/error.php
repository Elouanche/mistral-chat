<?php
// views/error.php

// Sécurisation des données entrantes
$errorCode = htmlspecialchars($_GET['code'] ?? 'UNKNOWN_ERROR', ENT_QUOTES, 'UTF-8');
$errorMessage = htmlspecialchars($_GET['message'] ?? 'Erreur inconnue', ENT_QUOTES, 'UTF-8');


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php require_once COMPONENT_PATH . 'head.php'; ?>
    <title>Mistral Chat - Error</title>
</head>

<body>
    <?php require_once COMPONENT_PATH . 'header.php'; ?>

<main role="main" class="error-page">
    <section class="error-content" aria-labelledby="error-title">
        <div class="container">
            <h1 id="error-title">Erreur : <?php echo $errorCode; ?></h1>
            <p><?php echo $errorMessage; ?></p>
            <a href="/" class="btn" role="button" aria-label="Retour à la page d'accueil">Retour à l'accueil</a>
        </div>
    </section>
</main>

<?php require_once COMPONENT_PATH . 'foot.php'; ?>