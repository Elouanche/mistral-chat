<?php
// Configuration de l'URL de base pour l'API Gateway
function getBaseUrl() {
    // Utiliser l'URL de base configurée si elle existe
    if (defined('BASE_URL')) {
        return rtrim(BASE_URL, '/');
    }
    
    // Sinon, construire l'URL à partir des informations du serveur
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];
    
    // Si l'hôte est vide, utiliser une valeur par défaut
    if (empty($host)) {
        $host = 'loremipsum.local';
    }
    
    return $protocol . $host;
}

define('API_GATEWAY_URL', getBaseUrl() . '/api/api_gateway.php');
?>