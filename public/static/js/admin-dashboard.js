const formConfigurations = {
    createUser: {
        service: 'AdminUser',
        action: 'createUser',
        fields: [
            { id: 'createUsername', key: 'username' },
            { id: 'createEmail', key: 'email' },
            { id: 'createUserPassword', key: 'password' }
        ],
        successMessage: 'User created'
    },
    deleteUser: {
        service: 'AdminUser',
        action: 'deleteUser',
        fields: [{ id: 'deleteUserId', key: 'user_id' }],
        successMessage: 'User deleted'
    },
    updateUser: {
        service: 'AdminUser',
        action: 'updateUser',
        fields: [
            { id: 'updateUserId', key: 'user_id' },
            { id: 'updateUsername', key: 'username' }
        ],
        successMessage: 'User updated'
    },
    createProduct: {
        service: 'AdminProduct',
        action: 'createProduct',
        fields: [
            { id: 'createProductName', key: 'name' },
            { id: 'createProductPrice', key: 'price' },
            { id: 'createProductDescription', key: 'description' }
        ],
        successMessage: 'Product created'
    },
    deleteProduct: {
        service: 'AdminProduct',
        action: 'deleteProduct',
        fields: [{ id: 'deleteProductId', key: 'product_id' }],
        successMessage: 'Product deleted'
    },
    updateProduct: {
        service: 'AdminProduct',
        action: 'updateProduct',
        fields: [
            { id: 'updateProductId', key: 'product_id' },
            { id: 'updateProductName', key: 'name' }
        ],
        successMessage: 'Product updated'
    },
    createOrder: {
        service: 'AdminOrder',
        action: 'createOrder',
        fields: [
            { id: 'orderUserId', key: 'user_id' },
            { id: 'orderProductId', key: 'product_id' }
        ],
        successMessage: 'Order created'
    },
    updateOrderStatus: {
        service: 'AdminOrder',
        action: 'updateOrderStatus',
        fields: [
            { id: 'updateOrderId', key: 'order_id' },
            { id: 'updateOrderStatus', key: 'status' }
        ],
        successMessage: 'Order status updated'
    },
    generateInvoice: {
        service: 'AdminOrder',
        action: 'generateInvoice',
        fields: [
            { id: 'invoiceOrderId', key: 'order_id' }
            // Supprimer le champ date car il n'est pas utilisé dans le backend
        ],
        successMessage: 'Invoice created successfully'
    }
};
const dashboardConfig = {
    "ordersTable": {
        service: "AdminOrder",
        action: "listOrders",
        
        filters: { status: "Pending" }
    },
    "possibleStatuses": ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled', 'Completed'],
    "statusUpdateForm": {
        service: "AdminOrder",
        action: "updateOrderStatus",
        fields: [
            { id: "orderId", key: "order_id" },
            { id: "newStatus", key: "status" }
        ]
    },
    "validateDelivery": {
        service: "Delivery",
        action: "createShipment",
        fields: [
            { id: "orderId", key: "order_id" }
        ]
    }
        
};
const context = {
    service: "AdminOrder",
    action: "listOrders",
    filters: {
        status: "Pending"
    }
};

