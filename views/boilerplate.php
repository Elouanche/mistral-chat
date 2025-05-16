<?php require_once SHARED_PATH . "session.php"; ?>
<?php require_once COMPONENT_PATH . "head.php"; ?>

<main id="main-content" role="main"  class="cancel-page">
    <div class="container">
        <div class="corp">
            <!-- Section Titres -->
            <h2>Titre</h2>
            <section class="mb-3" aria-labelledby="section-titres">
                <h1 id="section-titres" class="mb-2">Titre de l'article <span class="text-secondary">(31/03/2015)</span></h1>
                <h2 class="mb-2">Titre du paragraphe (niveau 2)</h2>
                <h3 class="mb-2">Titre du paragraphe (niveau 3)</h3>
                <h4 class="mb-2">Titre du paragraphe (niveau 4)</h4>
                <h5 class="mb-2">Titre du paragraphe (niveau 5)</h5>
                <h6 class="mb-2">Titre du paragraphe (niveau 6)</h6>
            </section>

            <!-- Section Texte -->
            <h2 id="section-texte">Texte</h2>
            <section class="mb-3" aria-labelledby="section-texte">
                <p class="mb-2">
                    Morbi quam ipsum, porta vel sodales in, luctus quis sapien.
                    <a href="#" class="text-highlight" aria-label="Lien dans le paragraphe">Lien dans le paragraphe</a>.
                    Proin imperdiet mauris non magna gravida fermentum.
                </p>

                <blockquote class="exergue mb-2" cite="#">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus at venenatis nisl.
                </blockquote>

                <p class="highlight mb-2">
                    Mise en évidence : Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                </p>
            </section>

            <!-- Section Images -->
            <h2 id="section-image">Image</h2>
            <section class="mb-3" aria-labelledby="section-image">
                <figure class="image-float left">
                    <img src="image-left-600x400.jpg" width="600" height="400" alt="Image flottante à gauche" loading="lazy" onerror="this.src='<?php echo STATIC_URL; ?>asset/default-image.webp'">
                    <figcaption>Image flottante à gauche, Morbi quam ipsum, porta vel sodales in, luctus quis sapien.</figcaption>
                </figure>

                <figure class="image-float right">
                    <img src="image-right-600x400.jpg" width="600" height="400" alt="Image flottante à droite" loading="lazy" onerror="this.src='<?php echo STATIC_URL; ?>asset/default-image.webp'">
                    <figcaption>Image flottante à droite, Morbi quam ipsum, porta vel sodales in, luctus quis sapien.</figcaption>
                </figure>

                <img src="wide-image-1950x500.jpg" alt="Image large" class="responsive-img mb-2" loading="lazy" onerror="this.src='<?php echo STATIC_URL; ?>asset/default-image.webp'">
            </section>
            
            <!-- Double bloc -->
            <h2 id="double-block">Double bloc</h2>
            <section class="double-block mb-3" aria-labelledby="double-block">
                <img src="img-test-1920.jpg" alt="Bloc double" loading="lazy" onerror="this.src='<?php echo STATIC_URL; ?>asset/default-image.webp'">
                <div class="content">
                    <p class="mb-1">Duis laoreet fermentum mi sed auctor. Mauris a ante ut urna suscipit ornare sed a tellus.</p>
                    <p>Sed gravida ultrices tincidunt. Sed elementum odio quis arcu vulputate venenatis.</p>
                </div>
            </section>

            <!-- Tableau -->
            <h2 id="section-tableau">Tableau</h2>
            <section class="mb-3" aria-labelledby="section-tableau">
                <table aria-describedby="tableau-description">
                    <caption id="tableau-description" class="mb-1">Titre du tableau</caption>
                    <thead>
                        <tr>
                            <th scope="col">Colonne 1</th>
                            <th scope="col">Colonne 2</th>
                            <th scope="col">Colonne 3</th>
                            <th scope="col">Colonne 4</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Contenu A</td>
                            <td>Contenu long Donec id elit non mi porta gravida at eget metus.</td>
                            <td>Contenu C</td>
                            <td>Contenu D</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- Formulaire-->
            <h2 id="section-formulaire">Formulaire</h2>
            <form action="#" method="post" class="mb-3" aria-labelledby="section-formulaire">
                <div class="form-group">
                    <label for="name">Nom :</label>
                    <input type="text" id="name" name="name" required aria-required="true">
                </div>

                <div class="form-group">
                    <label for="email">Email :</label>
                    <input type="email" id="email" name="email" required aria-required="true">
                </div>

                <div class="form-group">
                    <label for="message">Message :</label>
                    <textarea id="message" name="message" required aria-required="true"></textarea>
                </div>

                <fieldset class="form-group">
                    <legend>Genre :</legend>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="gender" value="male" id="gender-male">
                            <span>Homme</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="gender" value="female" id="gender-female">
                            <span>Femme</span>
                        </label>
                    </div>
                </fieldset>

                <div class="form-group">
                    <label for="file">Fichier :</label>
                    <input type="file" id="file" name="file">
                </div>

                <button type="submit" class="button-spe">Envoyer</button>
            </form>

            <!-- Boutons d'action -->
            <section aria-labelledby="section-bouton">
                <h2 id="section-bouton">Bouton</h2>
                <div class="button-group mb-3" role="group" aria-labelledby="section-bouton">
                    <button class="button-spe" aria-label="Acheter maintenant">Acheter</button>
                    <button class="button-spe" aria-label="Ajouter au panier">Ajouter au panier</button>
                </div>
            </section>

            
            <!-- Flex block -->
            <section aria-labelledby="section-flex">
                <h2 id="section-flex">Flex block</h2>
                <div class="flex flex-col items-center justify-between gap-md">
                    <div class="flex-1">Élément flexible qui prendra tout l'espace disponible</div>
                    <div class="flex-none">Élément qui ne sera pas redimensionné</div>
                </div>
            </section>

            <!-- Grid -->
            <section aria-labelledby="section-grid">
                <h2 id="section-grid">Grid</h2>
                <div class="grid grid-cols-3 gap-md">
                    <div class="col-span-2">Élément qui prend 2 colonnes</div>
                    <div>Élément normal</div>
                    <div class="col-span-3">Élément qui prend toute la largeur</div>
                </div>
            </section>

            <!-- Exemple responsive -->
            <h2 id="section-mobile">Exemple mobile</h2>
            <div class="flex md-flex-col" aria-labelledby="section-mobile">
            <!-- Sera en ligne sur desktop, en colonne sur tablette/mobile -->
            </div>

            
            <!-- Carrousel -->
            <section aria-labelledby="section-carousel">
                <h2 id="section-carousel">Carrousel</h2>
                <link rel="stylesheet" href="<?= STATIC_URL; ?>css/styles-carrousel.css">
                <div class="carousel" role="region" aria-roledescription="carrousel" aria-label="Projets">
                    <button class="carousel-button carousel-button-left" aria-label="Projet précédent" id="carousel-prev">&larr;</button>
                    <div class="carousel-container">
                        <div class="carousel-track">
                            <div class="carousel-slide" role="group" aria-label="Projet 1 sur 3">
                                <h3>Projet 1</h3>
                                <p>Description du projet 1</p>
                            </div>
                            <div class="carousel-slide" role="group" aria-label="Projet 2 sur 3">
                                <h3>Projet 2</h3>
                                <p>Description du projet 2</p>
                            </div>
                            <div class="carousel-slide" role="group" aria-label="Projet 3 sur 3">
                                <h3>Projet 3</h3>
                                <p>Description du projet 3</p>
                            </div>
                        </div>
                    </div>
                    <button class="carousel-button carousel-button-right" aria-label="Projet suivant" id="carousel-next">&rarr;</button>
                </div>
            </section>

            <!-- Galerie -->
            <h2 id="section-galerie">Galerie</h2>
            <link rel="stylesheet" href="<?= STATIC_URL; ?>css/styles-gallery.css">
            <div class="gallery-container" role="region" aria-roledescription="galerie d'images">
                <div class="main-image-container">
                    <button id="bouton-l" class="gallery-button gallery-button-left" aria-label="Image précédente">&larr;</button>
                    <img src="<?php echo STATIC_URL; ?>asset/default-1.jpg" alt="Image principale" loading="lazy" class="main-image" onerror="this.src='<?php echo STATIC_URL; ?>asset/default-image.webp'" id="main-gallery-image">
                    <button id="bouton-r" class="gallery-button gallery-button-right" aria-label="Image suivante">&rarr;</button>
                </div>
                <div class="thumbnails-container" role="list" aria-label="Vignettes">
                    <img src="<?php echo STATIC_URL; ?>asset/default-1.webp" alt="Vignette 1 (sélectionnée)" loading="lazy" class="thumbnail active" onerror="this.src='<?php echo STATIC_URL; ?>asset/default-image.webp'" role="button" aria-selected="true" tabindex="0">
                    <img src="<?php echo STATIC_URL; ?>asset/default-2.webp" alt="Vignette 2" loading="lazy" class="thumbnail" onerror="this.src='<?php echo STATIC_URL; ?>asset/default-image.webp'" role="button" aria-selected="false" tabindex="0">
                    <img src="<?php echo STATIC_URL; ?>asset/default-3.webp" alt="Vignette 3" loading="lazy" class="thumbnail" onerror="this.src='<?php echo STATIC_URL; ?>asset/default-image.webp'" role="button" aria-selected="false" tabindex="0">
                </div>
            </div>
            
            <!-- Section Produits -->
            <link rel="stylesheet" href="<?= STATIC_URL; ?>css/styles-products.css">
            <section class="products-section mb-3" aria-labelledby="section-produits">
                <h2 id="section-produits">Produits</h2>
                <article class="product">
                    <figure>
                        <img src="<?php echo STATIC_URL; ?>asset/img-utile.png" alt="Image du tournevis de précision avec 62 embouts">
                        <figcaption>Tournevis de précision</figcaption>
                    </figure>
                    <div class="product-info">
                        <h2>Tournevis de précision</h2>
                        <ul>
                            <li>62 embouts</li>
                            <li>Embouts magnétiques</li>
                            <li>Alliage renforcé</li>
                        </ul>
                        <button class="button-spe" aria-label="Acheter le tournevis de précision pour 24.90€">24,90€ Acheter</button>
                    </div>
                </article>
                
                <!-- Contrôles de pagination -->
                <div class="pagination-controls flex justify-between items-center mb-2">
                    <div class="products-per-page">
                        <label for="products-per-page">Produits par page :</label>
                        <select id="products-per-page" name="products-per-page">
                            <option value="8">8</option>
                            <option value="12">12</option>
                            <option value="16">16</option>
                            <option value="24">24</option>
                        </select>
                    </div>
                    <nav class="pagination" aria-label="Pagination des produits">
                        <button class="pagination-button" aria-label="Page précédente" disabled id="prev-page">&laquo;</button>
                        <span class="current-page" aria-live="polite">Page 1 sur 5</span>
                        <button class="pagination-button" aria-label="Page suivante" id="next-page">&raquo;</button>
                    </nav>
                </div>
            </section>
            


            <!-- Liste des Produits -->
            <section class="products-container" aria-labelledby="section-produits" role="list">
                
                <!-- Carte produit -->
                <article class="product-card" role="listitem">
                    <figure class="product-image">
                        <img src="product1.jpg" alt="Nom du produit 1" loading="lazy" onerror="this.src='<?= STATIC_URL; ?>asset/default-image.webp'">
                    </figure>
                    <div class="product-details">
                        <h3 class="product-name">Nom du produit 1</h3>
                        <p class="product-price"><span class="visually-hidden">Prix : </span>29,99 €</p>
                        <p class="product-availability in-stock"><span class="visually-hidden">Disponibilité : </span>En stock</p>
                        <button class="button-spe add-to-cart" type="button" data-product-id="1">Ajouter au panier</button>
                    </div>
                </article>
                
                <article class="product-card" role="listitem">
                    <figure class="product-image">
                        <img src="product2.jpg" alt="Nom du produit 2" loading="lazy" onerror="this.src='<?= STATIC_URL; ?>asset/default-image.webp'">
                    </figure>
                    <div class="product-details">
                        <h3 class="product-name">Nom du produit 2</h3>
                        <p class="product-price"><span class="visually-hidden">Prix : </span>49,99 €</p>
                        <p class="product-availability low-stock"><span class="visually-hidden">Disponibilité : </span>Stock limité</p>
                        <button class="button-spe add-to-cart" type="button" data-product-id="2">Ajouter au panier</button>
                    </div>
                </article>
                
                <article class="product-card" role="listitem">
                    <figure class="product-image">
                        <img src="product3.jpg" alt="Nom du produit 3" loading="lazy" onerror="this.src='<?= STATIC_URL; ?>asset/default-image.webp'">
                    </figure>
                    <div class="product-details">
                        <h3 class="product-name">Nom du produit 3</h3>
                        <p class="product-price"><span class="visually-hidden">Prix : </span>19,99 €</p>
                        <p class="product-availability out-of-stock"><span class="visually-hidden">Disponibilité : </span>Rupture de stock</p>
                        <button class="button-spe add-to-cart" type="button" data-product-id="3" disabled aria-disabled="true">Indisponible</button>
                    </div>
                </article>
                
                <article class="product-card" role="listitem">
                    <figure class="product-image">
                        <img src="product4.jpg" alt="Nom du produit 4" loading="lazy" onerror="this.src='<?= STATIC_URL; ?>asset/default-image.webp'">
                    </figure>
                    <div class="product-details">
                        <h3 class="product-name">Nom du produit 4</h3>
                        <p class="product-price"><span class="visually-hidden">Prix : </span>39,99 €</p>
                        <p class="product-availability in-stock"><span class="visually-hidden">Disponibilité : </span>En stock</p>
                        <button class="button-spe add-to-cart" type="button" data-product-id="4">Ajouter au panier</button>
                    </div>
                </article>
                <!-- Pagination bas de page -->
                <nav class="pagination-bottom flex justify-center mt-2" aria-label="Pagination des produits">
                    <button class="pagination-button" aria-label="Première page" disabled id="first-page">&laquo;</button>
                    <button class="pagination-button active" aria-current="page" aria-label="Page 1">1</button>
                    <button class="pagination-button" aria-label="Page 2">2</button>
                    <button class="pagination-button" aria-label="Page 3">3</button>
                    <button class="pagination-button" aria-label="Page 4">4</button>
                    <button class="pagination-button" aria-label="Page 5">5</button>
                    <button class="pagination-button" aria-label="Dernière page" id="last-page">&raquo;</button>
                </nav>
            </section>

            

            <script src="https://code.jquery.com/jquery-1.11.1.min.js"></script>
            <section class="custom">
                <div class="presonalisation"> 
                    <!-- Svg a change pour un autre produit + image a change au besoin -->
                    <img class="image" src="product2.jpg" alt="Product image of item 2, showcasing front view" loading="lazy" onerror="this.src='<?= STATIC_URL; ?>asset/default-image.webp'">
                    
                    <svg class="svg" id="Plan_1" aria-label="Custom plan with personalized name and number">
                    <!-- Bordure extérieure -->
                        <g>
                            <g fill="none" stroke-width="6" text-anchor="middle">
                                <!-- Bordure intérieure -->
                                <text class="st1" id="pre" x="125" y="60" font-family="mdr" >Jessy</text>
                                <text class="st2" id="nom" x="170" y="90" font-family='mama'>PIQUEREL</text>
                                <text class="st3" id="num" x="160" y="170" font-family='mama'  text-anchor="middle">80</text>
                                
                            </g>
                            <g fill="black" stroke="white" stroke-width="3"  text-anchor="middle" >
                                <!-- Texte intérieure -->
                                <text id="pre" x="125" y="60"  font-family="mdr">Jessy</text>
                                <text id="nom" x="170" y="90" font-family='mama'>PIQUEREL</text>
                                <text id="num" x="160" y="170" fill="black" stroke="white"  font-family='mama'   >80</text>
                            </g>
                            <g  fill="black" stroke="black" stroke-width="1"  text-anchor="middle">
                                <!-- Texte intérieure -->
                                <text id="pre" x="125" y="60" font-family="mdr" >Jessy</text>
                                <text id="nom" x="170" y="90"font-family='mama'>PIQUEREL</text>
                                <text id="num" x="160" y="170" fill="black" stroke="black" font-family='mama'>80</text>
                            </g>
                        </g>
                        <g id="svg_1" transform="matrix(0.19 0 0 0.19 210 160)">
                            <path d="m170.43,91.27c1,-2 0.7,-4.4 -0.7,-6.1l-3.4,-4.2c-1,-1.2 -2.3,-1.9 -3.9,-2.1c0.3,-1.6 0,-3.3 -1,-4.6l-18.2,-24.5l18.1,-24.4c0.8,-1 1.2,-2.2 1.2,-3.5c0,-3.2 -2.6,-5.7 -5.7,-5.7l0,0l-35,0c-1.8,0 -3.5,0.9 -4.6,2.3l-5.8,7.9l-5.8,-7.9c-1.1,-1.5 -2.8,-2.3 -4.6,-2.3l-79.1,0c-3.2,0 -5.7,2.6 -5.7,5.7l0,55.8c0,2.1 1.2,4 2.9,5c-0.7,0.9 -1.3,1.8 -1.7,2.9c-0.5,1.3 -0.7,2.6 -0.7,4.1c0,1.4 0.2,2.7 0.7,3.9c0.3,0.8 0.7,1.5 1.1,2.2l-1.3,2c-1.2,1.8 -1.3,4 -0.3,5.9c1,1.9 2.9,3 5.1,3l13.2,0c0.6,0 1.1,0 1.6,-0.1l9.6,0c0.3,0 0.6,0.1 0.9,0.1l4.4,0c0.3,0 0.6,0 0.9,-0.1l7.5,0c0.3,0 0.6,0.1 0.9,0.1l4.4,0c0.3,0 0.6,0 0.9,-0.1l8.4,0c0.5,0 1,0.1 1.5,0.1l15.9,0c0.3,0 0.6,0 1,-0.1l5,0c0.3,0.1 0.6,0.1 0.9,0.1l6.6,0c0.2,0 0.5,0 0.7,-0.1c0.2,0 0.5,0.1 0.7,0.1l13.7,0c0.3,0 0.6,0 0.9,-0.1l2.5,0c0.3,0 0.6,0.1 0.9,0.1l4.4,0c0.3,0 0.6,0 0.9,-0.1l8.1,0c0.3,0 0.6,0.1 0.9,0.1l5.9,0c0.3,0 0.6,0 0.9,-0.1l1.2,0c0.3,0 0.6,0.1 0.9,0.1l11,0c1.7,0 3.2,-0.2 4.5,-0.6c1.6,-0.5 2.9,-1.3 4.1,-2.3c1.2,-1.1 2.1,-2.5 2.6,-4.1c0.4,-1.3 0.6,-2.6 0.6,-4.1c0,-1.5 -0.3,-2.8 -0.6,-3.8c-0.2,-0.2 -0.1,-0.3 0,-0.5z" id="svg_2" stroke-width="101"/>
                            <g id="svg_3">
                                <g id="svg_4">
                                    <g id="svg_5">
                                        <g id="svg_6">
                                            <polygon id="svg_7" points="21.8333740234375,21.86669921875 100.8333740234375,21.86669921875 111.3333740234375,35.9666748046875 121.7333984375,21.86669921875 156.7333984375,21.86669921875 136.03338623046875,49.76666259765625 156.7333984375,77.66668701171875 122.2333984375,77.66668701171875 111.7333984375,63.56671142578125 101.2333984375,77.66668701171875 21.8333740234375,77.66668701171875 " stroke-width="101"/>
                                            <polygon class="st4" id="svg_8" points="40.3333740234375,40.36669921875 40.3333740234375,70.76666259765625 28.63336181640625,70.76666259765625 28.63336181640625,28.76666259765625 95.63336181640625,28.76666259765625 111.3333740234375,49.76666259765625 95.63336181640625,70.76666259765625 81.13336181640625,70.76666259765625 96.8333740234375,49.76666259765625 89.8333740234375,40.36669921875 " stroke-width="101"/>
                                            <polygon class="st5" id="svg_9" points="141.433349609375,28.76666259765625 126.933349609375,28.76666259765625 111.3333740234375,49.76666259765625 126.933349609375,70.76666259765625 141.433349609375,70.76666259765625 125.8333740234375,49.76666259765625 " stroke-width="101"/>
                                        </g>
                                    </g>
                                    <rect class="st4" height="23.3" id="svg_10" stroke-width="101" width="11.6" x="57.53" y="47.47"/>
                                </g>
                                <g id="svg_11">
                                    <path class="st4" d="m28.03,88.87c-0.5,0 -0.9,0.1 -1.1,0.2c-0.2,0.2 -0.4,0.5 -0.4,0.9c0,0.4 0.1,0.7 0.4,0.9c0.2,0.2 0.6,0.3 1.1,0.3l7.7,0c1.7,0 3.1,0.4 4,1.1c0.9,0.7 1.4,1.9 1.4,3.4c0,0.8 -0.1,1.6 -0.3,2.2c-0.2,0.7 -0.6,1.2 -1.1,1.7c-0.5,0.5 -1.1,0.8 -1.9,1c-0.8,0.2 -1.7,0.4 -2.8,0.4l-13.2,0l2.8,-4.2l10.7,0c0.5,0 0.9,-0.1 1.1,-0.3c0.2,-0.2 0.4,-0.5 0.4,-0.9c0,-0.4 -0.1,-0.7 -0.4,-0.9c-0.2,-0.2 -0.6,-0.3 -1.1,-0.3l-7.7,0c-0.9,0 -1.7,-0.1 -2.4,-0.3c-0.7,-0.2 -1.2,-0.5 -1.7,-1c-0.4,-0.4 -0.8,-0.9 -1,-1.5c-0.2,-0.6 -0.3,-1.2 -0.3,-1.9c0,-0.8 0.1,-1.5 0.3,-2.1c0.2,-0.6 0.6,-1.2 1.1,-1.6c0.5,-0.4 1.1,-0.8 1.9,-1c0.8,-0.2 1.7,-0.4 2.8,-0.4l31,0l-2.8,4.2l-5.1,0l0,12.1l-4.4,0l0,-12.1l-19,0l0,0.1z" id="svg_12" stroke-width="101"/>
                                    <path class="st4" d="m65.23,100.87l-4.4,0l0,-16.3l4.4,0l0,16.3z" id="svg_13" stroke-width="101"/>
                                    <path class="st4" d="m76.03,100.87c-1.2,0 -2.4,-0.2 -3.4,-0.6c-1,-0.4 -1.9,-1 -2.7,-1.7c-0.8,-0.7 -1.3,-1.6 -1.8,-2.6c-0.4,-1 -0.6,-2.1 -0.6,-3.3c0,-1.2 0.2,-2.3 0.6,-3.3c0.4,-1 1,-1.8 1.8,-2.5c0.8,-0.7 1.7,-1.3 2.7,-1.6c1,-0.4 2.2,-0.6 3.4,-0.6l10,0l-2.8,4.2l-7.1,0c-0.6,0 -1.1,0.1 -1.7,0.3c-0.5,0.2 -0.9,0.5 -1.3,0.8c-0.4,0.3 -0.7,0.8 -0.9,1.3c-0.2,0.5 -0.3,1 -0.3,1.6s0.1,1.1 0.3,1.6c0.2,0.5 0.5,0.9 0.9,1.2c0.4,0.3 0.8,0.6 1.3,0.8c0.5,0.2 1.1,0.3 1.7,0.3l11.5,0l0,-12.1l4.4,0l0,3.8c0,0.5 0,1 0,1.5c0,0.5 0,0.9 -0.1,1.4c0.3,-0.3 0.6,-0.7 1,-1.1c0.4,-0.4 0.9,-1 1.6,-1.6l4.1,-4l6.4,0l-5.4,4.7c-0.5,0.4 -0.9,0.8 -1.2,1.1c-0.4,0.3 -0.7,0.6 -1,0.8c-0.3,0.2 -0.5,0.4 -0.8,0.6c-0.2,0.2 -0.5,0.3 -0.7,0.5c0.4,0.3 0.9,0.7 1.5,1.2c0.6,0.5 1.3,1.2 2.2,2l5.9,5.4l-6.6,0l-4.3,-4.3c-0.7,-0.7 -1.3,-1.3 -1.7,-1.7c-0.4,-0.4 -0.7,-0.7 -0.9,-1c0,0.4 0,0.8 0,1.2c0,0.4 0,0.8 0,1.2l0,4.7l-16,0l0,-0.2z" id="svg_14" stroke-width="101"/>
                                    <path class="st4" d="m111.33,88.87l0,2l11.2,0l-2.5,3.7l-8.7,0l0,2.2l12.1,0l-2.8,4.2l-13.7,0l0,-16.3l29.9,0c0.8,0 1.6,0.1 2.5,0.3c0.9,0.2 1.6,0.5 2.4,0.9c0.7,0.4 1.3,1 1.8,1.8c0.5,0.7 0.7,1.7 0.7,2.9c0,0.6 -0.1,1.2 -0.3,1.8c-0.2,0.6 -0.4,1.1 -0.7,1.5c-0.3,0.5 -0.7,0.8 -1.2,1.2c-0.5,0.3 -1,0.5 -1.6,0.7c0.2,0.2 0.5,0.5 0.8,0.8c0.3,0.3 0.7,0.8 1.2,1.3l2.6,3.2l-5.9,0l-3.2,-4.3l-6.7,0l0,4.3l-4.4,0l0,-8.3l12.4,0c0.7,0 1.3,-0.2 1.7,-0.5c0.5,-0.3 0.7,-0.8 0.7,-1.3c0,-0.6 -0.2,-1.1 -0.6,-1.4c-0.4,-0.3 -1,-0.5 -1.8,-0.5l-25.9,0l0,-0.2z" id="svg_15" stroke-width="101"/>
                                    <path class="st4" d="m159.73,91.07c1.7,0 3.1,0.4 4,1.1c0.9,0.7 1.4,1.9 1.4,3.4c0,0.8 -0.1,1.6 -0.3,2.2c-0.2,0.7 -0.6,1.2 -1.1,1.7c-0.5,0.5 -1.1,0.8 -1.9,1c-0.8,0.2 -1.7,0.4 -2.8,0.4l-11,0l-3.4,-4.2l14.7,0c0.5,0 0.9,-0.1 1.1,-0.3c0.2,-0.2 0.4,-0.5 0.4,-0.9c0,-0.4 -0.1,-0.7 -0.4,-0.9c-0.2,-0.2 -0.6,-0.3 -1.1,-0.3l-7.7,0c-0.9,0 -1.7,-0.1 -2.4,-0.3c-0.7,-0.2 -1.2,-0.5 -1.7,-1c-0.4,-0.4 -0.8,-0.9 -1,-1.5c-0.2,-0.6 -0.3,-1.2 -0.3,-1.9c0,-0.8 0.1,-1.5 0.3,-2.1c0.2,-0.6 0.6,-1.2 1.1,-1.6c0.5,-0.4 1.1,-0.8 1.9,-1c0.8,-0.2 1.7,-0.4 2.8,-0.4l9.4,0l3.4,4.2l-13.2,0c-0.5,0 -0.9,0.1 -1.1,0.2c-0.2,0.2 -0.4,0.5 -0.4,0.9c0,0.4 0.1,0.7 0.4,0.9c0.2,0.2 0.6,0.3 1.1,0.3l7.8,0l0,0.1z" id="svg_16" stroke-width="101"/>
                                </g>
                            </g>
                            <path class="st4" d="m157.63,78.17l-35.7,0l-10.2,-13.8l-10.2,13.8l-80.2,0l0,-56.7l79.7,0l10.2,13.8l10.2,-13.8l36.1,0l-21.1,28.4l21.2,28.3zm-35.2,-0.9l33.5,0l-20.4,-27.5l20.4,-27.5l-33.9,0l-10.7,14.4l-10.7,-14.4l-78.4,0l0,55l78.8,0l10.7,-14.4l10.7,14.4z" id="svg_17" stroke-width="101"/>
                        </g>
                    </svg>
                </div>
                <fieldset id="perso" class="detail" >
                    <div id="persoHead">
                        <p>
                            <span class="h3-like">Personnalisez votre produit</span>
                        </p>
                    </div>
                    <div id="persoFields" style="display:block;">
                        <div class='row'>
                            <div class='small-6 columns'>
                                <span class='label'>
                                    <label for='detail1'>Votre Prénom</label>
                                </span>
                            </div>
                            <div class='small-6 columns'>
                                <span class='fields'>
                                    <input type="text" value="" id="pp_pre" onkeyup="changeText('pre', this.value)">
                                </span>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='small-6 columns'>
                                <span class='label'>
                                    <label for='detail2'>Votre Nom</label>
                                </span>
                            </div>
                            <div class='small-6 columns'>
                                <span class='fields'>
                                    <input type="text" value="" id="pp_nom" onkeyup="changeText('nom', this.value)">
                                </span>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='small-6 columns'>
                                <span class='label'>
                                    <label for='detail3'>Votre Numéro</label>
                                </span>
                            </div>
                            <div class='small-6 columns'>
                                <span class='fields'>
                                    <input type="number" value="" id="pp_num" onkeyup="changeText('num', this.value)">
                                </span>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='small-6 columns'>
                                <span class='label'>
                                    <label for='detail4'>Couleur</label>
                                </span>
                            </div>
                            <input type="color" id="colorPicker" onchange="changeColor()">
                        </div>
                    <script>
                        var ListeDetail = new Array();ListeDetail["detail0"]=["Personnalisation",0];ListeDetail["detail1"]=["Votre Prénom",0];ListeDetail["detail2"]=["Votre Nom",0];ListeDetail["detail3"]=["Votre Numéro",0];ListeDetail["detail4"]=["Couleur",0];ListeDetail["detail5"]=["Choix des options",0];ListeDetail["detail6"]=["Taille du maillot",0];ListeDetail["detail7"]=["Age de l'enfant",0];
                    </script>
                    <input type="hidden" id="customisedtext_9" name="customised_text"/>
                    <input type="hidden" id="customisedtext_13" name="customised_text" />
                </fieldset>
                        
				
            </section>
            <!-- Section Accordéon -->
            <section aria-labelledby="section-accordion">
                <h2 id="section-accordion">Accordéon / FAQ</h2>
                <div class="accordion" role="region">
                    <div class="accordion-item">
                        <h3>
                            <button class="accordion-trigger" aria-expanded="false" aria-controls="accordion-panel-1" id="accordion-header-1">
                                Question 1
                                <span class="accordion-icon" aria-hidden="true"></span>
                            </button>
                        </h3>
                        <div id="accordion-panel-1" role="region" aria-labelledby="accordion-header-1" class="accordion-panel" hidden>
                            <p>Réponse détaillée à la première question. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus at venenatis nisl.</p>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h3>
                            <button class="accordion-trigger" aria-expanded="false" aria-controls="accordion-panel-2" id="accordion-header-2">
                                Question 2
                                <span class="accordion-icon" aria-hidden="true"></span>
                            </button>
                        </h3>
                        <div id="accordion-panel-2" role="region" aria-labelledby="accordion-header-2" class="accordion-panel" hidden>
                            <p>Réponse détaillée à la deuxième question. Morbi quam ipsum, porta vel sodales in, luctus quis sapien.</p>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h3>
                            <button class="accordion-trigger" aria-expanded="false" aria-controls="accordion-panel-3" id="accordion-header-3">
                                Question 3
                                <span class="accordion-icon" aria-hidden="true"></span>
                            </button>
                        </h3>
                        <div id="accordion-panel-3" role="region" aria-labelledby="accordion-header-3" class="accordion-panel" hidden>
                            <p>Réponse détaillée à la troisième question. Praesent imperdiet mauris non magna gravida fermentum.</p>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Section notification -->
            <section aria-labelledby="section-accordion">
                <h2 id="section-accordion">Accordéon / FAQ</h2>
                <div class="notification success">
                    <span class="notification-message">Opération réussie !</span>
                    <button class="close-button" onclick="closeNotification(this.parentElement)">×</button>
                </div>
            </section>
            <!-- Section search barre -->
            <section aria-labelledby="section-search-barre">
                <h2 id="section-search-barre">Search-barre</h2>
                <div class="search-container">
                    <label for="search" class="sr-only">Rechercher</label>
                    <input type="text" id="search" class="search-input" placeholder="Rechercher..." aria-label="Champ de recherche" oninput="showSuggestions(this.value)">
                    <ul id="suggestions" class="search-suggestions" role="listbox"></ul>
                    
                    <div class="filters">
                        <label>
                            <input type="checkbox" name="filter" value="recent"> Récents
                        </label>
                        <label>
                            <input type="checkbox" name="filter" value="populaire"> Populaires
                        </label>
                    </div>
                </div>
            </section>
            <section aria-labelledby="section-progresse-barre">
                <h2  id="section-progresse-barre" >Processus de paiement</h2>
                <div class="container">
                    
                    
                    <div class="progress-container">
                    <div class="steps">
                        <div class="step active" id="step1">1<div class="step-label">Panier</div></div>
                        <div class="step" id="step2">2<div class="step-label">Informations</div></div>
                        <div class="step" id="step3">3<div class="step-label">Livraison</div></div>
                        <div class="step" id="step4">4<div class="step-label">Paiement</div></div>
                        <div class="step" id="step5">5<div class="step-label">Confirmation</div></div>
                    </div>
                    
                    <div class="progress-bar">
                        <div class="progress-bar-fill" id="progress-fill"></div>
                    </div>
                    </div>
                    
                    <div id="content">
                    <div id="content-1">
                        <h2>Étape 1: Votre panier</h2>
                        <p>Vérifiez les articles dans votre panier avant de continuer.</p>
                    </div>
                    
                    <div id="content-2" style="display:none">
                        <h2>Étape 2: Vos informations</h2>
                        <p>Entrez vos coordonnées personnelles pour la commande.</p>
                    </div>
                    
                    <div id="content-3" style="display:none">
                        <h2>Étape 3: Options de livraison</h2>
                        <p>Choisissez votre méthode de livraison préférée.</p>
                    </div>
                    
                    <div id="content-4" style="display:none">
                        <h2>Étape 4: Paiement</h2>
                        <p>Entrez vos informations de paiement en toute sécurité.</p>
                    </div>
                    
                    <div id="content-5" style="display:none">
                        <h2>Étape 5: Confirmation</h2>
                        <p>Votre commande a été traitée avec succès!</p>
                    </div>
                    </div>
                    
                    <div class="controls">
                    <button id="prev" disabled>Précédent</button>
                    <button id="next">Suivant</button>
                    </div>
                </div>
            </section>
        </div>
    </div>
