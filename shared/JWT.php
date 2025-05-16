<?php
// Inclusion des fichiers nécessaires pour la génération et vérification du JWT
require_once __DIR__ . '/generateJWT.php';
require_once __DIR__ . '/verifyJWT.php';

// Initialiser l'état d'authentification
$isAuthenticated = false;
$userId = null;  // Par défaut, utilisateur non authentifié

// Récupération des entêtes HTTP
$headers = getallheaders();

// Vérifier si un token est présent dans les entêtes (Authorization)
if (isset($headers['Authorization'])) {
    // Extraire le token JWT de l'entête Authorization
    $jwt = str_replace('Bearer ', '', $headers['Authorization']);
    $user = verifyJWT($jwt);

    if ($user) {
        // Si le token est valide, l'utilisateur est authentifié
        $isAuthenticated = true;
        $userId = $user->id;  // Récupérer l'ID de l'utilisateur
    }
}

// Si l'utilisateur n'est pas authentifié, générer un token anonyme
if (!$isAuthenticated) {
    // Créer un ID unique pour un utilisateur invité
    $userId = bin2hex(random_bytes(16)); // Générer un ID aléatoire
    $userData = [
        'id' => $userId,
        'role' => 'guest'  // Attribuer un rôle par défaut
    ];
    // Générer un JWT pour l'utilisateur anonyme
    $jwt = generateJWT($userData);
}

// Vous pouvez maintenant passer l'ID et le token JWT pour vos opérations suivantes.
?>