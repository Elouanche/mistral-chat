<?php

require_once SHARED_PATH . 'userAcces.php';

// Protection des données utilisateur
$userId = (int) $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['user_username'], ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars($_SESSION['user_email'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php require_once COMPONENT_PATH . 'head.php'; ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(STATIC_URL); ?>css/styles-modal.css">
    <title>Mistral Chat - Account</title>
</head>

<body>
    <?php require_once COMPONENT_PATH . 'header.php'; ?>


<main role="main" class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <section class="card shadow">
                <div class="card-header text-center">
                    <h1 class="h4">Mon Compte</h1>
                </div>
                <div class="card-body">
                    <dl class="row mb-3">
                        <dt class="col-sm-4">Nom d'utilisateur :</dt>
                        <dd class="col-sm-8"><?= $username ?></dd>

                        <dt class="col-sm-4">Email :</dt>
                        <dd class="col-sm-8"><?= $email ?></dd>
                    </dl>

                    <div class="text-center mt-4" role="group" aria-label="Actions du compte">
                        <a href="/user/edit-profile" class="btn btn-primary me-2" role="button">Modifier le profil</a>
                        <a href="/user/change-password" class="btn btn-secondary me-2" role="button">Changer le mot de passe</a>
                        <button id="logoutBtn" class="btn btn-warning me-2" type="button">Déconnexion</button>
                        <button 
                            id="deleteAccountBtn" 
                            class="btn btn-danger" 
                            type="button" 
                            aria-haspopup="dialog" 
                            aria-controls="deleteConfirmModal"
                            aria-expanded="false"
                        >
                            Supprimer le compte
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div class="modal-overlay" id="deleteConfirmModal" role="dialog" aria-modal="true" aria-labelledby="deleteConfirmModalLabel" hidden>
        <div class="modal-content" role="document">
            <div class="modal-header">
                <h2 class="modal-title" id="deleteConfirmModalLabel">Confirmation de suppression</h2>
                <button 
                    type="button" 
                    id="closeModalBtn" 
                    aria-label="Fermer la fenêtre de confirmation"
                >
                    ×
                </button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <button type="button" id="cancelDeleteBtn" class="btn btn-secondary">Annuler</button>
                <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Supprimer définitivement</button>
            </div>
        </div>
    </div>
</main>

<!-- Scripts -->

<script>
    // Définir la variable userId pour le script account.js
    const userId = <?= json_encode($userId) ?>;
</script>
<script src="<?= htmlspecialchars(STATIC_URL); ?>js/page-account.js" defer></script>

<?php require_once COMPONENT_PATH . 'foot.php'; ?>
