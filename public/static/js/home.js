document.addEventListener('DOMContentLoaded', function() {
    // Éléments DOM
    const modelsGrid = document.getElementById('home-models-grid');
    const modelsLoading = document.getElementById('home-models-loading');
    
    // Initialisation
    loadModels();
    
    // Fonction pour charger les modèles disponibles
    async function loadModels() {
        if (modelsLoading) {
            modelsLoading.style.display = 'flex';
        }
        
        const context = {
            service: 'AI',
            action: 'getModels'
        };

        try {
            const data = await postData(context);
            
            if (modelsLoading) {
                modelsLoading.style.display = 'none';
            }
            
            if (data.status === 'success' && modelsGrid) {
                data.data.forEach(model => {
                    modelsGrid.appendChild(createModelCard(model));
                });
            } else {
                console.error('Erreur lors du chargement des modèles:', data.message);
            }
        } catch (error) {
            if (modelsLoading) {
                modelsLoading.style.display = 'none';
            }
            console.error('Erreur lors du chargement des modèles:', error);
        }
    }
    
    // Fonction pour créer une carte de modèle
    function createModelCard(model) {
        const card = document.createElement('div');
        card.className = 'model-card';
        
        // Déterminer la classe de badge en fonction du niveau du modèle
        let badgeClass = 'badge-free';
        let badgeText = 'Gratuit';
        
        if (model.premium_level === 2) {
            badgeClass = 'badge-premium';
            badgeText = 'Premium';
        } else if (model.premium_level === 1) {
            badgeClass = 'badge-standard';
            badgeText = 'Standard';
        }
        
        card.innerHTML = `
            <div class="model-header">
                <h3>${model.display_name}</h3>
                <span class="model-badge ${badgeClass}">${badgeText}</span>
            </div>
            <div class="model-body">
                <p>${model.description || 'Aucune description disponible'}</p>
            </div>
            <div class="model-footer">
                <a href="/chat?model=${model.id}" class="button-secondary">Essayer</a>
            </div>
        `;
        
        return card;
    }
});