// Fonctions utilitaires
function showLoader() {
    let loader = document.getElementById('admin-loader');
    if (!loader) {
        loader = document.createElement('div');
        loader.id = 'admin-loader';
        loader.innerHTML = '<div class="loader-spinner"></div>';
        document.body.appendChild(loader);
        
        loader.style.position = 'fixed';
        loader.style.top = '0';
        loader.style.left = '0';
        loader.style.width = '100%';
        loader.style.height = '100%';
        loader.style.backgroundColor = 'rgba(0,0,0,0.5)';
        loader.style.display = 'flex';
        loader.style.justifyContent = 'center';
        loader.style.alignItems = 'center';
        loader.style.zIndex = '1001';
        
        const style = document.createElement('style');
        style.textContent = `
            .loader-spinner {
                border: 5px solid #f3f3f3;
                border-top: 5px solid #3498db;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                animation: spin 2s linear infinite;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    }
    loader.style.display = 'flex';
}

function hideLoader() {
    const loader = document.getElementById('admin-loader');
    if (loader) {
        loader.style.display = 'none';
    }
}

function showNotification(message, type = 'info') {
    let notification = document.getElementById('admin-notification');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'admin-notification';
        document.body.appendChild(notification);
        
        notification.style.position = 'fixed';
        notification.style.bottom = '20px';
        notification.style.right = '20px';
        notification.style.padding = '15px 20px';
        notification.style.borderRadius = '5px';
        notification.style.color = 'white';
        notification.style.fontWeight = 'bold';
        notification.style.zIndex = '1000';
        notification.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';
    }
    
    switch (type) {
        case 'success':
            notification.style.backgroundColor = '#4CAF50';
            break;
        case 'error':
            notification.style.backgroundColor = '#F44336';
            break;
        case 'warning':
            notification.style.backgroundColor = '#FF9800';
            break;
        default:
            notification.style.backgroundColor = '#2196F3';
    }
    
    notification.textContent = message;
    notification.style.display = 'block';
    
    setTimeout(() => {
        notification.style.display = 'none';
    }, 3000);
}

// Charger les commandes
async function loadOrders(page = 1, customFilters = null) {
    const tbody = document.getElementById('ordersTableBody');
    if (!tbody) return;

    try {
        showLoader();
        const filters = customFilters || {};
        
        if (dashboardConfig.ordersTable.filters && !customFilters) {
            Object.assign(filters, dashboardConfig.ordersTable.filters);
        }
        
        const context = {
            service: 'AdminOrder',
            action: 'listOrders',
            data: {
                page: page,
                limit: 10,
                filters: filters
            }
        };
        
        const response = await postData(context);

        if (!response || response.status !== 'success' || !Array.isArray(response.data?.orders)) {
            throw new Error('Format de réponse invalide');
        }

        tbody.innerHTML = '';
        
        response.data.orders.forEach(order => {
            // Récupérer le premier item pour les informations du produit
            const firstItem = order.items && order.items[0] ? order.items[0] : null;
            
            const row = document.createElement('tr');
            row.setAttribute('data-order-id', order.id);
            row.setAttribute('data-status', order.status);

            const paymentStatus = order.payment_status || (order.status === 'Paid' ? 'Payé' : 'En attente');
            const shippingStatus = order.shipping_status || 
                (order.status === 'Shipped' ? 'Expédié' : 
                 order.status === 'Processing' ? 'En préparation' : 'Non expédié');
                 
            const orderDate = new Date(order.created_at).toLocaleDateString('fr-FR');
            const total = parseFloat(order.total_amount).toFixed(2);

            row.innerHTML = `
                <td><input type="checkbox" class="order-checkbox" data-order-id="${order.id}" data-status="${order.status}"></td>
                <td>#${order.id}</td>
                <td>
                    <div>Client #${order.user_id}</div>
                    <small class="text-muted">${order.shipping_street}, ${order.shipping_city}</small>
                </td>
                <td>${orderDate}</td>
                <td>${total} €</td>
                <td>
                    <select class="form-select form-select-sm status-select" data-original-status="${order.status}">
                        ${dashboardConfig.possibleStatuses.map(s =>
                            `<option value="${s}" ${s === order.status ? 'selected' : ''}>${s}</option>`
                        ).join('')}
                    </select>
                </td>
                <td><span class="badge ${getPaymentStatusBadgeClass(paymentStatus)}">${paymentStatus}</span></td>
                <td><span class="badge ${getShippingStatusBadgeClass(shippingStatus)}">${shippingStatus}</span></td>
                <td class="action-buttons d-flex gap-2">
                    ${getActionButtons(order)}
                </td>
            `;
            tbody.appendChild(row);
        });

        // Mettre à jour la pagination avec les données reçues
        if (response.data.pagination) {
            updatePagination(response.data.pagination);
        }

        attachButtonEventHandlers();
        attachInlineStatusHandlers();
        hideLoader();
    } catch (error) {
        console.error('Erreur lors du chargement:', error);
        tbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger">
            Erreur lors du chargement des commandes. Veuillez réessayer.</td></tr>`;
        hideLoader();
    }
}

function updatePagination(pagination) {
    const paginationElement = document.getElementById('ordersPagination');
    if (!paginationElement) return;

    let html = '';
    
    // Previous button
    html += `
        <li class="page-item ${pagination.page <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${pagination.page - 1}" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>
    `;

    // Page numbers
    for (let i = 1; i <= pagination.pages; i++) {
        html += `
            <li class="page-item ${pagination.page === i ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `;
    }

    // Next button
    html += `
        <li class="page-item ${pagination.page >= pagination.pages ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${pagination.page + 1}" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
    `;

    paginationElement.innerHTML = html;
}

function getPaymentStatusBadgeClass(status) {
    const classes = {
        'Payé': 'bg-success',
        'En attente': 'bg-warning text-dark',
        'Échoué': 'bg-danger',
        'Remboursé': 'bg-info'
    };
    return classes[status] || 'bg-secondary';
}

function getShippingStatusBadgeClass(status) {
    const classes = {
        'Expédié': 'bg-success',
        'En préparation': 'bg-warning text-dark',
        'Non expédié': 'bg-secondary',
        'Livré': 'bg-info'
    };
    return classes[status] || 'bg-secondary';
}

function getActionButtons(order) {
    let buttons = '';
    
    // Bouton détails commande toujours présent
    buttons += `<button class="btn btn-sm btn-outline-primary view-details-btn" data-order-id="${order.id}">
        <i class="fas fa-eye"></i>
    </button>`;

    // Boutons conditionnels selon le statut
    if (order.status === 'Pending') {
        buttons += `<button class="btn btn-sm btn-outline-success create-invoice-btn" data-order-id="${order.id}">
            <i class="fas fa-file-invoice"></i>
        </button>`;
    } 
    else if (order.status === 'Processing') {
        buttons += `<button class="btn btn-sm btn-outline-info create-shipping-btn" data-order-id="${order.id}">
            <i class="fas fa-shipping-fast"></i>
        </button>`;
    }
    
    return buttons;
}

