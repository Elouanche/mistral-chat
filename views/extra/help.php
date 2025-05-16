<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once SHARED_PATH . "session.php";
require_once COMPONENT_PATH . "head.php";
?>

<main class="help-page"  role="main">
    <h1>Centre d'aide</h1>
    
    <section>
        <h2>Questions fréquentes</h2>
        <div class="faq-item">
            <h3>Comment suivre ma commande ?</h3>
            <p>Vous pouvez suivre votre commande depuis votre espace client dans la section "Mes commandes".</p>
        </div>
        
        <div class="faq-item">
            <h3>Politique de retour</h3>
            <p>Vous avez 14 jours pour retourner un article non utilisé dans son emballage d'origine.</p>
        </div>
    </section>
    
    <section class="contact-support">
        <h2>Contactez le support</h2>
        <p>Email : support@loremipsum.com</p>
        <p>Téléphone : 01 23 45 67 89</p>
    </section>
</main>

<?php require_once COMPONENT_PATH . "foot.php"; ?>