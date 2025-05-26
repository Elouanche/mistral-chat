document.addEventListener('DOMContentLoaded', function() {
    // Éléments DOM
    const conversationList = document.getElementById('conversation-list');
    const welcomeScreen = document.getElementById('welcome-screen');
    const chatInterface = document.getElementById('chat-interface');
    const messagesContainer = document.getElementById('messages-container');
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const conversationTitle = document.getElementById('conversation-title');
    const newChatBtn = document.getElementById('new-chat-btn');
    const welcomeNewChatBtn = document.getElementById('welcome-new-chat-btn');
    const showArchivedCheckbox = document.getElementById('show-archived');
    const modelsGrid = document.getElementById('models-grid');
    const newChatModal = document.getElementById('new-chat-modal');
    const newChatForm = document.getElementById('new-chat-form');
    const chatModelSelect = document.getElementById('chat-model');
    const editTitleBtn = document.getElementById('edit-title-btn');
    const editTitleModal = document.getElementById('edit-title-modal');
    const editTitleForm = document.getElementById('edit-title-form');
    const editTitleInput = document.getElementById('edit-title-input');
    const archiveBtn = document.getElementById('archive-btn');
    const deleteBtn = document.getElementById('delete-btn');
    const deleteConfirmModal = document.getElementById('delete-confirm-modal');
    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
    const conversationsLoading = document.getElementById('conversations-loading');
    const modelsLoading = document.getElementById('models-loading');
    
    // Variables globales
    let currentConversationId = null;
    let currentModelId = null;
    let conversations = [];
    let models = [];
    
    // Initialisation
    init();
    
    // Fonctions
    function init() {
        // Charger les modèles disponibles
        loadModels();
        
        // Charger les conversations
        loadConversations();
        
        // Configurer les écouteurs d'événements
        setupEventListeners();
        
        // Ajuster la hauteur du textarea
        adjustTextareaHeight();
    }
    
    function setupEventListeners() {
        // Ouvrir le modal de nouvelle conversation
        newChatBtn.addEventListener('click', openNewChatModal);
        welcomeNewChatBtn.addEventListener('click', openNewChatModal);
        
        // Fermer les modals
        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', closeAllModals);
        });
        
        // Soumettre le formulaire de nouvelle conversation
        newChatForm.addEventListener('submit', handleNewChatSubmit);
        
        // Soumettre le formulaire de chat
        chatForm.addEventListener('submit', handleChatSubmit);
        
        // Ajuster la hauteur du textarea lors de la saisie
        chatInput.addEventListener('input', adjustTextareaHeight);
        
        // Afficher/masquer les conversations archivées
        showArchivedCheckbox.addEventListener('change', loadConversations);
        
        // Éditer le titre de la conversation
        editTitleBtn.addEventListener('click', openEditTitleModal);
        editTitleForm.addEventListener('submit', handleEditTitleSubmit);
        
        // Archiver la conversation
        archiveBtn.addEventListener('click', handleArchiveConversation);
        
        // Supprimer la conversation
        deleteBtn.addEventListener('click', openDeleteConfirmModal);
        confirmDeleteBtn.addEventListener('click', handleDeleteConversation);
        
        // Fermer les modals en cliquant à l'extérieur
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                closeAllModals();
            }
        });
    }
    
    function loadModels() {
        const context = {
            service: 'MistralApi',
            action: 'getModels',
            data: {}
        };

        postData(context)
            .then(data => {
                if (data.status === 'success') {
                    models = data.data || [];
                    renderModels();
                    populateModelSelect();
                } else {
                    console.error('Erreur:', data.message);
                    showNotification('Erreur lors du chargement des modèles', 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur lors du chargement des modèles', 'error');
            });
    }
        
    function renderModels() {
        if (!modelsGrid) return;
        
        modelsGrid.innerHTML = '';
        
        if (models.length === 0) {
            modelsGrid.innerHTML = '<p>Aucun modèle disponible pour le moment.</p>';
            return;
        }
        
        models.forEach(model => {
            const modelCard = document.createElement('div');
            modelCard.className = 'model-card';
            modelCard.innerHTML = `
                <h4>${model.display_name}</h4>
                <p>${model.description || 'Aucune description disponible'}</p>
            `;
            modelsGrid.appendChild(modelCard);
        });
    }
    
    function populateModelSelect() {
        if (!chatModelSelect) return;
        
        chatModelSelect.innerHTML = '';
        
        if (models.length === 0) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Aucun modèle disponible';
            option.disabled = true;
            option.selected = true;
            chatModelSelect.appendChild(option);
            return;
        }
        
        models.forEach(model => {
            const option = document.createElement('option');
            option.value = model.id;
            option.textContent = model.display_name;
            chatModelSelect.appendChild(option);
        });
    }
    
    function loadConversations() {
        conversationsLoading.style.display = 'flex';
        
        const includeArchived = showArchivedCheckbox.checked;
        
        fetch(`/api/ai/conversations?include_archived=${includeArchived}`)
            .then(response => response.json())
            .then(data => {
                conversations = data.data || [];
                renderConversations();
                conversationsLoading.style.display = 'none';
            })
            .catch(error => {
                console.error('Erreur lors du chargement des conversations:', error);
                conversationsLoading.style.display = 'none';
            });
    }
    
    function renderConversations() {
        if (!conversationList) return;
        
        // Sauvegarder l'ID de la conversation actuelle
        const previousConversationId = currentConversationId;
        
        conversationList.innerHTML = '';
        
        if (conversations.length === 0) {
            const emptyState = document.createElement('div');
            emptyState.className = 'empty-state';
            emptyState.textContent = 'Aucune conversation trouvée. Créez-en une nouvelle pour commencer.';
            conversationList.appendChild(emptyState);
            return;
        }
        
        conversations.forEach(conversation => {
            const conversationItem = document.createElement('div');
            conversationItem.className = 'conversation-item';
            if (conversation.is_archived) {
                conversationItem.classList.add('archived');
            }
            if (conversation.id === currentConversationId) {
                conversationItem.classList.add('active');
            }
            
            const lastMessage = conversation.last_message ? conversation.last_message.content : 'Aucun message';
            const lastMessagePreview = lastMessage.length > 50 ? lastMessage.substring(0, 50) + '...' : lastMessage;
            
            const date = new Date(conversation.updated_at);
            const formattedDate = date.toLocaleDateString('fr-FR', { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric' 
            });
            
            conversationItem.innerHTML = `
                <h3>${conversation.title}</h3>
                <p>${lastMessagePreview}</p>
                <div class="meta">
                    <span>${formattedDate}</span>
                    <span>${conversation.message_count || 0} messages</span>
                </div>
            `;
            
            conversationItem.addEventListener('click', () => {
                loadConversation(conversation.id);
            });
            
            conversationList.appendChild(conversationItem);
        });
        
        // Si une conversation était sélectionnée et qu'elle existe toujours, la recharger
        if (previousConversationId && conversations.some(c => c.id === previousConversationId)) {
            loadConversation(previousConversationId);
        }
    }
    
    function loadConversation(conversationId) {
        currentConversationId = conversationId;
        
        // Mettre à jour l'UI pour montrer la conversation active
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.classList.remove('active');
        });
        
        const activeItem = Array.from(document.querySelectorAll('.conversation-item')).find(item => {
            return item.querySelector('h3').textContent === 
                conversations.find(c => c.id === conversationId)?.title;
        });
        
        if (activeItem) {
            activeItem.classList.add('active');
        }
        
        // Afficher l'interface de chat et masquer l'écran d'accueil
        welcomeScreen.style.display = 'none';
        chatInterface.style.display = 'flex';
        
        // Afficher un indicateur de chargement dans le conteneur de messages
        messagesContainer.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner"></div>
            </div>
        `;
        
        // Charger les détails de la conversation et ses messages
        fetch(`/api/ai/conversation?conversation_id=${conversationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const conversation = data.data;
                    currentModelId = conversation.model_id;
                    
                    // Mettre à jour le titre
                    conversationTitle.textContent = conversation.title;
                    
                    // Mettre à jour le bouton d'archivage
                    updateArchiveButton(conversation.is_archived);
                    
                    // Afficher les messages
                    renderMessages(conversation.messages || []);
                } else {
                    throw new Error(data.message || 'Erreur lors du chargement de la conversation');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                messagesContainer.innerHTML = `
                    <div class="error-message">
                        <p>Erreur lors du chargement de la conversation. Veuillez réessayer.</p>
                    </div>
                `;
            });
    }
    
    function renderMessages(messages) {
        messagesContainer.innerHTML = '';
        
        if (messages.length === 0) {
            const emptyState = document.createElement('div');
            emptyState.className = 'empty-state';
            emptyState.textContent = 'Aucun message dans cette conversation. Envoyez un message pour commencer.';
            messagesContainer.appendChild(emptyState);
            return;
        }
        
        messages.forEach(message => {
            const messageElement = document.createElement('div');
            messageElement.className = `message ${message.role}`;
            
            // Formater le contenu avec Markdown si c'est un message de l'assistant
            let formattedContent = message.content;
            if (message.role === 'assistant') {
                formattedContent = marked.parse(message.content);
            }
            
            const date = new Date(message.created_at);
            const formattedTime = date.toLocaleTimeString('fr-FR', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            messageElement.innerHTML = `
                <div class="content">${formattedContent}</div>
                <div class="timestamp">${formattedTime}</div>
            `;
            
            messagesContainer.appendChild(messageElement);
        });
        
        // Faire défiler jusqu'au dernier message
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        // Appliquer la coloration syntaxique aux blocs de code
        document.querySelectorAll('pre code').forEach((block) => {
            hljs.highlightElement(block);
        });
    }
    
    function handleChatSubmit(event) {
        event.preventDefault();
        
        const message = chatInput.value.trim();
        if (!message || !currentConversationId) return;
        
        // Désactiver le formulaire pendant l'envoi
        chatInput.disabled = true;
        document.getElementById('send-btn').disabled = true;
        
        showUserMessage(message);
        showLoadingMessage();

        const context = {
            service: 'MistralApi',
            action: 'sendChatRequest',
            data: {
                conversation_id: currentConversationId,
                message: message,
                model_id: currentModelId
            }
        };

        postData(context)
            .then(data => {
                removeLoadingMessage();
                if (data.status === 'success') {
                    showAssistantMessage(data.data.response);
                    loadConversations(); // Mettre à jour la liste des conversations
                } else {
                    showErrorMessage(data.message || 'Une erreur est survenue lors de l\'envoi du message.');
                }
            })
            .catch(error => {
                removeLoadingMessage();
                console.error('Erreur:', error);
                showErrorMessage('Une erreur réseau est survenue. Veuillez réessayer.');
            })
            .finally(() => {
                // Réactiver le formulaire
                chatInput.disabled = false;
                document.getElementById('send-btn').disabled = false;
                chatInput.value = '';
                adjustTextareaHeight();
                chatInput.focus();
            });
    }
    
    function openNewChatModal() {
        newChatModal.classList.add('active');
        document.getElementById('chat-title').value = 'Nouvelle conversation';
        document.getElementById('system-prompt').value = '';
        
        // Sélectionner le premier modèle par défaut
        if (models.length > 0 && chatModelSelect.options.length > 0) {
            chatModelSelect.value = chatModelSelect.options[0].value;
        }
    }
    
    function handleNewChatSubmit(event) {
        event.preventDefault();
        
        const title = document.getElementById('chat-title').value.trim();
        const modelId = chatModelSelect.value;
        const systemPrompt = document.getElementById('system-prompt').value.trim();
        
        if (!title || !modelId) {
            showNotification('Veuillez remplir tous les champs obligatoires.', 'error');
            return;
        }
        
        const submitBtn = newChatForm.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Création en cours...';

        const context = {
            service: 'AiConversation',
            action: 'createConversation',
            data: {
                title: title,
                model_id: modelId,
                system_prompt: systemPrompt
            }
        };
        
        postData(context)
            .then(data => {
                if (data.status === 'success') {
                    closeAllModals();
                    loadConversations();
                    loadConversation(data.data.conversation_id);
                    showNotification('Conversation créée avec succès', 'success');
                } else {
                    showNotification(data.message || 'Erreur lors de la création de la conversation', 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Une erreur est survenue lors de la création de la conversation', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Créer';
            });
    }
    
    function openEditTitleModal() {
        if (!currentConversationId) return;
        
        const conversation = conversations.find(c => c.id === currentConversationId);
        if (!conversation) return;
        
        editTitleInput.value = conversation.title;
        editTitleModal.classList.add('active');
        editTitleInput.focus();
        editTitleInput.select();
    }
    
    function handleEditTitleSubmit(event) {
        event.preventDefault();
        
        if (!currentConversationId) return;
        
        const newTitle = editTitleInput.value.trim();
        if (!newTitle) {
            alert('Le titre ne peut pas être vide.');
            return;
        }
        
        // Désactiver le formulaire pendant l'envoi
        const submitBtn = editTitleForm.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        
        fetch('/api/ai/conversation/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                conversation_id: currentConversationId,
                title: newTitle
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Mettre à jour le titre dans l'interface
                conversationTitle.textContent = newTitle;
                
                // Mettre à jour la liste des conversations
                loadConversations();
                
                // Fermer le modal
                closeAllModals();
            } else {
                alert(data.message || 'Erreur lors de la mise à jour du titre');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la mise à jour du titre');
        })
        .finally(() => {
            // Réactiver le formulaire
            submitBtn.disabled = false;
        });
    }
    
    function handleArchiveConversation() {
        if (!currentConversationId) return;
        
        const conversation = conversations.find(c => c.id === currentConversationId);
        if (!conversation) return;
        
        const isArchived = conversation.is_archived;
        const action = isArchived ? 'unarchive' : 'archive';
        const confirmMessage = isArchived 
            ? 'Voulez-vous vraiment désarchiver cette conversation ?' 
            : 'Voulez-vous vraiment archiver cette conversation ?';
        
        if (!confirm(confirmMessage)) return;
        
        fetch(`/api/ai/conversation/${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                conversation_id: currentConversationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Mettre à jour le bouton d'archivage
                updateArchiveButton(!isArchived);
                
                // Recharger les conversations
                loadConversations();
            } else {
                alert(data.message || `Erreur lors de l'${action} de la conversation`);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert(`Une erreur est survenue lors de l'${action} de la conversation`);
        });
    }
    
    function updateArchiveButton(isArchived) {
        if (isArchived) {
            archiveBtn.innerHTML = '<i class="fas fa-box-open"></i>';
            archiveBtn.title = 'Désarchiver la conversation';
        } else {
            archiveBtn.innerHTML = '<i class="fas fa-archive"></i>';
            archiveBtn.title = 'Archiver la conversation';
        }
    }
    
    function openDeleteConfirmModal() {
        if (!currentConversationId) return;
        deleteConfirmModal.classList.add('active');
    }
    
    function handleDeleteConversation() {
        if (!currentConversationId) return;
        
        fetch('/api/ai/conversation/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                conversation_id: currentConversationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Fermer le modal
                closeAllModals();
                
                // Recharger les conversations
                currentConversationId = null;
                loadConversations();
                
                // Afficher l'écran d'accueil
                welcomeScreen.style.display = 'flex';
                chatInterface.style.display = 'none';
            } else {
                alert(data.message || 'Erreur lors de la suppression de la conversation');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la suppression de la conversation');
        });
    }
    
    function closeAllModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.classList.remove('active');
        });
    }
    
    function adjustTextareaHeight() {
        chatInput.style.height = 'auto';
        chatInput.style.height = (chatInput.scrollHeight) + 'px';
    }
});