function attachButtonEventHandlers() {


    // Réattacher les nouveaux gestionnaires
    document.querySelectorAll('.create-invoice-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.dataset.orderId;
            await generateInvoice(id);
        });
        btn.replaceWith(btn.cloneNode(true));
    });

    document.querySelectorAll('.create-invoice-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.dataset.orderId;
            await generateInvoice(id);
        });
        btn.replaceWith(btn.cloneNode(true));
    });

    document.querySelectorAll('.create-shipping-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.dataset.orderId;
            await createShippingRequest(id);
        });
        btn.replaceWith(btn.cloneNode(true));
    });

    document.querySelectorAll('.validate-delivery-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.dataset.orderId;
            if (confirm('Valider cette livraison ?')) {
                await validateDelivery(id);
            }
        });
        btn.replaceWith(btn.cloneNode(true));
    });
    
    // Ajouter le gestionnaire pour les boutons de visualisation des détails
    document.querySelectorAll('.view-details-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.orderId;
            viewOrderDetails(id);
        });
        btn.replaceWith(btn.cloneNode(true));
    });
}

function attachInlineStatusHandlers() {
    const table = document.getElementById('pendingOrdersTable');

    table.addEventListener('change', (e) => {
        if (e.target.classList.contains('status-select')) {
            const row = e.target.closest('tr');
            const saveBtn = row.querySelector('.save-status-btn');
            if (e.target.value !== e.target.getAttribute('data-original-status')) {
                const actionCell = row.querySelector('.action-buttons');
                actionCell.querySelectorAll('button').forEach(btn => {
                    if (!btn.classList.contains('save-status-btn')) {
                        btn.style.display = 'none';
                    } else {
                        btn.style.display = 'inline-block';
                        // Changer le texte du bouton en fonction du statut sélectionné
                        btn.textContent = e.target.value === 'Processing' ? 'Générer la facture' : 'Sauvegarder';
                         btn.textContent = e.target.value === 'Shipped' ? 'Demande livraison' : 'Sauvegarder';
                    }
                });
            } else {
                const actionCell = row.querySelector('.action-buttons');
                const status = e.target.getAttribute('data-original-status');
                actionCell.innerHTML = getActionButtons(row.dataset.orderId, status);
            }
        }
    });

    table.addEventListener('click', async (e) => {
        if (e.target.classList.contains('save-status-btn')) {
            const row = e.target.closest('tr');
            const select = row.querySelector('.status-select');
            const orderId = row.dataset.orderId;
            const newStatus = select.value;
            
            // Générer la facture si le nouveau statut est "Processing"
            if (newStatus === 'Processing') {
                const invoiceResult = await generateInvoice(orderId);
                if (!invoiceResult) {
                    alert('Erreur lors de la génération de la facture');
                    return;
                }
            }

            const context = {
                service: 'AdminOrder',
                action: 'updateOrderStatus',
                data: {
                    order_id: orderId,
                    status: newStatus
                }
            };

            const response = await postData(context);

            if (response.status === 'success') {
                select.setAttribute('data-original-status', newStatus);
                e.target.style.display = 'none';
                await loadOrders();
                alert('Statut mis à jour avec succès!');
            } else {
                alert(`Erreur: ${response.message}`);
            }
        }
    });
}

function getActionButtons(order) {
    let buttons = '';
    
    const orderId = typeof order === 'string' ? order : order.id;
    const status = typeof order === 'string' ? arguments[1] : order.status;
    
    // Bouton détails commande toujours présent
    buttons += `<button class="btn btn-sm btn-outline-primary view-details-btn" data-order-id="${orderId}">
        <i class="fas fa-eye"></i>
    </button>`;

    // Boutons conditionnels selon le statut
    if (status === 'Pending') {
        buttons += `<button class="btn btn-sm btn-outline-success create-invoice-btn" data-order-id="${orderId}">
            <i class="fas fa-file-invoice"></i>
        </button>`;
    } 
    else if (status === 'Processing') {
        buttons += `<button class="btn btn-sm btn-outline-info create-shipping-btn" data-order-id="${orderId}">
            <i class="fas fa-shipping-fast"></i>
        </button>`;
    }
    
    return buttons;
}

async function updateOrderStatus(orderIds, newStatus) {
    showLoader();
    const orders = Array.isArray(orderIds) ? orderIds : [orderIds];
    
    // Si le nouveau statut est Processing, générer d'abord les factures
    if (newStatus === 'Processing') {
        const invoiceResult = await generateInvoice(orders);
        if (!invoiceResult) {
            hideLoader();
            showNotification('Erreur lors de la génération des factures', 'error');
            return false;
        }
    }

    const context = {
        service: 'AdminOrder',
        action: 'updateOrderStatus',
        data: {
            order_id: orders,
            status: newStatus
        }
    };

    try {
        const response = await postData(context);
        hideLoader();
        
        if (response.status === 'success') {
            showNotification(`Statut mis à jour pour ${orders.length} commande(s)`, 'success');
            await loadOrders(); // Recharger la liste
            return true;
        } else {
            showNotification(response.message || 'Erreur lors de la mise à jour', 'error');
            return false;
        }
    } catch (error) {
        hideLoader();
        showNotification('Erreur lors de la communication avec le serveur', 'error');
        return false;
    }
}

