<?php
// views/user/account.php
require_once SHARED_PATH . 'session.php';

// Redirection si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    if (!empty($_SESSION['admin']) && $_SESSION['admin'] === 'admin') {
        header('Location: /admin/dashboard');
    } else {
        header('Location: /');
    }
    exit;
}

require_once COMPONENT_PATH . 'head.php';
?>

<link rel="stylesheet" href="<?= htmlspecialchars(STATIC_URL); ?>css/login.css">

<main role="main" class="login">
    <section id="connexion" class="login-container" aria-labelledby="connexionTitle">
        <h2 id="connexionTitle">Connexion</h2>
        <form id="loginForm" method="POST" novalidate>
            <div class="form-group">
                <label for="loginEmail">Email</label>
                <input type="email" id="loginEmail" name="email" required aria-required="true" autocomplete="email">
            </div>
            <div class="form-group">
                <label for="loginPassword">Mot de passe</label>
                <input type="password" id="loginPassword" name="password" required aria-required="true" autocomplete="current-password">
            </div>
            <button type="submit" class="button-spe">Se connecter</button>
        </form>
        <div id="loginErrorMessage" class="error-message" role="alert" hidden></div>
    </section>

    <section id="inscription" class="register-container" aria-labelledby="inscriptionTitle" hidden>
        <h2 id="inscriptionTitle">Inscription</h2>
        <form id="registerForm" method="POST" novalidate>
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" required aria-required="true" autocomplete="username">
            </div>
            <div class="form-group">
                <label for="registerEmail">Email</label>
                <input type="email" id="registerEmail" name="email" required aria-required="true" autocomplete="email">
            </div>
            <div class="form-group">
                <label for="phone">Téléphone (optionnel)</label>
                <input type="tel" id="phone" name="phone" placeholder="+33 6 12 34 56 78" autocomplete="tel">
            </div>
            <div class="form-group">
                <label for="registerPassword">Mot de passe</label>
                <input type="password" id="registerPassword" name="password" required aria-required="true" autocomplete="new-password">
            </div>
            <button type="submit" class="button-spe">S'inscrire</button>
        </form>
        <div id="registerErrorMessage" class="error-message" role="alert" hidden></div>
    </section>

    <div class="text-center mt-3">
        <button id="switch_forms" type="button" class="btn btn-link" aria-controls="connexion inscription" aria-expanded="true">
            Passer à l'inscription
        </button>
    </div>
</main>

<!-- Scripts -->


<script src="<?= htmlspecialchars(STATIC_URL); ?>js/page-login.js" defer></script>

<?php require_once COMPONENT_PATH . 'foot.php'; ?>