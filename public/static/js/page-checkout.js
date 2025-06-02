// Variables globales qui seront définies dans la page checkout.php
let stripe = null;
let elements = null;

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser Stripe avec la clé publique
    stripe = Stripe(stripePublicKey);
      initialize();
    checkStatus();
    
    // Ajouter l'écouteur d'événements pour le formulaire
    document
        .querySelector("#payment-form")
        .addEventListener("submit", handleSubmit);
});

async function initialize() {
    // Utiliser postData au lieu de fetch
    const response = await postData({
        service: 'Payment',
        action: 'createPaymentIntent',
        data: {
            checkout_type: checkoutType,
            type: checkoutType,
            plan_id: planId || null,
            amount: total,
            order_id: orderId,
            user_id: userId
        }
    });

    if (response && response.clientSecret) {
        elements = stripe.elements({ clientSecret: response.clientSecret });
        const paymentElement = elements.create("payment");
        paymentElement.mount("#payment-element");
    } else {
        showMessage("Erreur lors de l'initialisation du paiement");
    }
}

async function handleSubmit(e) {
    e.preventDefault();
    setLoading(true);

    const { error } = await stripe.confirmPayment({
        elements,
        confirmParams: {
            return_url: `${window.location.origin}/success`,
            payment_method_data: {
                billing_details: {
                    email: userEmail || ''
                }
            }
        },
    });    if (error) {
        if (error.type === "card_error" || error.type === "validation_error") {
            showMessage(error.message);
        } else {
            showMessage("Une erreur inattendue s'est produite.");
        }
    }
    
    setLoading(false);
}

async function handleSuccessfulPayment(paymentIntent) {
    const urlParams = new URLSearchParams(window.location.search);
    const checkoutType = urlParams.get('type');
    const metadata = paymentIntent.metadata || {};

    if (metadata.type === 'subscription' || checkoutType === 'subscription') {
        window.location.href = '/subscription-success';
    } else {
        window.location.href = '/order-success';
    }
}

async function checkStatus() {
    const clientSecret = new URLSearchParams(window.location.search).get(
        "payment_intent_client_secret"
    );
    
    if (!clientSecret) {
        return;
    }
    
    const { paymentIntent } = await stripe.retrievePaymentIntent(clientSecret);
    
    switch (paymentIntent.status) {
        case "succeeded":
            showMessage("Le paiement a réussi!");
            await handleSuccessfulPayment(paymentIntent);
            break;
        case "processing":
            showMessage("Votre paiement est en cours de traitement.");
            break;
        case "requires_payment_method":
            showMessage("Votre paiement n'a pas réussi, veuillez réessayer.");
            break;
        default:
            showMessage("Une erreur s'est produite.");
            break;
    }
}

function showMessage(messageText) {
    const messageContainer = document.querySelector("#payment-message");
    messageContainer.classList.remove("hidden");
    messageContainer.textContent = messageText;
    setTimeout(function () {
        messageContainer.classList.add("hidden");
        messageText.textContent = "";
    }, 4000);
}

function setLoading(isLoading) {
    if (isLoading) {
        document.querySelector("#submit").disabled = true;
        document.querySelector("#spinner").classList.remove("hidden");
        document.querySelector("#button-text").classList.add("hidden");
    } else {
        document.querySelector("#submit").disabled = false;
        document.querySelector("#spinner").classList.add("hidden");
        document.querySelector("#button-text").classList.remove("hidden");
    }
}
        
        if (result.status === 'error') {
            showNotification(result.message || 'Erreur lors de la création de la commande', 'error');
            return;
        }
        
        
    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Une erreur est survenue lors de la création de la commande', 'error');
    }
}