// Correction pour la fonction generateInvoice (lignes ~485-507)
async function generateInvoice(orderId) {
    const orderIds = Array.isArray(orderId) ? orderId : [orderId];
    
    try {
        showLoader();
        
        const context = {
            service: 'AdminOrder',
            action: 'generateInvoice',
            data: {
                order_id: orderIds
            }
        };

        const response = await postData(context);

        if (response.status === 'success') {
            // Ouvrir la facture dans un nouvel onglet si disponible
            if (response.file_url) {
                window.open(response.file_url, '_blank');
            }
            
            // Mettre à jour le statut de la commande vers 'Processing'
            for (const id of orderIds) {
                await updateOrderStatus([id], 'Processing');
            }
            
            showNotification('Facture(s) générée(s) avec succès', 'success');
            await loadOrders();
            return true;
        } else {
            showNotification(response.message || 'Erreur lors de la création de la facture', 'error');
            return false;
        }
    } catch (error) {
        console.error('Erreur lors de la génération de facture:', error);
        showNotification('Erreur lors de la génération de facture', 'error');
        return false;
    } finally {
        hideLoader();
    }
}

async function createShippingRequest(orderId) {
    try {
        showLoader();
        
        // Demander le service de livraison
        const deliveryService = await promptDeliveryService();
        if (!deliveryService) {
            hideLoader();
            return false;
        }
        
        const context = {
            service: 'Delivery',
            action: 'createDelivery',
            data: {
                order_id: orderId,
                delivery_service: deliveryService
            }
        };

        const response = await postData(context);

        if (response.status === 'success' || response.status === 'pending') {
            showNotification('Demande d\'expédition créée avec succès', 'success');
            
            // Si l'étiquette est disponible, l'afficher
            if (response.data && response.data.label_url) {
                window.open(response.data.label_url, '_blank');
            }
            
            await loadOrders();
            return true;
        } else {
            showNotification(response.message || 'Erreur lors de la création de l\'expédition', 'error');
            return false;
        }
    } catch (error) {
        console.error('Erreur lors de la création de l\'expédition:', error);
        showNotification('Erreur lors de la création de l\'expédition', 'error');
        return false;
    } finally {
        hideLoader();
    }
}

async function validateDelivery(orderId) {
    try {
        showLoader();
        const context = {
            service: 'Delivery',
            action: 'updateShipment',
            data: {
                order_id: orderId,
                status: 'Delivered'
            }
        };

        const response = await postData(context);

        if (response.status === 'success') {
            await updateOrderStatus([orderId], 'Delivered');
            showNotification(`Livraison validée pour la commande #${orderId}`, 'success');
            await loadOrders();
        } else {
            showNotification(response.message || 'Erreur lors de la validation', 'error');
        }
    } catch (error) {
        console.error('Erreur lors de la validation de livraison:', error);
        showNotification('Erreur lors de la validation de livraison', 'error');
    } finally {
        hideLoader();
    }
}

function setupBulkActions() {
    const bulkSelect = document.getElementById('bulkActionSelect');
    const applyBtn = document.getElementById('applyBulkAction');

    bulkSelect.innerHTML = `
        <option value="">Actions groupées</option>
        <option value="generate-invoice">Générer Factures</option>
        <option value="create-shipping">Créer Demandes Livraison</option>
        <option value="validate-delivery">Valider livraisons</option>
        <option value="delete">Supprimer les commandes</option>
        <option value="Pending">Marquer comme En attente</option>
        <option value="Processing">Marquer comme En traitement</option>
        <option value="Shipped">Marquer comme Expédié</option>
        <option value="Delivered">Marquer comme Livré</option>
        <option value="Cancelled">Marquer comme Annulé</option>
    `;

    applyBtn.addEventListener('click', async () => {
        const action = bulkSelect.value;
        if (!action) return alert('Sélectionnez une action');

        const checkboxes = document.querySelectorAll('.order-checkbox:checked');
        const selectedOrders = Array.from(checkboxes).map(cb => cb.dataset.orderId);

        if (selectedOrders.length === 0) {
            return alert('Sélectionnez au moins une commande');
        }

        if (!confirm(`Confirmer l'action "${action}" sur ${selectedOrders.length} commande(s) ?`)) return;

        try {
            showLoader();
            if (action === 'delete') {
                await deleteOrders(selectedOrders);
            } else if (action === 'generate-invoice') {
                await generateInvoice(selectedOrders);
            } else if (action === 'create-shipping') {
                await createBulkShipments(selectedOrders);
            } else if (action === 'validate-delivery') {
                for (const orderId of selectedOrders) {
                    await validateDelivery(orderId);
                }
            } else {
                await updateOrderStatus(selectedOrders, action);
            }
            await loadOrders();
            showNotification('Actions groupées terminées avec succès', 'success');
        } catch (error) {
            console.error('Erreur action groupée:', error);
            showNotification('Erreur lors du traitement des actions groupées', 'error');
        } finally {
            hideLoader();
        }
    });
}

