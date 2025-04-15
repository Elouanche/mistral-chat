
const baseUrl = `${window.location.protocol}//${window.location.host}`;
const apiUrl = `${baseUrl}/api/api_gateway.php`;
/**
 * Helper function to send POST requests.
 * @param {string} url - The endpoint URL.
 * @param {Object} data - The data to send in the request body.
 * @returns {Promise<Object>} - The JSON response.
 */
async function postData(url, data) {
    try {
        console.log('Sending request to:', url);
        console.log('Request data:', data);
        
        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify(data),
        });
        
        console.log('Response status:', response.status);
        
        const text = await response.text();
        let jsonResponse;
        
        try {
            jsonResponse = JSON.parse(text);
            console.log('API Response:', jsonResponse);
        } catch (e) {
            console.error('Invalid JSON response:', text);
            throw new Error('Invalid JSON response from server');
        }
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}, message: ${jsonResponse.message || 'Unknown error'}`);
        }
        if (jsonResponse.status === 'success') {
            // Gérer la redirection en fonction de la réponse, mais seulement pour certaines actions
            // Vérifier si la requête concerne le panier (Cart)
            const isCartOperation = data && (data.service === 'Cart');
            const noRedirectServices = [
                'Order', 'Returned', 'Review',
                'AdminOrder', 'AdminProduct', 'AdminReturned', 'AdminUser',
                'Payment', 'Notification', 'Delivery', 'Import', 'Analytics',
                'ErrorHandling', 'Monitoring'
            ];
            const service = data?.service;
            
            if (jsonResponse.data && jsonResponse.data.redirect && !isCartOperation) {
                window.location.href = jsonResponse.data.redirect;
            } else if (jsonResponse.data && !isCartOperation && !noRedirectServices.includes(service) ) {
                // Redirection par défaut si aucune redirection n'est spécifiée
                window.location.href = '/';
            }
            // Ne pas rediriger par défaut pour les opérations du panier
        }
        if (jsonResponse.error) {
            if (jsonResponse.isAdmin) {
                // Afficher l'erreur dans l'interface admin
                console.error(jsonResponse.error);
                throw new Error(jsonResponse.error);
            } else {
                // Rediriger vers la page d'erreur pour les utilisateurs normaux
                window.location.href = `/error?code=${encodeURIComponent(jsonResponse.code)}&message=${encodeURIComponent(jsonResponse.error)}`;
            }
        }
        
        return jsonResponse;
    } catch (error) {
        console.error("Error:", error);
        return { error: error.message };
    }
}