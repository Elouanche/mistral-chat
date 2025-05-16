
// Stockage local du panier pour les calculs côté client
let cartState = {
    items: [],
    total_amount: 0,
    item_count: 0
};

// Initialisation du panier côté client
function initializeCartState() {
    // Récupérer tous les articles du panier depuis le DOM
    const cartItems = document.querySelectorAll('.cart-item');
    cartState.items = [];
    cartState.total_amount = 0;
    cartState.item_count = cartItems.length;

    cartItems.forEach(item => {
        const productId = parseInt(item.dataset.productId, 10);
        const priceElement = item.querySelector('.price');
        const quantityElement = item.querySelector('.quantity');
        const totalElement = item.querySelector('.item-total');

        if (priceElement && quantityElement) {
            // Extraire le prix unitaire (format: "Prix unitaire: 12.34€")
            const priceText = priceElement.textContent;
            const price = parseFloat(priceText.replace(/[^0-9.,]/g, '').replace(',', '.'));
            
            // Extraire la quantité
            const quantity = parseInt(quantityElement.textContent, 10);
            
            // Ajouter l'article au panier local
            cartState.items.push({
                product_id: productId,
                price: price,
                quantity: quantity,
                item_total: price * quantity
            });
            
            // Mettre à jour le total du panier
            cartState.total_amount += price * quantity;
        }
    });
}

// Appeler cette fonction au chargement de la page
document.addEventListener('DOMContentLoaded', initializeCartState);

// Fonction pour mettre à jour l'affichage du panier
function updateCartDisplay() {
    // Mettre à jour le total et le nombre d'articles
    updateCartSummary();
    
    // Mettre à jour chaque article
    cartState.items.forEach(item => {
        updateCartItem(item);
    });
}

function updateCartSummary() {
    const totalElement = document.querySelector('.cart-total span');
    const itemCountElement = document.querySelector('.item-count');

    if (totalElement) {
        // Formater le total avec 2 décimales
        totalElement.textContent = `${cartState.total_amount.toFixed(2)}€`;
    }

    if (itemCountElement) {
        const count = cartState.items.length;
        itemCountElement.textContent = `(${count} article${count > 1 ? 's' : ''})`;
    }

    // Afficher ou masquer les boutons si le panier est vide
    const cartActions = document.querySelector('.cart-actions');
    const emptyCart = document.querySelector('.empty-cart');

    if (cartActions && emptyCart) {
        if (cartState.items.length === 0) {
            cartActions.style.display = 'none';
            emptyCart.style.display = 'block';
        } else {
            cartActions.style.display = 'flex';
            emptyCart.style.display = 'none';
        }
    }
}

// Fonction pour mettre à jour un élément du panier spécifique
function updateCartItem(item) {
    const itemElement = document.querySelector(`[data-product-id="${item.product_id}"]`);
    
    if (itemElement) {
        const quantityDisplay = itemElement.querySelector('.quantity');
        const totalDisplay = itemElement.querySelector('.item-total');
        const minusButton = itemElement.querySelector('.quantity-btn.minus');
        
        // Mettre à jour la quantité
        if (quantityDisplay) {
            quantityDisplay.textContent = item.quantity;
        }
        
        // Mettre à jour le total de l'article
        if (totalDisplay) {
            totalDisplay.textContent = `Total: ${item.item_total.toFixed(2)}€`;
        }
        
        // Gérer le bouton -
        if (minusButton) {
            minusButton.disabled = item.quantity <= 1;
        }
    }
}

// Fonction pour trouver un article dans le panier local
function findCartItem(productId) {
    return cartState.items.find(item => item.product_id === productId);
}

// Fonction pour recalculer le total du panier
function recalculateCartTotal() {
    cartState.total_amount = cartState.items.reduce((total, item) => {
        return total + item.item_total;
    }, 0);
}

// Fonction pour augmenter la quantité d'un produit localement
function increaseItemQuantityLocally(productId) {
    const item = findCartItem(productId);
    
    if (item) {
        item.quantity += 1;
        item.item_total = item.price * item.quantity;
        recalculateCartTotal();
        updateCartDisplay();
        return true;
    }
    return false;
}

