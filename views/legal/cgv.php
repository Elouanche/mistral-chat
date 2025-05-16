<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once SHARED_PATH . "session.php";
require_once COMPONENT_PATH . "head.php";
?>

<main class="cgv-page"  role="main">
    <h1>Conditions Générales de Vente</h1>
    
    <section>
        <h2>Article 1 - Objet</h2>
        <p>Les présentes conditions générales de vente régissent les relations contractuelles entre la société et ses clients.</p>
    </section>
    
    <section>
        <h2>Article 2 - Prix</h2>
        <p>Les prix sont indiqués en euros toutes taxes comprises.</p>
    </section>
    
    <section>
        <h2>Article 3 - Paiement</h2>
        <p>Le paiement s'effectue au moment de la commande par carte bancaire.</p>
    </section>
    
    <section>
        <h2>Article 4 - Livraison</h2>
        <p>Les délais de livraison sont indiqués à titre indicatif et ne sont pas garantis.</p>
    </section>
    
    <section>
        <h2>Article 5 - Retours</h2>
        <p>Les produits peuvent être retournés dans un délai de 14 jours à réception.</p>
    </section>
</main>

<?php require_once COMPONENT_PATH . "foot.php"; ?>