</main>

<!-- Scripts JavaScript -->
<script src="<?= STATIC_URL; ?>js/scripts-carrousel.js" defer></script>
<script src="<?= STATIC_URL; ?>js/scripts-gallery.js" defer></script>
<script src="<?= STATIC_URL; ?>js/scripts-products.js" defer></script>
<script>
    // Attendre que le DOM et tous les scripts soient chargés
    document.addEventListener('DOMContentLoaded', function() {
        const images = [
            '<?php echo STATIC_URL; ?>asset/default-1.webp',
            '<?php echo STATIC_URL; ?>asset/default-2.webp',
            '<?php echo STATIC_URL; ?>asset/default-3.webp'
        ];
        const gallery = new ImageGallery(images);
        
        // Gestion du focus avec le clavier pour les vignettes de la galerie
        const thumbnails = document.querySelectorAll('.thumbnail');
        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        });
        
        // Gestion de l'accessibilité du carrousel
        const slides = document.querySelectorAll('.carousel-slide');
        const updateSlideLabels = () => {
            slides.forEach((slide, index) => {
                slide.setAttribute('aria-label', `Projet ${index + 1} sur ${slides.length}`);
            });
        };
        updateSlideLabels();
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner tous les boutons d'accordéon
    const accordionTriggers = document.querySelectorAll('.accordion-trigger');
    
    // Ajouter des écouteurs d'événements à chaque bouton
    accordionTriggers.forEach(trigger => {
        trigger.addEventListener('click', function() {
            // Obtenir l'état actuel
            const expanded = this.getAttribute('aria-expanded') === 'true';
            
            // Inverser l'état
            this.setAttribute('aria-expanded', !expanded);
            
            // Obtenir le panneau associé
            const panelId = this.getAttribute('aria-controls');
            const panel = document.getElementById(panelId);
            
            // Afficher ou masquer le panneau
            panel.hidden = expanded;
        });
        
        // Gérer la navigation au clavier
        trigger.addEventListener('keydown', function(e) {
            const triggers = Array.from(accordionTriggers);
            const index = triggers.indexOf(this);
            let targetTrigger;
            
            switch(e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    targetTrigger = triggers[(index + 1) % triggers.length];
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    targetTrigger = triggers[(index - 1 + triggers.length) % triggers.length];
                    break;
                case 'Home':
                    e.preventDefault();
                    targetTrigger = triggers[0];
                    break;
                case 'End':
                    e.preventDefault();
                    targetTrigger = triggers[triggers.length - 1];
                    break;
                default:
                    return;
            }
            
            targetTrigger.focus();
        });
    });
});
</script>
<script>
    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }
    function changeText(elementId, newValue) {
        var elements = document.querySelectorAll("[id$='" + elementId + "']");
        elements.forEach(function(el) {
            if (elementId === 'nom') {
                newValue = newValue.toUpperCase(); // Mettre en majuscules
            } else if (elementId === 'pre') {
                newValue = capitalizeFirstLetter(newValue); // Mettre la première lettre en majuscule
            }
            el.textContent = newValue;
        });
    }
    function changeColor() {
        var color = document.getElementById('colorPicker').value;
        var elements = document.querySelectorAll(".st1, .st2, .st3");
        elements.forEach(function(el) {
            el.setAttribute('fill', color);
            el.setAttribute('stroke', color);
        });
    }