async function deleteOrders(orderIds) {
    const context = {
        service: 'AdminOrder',
        action: 'deleteOrders',
        data: {
            order_id: orderIds
        }
    };

    const response = await postData(context);
    
    if (response.status === 'success') {
        showNotification(`${orderIds.length} commande(s) supprimée(s) avec succès`, 'success');
        if (response.warnings && response.warnings.length > 0) {
            showNotification(`Attention: ${response.warnings.join(', ')}`, 'warning');
        }
        return true;
    } else {
        showNotification(response.message || 'Erreur lors de la suppression', 'error');
        if (response.errors && response.errors.length > 0) {
            console.error('Erreurs de suppression:', response.errors);
        }
        return false;
    }
}

/**
 * Script de gestion des livraisons pour le tableau de bord administrateur
 */

// Fonction pour afficher les détails d'une commande
async function viewOrderDetails(orderId) {
    try {
        showLoader();
        
        const context = {
            service: 'AdminOrder',
            action: 'getOrder',
            data: {
                order_id: orderId
            }
        };
        
        const response = await postData(context);
        
        if (!response || response.status !== 'success') {
            throw new Error('Erreur lors de la récupération des détails de la commande');
        }
        
        const order = response.data;
        
        // Créer la modale si elle n'existe pas
        let modal = document.getElementById('orderDetailsModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'orderDetailsModal';
            modal.className = 'modal';
            modal.setAttribute('tabindex', '-1');
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-labelledby', 'orderDetailsModalLabel');
            modal.setAttribute('aria-hidden', 'true');
            
            document.body.appendChild(modal);
        }
        
        // Formater la date
        const orderDate = new Date(order.created_at || new Date()).toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        // Calculer le total
        let totalItems = 0;
        let itemsHtml = '';
        
        if (order.items && Array.isArray(order.items)) {
            order.items.forEach(item => {
                const itemTotal = parseFloat(item.price || 0) * parseInt(item.quantity || 1);
                totalItems += itemTotal;
                
                itemsHtml += `
                <tr>
                    <td>#${item.product_id || ''}</td>
                    <td>${item.product_name || 'Produit #' + (item.product_id || '')}</td>
                    <td>${item.quantity || 1}</td>
                    <td>${parseFloat(item.price || 0).toFixed(2)} €</td>
                    <td>${itemTotal.toFixed(2)} €</td>
                </tr>`;
            });
        } else {
            itemsHtml = '<tr><td colspan="5" class="text-center">Aucun article trouvé pour cette commande</td></tr>';
        }
        
        // Adresse de livraison avec vérification des valeurs nulles
        const shippingAddress = order.shipping_street ? 
            `${order.shipping_street || ''}<br>
            ${order.shipping_postal_code || ''} ${order.shipping_city || ''}<br>
            ${order.shipping_state || ''}, ${order.shipping_country || ''}` : 
            'Aucune adresse de livraison';
        
        // Construire le contenu de la modale
        modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détails de la commande #${order.id || ''}</h5>
                    <button type="button" class="btn-close close-modal" aria-label="Fermer">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Informations client</h6>
                            <p><strong>Client ID:</strong> #${order.user_id || ''}</p>
                            <p><strong>Date de commande:</strong> ${orderDate}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Adresse de livraison</h6>
                            <p>${shippingAddress}</p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="orderStatusSelect">Statut de la commande</label>
                                <select id="orderStatusSelect" class="form-select" data-order-id="${order.id || ''}">
                                    ${dashboardConfig.possibleStatuses.map(status => 
                                        `<option value="${status}" ${status === (order.status || 'Pending') ? 'selected' : ''}>${status}</option>`
                                    ).join('')}
                                </select>
                            </div>
                            <button class="btn btn-primary mt-2" id="updateStatusBtn" data-order-id="${order.id || ''}">
                                Mettre à jour le statut
                            </button>
                        </div>
                    </div>
                    
                    <h6>Articles commandés</h6>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Produit</th>
                                    <th>Quantité</th>
                                    <th>Prix unitaire</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${itemsHtml}
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                    <td><strong>${parseFloat(order.total_amount || 0).toFixed(2)} €</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h6>Statut du paiement</h6>
                            <span class="badge ${getPaymentStatusBadgeClass(order.payment_status || 'En attente')}">
                                ${order.payment_status || 'En attente'}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <h6>Statut de livraison</h6>
                            <span class="badge ${getShippingStatusBadgeClass(order.shipping_status || 'Non expédié')}">
                                ${order.shipping_status || 'Non expédié'}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-modal">Fermer</button>
                    ${(order.status || '') === 'Pending' ? 
                        `<button type="button" class="btn btn-success create-invoice-modal-btn" data-order-id="${order.id || ''}">
                            Générer facture
                        </button>` : ''}
                    ${(order.status || '') === 'Processing' ? 
                        `<button type="button" class="btn btn-info create-shipping-modal-btn" data-order-id="${order.id || ''}">
                            Créer expédition
                        </button>` : ''}
                </div>
            </div>
        </div>`;
        
        // Afficher la modale
        modal.style.display = 'block';
        
        // Ajouter les gestionnaires d'événements pour fermer la modale
        modal.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        });
        
        // Fermer la modale en cliquant en dehors
        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        // Ajouter les gestionnaires d'événements pour les boutons de la modale
        document.getElementById('updateStatusBtn').addEventListener('click', async function() {
            const orderId = this.dataset.orderId;
            const newStatus = document.getElementById('orderStatusSelect').value;
            
            try {
                showLoader();
                const response = await updateOrderStatus([orderId], newStatus);
                
                if (response.status === 'success') {
                    showNotification('Statut mis à jour avec succès', 'success');
                    modal.style.display = 'none';
                    await loadOrders(); // Recharger la liste des commandes
                } else {
                    showNotification(response.message || 'Erreur lors de la mise à jour', 'error');
                }
                
                hideLoader();
            } catch (error) {
                console.error('Erreur lors de la mise à jour du statut:', error);
                hideLoader();
                showNotification('Erreur lors de la mise à jour du statut', 'error');
            }
        });
        
        // Gestionnaire pour le bouton de génération de facture
        const invoiceBtn = modal.querySelector('.create-invoice-modal-btn');
        if (invoiceBtn) {
            invoiceBtn.addEventListener('click', async function() {
                const orderId = this.dataset.orderId;
                const result = await generateInvoice(orderId);
                if (result) {
                    modal.style.display = 'none';
                }
            });
        }
        
        // Gestionnaire pour le bouton de création d'expédition
        const shippingBtn = modal.querySelector('.create-shipping-modal-btn');
        if (shippingBtn) {
            shippingBtn.addEventListener('click', async function() {
                const orderId = this.dataset.orderId;
                await createShippingRequest(orderId);
                modal.style.display = 'none';
            });
        }
        
        hideLoader();
    } catch (error) {
        console.error('Erreur lors de l\'affichage des détails:', error);
        hideLoader();
        showNotification('Erreur lors de la récupération des détails', 'error');
    }
}

// Fonction pour mettre à jour le statut d'une commande depuis la modale
async function updateOrderStatusFromModal(orderId, newStatus, modalInstance) {
    try {
        showLoader();
        
        const response = await updateOrderStatus([orderId], newStatus);
        
        if (response.status === 'success') {
            showNotification('Statut mis à jour avec succès', 'success');
            await loadOrders(); // Recharger la liste des commandes
            modalInstance.hide(); // Fermer la modale
        } else {
            showNotification(response.message || 'Erreur lors de la mise à jour', 'error');
        }
        
        hideLoader();
    } catch (error) {
        console.error('Erreur lors de la mise à jour du statut:', error);
        hideLoader();
        showNotification('Erreur lors de la mise à jour du statut', 'error');
    }
}

// Fonction pour gérer les clics sur les boutons d'action
function handleActionButtons(e) {
    // Vérifier si c'est un bouton de détails
    if (e.target.closest('.view-details-btn')) {
        const btn = e.target.closest('.view-details-btn');
        const orderId = btn.dataset.orderId;
        viewOrderDetails(orderId);
    }
}

// Ajouter des styles pour la modale
function addModalStyles() {
    if (document.getElementById('admin-modal-styles')) return;
    
    const style = document.createElement('style');
    style.id = 'admin-modal-styles';
    style.textContent = `
        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-dialog {
            margin: 30px auto;
            max-width: 800px;
        }
        
        .modal-content {
            position: relative;
            background-color: #fff;
            border-radius: 6px;
            box-shadow: 0 3px 9px rgba(0,0,0,.5);
            padding: 0;
        }
        
        .modal-header {
            padding: 15px;
            border-bottom: 1px solid #e5e5e5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            margin: 0;
            line-height: 1.42857143;
        }
        
        .btn-close {
            font-size: 21px;
            font-weight: 700;
            line-height: 1;
            color: #000;
            text-shadow: 0 1px 0 #fff;
            opacity: .2;
            background: transparent;
            border: 0;
            cursor: pointer;
        }
        
        .modal-body {
            position: relative;
            padding: 15px;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .modal-footer {
            padding: 15px;
            text-align: right;
            border-top: 1px solid #e5e5e5;
        }
    `;
    
    document.head.appendChild(style);
}

document.addEventListener('DOMContentLoaded', async function() {
    if (!document.querySelector('.admin-dashboard')) return;

    try {
        addModalStyles();
        await loadOrders();
        setupBulkActions();
        attachButtonEventHandlers();
        attachInlineStatusHandlers();
        
        setupEventHandlers();
        
    } catch (error) {
        console.error('Erreur lors de l\'initialisation:', error);
        showNotification('Erreur lors du chargement de la page', 'error');
    }
});


function handleBulkAction() {
    const action = document.getElementById('bulkActionSelect').value;
    if (!action) return alert('Sélectionnez une action');

    const checkboxes = document.querySelectorAll('.order-checkbox:checked');
    const selectedOrders = Array.from(checkboxes).map(cb => cb.dataset.orderId);

    if (selectedOrders.length === 0) {
        return alert('Sélectionnez au moins une commande');
    }

    if (!confirm(`Confirmer l'action "${action}" sur ${selectedOrders.length} commande(s) ?`)) return;

    try {
        showLoader();
        if (action === 'delete') {
            deleteOrders(selectedOrders);
        } else if (action === 'generate-invoice') {
            generateInvoice(selectedOrders);
        } else if (action.includes('status-')) {
            updateOrderStatus(selectedOrders, action.replace('status-', ''));
        } else {
            updateOrderStatus(selectedOrders, action);
        }
        loadOrders().then(() => {
            showNotification('Actions groupées terminées avec succès', 'success');
        });
    } catch (error) {
        console.error('Erreur action groupée:', error);
        showNotification('Erreur lors du traitement des actions groupées', 'error');
        hideLoader();
    }
}

function initUI() {
    // Initialiser les éléments d'interface utilisateur
    const statusFilters = document.getElementById('statusFilters');
    if (statusFilters) {
        statusFilters.innerHTML = dashboardConfig.possibleStatuses.map(status => 
            `<button class="btn btn-outline-secondary btn-sm m-1 filter-btn" data-status="${status}">
                ${status} <span class="badge bg-secondary" id="count-${status}">0</span>
            </button>`
        ).join('');
        
        // Ajouter gestionnaires pour les boutons de filtre
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const status = btn.dataset.status;
                filterOrdersByStatus(status);
            });
        });
    }
}

