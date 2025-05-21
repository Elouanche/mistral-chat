if (typeof baseUrl === 'undefined') {
    var baseUrl = `${window.location.protocol}//${window.location.host}`;
}

if (typeof apiUrl === 'undefined') {
    var apiUrl = `${baseUrl}/api/api_gateway.php`;
}
/**
 * Helper function to send POST requests.
 * @param {Object} data - The data to send in the request body.
 * @returns {Promise<Object>} - The JSON response.
 */
async function postData(data) {
    try {
        console.log('Request data:', data);
        
        const response = await fetch(apiUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify(data),
        });
        

        const contentType = response.headers.get("Content-Type");
        
        if (contentType && contentType.includes("application/pdf")) {
            const blob = await response.blob();
            const fileUrl = window.URL.createObjectURL(blob);
        
            window.open(fileUrl, '_blank');
            
            return { status: "success", message: "PDF opened and download triggered." };
        }

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

        if (jsonResponse.status === 'success' || jsonResponse.status === 'pending') {
            console.log('Success response:', jsonResponse);
            
            // Gestion de la redirection
            const redirectUrl = jsonResponse.redirect || jsonResponse.data?.redirect;
            if (redirectUrl) {
                console.log('Redirecting to:',  redirectUrl);
                window.location.href =  redirectUrl;
                return;
            }

            // Gestion des PDFs
            if (jsonResponse.pdf_url) {
                const fullPdfUrl = `${baseUrl}${jsonResponse.pdf_url}`;
                window.open(fullPdfUrl, '_blank');
                const link = document.createElement('a');
                link.href = fullPdfUrl;
                link.download = fullPdfUrl.split('/').pop();
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }

        if (jsonResponse.error) {
            if (jsonResponse.isAdmin) {
                console.error(jsonResponse.error);
                throw new Error(jsonResponse.error);
            } else {
                window.location.href = `/error?code=${encodeURIComponent(jsonResponse.code)}&message=${encodeURIComponent(jsonResponse.error)}`;
            }
        }

        return jsonResponse;
    } catch (error) {
        console.error("Error:", error);
        return { error: error.message };
    }
}