// Fonction pour diminuer la quantité d'un produit localement
function decreaseItemQuantityLocally(productId) {
    const item = findCartItem(productId);
    
    if (item) {
        if (item.quantity > 1) {
            item.quantity -= 1;
            item.item_total = item.price * item.quantity;
            recalculateCartTotal();
            updateCartDisplay();
            return true;
        } else {
            // Si la quantité est 1, on supprime l'article
            removeItemLocally(productId);
            return true;
        }
    }
    return false;
}

// Fonction pour supprimer un article localement
function removeItemLocally(productId) {
    const index = cartState.items.findIndex(item => item.product_id === productId);
    
    if (index !== -1) {
        // Supprimer l'élément du DOM
        const itemElement = document.querySelector(`[data-product-id="${productId}"]`);
        if (itemElement) {
            itemElement.remove();
        }
        
        // Supprimer l'élément du panier local
        cartState.items.splice(index, 1);
        recalculateCartTotal();
        updateCartSummary();
        return true;
    }
    return false;
}

// Fonction pour augmenter la quantité d'un produit
async function increaseQuantity(productId) {
    // Convertir en entier
    productId = parseInt(productId, 10);
    
    // Mettre à jour localement AVANT d'envoyer la requête au serveur
    const updated = increaseItemQuantityLocally(productId);
    
    if (!updated) {
        showNotification('Produit non trouvé dans le panier', 'error');
        return;
    }
    
    // Notification immédiate
    showNotification('Quantité augmentée');
    
    // Ensuite, informer le serveur de ce changement
    const data = {
        service: "Cart",
        action: "increaseQuantity",
        data: {
            product_id: productId
        }
    };
    
    // Envoyer la requête au serveur (mais ne pas attendre pour la mise à jour de l'UI)
    postData(data)
        .then(response => {
            if (response.status !== 'success') {
                showNotification(response.message || 'Erreur lors de la mise à jour du panier', 'error');
                // Si erreur, réinitialiser l'état du panier en rechargeant la page
                window.location.reload();
            }
        })
        .catch(error => {
            showNotification('Erreur de communication avec le serveur', 'error');
            console.error('Error:', error);
            // Si erreur, réinitialiser l'état du panier en rechargeant la page
            window.location.reload();
        });
}

// Fonction pour diminuer la quantité d'un produit
async function decreaseQuantity(productId) {
    // Convertir en entier
    productId = parseInt(productId, 10);
    
    // Mettre à jour localement AVANT d'envoyer la requête au serveur
    const updated = decreaseItemQuantityLocally(productId);
    
    if (!updated) {
        showNotification('Produit non trouvé dans le panier', 'error');
        return;
    }
    
    // Notification immédiate
    showNotification('Quantité diminuée');
    
    // Ensuite, informer le serveur de ce changement
    const data = {
        service: "Cart",
        action: "decreaseQuantity",
        data: {
            product_id: productId
        }
    };
    
    // Envoyer la requête au serveur (mais ne pas attendre pour la mise à jour de l'UI)
    postData(data)
        .then(response => {
            if (response.status !== 'success') {
                showNotification(response.message || 'Erreur lors de la mise à jour du panier', 'error');
                // Si erreur, réinitialiser l'état du panier en rechargeant la page
                window.location.reload();
            }
        })
        .catch(error => {
            showNotification('Erreur de communication avec le serveur', 'error');
            console.error('Error:', error);
            // Si erreur, réinitialiser l'état du panier en rechargeant la page
            window.location.reload();
        });
}

