<?php require_once SHARED_PATH . "session.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link type="text/css" rel="stylesheet" href="<?php echo STATIC_URL; ?>css/normalize.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link type="text/css" rel="stylesheet" href="<?php echo STATIC_URL; ?>css/My_style.css">
    <link rel="icon" href="<?php echo STATIC_URL; ?>asset/icon.webp" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <title>Loremipsum</title>
</head>


<body>
    <header id="header" >
        <nav aria-label="Navigation principale">
            <div role="banner">
                <a href="/" id="partiel" aria-label="Accueil Lorempsum">
                    <h1>Lorempsum</h1>
                    <div class="logo">
                        <img src="<?php echo STATIC_URL; ?>asset/logo.svg" alt="Logo de Lorempsum">
                    </div>
                </a>
            </div>
        </nav>
    </header>

    <!-- Mini Header juste en dessous -->
    <nav aria-label="Navigation secondaire" class="mini-header">
        <ul>
            <?php if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== 'admin'): ?>
                <li><a href="/cart" aria-label="Accéder au panier">Panier</a></li>
            <?php endif; ?>
            <li><a href="/user/account" aria-label="Accéder au compte">Compte</a></li>
            <li><a href="/about" aria-label="À propos de nous">À propos</a></li>
            
            <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === 'admin'): ?>
                <li><a href="/admin/dashboard" aria-label="Accéder au tableau de bord administrateur">Dashboard</a></li>
                <li><a href="/admin/support" aria-label="Accéder au support client">Support</a></li>
                <li><a href="/admin/stats" aria-label="Accéder aux statistiques">Statistiques</a></li>
            <?php endif; ?>
            <!-- Ajout des boutons sous le mini-header -->
            <div class="mini-header-buttons">
                <button class="button-spe"
                    aria-label="Acheter le tournevis de précision pour 24.90€"
                    data-product-id="5"
                    onclick="addToCart(5);">
                    24,90€ Acheter
                </button>
            </div>
    
        </ul>
    </nav>
    

    <script src="<?php echo STATIC_URL; ?>js/scripts-header.js"></script>


