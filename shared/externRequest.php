<?php

/**
 * Effectue une requête HTTP externe
 * 
 * @param string $url URL de l'endpoint
 * @param string $method Méthode HTTP (GET, POST, PUT, DELETE)
 * @param array|null $data Données à envoyer
 * @param array $headers En-têtes HTTP
 * @param int $timeout Timeout en secondes
 * @return array Réponse décodée de l'API
 * @throws Exception En cas d'erreur
 */
function externRequest($url, $method = 'GET', $data = null, $headers = [], $timeout = 30) {
    $curl = curl_init();

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers
    ];

    if ($data && in_array($method, ['POST', 'PUT'])) {
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    }

    curl_setopt_array($curl, $options);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    if (curl_errno($curl)) {
        $error = curl_error($curl);
        curl_close($curl);
        throw new Exception("Erreur cURL: $error");
    }

    curl_close($curl);

    // Décoder la réponse JSON
    $decodedResponse = json_decode($response, true);
    
    // Vérifier si la requête a réussi
    if ($httpCode >= 400) {
        throw new Exception("Erreur HTTP $httpCode: " . ($decodedResponse['error'] ?? 'Erreur inconnue'));
    }

    return $decodedResponse ?? $response;
}
