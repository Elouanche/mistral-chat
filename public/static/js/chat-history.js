document.addEventListener('DOMContentLoaded', function() {
    // Éléments DOM
    const conversationsGrid = document.getElementById('conversations-grid');
    const historyLoading = document.getElementById('history-loading');
    const dateFilter = document.getElementById('date-filter');
    const modelFilter = document.getElementById('model-filter');
    const searchInput = document.getElementById('search-input');
    const searchBtn = document.getElementById('search-btn');
    const pagination = document.getElementById('pagination');
    
    // Variables globales
    let currentPage = 1;
    let totalPages = 1;
    let currentFilters = {
        date: 'all',
        model: 'all',
        search: ''
    };
    
    // Initialisation
    init();
    
    // Fonctions
    function init() {
        // Charger les modèles disponibles pour le filtre
        loadModels();
        
        // Charger les conversations
        loadConversations();
        
        // Configurer les écouteurs d'événements
        setupEventListeners();
    }
    
    function setupEventListeners() {
        // Filtres
        dateFilter.addEventListener('change', function() {
            currentFilters.date = this.value;
            currentPage = 1;
            loadConversations();
        });
        
        modelFilter.addEventListener('change', function() {
            currentFilters.model = this.value;
            currentPage = 1;
            loadConversations();
        });
        
        // Recherche
        searchBtn.addEventListener('click', function() {
            currentFilters.search = searchInput.value.trim();
            currentPage = 1;
            loadConversations();
        });
        
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                currentFilters.search = searchInput.value.trim();
                currentPage = 1;
                loadConversations();
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
    
    
    function loadConversations() {
        historyLoading.style.display = 'flex';
        conversationsGrid.innerHTML = '';

        const context = {
            service: 'AiConversation',
            action: 'getUserConversations',
            data: {
                page: currentPage,
                date_filter: currentFilters.date !== 'all' ? currentFilters.date : null,
                model_id: currentFilters.model !== 'all' ? currentFilters.model : null,
                search: currentFilters.search || null,
                include_archived: true
            }
        };

        postData(context)
            .then(data => {
                historyLoading.style.display = 'none';
                if (data.status === 'success') {
                    totalPages = data.meta?.total_pages || 1;
                    updatePagination();
                    
                    if (data.data && data.data.length > 0) {
                        data.data.forEach(conversation => {
                            conversationsGrid.appendChild(createConversationCard(conversation));
                        });
                    } else {
                        showEmptyState();
                    }
                } else {
                    showNotification(data.message || 'Erreur lors du chargement des conversations', 'error');
                }
            })
            .catch(error => {
                historyLoading.style.display = 'none';
                console.error('Erreur:', error);
                showNotification('Erreur lors du chargement des conversations', 'error');
            });
    }
        
    function createConversationCard(conversation) {
        const card = document.createElement('div');
        card.className = 'conversation-card';
        card.dataset.id = conversation.id;
        
        // Formater la date
        const date = new Date(conversation.created_at);
        const formattedDate = date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        card.innerHTML = `
            <div class="card-header">
                <h3>${conversation.title}</h3>
                <span class="model-badge">${conversation.model_name}</span>
            </div>
            <div class="card-body">
                <p>${conversation.last_message || 'Aucun message'}</p>
            </div>
            <div class="card-footer">
                <span class="date">${formattedDate}</span>
                <a href="/chat?id=${conversation.id}" class="button-secondary">Ouvrir</a>
            </div>
        `;
        
        return card;
    }
    
    function updatePagination() {
        pagination.innerHTML = '';
        
        if (totalPages <= 1) {
            return;
        }
        
        // Bouton précédent
        const prevBtn = document.createElement('button');
        prevBtn.className = 'pagination-btn';
        prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prevBtn.disabled = currentPage === 1;
        prevBtn.addEventListener('click', function() {
            if (currentPage > 1) {
                currentPage--;
                loadConversations();
            }
        });
        pagination.appendChild(prevBtn);
        
        // Pages
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        
        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = 'pagination-btn' + (i === currentPage ? ' active' : '');
            pageBtn.textContent = i;
            pageBtn.addEventListener('click', function() {
                currentPage = i;
                loadConversations();
            });
            pagination.appendChild(pageBtn);
        }
        
        // Bouton suivant
        const nextBtn = document.createElement('button');
        nextBtn.className = 'pagination-btn';
        nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.addEventListener('click', function() {
            if (currentPage < totalPages) {
                currentPage++;
                loadConversations();
            }
        });
        pagination.appendChild(nextBtn);
    }
    
    function showNotification(message, type = 'info') {
        // Vérifier si la fonction est disponible globalement
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, type);
        } else {
            // Fallback simple
            alert(message);
        }
    }
});