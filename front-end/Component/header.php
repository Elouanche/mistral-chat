
<body>
    <header id="header" class="small small-image">
        <nav aria-label="Navigation principale">
            <div role="banner">
                <a href="/" id="partiel" aria-label="Accueil Lorempsum">
                    <h1>Mistral-GPT</h1>
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
            
    
        </ul>
    </nav>
    