function filterOrdersByStatus(status) {
    loadOrders(1, { status: status }).then(() => {
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.status === status) {
                btn.classList.add('active');
            }
        });
    });
}

function setupEventHandlers() {
    // Supprimer les gestionnaires existants
    document.removeEventListener('click', handleShipmentClickEvents);
    
    // Ajouter les nouveaux gestionnaires
    document.addEventListener('click', handleShipmentClickEvents);

    // Select/Deselect All - supprimer les gestionnaires existants d'abord
    const selectAllBtn = document.getElementById('selectAllOrders');
    const deselectAllBtn = document.getElementById('deselectAllOrders');
    
    if (selectAllBtn) {
        const newSelectAllBtn = selectAllBtn.cloneNode(true);
        selectAllBtn.parentNode.replaceChild(newSelectAllBtn, selectAllBtn);
        newSelectAllBtn.addEventListener('click', () => {
            document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = true);
        });
    }
    
    if (deselectAllBtn) {
        const newDeselectAllBtn = deselectAllBtn.cloneNode(true);
        deselectAllBtn.parentNode.replaceChild(newDeselectAllBtn, deselectAllBtn);
        newDeselectAllBtn.addEventListener('click', () => {
            document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = false);
        });
    }

    // Pagination - supprimer les gestionnaires existants d'abord
    const paginationEl = document.getElementById('ordersPagination');
    if (paginationEl) {
        const newPaginationEl = paginationEl.cloneNode(true);
        paginationEl.parentNode.replaceChild(newPaginationEl, paginationEl);
        newPaginationEl.addEventListener('click', async (e) => {
            if (e.target.classList.contains('page-link')) {
                e.preventDefault();
                const page = e.target.dataset.page;
                if (page) {
                    await loadOrders(parseInt(page));
                }
            }
        });
    }
}

