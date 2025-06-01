document.addEventListener('DOMContentLoaded', function() {
    // Éléments DOM
    const plansGrid = document.getElementById('plans-grid');
    const plansLoading = document.getElementById('plans-loading');
    const currentSubscriptionLoading = document.getElementById('current-subscription-loading');
    const currentSubscriptionContent = document.getElementById('current-subscription-content');
    const subscribeModal = document.getElementById('subscribe-modal');
    const planNameSpan = document.getElementById('plan-name');
    const planDetailsDiv = document.getElementById('plan-details');
    const cancelConfirmModal = document.getElementById('cancel-confirm-modal');
    const confirmCancelBtn = document.getElementById('confirm-cancel-btn');
    
    // Variables globales
    let plans = [];
    let currentSubscription = null;
    let selectedPlanId = null;
    
    // Initialisation
    init();
    
    // Fonctions
    function init() {
        // Charger les plans disponibles
        loadPlans();
        
        // Charger l'abonnement actuel si l'utilisateur est connecté
        if (currentSubscriptionContent) {
            loadCurrentSubscription();
        }
        
        // Configurer les écouteurs d'événements
        setupEventListeners();
    }
    
    function setupEventListeners() {
        // Fermer les modals
        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', closeAllModals);
        });
        
        // Bouton pour procéder au checkout
        const proceedCheckoutBtn = document.getElementById('proceed-checkout');
        if (proceedCheckoutBtn) {
            proceedCheckoutBtn.addEventListener('click', handleProceedToCheckout);
        }
        
        // Confirmer l'annulation de l'abonnement
        if (confirmCancelBtn) {
            confirmCancelBtn.addEventListener('click', handleCancelSubscription);
        }
        
        // Fermer les modals en cliquant à l'extérieur
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                closeAllModals();
            }
        });
    }

    // Fonction pour rediriger vers la page de checkout
    async function handleProceedToCheckout() {
        if (!selectedPlanId) return;
        
        try {
            // Rediriger vers la page de checkout avec l'ID du plan
            window.location.href = `/checkout?plan_id=${selectedPlanId}&type=subscription`;
        } catch (error) {
            console.error('Erreur lors de la redirection vers le checkout:', error);
            alert('Une erreur est survenue. Veuillez réessayer plus tard.');
        }
    }
    
    function closeAllModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.classList.remove('active');
        });
    }
    
    function renderPlans() {
        if (!plansGrid) return;
        
        plansGrid.innerHTML = '';
        
        if (plans.length === 0) {
            plansGrid.innerHTML = '<p>Aucun plan disponible pour le moment.</p>';
            return;
        }
        
        // Trouver le plan populaire (celui qui n'est pas gratuit avec le prix le plus bas)
        let popularPlan = null;
        let nonFreePlans = plans.filter(plan => parseFloat(plan.price) > 0);
        if (nonFreePlans.length > 0) {
            popularPlan = nonFreePlans.reduce((prev, current) => 
                parseFloat(prev.price) < parseFloat(current.price) ? prev : current
            );
        }
        
        plans.forEach(plan => {
            const planCard = document.createElement('div');
            planCard.className = 'plan-card';
            if (popularPlan && plan.id === popularPlan.id) {
                planCard.classList.add('popular');
                planCard.innerHTML = '<div class="popular-badge">Populaire</div>';
            }
            
            // Extraire les fonctionnalités du plan
            let features = [];
            try {
                const featuresObj = typeof plan.features === 'string' 
                    ? JSON.parse(plan.features) 
                    : plan.features || {};
                
                // Ajouter les modèles disponibles
                if (featuresObj && featuresObj.models && featuresObj.models.length > 0) {
                    features.push(`Accès aux modèles: ${featuresObj.models.join(', ')}`);
                }
                
                // Ajouter le support prioritaire
                if (featuresObj && featuresObj.priority_support) {
                    features.push('Support prioritaire');
                }
            } catch (e) {
                console.error('Erreur lors du parsing des fonctionnalités:', e);
            }
            
            // Ajouter la limite de tokens
            if (plan.token_limit) {
                features.push(`${plan.token_limit.toLocaleString()} tokens par mois`);
            } else {
                // Utiliser les valeurs de max_conversations et max_messages_per_day comme alternative
                features.push(`${plan.max_conversations} conversations`);
                features.push(`${plan.max_messages_per_day} messages par jour`);
            }
            
            // Générer la liste des fonctionnalités
            const featuresList = features.map(feature => 
                `<li class="feature-item"><i class="fas fa-check"></i> ${feature}</li>`
            ).join('');
            
            planCard.innerHTML += `
                <div class="plan-header">
                    <h3 class="plan-name">${plan.name}</h3>
                    <p class="plan-price">
                        <span class="currency">€</span>${parseFloat(plan.price).toFixed(2)}
                        <span class="period">/mois</span>
                    </p>
                </div>
                <div class="plan-features">
                    <ul class="feature-list">
                        ${featuresList}
                    </ul>
                </div>
                <div class="plan-footer">
                    <button class="button-primary subscribe-btn" data-plan-id="${plan.id}" data-plan-name="${plan.name}">
                        ${parseFloat(plan.price) > 0 ? 'S\'abonner' : 'Commencer gratuitement'}
                    </button>
                </div>
            `;
            
            plansGrid.appendChild(planCard);
        });
        
        // Ajouter les écouteurs d'événements pour les boutons d'abonnement
        document.querySelectorAll('.subscribe-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const planId = this.getAttribute('data-plan-id');
                const planName = this.getAttribute('data-plan-name');
                openSubscribeModal(planId, planName);
            });
        });
    }
    
    
    function renderCurrentSubscription() {
        if (!currentSubscriptionContent || !currentSubscription) return;
        
        const plan = currentSubscription.plan || {};
        const usage = currentSubscription.usage || {};
        
        // Calculer le pourcentage d'utilisation
        const usagePercent = usage.tokens_used && usage.tokens_limit 
            ? Math.min(100, Math.round((usage.tokens_used / usage.tokens_limit) * 100))
            : 0;

        // Formater les dates
        const startDate = new Date(currentSubscription.started_at);
        const formattedStartDate = startDate.toLocaleDateString('fr-FR');
        
        // Gérer la date d'expiration
        let formattedEndDate;
        if (!currentSubscription.expires_at || currentSubscription.expires_at === '0000-00-00 00:00:00') {
            formattedEndDate = 'Illimité';
        } else {
            const endDate = new Date(currentSubscription.expires_at);
            formattedEndDate = endDate.toLocaleDateString('fr-FR');
        }
        
        currentSubscriptionContent.innerHTML = `
            <div class="subscription-info">
                <div class="subscription-details">
                    <h3>Votre abonnement actuel</h3>
                    <div class="subscription-meta">
                        <div class="subscription-meta-item">
                            <span class="meta-label">Plan</span>
                            <span class="meta-value">${plan.name || 'Non spécifié'}</span>
                        </div>
                        <div class="subscription-meta-item">
                            <span class="meta-label">Statut</span>
                            <span class="meta-value">${currentSubscription.status === 'active' ? 'Actif' : 'Inactif'}</span>
                        </div>
                        <div class="subscription-meta-item">
                            <span class="meta-label">Période</span>
                            <span class="meta-value">${formattedStartDate} - ${formattedEndDate}</span>
                        </div>
                    </div>
                    <div class="subscription-actions">
                        <button id="change-plan-btn" class="button-secondary">Changer de plan</button>
                        <button id="cancel-subscription-btn" class="button-danger">Annuler l'abonnement</button>
                    </div>
                </div>
                <div class="usage-meter">
                    <h4>Utilisation des tokens ce mois-ci</h4>
                    <div class="meter-container">
                        <div class="meter-fill" style="width: ${usagePercent}%"></div>
                    </div>
                    <div class="meter-labels">
                        <span>${usage.tokens_used ? usage.tokens_used.toLocaleString() : 0} utilisés</span>
                        <span>${usage.tokens_remaining ? usage.tokens_remaining.toLocaleString() : 0} restants</span>
                    </div>
                </div>
            </div>
        `;
        
        // Ajouter les écouteurs d'événements
        document.getElementById('change-plan-btn').addEventListener('click', function() {
            // Faire défiler jusqu'à la section des plans
            document.querySelector('.plans-comparison').scrollIntoView({ behavior: 'smooth' });
        });
        
        document.getElementById('cancel-subscription-btn').addEventListener('click', function() {
            openCancelConfirmModal();
        });
    }
    
    function openSubscribeModal(planId, planName) {
        if (!subscribeModal) return;
        
        // Stocker l'ID du plan sélectionné
        selectedPlanId = planId;
        
        // Mettre à jour les informations du plan
        planNameSpan.textContent = planName;
        
        // Trouver le plan sélectionné
        const selectedPlan = plans.find(p => p.id == planId);
        if (!selectedPlan) return;
        
        // Préparer l'affichage de la limite de tokens
        const tokenLimit = selectedPlan.token_limit 
            ? `${selectedPlan.token_limit.toLocaleString()} tokens par mois`
            : `${selectedPlan.max_messages_per_day || 0} messages par jour`;
        
        // Afficher les détails du plan
        planDetailsDiv.innerHTML = `
            <h4>Détails du plan</h4>
            <div class="plan-summary">
                <span class="plan-name">${selectedPlan.name}</span>
                <span class="plan-price">${parseFloat(selectedPlan.price).toFixed(2)} €/mois</span>
            </div>
            <p class="plan-description">${selectedPlan.description || 'Aucune description disponible'}</p>
            <p>Limite d'utilisation: ${tokenLimit}</p>
        `;
        
        // Afficher le modal
        subscribeModal.classList.add('active');
    }
    
    function openCancelConfirmModal() {
        if (!cancelConfirmModal || !currentSubscription) return;
        cancelConfirmModal.classList.add('active');
    }
    
    function renderCurrentSubscription() {
        if (!currentSubscriptionContent || !currentSubscription) return;
        
        const plan = currentSubscription.plan || {};
        const usage = currentSubscription.usage || {};
        
        // Calculer le pourcentage d'utilisation
        const usagePercent = usage.tokens_used && usage.tokens_limit 
            ? Math.min(100, Math.round((usage.tokens_used / usage.tokens_limit) * 100))
            : 0;

        // Formater les dates
        const startDate = new Date(currentSubscription.started_at);
        const formattedStartDate = startDate.toLocaleDateString('fr-FR');
        
        // Gérer la date d'expiration
        let formattedEndDate;
        if (!currentSubscription.expires_at || currentSubscription.expires_at === '0000-00-00 00:00:00') {
            formattedEndDate = 'Illimité';
        } else {
            const endDate = new Date(currentSubscription.expires_at);
            formattedEndDate = endDate.toLocaleDateString('fr-FR');
        }
        
        currentSubscriptionContent.innerHTML = `
            <div class="subscription-info">
                <div class="subscription-details">
                    <h3>Votre abonnement actuel</h3>
                    <div class="subscription-meta">
                        <div class="subscription-meta-item">
                            <span class="meta-label">Plan</span>
                            <span class="meta-value">${plan.name || 'Non spécifié'}</span>
                        </div>
                        <div class="subscription-meta-item">
                            <span class="meta-label">Statut</span>
                            <span class="meta-value">${currentSubscription.status === 'active' ? 'Actif' : 'Inactif'}</span>
                        </div>
                        <div class="subscription-meta-item">
                            <span class="meta-label">Période</span>
                            <span class="meta-value">${formattedStartDate} - ${formattedEndDate}</span>
                        </div>
                    </div>
                    <div class="subscription-actions">
                        <button id="change-plan-btn" class="button-secondary">Changer de plan</button>
                        <button id="cancel-subscription-btn" class="button-danger">Annuler l'abonnement</button>
                    </div>
                </div>
                <div class="usage-meter">
                    <h4>Utilisation des tokens ce mois-ci</h4>
                    <div class="meter-container">
                        <div class="meter-fill" style="width: ${usagePercent}%"></div>
                    </div>
                    <div class="meter-labels">
                        <span>${usage.tokens_used ? usage.tokens_used.toLocaleString() : 0} utilisés</span>
                        <span>${usage.tokens_remaining ? usage.tokens_remaining.toLocaleString() : 0} restants</span>
                    </div>
                </div>
            </div>
        `;
        
        // Ajouter les écouteurs d'événements
        document.getElementById('change-plan-btn').addEventListener('click', function() {
            // Faire défiler jusqu'à la section des plans
            document.querySelector('.plans-comparison').scrollIntoView({ behavior: 'smooth' });
        });
        
        document.getElementById('cancel-subscription-btn').addEventListener('click', function() {
            openCancelConfirmModal();
        });
    }
    
    function handleCancelSubscription() {
        const context = {
            service: 'Subscription',
            action: 'cancelSubscription', // Changé de 'cancel' à 'cancelSubscription'
            data: {
                subscription_id: currentSubscription.id
            }
        };

        postData(context)
            .then(data => {
                if (data.status === 'success') {
                    closeAllModals();
                    loadCurrentSubscription();
                    alert('Abonnement annulé avec succès');
                } else {
                    alert(data.message || 'Erreur lors de l\'annulation de l\'abonnement');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de l\'annulation de l\'abonnement');
            });
    }    function loadCurrentSubscription() {
        if (!currentSubscriptionContent) return;
        if (!currentUserId) {
            currentSubscriptionContent.innerHTML = `
                <div class="no-subscription">
                    <p>Vous devez être connecté pour voir votre abonnement. <a href="/user/login.php">Se connecter</a></p>
                </div>
            `;
            currentSubscriptionLoading.style.display = 'none';
            currentSubscriptionContent.style.display = 'block';
            return;
        }

        currentSubscriptionLoading.style.display = 'flex';
        currentSubscriptionContent.style.display = 'none';

        const context = {
            service: 'Subscription',
            action: 'getUserSubscription',  // Changé de 'getCurrent' à 'getUserSubscription'
            data: {
                user_id: currentUserId
            }
        };

        postData(context)
            .then(data => {                if (data.status === 'success') {
                    if (data.data) {
                        currentSubscription = data.data;
                        renderCurrentSubscription();
                    } else {
                        currentSubscriptionContent.innerHTML = `
                            <div class="no-subscription">
                                <p>Vous n'avez pas d'abonnement actif. Choisissez un plan ci-dessous pour commencer.</p>
                            </div>
                        `;
                    }
                } else {
                    console.error('Erreur:', data);
                    currentSubscriptionContent.innerHTML = '<p class="error-message">Erreur lors du chargement de votre abonnement. Veuillez réessayer.</p>';
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                currentSubscriptionContent.innerHTML = '<p class="error-message">Erreur lors du chargement de votre abonnement. Veuillez réessayer.</p>';
            })
            .finally(() => {
                currentSubscriptionLoading.style.display = 'none';
                currentSubscriptionContent.style.display = 'block';
            });
    }

    function loadPlans() {
        if (!plansGrid) return;
        
        plansLoading.style.display = 'flex';

        const context = {
            service: 'Subscription',
            action: 'getAvailablePlans'
        };
        
        postData(context)
            .then(data => {
                if (data.status === 'success') {
                    plans = data.data || [];
                    renderPlans();
                } else {
                    plansGrid.innerHTML = '<p class="error-message">Erreur lors du chargement des plans. Veuillez réessayer.</p>';
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                plansGrid.innerHTML = '<p class="error-message">Erreur lors du chargement des plans. Veuillez réessayer.</p>';
            })
            .finally(() => {
                plansLoading.style.display = 'none';
            });
    }
});
