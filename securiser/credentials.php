<?php
require_once __DIR__ . '/../DIR.php';
require_once BASE_PATH . '/env_helper.php';

$serveur = get_env_variable('DB_HOST');
$utilisateur = get_env_variable('DB_USER');
$mot_de_passe = get_env_variable('DB_PASSWORD');
$base_de_donnees = get_env_variable('DB_NAME');
?>