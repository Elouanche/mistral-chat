<?php require_once SHARED_PATH . "session.php"; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php require_once COMPONENT_PATH . 'head.php'; ?>
    <link type="text/css" rel="stylesheet" href="<?php echo STATIC_URL; ?>css/home.css">
    <title>Mistral Chat - IA Conversationnelle</title>
</head>


<body>
    <?php require_once COMPONENT_PATH . 'header.php'; ?>

    <main class="home-container">
        <section class="hero-section">
            <div class="hero-content">
                <h1>Discutez avec Mistral Chat</h1>
                <p>Une IA conversationnelle puissante basée sur les modèles de Mistral AI</p>
                <div class="hero-buttons">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="/chat" class="button-primary"><i class="fas fa-comment"></i> Commencer à discuter</a>
                    <?php else: ?>
                        <a href="/user/login" class="button-primary"><i class="fas fa-sign-in-alt"></i> Se connecter</a>
                        <a href="/user/register" class="button-secondary"><i class="fas fa-user-plus"></i> S'inscrire</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-image">
                <img src="<?php echo STATIC_URL; ?>asset/chat-illustration.svg" alt="Illustration de conversation avec Mistral Chat">
            </div>
        </section>


        <section class="features-section">
            <h2>Fonctionnalités principales</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3>IA avancée</h3>
                    <p>Profitez des modèles de langage les plus performants de Mistral AI pour des conversations naturelles et intelligentes.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <h3>Historique complet</h3>
                    <p>Retrouvez facilement toutes vos conversations passées et reprenez-les à tout moment.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h3>Sécurité garantie</h3>
                    <p>Vos données sont chiffrées et protégées. Nous respectons votre vie privée.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <h3>Réponses rapides</h3>
                    <p>Obtenez des réponses instantanées à toutes vos questions grâce à notre infrastructure optimisée.</p>
                </div>
            </div>
        </section>
        
        <section class="models-section">
            <h2>Modèles disponibles</h2>
            <div class="models-grid" id="home-models-grid">
                <!-- Les modèles seront chargés dynamiquement ici -->
                <div class="loading-spinner" id="home-models-loading">
                    <div class="spinner"></div>
                </div>
            </div>
        </section>
        
        <section class="subscription-section">
            <div class="subscription-content">
                <h2>Abonnez-vous pour plus de possibilités</h2>
                <p>Débloquez des fonctionnalités avancées et des modèles plus performants avec nos plans d'abonnement.</p>
                <a href="/subscription" class="button-primary"><i class="fas fa-star"></i> Voir les abonnements</a>
            </div>
        </section>
    </main>
    
    <?php require_once COMPONENT_PATH . 'footer.php'; ?>
    
    <script src="<?php echo STATIC_URL; ?>js/home.js"></script>
</body>
</html>

<?php require_once COMPONENT_PATH . "foot.php"; ?>
