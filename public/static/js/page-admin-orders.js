document.addEventListener('DOMContentLoaded', function() {
    const statusFilter = document.getElementById('statusFilter');
    const searchInput = document.getElementById('searchOrder');
    const ordersTable = document.getElementById('ordersTable');
    const modal = document.getElementById('orderDetailsModal');
    const closeModal = document.querySelector('.close');
    
    // Récupérer les données des commandes et détails depuis PHP
    const orderDetails = JSON.parse(document.getElementById('orderDetailsData').textContent);
    const orders = JSON.parse(document.getElementById('ordersData').textContent);
    
    // Filtrage par statut
    statusFilter.addEventListener('change', filterOrders);
    
    // Recherche
    searchInput.addEventListener('input', filterOrders);
    
    // Fonction de filtrage combinée
    function filterOrders() {
        const statusValue = statusFilter.value;
        const searchTerm = searchInput.value.toLowerCase();
        const rows = ordersTable.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const orderId = row.querySelector('td:nth-child(1)').textContent;
            const customer = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const status = row.getAttribute('data-status');
            
            const statusMatch = statusValue === 'all' || status === statusValue;
            const searchMatch = orderId.includes(searchTerm) || customer.includes(searchTerm);
            
            if (statusMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    // Détection des changements de statut
    const statusSelects = document.querySelectorAll('.status-select');
    statusSelects.forEach(select => {
        select.addEventListener('change', function() {
            const originalStatus = this.getAttribute('data-original-status');
            const currentStatus = this.value;
            const saveBtn = this.closest('tr').querySelector('.save-status-btn');
            
            if (originalStatus !== currentStatus) {
                saveBtn.style.display = 'inline-block';
            } else {
                saveBtn.style.display = 'none';
            }
        });
    });
    
    const saveButtons = document.querySelectorAll('.save-status-btn');
    saveButtons.forEach(button => {
        button.addEventListener('click', async function() {
            const row = this.closest('tr');
            const orderId = row.getAttribute('data-order-id');
            const statusSelect = row.querySelector('.status-select');
            const newStatus = statusSelect.value;
            
            try {
                const context = {
                    service: 'Order',
                    action: 'updateOrderStatus',
                    data: { 
                        order_id: orderId,
                        status: newStatus 
                    }
                };

                const result = await postData(context);
                
                if (result.status === 'success') {
                    alert('Statut mis à jour avec succès!');
                    statusSelect.setAttribute('data-original-status', newStatus);
                    row.setAttribute('data-status', newStatus);
                    this.style.display = 'none';
                } else {
                    alert(`Erreur: ${result.message || 'Une erreur est survenue lors de la mise à jour du statut.'}`);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Une erreur est survenue lors de la communication avec le serveur.');
            }
        });
    });
    
    // Affichage des détails de la commande
    const viewDetailsButtons = document.querySelectorAll('.view-details-btn');
    viewDetailsButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            const order = orders.find(o => o.id === orderId);
            const products = orderDetails[orderId];
            
            // Remplir les informations de la commande
            document.getElementById('modalOrderId').textContent = orderId;
            document.getElementById('modalCustomer').textContent = order.username;
            document.getElementById('modalDate').textContent = new Date(order.created_at).toLocaleString();
            document.getElementById('modalStatus').textContent = order.status;
            document.getElementById('modalAddress').textContent = order.shipping_address;
            
            // Remplir le tableau des produits
            const tbody = document.querySelector('#modalProductsTable tbody');
            tbody.innerHTML = '';
            let total = 0;
            
            products.forEach(product => {
                const tr = document.createElement('tr');
                const subtotal = product.quantity * product.price;
                total += subtotal;
                
                tr.innerHTML = `
                    <td>${product.product_name}</td>
                    <td>${product.quantity}</td>
                    <td>${parseFloat(product.price).toFixed(2)} €</td>
                    <td>${subtotal.toFixed(2)} €</td>
                `;
                
                tbody.appendChild(tr);
            });
            
            document.getElementById('modalTotal').textContent = `${total.toFixed(2)} €`;
            
            // Afficher la modal
            modal.style.display = 'block';
        });
    });
    
    // Fermer la modal
    closeModal.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    // Fermer la modal en cliquant en dehors
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});