<?php //require_once COMPONENT_PATH . "cookie.html"; ?>
<!--
-rendre tout propre
-classe de style recusive
-image optimiser
-bouton fonctionnel
-mini-header pour user, pannier apropo ... fond noir ou gris
-redpensiv
-tab
-verification assecibilité
bannier cookie
alt
Potential for XSS vulnerabilities with unsanitized output
No CSRF protection visible for forms
No input validation shown*
Some inconsistent section structures
Commented section that should be removed ("a enlever")
Duplicate "Polyvalent" sections
Inconsistent CSS class naming (mix of Englis
Added width/height attributes to prevent layout shifts
Added screen reader text for visual elements
Added id attributes for aria-labelledby assoc
Added a main-content ID for skip links
Added CSRF token generation for forms
Used h() function (assumed to be htmlspecialchars) to escape output
Used url_for() function for generating URLs
Used proper form submission instead of butt
Standardized section structure with consistent classes
Removed duplicate "Polyvalent" section
Removed commented code marked for deletion
Added missing descriptive text where needed
Organized content with better semantic structure
Added missing form elements for add-to-cart
Create the backend functions like generate_csrf_token() and h() if they don't exist
Implement a real cookie management system (beyond just localStorage)
Create the required legal pages (privacy policy, terms, etc.)
Implement proper form processing and validation
Add structured data (Schema.org) for better SEO
Implement responsive design with appropriate media queries

W

géré les lang
uniformaisation des boutons
Basic JavaScript for cookie consent and interactive elements
More consistent styling classes
Improved form handling


ajouter box shadow flesh carrousel
 -->
 <main role="main" id="main-content" aria-label="Contenu principal de la page d'accueil">
    
    <!-- Section 1 : Carrousel affichant différents projets 
     
    <section aria-labelledby="section-carousel">        
        <div class="carousel" role="region" aria-roledescription="carrousel" aria-label="Projets">
            <button class="carousel-button carousel-button-left" aria-label="image précédent" id="carousel-prev">&larr;</button>
            <div class="carousel-container">
                <div class="carousel-track">
                    <div class="carousel-slide" role="group" aria-label="Projet 1 sur 3">
                        <h2>Un Outil</h2>
                        <h3>Conçu pour durer</h3>
                        <img src="<?php echo STATIC_URL; ?>asset/fond-1.webp" 
                            alt="Tournevis de précision avec 62 embouts" 
                            loading="lazy" 
                            onerror="this.src='<?php echo STATIC_URL; ?>asset/default-image.webp'">
                    </div>
                    <div class="carousel-slide" role="group" aria-label="Projet 2 sur 3">
                        <h2>Un Outil</h2>
                        <h3>Pour tout vos projets</h3>
                        <img src="<?php echo STATIC_URL; ?>asset/fond-2.webp" 
                            alt="Tournevis de précision avec 62 embouts" 
                            loading="lazy" 
                            onerror="this.src='<?php echo STATIC_URL; ?>asset/default-image.webp'">
                    </div>
                    <div class="carousel-slide" role="group" aria-label="Projet 3 sur 3">
                        <h2>Un Outil</h2>
                        <h3>Que vous saurez aimer</h3>
                        <img src="<?php echo STATIC_URL; ?>asset/fond-3.webp" 
                            alt="Tournevis de précision avec 62 embouts" 
                            loading="lazy" 
                            onerror="this.src='<?php echo STATIC_URL; ?>asset/default-image.webp'">
                    </div>
                </div>
            </div>
            <button class="carousel-button carousel-button-right" aria-label="image suivant" id="carousel-next">&rarr;</button>
        </div>
    </section>
    <script src="<?php echo STATIC_URL; ?>js/scripts-carrousel.js" ></script>
    
    
    
    
    -->
    
    

    <!-- Section 2 : Introduction du produit -->
    <section class="product-intro">
        <img src="<?php echo STATIC_URL; ?>asset/produit.webp" 
            alt="Tournevis de précision avec 62 embouts" 
            loading="lazy" 
            onerror="this.src='<?php echo STATIC_URL; ?>asset/default-image.webp'">
        <div>
            <h2>Tournevis de précision</h2>
            <h4 class="sub-title">62 embouts - Rangement magnétique - Confortable</h4>
            <button class="button-spe"
                aria-label="Acheter le tournevis de précision pour 24.90€"
                data-product-id="5"
                onclick="addToCart(5);">
                24,90€ Acheter
            </button>
        </div>
    </section>

    <!-- Section 3 : Qualité du produit -->
    <section class="feature-block">
        <h2 class="feature-title">Qualité</h2>
        <div class="feature-content">
            <img src="<?php echo STATIC_URL; ?>asset/26.png" 
                alt="Qualité du tournevis" 
                loading="lazy" 
                onerror="this.src='<?php echo STATIC_URL; ?>asset/qualité.webp'">
            <div class="feature-text">
                <p>Fabriqué en alliage d'acier, de chrome et de vanadium, ce tournevis est extrêmement résistant aux chocs et à l'usure.</p>
            </div>
        </div>
    </section>

    <!-- Section 4 : Présentation des embouts aimantés -->
    <section class="embout-aimenter">
        <img src="<?php echo STATIC_URL; ?>asset/tournevisse.webp" 
            alt="Embout aimanté gauche" 
            loading="lazy" 
            onerror="this.src='<?php echo STATIC_URL; ?>asset/default-image.webp'">
        
        <h2>Embouts aimantés</h2>
        <img src="<?php echo STATIC_URL; ?>asset/tournevisse.webp" 
            alt="Embout aimanté droit" 
            loading="lazy" 
            onerror="this.src='<?php echo STATIC_URL; ?>asset/default-image.webp'">
    </section>

    <!-- Section 5 : Polyvalence des embouts fournis -->
    <section class="feature-block reverse">
        <h2 class="feature-title">Polyvalence</h2>
        <div class="feature-content">
            <img src="<?php echo STATIC_URL; ?>asset/25.png" 
                alt="Différents embouts disponibles pour le tournevis"
                loading="lazy" 
                onerror="this.src='<?php echo STATIC_URL; ?>asset/default-image.webp'">
            <div class="feature-text">
                <ul class="tooltip">
                    <li><strong>63 Embouts</strong></li>
                    <li>Embouts usinés avec précision et solidité.</li>
                    <li class="embout-type"><h6>Phillips | Torx | Hex | Fente | Plate | Triangle | Pozidriv</h6></li>
                </ul>
            </div>
        </div>
    </section>

   
    
    
    <!-- Section 6 : Polyvalent tout un concept -->
    <section class="polyvalent">
        <h2>Polyvalent</h2>
        <p><strong>63 Embouts</strong> usiné avec précision et solidité, prêt à toutes vos réparations.</p>
        <img src="<?php echo STATIC_URL; ?>asset/polyvalence.webp" 
            alt="Embouts de tournevis polyvalents" 
            loading="lazy" 
            onerror="this.src='<?php echo STATIC_URL; ?>asset/default-image.webp'">
        <h6>Phillips | Torx | Hex | Fente | Plate | Triangle | Pozidriv</h6>
        <img class="bottom-icon" src="<?php echo STATIC_URL; ?>asset/embout.webp" 
            alt="Icône d'embout typique" 
            loading="lazy" 
            onerror="this.src='<?php echo STATIC_URL; ?>asset/default-image.webp'">
    </section>

    


    <!-- Section 7 : Compact -->
    <section class="product compact">
        <figure>
            <img src="<?php echo STATIC_URL; ?>asset/compact.webp" 
                alt="Tournevis compact et portable" 
                loading="lazy" 
                onerror="this.src='<?php echo STATIC_URL; ?>asset/default-image.webp'">
        </figure>
        <div class="product-info">
            <h2>Compact</h2>
            <p>Avec sa taille compacte, emmenez-le partout avec vous.</p>
            <h6 class="embout-type">(20 x 10 x 3,3 cm)</h6>
        </div>
    </section>
    <!-- Section 8 : Cadeau Idéal -->
    <section class="gift-section" aria-labelledby="gift-title">
        <div class="gift-content">
            <h2 id="gift-title">Le cadeau idéal pour tous les bricoleurs</h2>
            <p class="delivery-info">Livré en 1 semaine dans toute la France métropolitaine</p>
            <button class="button-spe"
                aria-label="Acheter le tournevis de précision pour 24.90€"
                data-product-id="5"
                onclick="addToCart(5);">
                24,90€ Acheter
            </button>
        </div>
    </section>
    <!-- Section 9 : Avis Clients -->
    <section class="customer-reviews">
        <div class="review-container">
            <img src="<?php echo STATIC_URL; ?>asset/use-1.webp" 
                alt="Avis clients" 
                loading="lazy" 
                onerror="this.src='<?php echo STATIC_URL; ?>asset/default-image.webp'">
            <div class="reviews-content">
                <h2>Avis Clients</h2>
                <div class="reviews-list">
                    <blockquote>
                        <p>"Super qualité, les embouts sont solides et précis !"</p>
                        <cite>- Julien D.</cite>
                    </blockquote>
                    <blockquote>
                        <p>"Un tournevis compact et pratique, parfait pour mes réparations."</p>
                        <cite>- Sophie L.</cite>
                    </blockquote>
                    <blockquote>
                        <p>"Livraison rapide et produit conforme, je recommande à 100% !"</p>
                        <cite>- Marc T.</cite>
                    </blockquote>
                </div>
                <button class="more-reviews">+</button>
            </div>
        </div>
    </section>
</main>
<?php $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null; ?>
<script>
    // Définir l'ID utilisateur pour les opérations du panier
    const sessionId = '<?php echo session_id(); ?>';
</script>

<script src="<?= htmlspecialchars(STATIC_URL); ?>js/page-cart.js" defer></script>

<!-- Scripts de panier local supprimés, utilisation de la BDD uniquement -->
<?php require_once COMPONENT_PATH . "foot.php"; ?>
