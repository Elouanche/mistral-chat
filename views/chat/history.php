<?php
require_once SHARED_PATH . 'userAcces.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php require_once COMPONENT_PATH . 'head.php'; ?>
    <link type="text/css" rel="stylesheet" href="<?php echo STATIC_URL; ?>css/page-chat.css">
    <title>Mistral Chat - Historique des conversations</title>
</head>

<body>
    <?php require_once COMPONENT_PATH . 'header.php'; ?>

    <main class="chat-container history-container">
        <div class="history-header">
            <h1>Historique des conversations</h1>
            <p>Retrouvez toutes vos conversations passées avec Mistral Chat.</p>
        </div>
        
        <div class="history-filters">
            <div class="filter-group">
                <label for="date-filter">Filtrer par date:</label>
                <select id="date-filter" class="filter-select">
                    <option value="all">Toutes les dates</option>
                    <option value="today">Aujourd'hui</option>
                    <option value="week">Cette semaine</option>
                    <option value="month">Ce mois</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="model-filter">Filtrer par modèle:</label>
                <select id="model-filter" class="filter-select">
                    <option value="all">Tous les modèles</option>
                    <!-- Les modèles seront chargés dynamiquement ici -->
                </select>
            </div>
            
            <div class="search-group">
                <input type="text" id="search-input" placeholder="Rechercher dans les conversations..." class="search-input">
                <button id="search-btn" class="button-primary">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
        
        <div class="conversations-grid" id="conversations-grid">
            <!-- Les conversations seront chargées dynamiquement ici -->
            <div class="loading-spinner" id="history-loading">
                <div class="spinner"></div>
            </div>
        </div>
        
        <div class="pagination" id="pagination">
            <!-- La pagination sera chargée dynamiquement ici -->
        </div>
    </main>
    

    
    <script src="<?php echo STATIC_URL; ?>js/page-chat-history.js"></script>
<?php require_once COMPONENT_PATH . 'foot.php'; ?>