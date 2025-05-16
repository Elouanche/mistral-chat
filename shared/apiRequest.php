<?php

function makeApiRequest($service, $action, $additionalData = []) {
    // Fusionner les données de base avec les données additionnelles
    $data = array_merge([
        'service' => $service,
        'action' => $action,
        'data' => $additionalData
    ]);

    // Inclure la configuration de l'API
    require_once CONFIG_PATH . 'api_config.php';
    
    // Initialiser cURL avec l'URL de l'API Gateway
    $ch = curl_init(API_GATEWAY_URL);
    
    // Configurer les options cURL
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    // Exécuter la requête
    $response = curl_exec($ch);
    
    // Fermer la session cURL
    curl_close($ch);


    // Décoder et retourner la réponse
    return json_decode($response, true) ?? [];
}