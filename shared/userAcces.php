<?php

if (!isset($_SESSION['user_id'])) {
    header('Location: /user/login');
    exit;
}

// Protection des données utilisateur
$userId = (int) $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['user_username'], ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars($_SESSION['user_email'], ENT_QUOTES, 'UTF-8');
?>