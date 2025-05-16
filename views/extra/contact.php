<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once SHARED_PATH . "session.php";
require_once COMPONENT_PATH . "head.php";
?>
<main class="contact-page"  role="main" style="min-height: 100vh; padding: 2rem;">
    <section class="contact-form" style="max-width: 1000px; margin: auto;">
        <h2>Formulaire de contact</h2>
        <form id="contactForm" novalidate style="display: flex; gap: 2rem; flex-wrap: wrap;flex-direction: row;">
            
            <!-- Partie gauche -->
            <div style="flex: 1; min-width: 300px;">
                <div class="form-group">
                    <label for="name">Nom :</label>
                    <input type="text" id="name" name="name" required placeholder="Votre nom complet">
                </div>

                <div class="form-group">
                    <label for="email">Email :</label>
                    <input type="email" id="email" name="email" required placeholder="exemple@domaine.com">
                </div>

                <div class="form-group">
                    <label for="order_id">ID de commande (facultatif) :</label>
                    <input type="text" id="order_id" name="order_id" placeholder="Ex : CMD123456">
                </div>
            </div>

            <!-- Partie droite -->
            <div style="flex: 1; min-width: 300px;">
                <div class="form-group">
                    <label for="message">Message :</label>
                    <textarea id="message" name="message" rows="9" required placeholder="Votre message ici..." style="width: 100%;"></textarea>
                </div>
            </div>

            <!-- Champ anti-bot -->
            <div style="display:none;">
                <input type="text" id="website" name="website">
            </div>

            <!-- Bouton submit -->
            <div style="width: 100%;">
                <button type="submit" class="button">Envoyer</button>
            </div>
        </form>
    </section>
</main>
<script src="<?= htmlspecialchars(STATIC_URL); ?>js/page-contact.js" defer></script>
<?php require_once COMPONENT_PATH . "foot.php"; ?>
