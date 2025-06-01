<?php
require_once "../../../DIR.php";
require_once SHARED_PATH . "session.php";
require_once CONFIG_PATH . "log_config.php";
require_once BASE_PATH . "env_helper.php";
require_once BASE_PATH . "securiser/oauth_config.php";
require_once SERVICE_CRUD_PATH . "AuthService.php";
require_once SERVICE_CRUD_PATH . "GoogleOAuthService.php";
require_once CONFIG_PATH . "coDB.php";

// Récupérer les données de la requête
$contentType = $_SERVER["CONTENT_TYPE"] ?? '';
$data = [];

if (strpos($contentType, "application/json") !== false) {
    $input = json_decode(file_get_contents("php://input"), true);
    if ($input) {
        $data = $input;
    }
} else {
    // Gérer les redirections OAuth standard
    if (isset($_GET['code'])) {
        $data['code'] = $_GET['code'];
    }
}

try {
    // Initialiser le client Google
    $client = new Google_Client();
    $client->setClientId(GOOGLE_CLIENT_ID);
    $client->setClientSecret(GOOGLE_CLIENT_SECRET);
    $client->setRedirectUri(GOOGLE_REDIRECT_URI);
    $client->addScope("email");
    $client->addScope("profile");
    
    // Traiter le jeton d'identification ou le code d'autorisation
    if (isset($data['credential'])) {
        // Traitement du jeton d'identification (Sign in with Google)
        $payload = $client->verifyIdToken($data['credential']);
        
        if ($payload) {
            // Créer un tableau avec les données Google pour l'authentification
            $googleData = [
                'sub' => $payload['sub'],
                'email' => $payload['email'],
                'given_name' => $payload['given_name'] ?? '',
                'family_name' => $payload['family_name'] ?? '',
                'picture' => $payload['picture'] ?? ''
            ];
            
            // Utiliser le service d'authentification pour gérer la connexion
            $authService = new AuthService(coDB());
            $result = $authService->handleGoogleLogin($googleData);
            
            // Renvoyer le résultat en JSON
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        }
    } elseif (isset($data['code'])) {
        // Traitement du code d'autorisation (OAuth 2.0 standard)
        $token = $client->fetchAccessTokenWithAuthCode($data['code']);
        $client->setAccessToken($token);
        
        // Obtenir les informations du profil
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        
        // Créer un tableau avec les données Google pour l'authentification
        $googleData = [
            'sub' => $google_account_info->id,
            'email' => $google_account_info->email,
            'given_name' => $google_account_info->givenName,
            'family_name' => $google_account_info->familyName,
            'picture' => $google_account_info->picture,
            'access_token' => $token['access_token'] ?? null
        ];
        
        // Utiliser le service d'authentification pour gérer la connexion
        $authService = new AuthService(coDB());
        $result = $authService->handleGoogleLogin($googleData);
        
        // Rediriger vers la page appropriée
        if (isset($result['redirect'])) {
            header('Location: ' . $result['redirect']);
        } else {
            header('Location: /user/account');
        }
        exit;
    }
    
    // Si on arrive ici, c'est qu'il y a eu un problème
    logError("Google OAuth callback failed - invalid data", ['data' => $data]);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid authentication data']);
    
} catch (Exception $e) {
    logError("Google OAuth callback error", ['error' => $e->getMessage()]);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Authentication error: ' . $e->getMessage()]);
}