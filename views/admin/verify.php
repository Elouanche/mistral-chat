<?php
// views/admin/verify.php
//348150
if (!isset($_SESSION['admin']  ) || $_SESSION['admin'] !== 'pending') {
    header('Location: /user/login');
    exit;
    
}
// Au début de chaque page admin :
/*
if (strpos($_SERVER['REQUEST_URI'], '/admin/') === 0) {
    require_once COMPONENT_PATH . "adminMiddleware.php";
    checkAdminAccess();
}
    */
?>
<?php require_once SHARED_PATH . "session.php";?>
<?php require_once COMPONENT_PATH . "head.php";?>
    <div class="admin-verify-container">
        <h2>Vérification Administrateur</h2>
        <p>Un code de vérification a été envoyé à votre adresse email.</p>
        
        <form id="adminVerifyForm"  method="POST">
        
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="loginEmail" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="loginPassword" name="password" required>
            </div>
            <div class="form-group">
                <label for="verification_code">Code de vérification</label>
                <input type="text" id="verification_code" name="verification_code" required maxlength="6" pattern="\d{6}" placeholder="Entrez le code à 6 chiffres">
            </div>
            <button type="submit" class="btn btn-primary">Vérifier</button>
        </form>
        
        <div id="errorMessage" class="error-message" style="display: none;"></div>
    </div>

<script src="<?= htmlspecialchars(STATIC_URL); ?>js/admin-verify.js" defer></script>
<?php require_once COMPONENT_PATH . "foot.php"; ?>