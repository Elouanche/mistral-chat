
<?php
/*
// a changer
use \Firebase\JWT\JWT;

require_once __DIR__ . '/../securiser/clef_secrete.php';

if (!defined('SECRET_KEY')) {
    define('SECRET_KEY', $clef_secrete);
}

function generateUniqueId() {
    return bin2hex(random_bytes(16));
}

function generateJWT($data) {
    $issuedAt = time();
    $expirationTime = $issuedAt + 3600;
    $payload = [
        'iat' => $issuedAt,
        'exp' => $expirationTime,
        'data' => $data  
    ];
    return JWT::encode($payload, SECRET_KEY);
}

$userData = [
    'id' => generateUniqueId(),
    'role' => 'guest'
];

$jwt = generateJWT($userData);
*/
?>