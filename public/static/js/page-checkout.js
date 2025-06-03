// Variables globales qui seront définies dans la page checkout.php
let checkoutType = null;
let userId = null;
let planId = null;
let checkoutData = null;

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les variables depuis la page PHP
    userId = window.userId || null;
    checkoutType = window.checkoutType || 'cart';
    planId = window.planId || null;
    checkoutData = window.checkoutData || null;

    // Vérifier si les données nécessaires sont présentes
    if (checkoutType === 'subscription') {
        if (!planId || !checkoutData || !checkoutData.name) {
            window.location.href = '/subscription?error=invalid_plan';
            return;
        }
    }

    // Ajouter l'écouteur d'événement au bouton de paiement
    const checkoutButton = document.getElementById('proceed-to-payment');
    if (checkoutButton) {
        checkoutButton.addEventListener('click', handlePayment);
    }
});

/**
 * Gère le processus de paiement
 */
async function handlePayment() {
    setLoading(true);

    try {
        // Préparer les données pour l'API en fonction du type de checkout
        const apiData = {
            checkout_type: checkoutType,
            user_id: userId
        };

        // Ajouter les données spécifiques au type de checkout
        if (checkoutType === 'subscription') {
            if (!planId || !checkoutData) {
                window.location.href = '/subscription?error=invalid_plan';
                return;
            }
            apiData.plan_id = planId;
            apiData.price_data = {
                name: checkoutData.name,
                unit_amount: checkoutData.price * 100, // Conversion en centimes pour Stripe
                currency: 'eur'
            };
            if (checkoutData.description) {
                apiData.price_data.description = checkoutData.description;
            }
        } else if (checkoutType === 'cart') {
            if (!checkoutData || !checkoutData.items || checkoutData.items.length === 0) {
                window.location.href = '/cart?error=empty_cart';
                return;
            }
            apiData.items = checkoutData.items.map(item => ({
                name: item.name,
                quantity: item.quantity,
                unit_amount: Math.round(item.price * 100), // Conversion en centimes pour Stripe
                currency: 'eur',
                description: item.description || undefined
            }));
            apiData.total_amount = Math.round(checkoutData.total_amount * 100); // Conversion en centimes
        }

        // Créer la session de checkout via l'API
        const response = await postData({
            service: 'Payment',
            action: 'createCheckoutSession',
            data: apiData
        });

        if (response.status === 'success' && response.data?.url) {
            window.location.href = response.data.url;
        } else {
            if (response.error === 'invalid_plan' || response.code === 'INVALID_PLAN') {
                window.location.href = '/subscription?error=invalid_plan';
                return;
            }
            showMessage(response.message || 'Erreur lors de la création de la session de paiement');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showMessage('Une erreur est survenue lors de la création de la session de paiement');
    } finally {
        setLoading(false);
    }
}

function showMessage(messageText) {
    const messageContainer = document.querySelector("#payment-message");
    if (messageContainer) {
        messageContainer.classList.remove("hidden");
        messageContainer.textContent = messageText;
        setTimeout(function () {
            messageContainer.classList.add("hidden");
            messageContainer.textContent = "";
        }, 4000);
    } else {
        alert(messageText);
    }
}

function setLoading(isLoading) {
    const button = document.querySelector("#proceed-to-payment");
    if (button) {
        button.disabled = isLoading;
        button.innerHTML = isLoading ? 
            '<div class="spinner"></div><span>Chargement...</span>' : 
            'Procéder au paiement';
    }
}