// Ajouter cette fonction de gestion d'événements pour éviter les duplications
function handleShipmentClickEvents(e) {
    if (e.target.closest('.create-shipment-btn')) {
        createShipment(e.target.closest('.create-shipment-btn').dataset.orderId);
    } else if (e.target.closest('.generate-label-btn')) {
        generateLabel(e.target.closest('.generate-label-btn').dataset.orderId);
    } else if (e.target.closest('.track-shipment-btn')) {
        trackShipment(e.target.closest('.track-shipment-btn').dataset.trackingNumber);
    } else if (e.target.closest('.void-label-btn')) {
        voidLabel(e.target.closest('.void-label-btn').dataset.orderId);
    }
}
// Fonctions de gestion des expéditions
function createShipment(orderId) {
    showLoader();
    const context = {
        service: 'Delivery',
        action: 'createShipment',
        data: { order_id: orderId }
    };
    
    postData(context)
        .then(data => {
            hideLoader();
            if (data.status === 'success') {
                showNotification('Expédition créée avec succès', 'success');
                updateOrderRow(orderId, 'Processing');
            } else {
                showNotification(`Erreur: ${data.message}`, 'error');
            }
        })
        .catch(error => {
            hideLoader();
            showNotification('Erreur de communication avec le serveur', 'error');
            console.error('Error:', error);
        });
}

/**
 * Génère une étiquette d'expédition pour une commande
 */
function generateLabel(orderId) {
    showLoader();
    
    const context = {
        service: 'Delivery',
        action: 'getShippingLabel',
        data: { 
            order_id: orderId 
        }
    };
    
    postData(context)
        .then(data => {
            hideLoader();
            if (data.status === 'success') {
                showNotification('Étiquette générée avec succès', 'success');
                
                if (data.label_url) {
                    window.open(data.label_url, '_blank');
                }
                
                updateOrderRow(orderId, 'Shipped');
            } else {
                showNotification(`Erreur: ${data.message}`, 'error');
            }
        })
        .catch(error => {
            hideLoader();
            showNotification('Erreur de communication avec le serveur', 'error');
            console.error('Error:', error);
        });
}

/**
 * Suit une expédition par son numéro de suivi
 */
