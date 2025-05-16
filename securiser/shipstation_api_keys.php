<?php
// Empêcher l'accès direct au fichier
if (!defined('SECURE_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Accès interdit');
}

require_once BASE_PATH . '/env_helper.php';

$shipstation_api_key = get_env_variable('SHIPSTATION_API_KEY');
$shipstation_api_secret = get_env_variable('SHIPSTATION_API_SECRET');

