<?php

require_once BASE_PATH . 'env_helper.php';
// Empêcher l'accès direct au fichier
if (!defined('SECURE_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Accès interdit');
}

// Clés d'API Stripe
$stripe_public_key = get_env_variable('STRIPE_PUBLIC_KEY');
$stripe_private_key = get_env_variable('STRIPE_PRIVATE_KEY');