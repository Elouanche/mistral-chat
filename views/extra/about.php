<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once SHARED_PATH . "session.php";
require_once COMPONENT_PATH . "head.php";
?>

<main class="about-page"  role="main">
    <h1>À propos de nous</h1>
    
    <section>
        <h2>Notre histoire</h2>
        <p>Fondée en 2023, notre entreprise s'est rapidement imposée comme un leader dans son domaine grâce à son engagement envers la qualité et l'innovation.</p>
    </section>
    
    <section>
        <h2>Notre mission</h2>
        <p>Nous nous engageons à fournir des produits de haute qualité tout en offrant un service client exceptionnel.</p>
    </section>
    
    <section>
        <h2>Notre équipe</h2>
        <p>Une équipe passionnée et expérimentée travaille sans relâche pour répondre à vos besoins.</p>
    </section>
</main>

<?php require_once COMPONENT_PATH . "foot.php"; ?>