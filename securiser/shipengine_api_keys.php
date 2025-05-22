<?php
// Fichier de configuration pour l'API ShipEngine
// Ce fichier contient les clés d'API pour ShipEngine
require_once BASE_PATH . 'env_helper.php';
if (!defined('SECURE_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Accès interdit');
}

// Clé API ShipEngine
$shipengine_api_key = get_env_variable('SHIPENGINE_API_KEY');



// Vous pouvez ajouter d'autres configurations spécifiques à ShipEngine ici

?>