</script>
<script>
        const suggestions = ["Apple", "Banane", "Cerise", "Datte", "Églantier"];
        function showSuggestions(value) {
            const suggestionBox = document.getElementById("suggestions");
            suggestionBox.innerHTML = "";
            if (!value) {
                suggestionBox.style.display = "none";
                return;
            }
            const filtered = suggestions.filter(s => s.toLowerCase().includes(value.toLowerCase()));
            if (filtered.length) {
                filtered.forEach(item => {
                    const li = document.createElement("li");
                    li.textContent = item;
                    li.setAttribute("role", "option");
                    li.onclick = () => {
                        document.getElementById("search").value = item;
                        suggestionBox.style.display = "none";
                    };
                    suggestionBox.appendChild(li);
                });
                suggestionBox.style.display = "block";
            } else {
                suggestionBox.style.display = "none";
            }
        }
    </script>
    <script>
    let currentStep = 1;
    const totalSteps = 5;
    
    const progressFill = document.getElementById('progress-fill');
    const prevButton = document.getElementById('prev');
    const nextButton = document.getElementById('next');
    
    function updateProgress() {
      // Update progress bar
      let width = ((currentStep - 1) / (totalSteps - 1)) * 100;
      progressFill.style.width = width + '%';
      
      // Update step indicators
      for (let i = 1; i <= totalSteps; i++) {
        const step = document.getElementById('step' + i);
        if (i <= currentStep) {
          step.classList.add('active');
        } else {
          step.classList.remove('active');
        }
      }
      
      // Show/hide content
      for (let i = 1; i <= totalSteps; i++) {
        const content = document.getElementById('content-' + i);
        content.style.display = i === currentStep ? 'block' : 'none';
      }
      
      // Update buttons
      prevButton.disabled = currentStep === 1;
      nextButton.textContent = currentStep === totalSteps ? 'Terminer' : 'Suivant';
    }
    
    prevButton.addEventListener('click', () => {
      if (currentStep > 1) {
        currentStep--;
        updateProgress();
      }
    });
    
    nextButton.addEventListener('click', () => {
      if (currentStep < totalSteps) {
        currentStep++;
        updateProgress();
      } else {
        alert('Paiement terminé avec succès!');
      }
    });
    
    // Initialize
    updateProgress();
  </script>

<?php require_once COMPONENT_PATH . "foot.php"; ?>