// Fonction pour supprimer un produit du panier
async function removeFromCart(productId) {
    // Convertir en entier
    productId = parseInt(productId, 10);
    
    // Mettre à jour localement AVANT d'envoyer la requête au serveur
    const removed = removeItemLocally(productId);
    
    if (!removed) {
        showNotification('Produit non trouvé dans le panier', 'error');
        return;
    }
    
    // Notification immédiate
    showNotification('Article supprimé du panier');
    
    // Ensuite, informer le serveur de ce changement
    const data = {
        service: "Cart",
        action: "removeItem",
        data: {
            product_id: productId
        }
    };
    
    // Envoyer la requête au serveur (mais ne pas attendre pour la mise à jour de l'UI)
    postData(data)
        .then(response => {
            if (response.status !== 'success') {
                showNotification(response.message || 'Erreur lors de la suppression de l\'article', 'error');
                // Si erreur, réinitialiser l'état du panier en rechargeant la page
                window.location.reload();
            }
        })
        .catch(error => {
            showNotification('Erreur de communication avec le serveur', 'error');
            console.error('Error:', error);
            // Si erreur, réinitialiser l'état du panier en rechargeant la page
            window.location.reload();
        });
}

// Fonction pour vider tout le panier
async function clearCart() {
    if (!confirm('Êtes-vous sûr de vouloir vider votre panier ?')) {
        return;
    }
    
    const data = {
        service: "Cart",
        action: "clearCart",
    };
    
    postData(data)
        .then(response => {
            if (response.status === 'success') {
                // Vider le panier local
                cartState.items = [];
                cartState.total_amount = 0;
                cartState.item_count = 0;
                
                // Mettre à jour l'interface
                const cartItemsContainer = document.getElementById('cart-items');
                if (cartItemsContainer) {
                    // Supprimer tous les articles
                    const articleElements = cartItemsContainer.querySelectorAll('.cart-item');
                    articleElements.forEach(el => el.remove());
                    
                    // Afficher le message de panier vide
                    const emptyCartHTML = `
                        <div class="empty-cart">
                            <p class="empty-cart-message" role="status">Votre panier est vide.</p>
                            <a href="/products" class="btn btn-primary">Continuer vos achats</a>
                        </div>
                    `;
                    
                    // Insérer le message si nécessaire
                    if (!cartItemsContainer.querySelector('.empty-cart')) {
                        cartItemsContainer.insertAdjacentHTML('beforeend', emptyCartHTML);
                    } else {
                        const emptyCart = cartItemsContainer.querySelector('.empty-cart');
                        emptyCart.style.display = 'block';
                    }
                }
                
                // Masquer les actions du panier
                const cartActions = document.querySelector('.cart-actions');
                if (cartActions) {
                    cartActions.style.display = 'none';
                }
                
                showNotification('Panier vidé avec succès');
            } else {
                showNotification(response.message || 'Erreur lors du vidage du panier', 'error');
            }
        })
        .catch(error => {
            showNotification('Erreur de communication avec le serveur', 'error');
            console.error('Error:', error);
        });
}

/**
 * Fonction pour ajouter un produit au panier
 * @param {number} productId - L'ID du produit à ajouter au panier
 */
async function addToCart(productId) {
    try {
        // Validation de l'ID du produit
        if (!productId || isNaN(parseInt(productId))) {
            console.error("Erreur de validation:", { productId });
            throw new Error("ID de produit invalide");
        }

        // Préparation des données pour l'API
        const context = {
            service: "Cart",
            action: "increaseQuantity",
            data: {
                product_id: parseInt(productId, 10)
                // L'ID utilisateur sera récupéré côté serveur depuis la session
            }
        };

        // Log de la requête
        console.log("Envoi de la requête:", context);
        
        // Envoi de la requête à l'API
        const response = await postData(context);
        console.log("Réponse reçue:", response);

        if (response.status === "success") {
            // Mise à jour visuelle du panier
            const cartCountElement = document.getElementById('cart-count');
            if (cartCountElement) {
                const currentCount = parseInt(cartCountElement.textContent) || 0;
                cartCountElement.textContent = currentCount + 1;
            }
            
            // Message de succès
            alert("Produit ajouté au panier avec succès !");
        } else {
            console.error("Erreur de réponse:", response);
            throw new Error(response.message || "Erreur lors de l'ajout du produit au panier");
        }
    } catch (error) {
        // Log détaillé de l'erreur
        console.error("Erreur détaillée:", {
            message: error.message,
            stack: error.stack,
            data: error.response ? error.response.data : null
        });
        alert(error.message || "Une erreur est survenue lors de l'ajout au panier");
    }
}