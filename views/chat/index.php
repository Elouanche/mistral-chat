<?php
require_once SHARED_PATH . 'userAcces.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php require_once COMPONENT_PATH . 'head.php'; ?>
    <link type="text/css" rel="stylesheet" href="<?php echo STATIC_URL; ?>css/page-chat.css">
    <title>Mistral Chat - Conversations</title>
</head>

<body>
    <?php require_once COMPONENT_PATH . 'header.php'; ?>

    <main class="chat-container">
        <button id="toggle-sidebar" class="button-icon" title="Afficher/Masquer les conversations">
            <i class="fas fa-bars"></i>
        </button>
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Conversations</h2>
                <button id="new-chat-btn" class="button-primary">
                    <i class="fas fa-plus"></i> Nouvelle conversation
                </button>
            </div>
            
            <div class="conversation-filter">
                <label class="switch">
                    <input type="checkbox" id="show-archived">
                    <span class="slider round"></span>
                </label>
                <span>Afficher les archives</span>
            </div>
            
            <div class="conversation-list" id="conversation-list">
                <!-- Les conversations seront chargées dynamiquement ici -->
                <div class="loading-spinner" id="conversations-loading">
                    <div class="spinner"></div>
                </div>
            </div>
        </div>
        
        <div class="chat-content">
            <div class="welcome-screen" id="welcome-screen">
                <div class="welcome-content">
                    <h1>Bienvenue sur Mistral Chat</h1>
                    <p>Sélectionnez une conversation existante ou créez-en une nouvelle pour commencer à discuter avec l'IA.</p>
                    
                    <div class="model-showcase">
                        <h3>Modèles disponibles</h3>
                        <div class="models-grid" id="models-grid">
                            <!-- Les modèles seront chargés dynamiquement ici -->
                            <div class="loading-spinner" id="models-loading">
                                <div class="spinner"></div>
                            </div>
                        </div>
                    </div>
                    
                    <button id="welcome-new-chat-btn" class="button-primary">
                        <i class="fas fa-plus"></i> Nouvelle conversation
                    </button>
                </div>
            </div>
            
            <div class="chat-interface" id="chat-interface" style="display: none;">
                <div class="chat-header">
                    <h2 id="conversation-title">Chargement...</h2>
                    <div class="chat-actions">
                        <button id="edit-title-btn" class="button-icon" title="Modifier le titre">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button id="archive-btn" class="button-icon" title="Archiver la conversation">
                            <i class="fas fa-archive"></i>
                        </button>
                        <button id="delete-btn" class="button-icon" title="Supprimer la conversation">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                
                <div class="messages-container" id="messages-container">
                    <!-- Les messages seront chargés dynamiquement ici -->
                </div>
                
                <div class="chat-input-container">
                    <form id="chat-form">
                        <textarea id="chat-input" placeholder="Écrivez votre message ici..." rows="1"></textarea>
                        <button type="submit" id="send-btn" class="button-primary">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Modal pour créer une nouvelle conversation -->
    <div class="modal" id="new-chat-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Nouvelle conversation</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="new-chat-form">
                    <div class="form-group">
                        <label for="chat-title">Titre</label>
                        <input type="text" id="chat-title" name="title" placeholder="Nouvelle conversation" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="chat-model">Modèle</label>
                        <select id="chat-model" name="model_id" required>
                            <!-- Les modèles seront chargés dynamiquement ici -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="system-prompt">Prompt système (optionnel)</label>
                        <textarea id="system-prompt" name="system_prompt" placeholder="Instructions spécifiques pour l'IA..."></textarea>
                        <small>Définit le comportement général de l'IA pour cette conversation.</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="button-secondary close-modal">Annuler</button>
                        <button type="submit" class="button-primary">Créer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal pour éditer le titre de la conversation -->
    <div class="modal" id="edit-title-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Modifier le titre</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="edit-title-form">
                    <div class="form-group">
                        <label for="edit-title-input">Nouveau titre</label>
                        <input type="text" id="edit-title-input" name="title" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="button-secondary close-modal">Annuler</button>
                        <button type="submit" class="button-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de confirmation de suppression -->
    <div class="modal" id="delete-confirm-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmer la suppression</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette conversation ? Cette action est irréversible.</p>
                
                <div class="form-actions">
                    <button type="button" class="button-secondary close-modal">Annuler</button>
                    <button type="button" id="confirm-delete-btn" class="button-danger">Supprimer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Définir l'ID utilisateur pour JavaScript -->
    <script>
        const userId = <?php echo $userId; ?>;
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/marked/4.0.2/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.5.1/highlight.min.js"></script>
    <script src="<?php echo STATIC_URL; ?>js/page-chat.js"></script>
    
    </body>

    <script src="<?= htmlspecialchars(STATIC_URL); ?>js/scripts-notification.js" defer></script>
    <script src="<?= htmlspecialchars(STATIC_URL); ?>js/postData.js" defer></script>
    <script src="<?= htmlspecialchars(STATIC_URL); ?>js/prepaForms.js" defer></script>
    
</html>