function trackShipment(trackingNumber) {
    showLoader();
    
    const context = {
        service: 'Delivery',
        action: 'trackShipment',
        data: { tracking_number: trackingNumber }
    };
    
    postData(context)
        .then(data => {
            hideLoader();
            if (data.status === 'success') {
                showTrackingModal(data.delivery, data.tracking_details);
            } else {
                showNotification(`Erreur: ${data.message}`, 'error');
            }
        })
        .catch(error => {
            hideLoader();
            showNotification('Erreur de communication avec le serveur', 'error');
            console.error('Error:', error);
        });
}

/**
 * Traite plusieurs expéditions en une seule fois
 */
function processMultipleShipments(orderIds) {
    showLoader();
    let processed = 0;
    let errors = 0;
    
    const processNext = (index) => {
        if (index >= orderIds.length) {
            hideLoader();
            showNotification(`Traitement terminé: ${processed} réussi(s), ${errors} erreur(s)`, 
                errors === 0 ? 'success' : 'warning');
            return;
        }
        
        createShipment(orderIds[index])
            .then(() => {
                processed++;
                processNext(index + 1);
            })
            .catch(() => {
                errors++;
                processNext(index + 1);
            });
    };
    
    processNext(0);
}

/**
 * Annule une étiquette d'expédition
 */
async function voidLabel(orderId) {
    if (!confirm('Êtes-vous sûr de vouloir annuler cette étiquette ?')) {
        return;
    }
    
    showLoader();
    
    const context = {
        service: 'Delivery', // Correct service
        action: 'updateShipment',
        data: { 
            order_id: orderId,
            status: 'Cancelled'
    }};
    
    try {
        const response = await postData(context);
        
        hideLoader();
        
        if (response.status === 'success') {
            showNotification('Étiquette annulée avec succès', 'success');
            updateOrderRow(orderId, 'Processing');
            await loadOrders();
        } else {
            showNotification(`Erreur: ${response.message}`, 'error');
        }
    } catch (error) {
        hideLoader();
        showNotification('Erreur de communication avec le serveur', 'error');
        console.error('Error:', error);
    }
}




// Fonction pour afficher ou mettre à jour un élément de suivi de commande
function updateOrderRow(orderId, newStatus) {
    const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
    if (row) {
        row.dataset.status = newStatus;
        const statusSelect = row.querySelector('.status-select');
        if (statusSelect) {
            statusSelect.value = newStatus;
            statusSelect.setAttribute('data-original-status', newStatus);
        }
        
        // Mettre à jour les actions disponibles
        const actionCell = row.querySelector('.action-buttons');
        if (actionCell) {
            actionCell.innerHTML = getActionButtons({ id: orderId, status: newStatus });
            attachButtonEventHandlers();
        }
    }
}
function showTrackingModal(delivery, trackingDetails) {
    let modal = document.getElementById('trackingModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'trackingModal';
        modal.className = 'modal';
        modal.setAttribute('tabindex', '-1');
        
        document.body.appendChild(modal);
    }
    
    let trackingHtml = '';
    if (Array.isArray(trackingDetails)) {
        trackingDetails.forEach(detail => {
            const date = new Date(detail.date).toLocaleDateString('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            trackingHtml += `
            <li class="list-group-item">
                <div class="d-flex justify-content-between">
                    <strong>${detail.status}</strong>
                    <span>${date}</span>
                </div>
                <div>${detail.location || ''}</div>
                <small class="text-muted">${detail.description || ''}</small>
            </li>`;
        });
    } else {
        trackingHtml = '<li class="list-group-item">Aucun détail de suivi disponible</li>';
    }
    
    modal.innerHTML = `
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Suivi de livraison</h5>
                <button type="button" class="btn-close close-modal" aria-label="Fermer">&times;</button>
            </div>
            <div class="modal-body">
                <div class="card mb-3">
                    <div class="card-header">Informations de livraison</div>
                    <div class="card-body">
                        <p><strong>Numéro de suivi:</strong> ${delivery.tracking_number || 'Non disponible'}</p>
                        <p><strong>Statut:</strong> ${delivery.status || 'Non disponible'}</p>
                        <p><strong>Transporteur:</strong> ${delivery.carrier || 'Non disponible'}</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">Historique de suivi</div>
                    <ul class="list-group list-group-flush">
                        ${trackingHtml}
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-modal">Fermer</button>
            </div>
        </div>
    </div>`;
    
    modal.style.display = 'block';
    
    modal.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            modal.style.display = 'none';
        });
    });
    
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
}
// Crée une demande d'expédition pour une commande
async function createShippingRequest(orderId) {
    const context = {
        service: 'Delivery',
        action: 'createShipment',
        data: {
            order_id: orderId
        }
    };

    try {
        showLoader();
        const response = await postData(context);
        hideLoader();
        
        if (response.status === 'success') {
            await updateOrderStatus([orderId], 'Shipped');
            showNotification(`Demande de livraison créée pour la commande #${orderId}`, 'success');
            await loadOrders();
            return true;
        } else {
            showNotification(response.message || 'Erreur lors de la création de la demande', 'error');
            return false;
        }
    } catch (error) {
        hideLoader();
        console.error('Erreur lors de la création de la demande de livraison:', error);
        showNotification('Erreur lors de la communication avec le serveur', 'error');
        return false;
    }
}