document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchProduct');
    const inventoryTable = document.getElementById('inventoryTable');
    const updateStocksBtn = document.getElementById('updateStocksBtn');
    
    // Fonction de recherche
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = inventoryTable.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const productName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const productDesc = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            
            if (productName.includes(searchTerm) || productDesc.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    
    // Mise à jour des stocks
    updateStocksBtn.addEventListener('click', async function() {
        const rows = inventoryTable.querySelectorAll('tbody tr');
        const productsToUpdate = [];
        
        rows.forEach(row => {
            const productId = row.getAttribute('data-product-id');
            const currentStock = row.querySelector('.current-stock').textContent;
            const newStock = row.querySelector('.stock-input').value;
            
            if (currentStock !== newStock) {
                productsToUpdate.push({
                    id: productId,
                    quantity: newStock
                });
            }
        });
        
        if (productsToUpdate.length === 0) {
            alert('Aucun stock n\'a été modifié.');
            return;
        }
        
        try {
            const context = {
                service: 'Product',
                action: 'updateStocks',
                data: { products: productsToUpdate }
            };

            const result = await postData(context);
            
            if (result.status === 'success') {
                alert('Stocks mis à jour avec succès!');
                productsToUpdate.forEach(product => {
                    const row = inventoryTable.querySelector(`tr[data-product-id="${product.id}"]`);
                    if (row) {
                        row.querySelector('.current-stock').textContent = product.quantity;
                    }
                });
            } else {
                alert(`Erreur: ${result.message || 'Une erreur est survenue lors de la mise à jour des stocks.'}`);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Une erreur est survenue lors de la communication avec le serveur.');
        }
    });
});