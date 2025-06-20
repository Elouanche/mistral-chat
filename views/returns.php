<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once SHARED_PATH . "session.php";
require_once COMPONENT_PATH . "head.php";
?>

<main role="main" class="returns-page">
    <h1>Retours produits</h1>
    
    <section>
        <h2>Procédure de retour</h2>
        <p>Vous pouvez initier un retour dans les 14 jours suivant la réception de votre commande.</p>
        <p>Merci de contacter notre service client pour obtenir une autorisation de retour.</p>
    </section>
    <a href="/contact">Nous contacter</a>
    <section>
        <h2>Conditions de retour</h2>
        <p>Les produits doivent être dans leur emballage d'origine et en parfait état.</p>
    </section>
</main>

<?php require_once COMPONENT_PATH . "foot.php"; ?>