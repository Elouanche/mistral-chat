<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once SHARED_PATH . "session.php";
require_once COMPONENT_PATH . "head.php";
?>

<main class="faq-page"  role="main">
    <h1>Foire aux questions</h1>

    <section>
        <div class="faq-item">
            <h2>Livraison et retours</h2>
            <p>Nos délais de livraison standard sont de 3-5 jours ouvrés. Les retours sont acceptés sous 14 jours.</p>
        </div>

        <div class="faq-item">
            <h2>Paiements sécurisés</h2>
            <p>Nous acceptons toutes les cartes bancaires via notre plateforme de paiement cryptée.</p>
        </div>

        <div class="faq-item">
            <h2>Garantie produit</h2>
            <p>Tous nos produits bénéficient d'une garantie de 2 ans.</p>
        </div>
    </section>
</main>

<?php require_once COMPONENT_PATH . "foot.php"; ?>