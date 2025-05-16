// Variables globales qui seront définies dans la page checkout.php
let cartData = [];
let checkoutUserId = null;

document.addEventListener('DOMContentLoaded', function() {
    // Récupérer les données du panier depuis la variable globale définie dans la page PHP
    cartData = window.cartData || [];
    checkoutUserId = window.checkoutUserId || null;
    
    // Préparer les données du panier pour le formulaire
    const cartDataInput = document.getElementById('cart-data');
    if (cartDataInput) {
        cartDataInput.value = JSON.stringify(cartData);
    }

    // Ajouter l'écouteur d'événement au formulaire de paiement
    const checkoutForm = document.getElementById('checkoutForm');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            e.preventDefault();
            // Récupérer l'ID utilisateur depuis la variable globale définie dans la page
            createOrder(checkoutUserId);
        });
    }
});

/**
 * Crée une commande avec les informations du formulaire
 * @param {number|null} userId - L'ID de l'utilisateur connecté ou null
 */
async function createOrder(userId) {
    // Construire l'objet de données avec les informations d'expédition
    const data = {
        shipping_info: {
            street: document.getElementById('shipping_street').value,
            city: document.getElementById('shipping_city').value,
            state: document.getElementById('shipping_state').value,
            postal_code: document.getElementById('shipping_postal_code').value,
            country: document.getElementById('shipping_country').value
        }
    };

    // Récupérer et traiter les articles du panier
    const cartItemElements = document.querySelectorAll('.cart-item');
    const items = [];
    let totalAmount = 0;

    cartItemElements.forEach(item => {
        const productId = parseInt(item.getAttribute('data-product-id'), 10);
        const priceElement = item.querySelector('.price');
        const quantityElement = item.querySelector('.quantity-info');

        if (!priceElement || !quantityElement) return;

        const priceText = priceElement.textContent;
        const price = parseFloat(priceText.replace('Prix :', '').replace('€', '').trim());

        const quantityText = quantityElement.textContent;
        const quantity = parseInt(quantityText.replace('Quantité :', '').trim(), 10);
        
        if (!isNaN(price) && !isNaN(quantity)) {
            const itemTotal = price * quantity;
            totalAmount += itemTotal;

            items.push({
                product_id: productId,
                quantity: quantity,
                price: price
            });
        }
    });

    // Ajouter les articles et le montant total
    data.items = items;
    data.total_amount = totalAmount;

    // Ajouter les informations utilisateur
    const userEmailElement = document.getElementById('user_email');
    const phoneElement = document.getElementById('phone');
    
    if (userEmailElement && phoneElement) {
        data.email = userEmailElement.value;
        data.phone = phoneElement.value;
    }

    if (userId) {
        data.user_id = userId;
    }

    // Préparer le contexte pour l'appel API
    const context = {
        service: "Order",
        action: "createOrder",
        data: data
    };

    try {
        console.log('Données à envoyer:', context);
        
        const result = await postData(context);
        console.log('Réponse API:', result);
        
        if (result.status === 'error') {
            showNotification(result.message || 'Erreur lors de la création de la commande', 'error');
            return;
        }
        
        
    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Une erreur est survenue lors de la création de la commande', 'error');
    }
}