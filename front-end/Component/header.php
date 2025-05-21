
<body>
    <header id="header" class="small small-image">
        <nav aria-label="Navigation principale">
            <div role="banner">
                <a href="/" id="partiel" aria-label="Accueil Mistral Chat">
                    <h1>Mistral Chat</h1>
                    <div class="logo">
                        <img src="<?php echo STATIC_URL; ?>asset/logo.svg" alt="Logo de Mistral Chat">
                    </div>
                </a>
            </div>
        </nav>
    </header>

    <!-- Mini Header juste en dessous -->
    <nav aria-label="Navigation secondaire" class="mini-header">
        <ul>
        <?php if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== 'admin'): ?>
                <li><a href="/chat" aria-label="Accéder au chat"><i class="fas fa-comment-dots"></i> Chat</a></li>
                <li><a href="/chat/history" aria-label="Historique des conversations"><i class="fas fa-history"></i> Historique</a></li>
                <li><a href="/subscription" aria-label="Gérer votre abonnement"><i class="fas fa-star"></i> Abonnement</a></li>
            <?php endif; ?>
            <li><a href="/user/account" aria-label="Accéder au compte"><i class="fas fa-user"></i> Compte</a></li>
            <li><a href="/about" aria-label="À propos de nous"><i class="fas fa-info-circle"></i> À propos</a></li>
            
            <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === 'admin'): ?>
                <li><a href="/admin/dashboard" aria-label="Accéder au tableau de bord administrateur"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="/admin/support" aria-label="Accéder au support client"><i class="fas fa-headset"></i> Support</a></li>
                <li><a href="/admin/stats" aria-label="Accéder aux statistiques"><i class="fas fa-chart-bar"></i> Statistiques</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    


