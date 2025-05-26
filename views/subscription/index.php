<!DOCTYPE html>
<html lang="fr">
<head>
    <?php require_once COMPONENT_PATH . 'head.php'; ?>
    <link type="text/css" rel="stylesheet" href="<?php echo STATIC_URL; ?>css/subscription.css">
    <title>Mistral Chat - Abonnements</title>
</head>

<body>
    <?php require_once COMPONENT_PATH . 'header.php'; ?>

    <main class="subscription-container">
        <section class="subscription-header">
            <h1>Abonnements Mistral Chat</h1>
            <p>Choisissez le plan qui correspond à vos besoins et accédez à des fonctionnalités avancées.</p>
        </section>
        
        <?php if (isset($_SESSION['user_id'])): ?>
        <section class="current-subscription">
            <div class="loading-spinner" id="current-subscription-loading">
                <div class="spinner"></div>
            </div>
            <div id="current-subscription-content" style="display: none;">
                <!-- Le contenu sera chargé dynamiquement ici -->
            </div>
        </section>
        <?php endif; ?>
        
        <section class="plans-comparison">
            <h2>Nos plans</h2>
            <div class="plans-grid" id="plans-grid">
                <div class="loading-spinner" id="plans-loading">
                    <div class="spinner"></div>
                </div>
                <!-- Les plans seront chargés dynamiquement ici -->
            </div>
        </section>
        
        <section class="subscription-faq">
            <h2>Questions fréquentes</h2>
            <div class="faq-list">
                <div class="faq-item">
                    <h3>Comment fonctionne la facturation ?</h3>
                    <p>La facturation est mensuelle et commence le jour de votre abonnement. Vous pouvez annuler à tout moment.</p>
                </div>
                <div class="faq-item">
                    <h3>Puis-je changer de plan ?</h3>
                    <p>Oui, vous pouvez changer de plan à tout moment. Le nouveau plan prendra effet immédiatement.</p>
                </div>
                <div class="faq-item">
                    <h3>Qu'est-ce qu'un token ?</h3>
                    <p>Les tokens sont l'unité de mesure utilisée pour calculer l'utilisation de l'API. Un token représente environ 4 caractères de texte.</p>
                </div>
                <div class="faq-item">
                    <h3>Que se passe-t-il si j'atteins ma limite de tokens ?</h3>
                    <p>Si vous atteignez votre limite mensuelle de tokens, vous ne pourrez plus utiliser l'API jusqu'au renouvellement de votre abonnement ou jusqu'à ce que vous passiez à un plan supérieur.</p>
                </div>
                <div class="faq-item">
                    <h3>Comment puis-je annuler mon abonnement ?</h3>
                    <p>Vous pouvez annuler votre abonnement à tout moment depuis votre compte. L'annulation prendra effet à la fin de la période de facturation en cours.</p>
                </div>
            </div>
        </section>
    </main>
    
    <!-- Modal pour s'abonner à un plan -->
    <div class="modal" id="subscribe-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>S'abonner au plan <span id="plan-name"></span></h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="subscribe-form">
                    <input type="hidden" id="plan-id" name="plan_id">
                    
                    <div class="plan-details" id="plan-details">
                        <!-- Les détails du plan seront chargés dynamiquement ici -->
                    </div>
                    
                    <div class="payment-details">
                        <h4>Informations de paiement</h4>
                        <div id="payment-element">
                            <!-- L'élément de paiement Stripe sera injecté ici -->
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="button-secondary close-modal">Annuler</button>
                        <button type="submit" class="button-primary">Confirmer l'abonnement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de confirmation d'annulation -->
    <div class="modal" id="cancel-confirm-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmer l'annulation</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir annuler votre abonnement ? Vous pourrez continuer à utiliser votre abonnement jusqu'à la fin de la période en cours.</p>
                
                <div class="form-actions">
                    <button type="button" class="button-secondary close-modal">Non, garder mon abonnement</button>
                    <button type="button" id="confirm-cancel-btn" class="button-danger">Oui, annuler mon abonnement</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://js.stripe.com/v3/"></script>
    <script src="<?php echo STATIC_URL; ?>js/subscription.js"></script>
    
   
    
    <?php require_once COMPONENT_PATH . 'footer.php'; ?>
</body>
</html>