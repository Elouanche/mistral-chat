<?php
// a changer
use \Firebase\JWT\JWT;

require_once __DIR__ . '/../securiser/clef_secrete.php';

// Vérifiez si la constante est déjà définie avant de la définir à nouveau
if (!defined('SECRET_KEY')) {
    define('SECRET_KEY', $clef_secrete);  // Définir la constante seulement si elle n'est pas encore définie
}

// Fonction pour vérifier un JWT et obtenir les données décodées
function verifyJWT($jwt) {
    try {
        $decoded = JWT::decode($jwt, SECRET_KEY, ['HS256']);
        return (object) $decoded->data;  // Retourner les données décodées, incluant l'ID
    } catch (Exception $e) {
        return null;  // JWT invalide
    }
}
?>