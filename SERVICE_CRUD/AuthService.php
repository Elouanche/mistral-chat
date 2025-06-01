<?php
require_once CRUD_PATH . 'UsersCRUD.php';

/**
 * Service d'authentification
 * Utilise UsersCRUD pour les opérations d'authentification
 * Intègre également les fonctionnalités d'authentification Google OAuth
 */
class AuthService {
    /** @var UsersCRUD $usersCRUD Instance du CRUD utilisateurs */
    private $usersCRUD;
    
    /**
     * Constructeur
     * 
     * @param mysqli $mysqli Instance de connexion mysqli
     */
    public function __construct($mysqli) {
        $this->usersCRUD = new UsersCRUD($mysqli);
        logInfo("AuthService initialized");
    }
    
    /**
     * Enregistre un nouvel utilisateur
     * 
     * @param array $data Données d'enregistrement (username/user_name, email/user_email, password/user_password, phone/user_phone)
     * @return array Statut de l'opération
     */
    public function register($data) {
        logInfo("Register attempt", ['email' => $data['email'] ?? $data['user_email'] ?? 'not provided']);
        
        $email = $data['user_email'] ?? $data['email'] ?? null;
        $password = $data['user_password'] ?? $data['password'] ?? null;
        $username = $data['user_name'] ?? $data['username'] ?? null;
        $phone = $data['user_phone'] ?? $data['phone'] ?? null;
        
        if (!$email || !$password) {
            logError("Register failed - missing credentials");
            return ['status' => 'error', 'message' => 'Email and password are required'];
        }
        
        // Génération automatique du username si vide
        if (!$username) {
            $baseUsername = explode('@', $email)[0];
            $username = $baseUsername;
            
            // Vérification de l'unicité du username généré
            $counter = 1;
            $tempUsername = $username;
            
            while (!empty($this->usersCRUD->get(['id'], ['username' => $tempUsername]))) {
                $tempUsername = $username . $counter;
                $counter++;
            }
            
            $username = $tempUsername;
        }
        
        // Vérification email existant
        $existingEmail = $this->usersCRUD->get(['id'], ['email' => $email]);
        if (!empty($existingEmail)) {
            logError("Register failed - email already exists", ['email' => $email]);
            return ['status' => 'error', 'message' => 'Email already exists'];
        }
        
        // Hachage du mot de passe
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        // Préparation des données pour l'insertion
        $userData = [
            'username' => $username,
            'email' => $email,
            'password_hash' => $hashedPassword
        ];
        
        if ($phone) {
            $userData['phone'] = $phone;
        }
        
        // Insertion de l'utilisateur
        $userId = $this->usersCRUD->insert($userData);
        
        if ($userId) {
            logInfo("User registered successfully", ['user_id' => $userId]);
            return [
                'status' => 'success',
                'message' => 'User registered successfully',
                'data' => ['user_id' => $userId],
                'redirect' => '/user/login'
            ];
        }
        
        logError("Register failed - database error");
        return ['status' => 'error', 'message' => 'Failed to register user'];
    }
    
    /**
     * Connecte un utilisateur
     * 
     * @param array $data Données de connexion (username/user_name/email/user_email, password/user_password)
     * @return array Statut de l'opération
     */
    public function login($data) {
        logInfo("Login attempt", ['user' => $data['email'] ?? $data['username'] ?? 'not provided']);
        
        $usernameOrEmail = $data['user_email'] ?? $data['email'] ?? $data['username'] ?? $data['user_name'] ?? null;
        $password = $data['user_password'] ?? $data['password'] ?? null;
        
        if (!$usernameOrEmail || !$password) {
            logError("Login failed - missing credentials");
            return ['status' => 'error', 'message' => 'Username/email and password are required'];
        }
        
        // Recherche de l'utilisateur par nom d'utilisateur ou email
        $users = $this->usersCRUD->get(
            ['id', 'username', 'email', 'password_hash', 'is_admin'],
            [
                'OR' => [
                    'username' => $usernameOrEmail,
                    'email' => $usernameOrEmail
                ]
            ]
        );

        if (empty($users)) {
            logError("Login failed - user not found", ['user' => $usernameOrEmail]);
            return ['status' => 'error', 'message' => 'User not found'];
        }

        $user = $users[0];
        
        // Vérification du mot de passe
        if (!password_verify($password, $user['password_hash'])) {
            logError("Login failed - invalid password", ['user_id' => $user['id']]);
            return ['status' => 'error', 'message' => 'Invalid credentials'];
        }
        
        // Si l'utilisateur est un administrateur, demander une vérification supplémentaire
        if ($user['is_admin']) {
            logInfo("Admin login - verification required", ['user_id' => $user['id']]);
            $_SESSION['admin'] = 'pending';
            // Génération d'un code de vérification
            $adminCode = $this->generateAdminCode();
            
            return [
                'status' => 'pending',
                'service' => 'Notification',
                'action' => 'sendEmail',
                'data' => [
                    'message' => 'Admin verification required',
                    'email_data' => [
                        'type' => 'admin_verification',
                        'to' => $user['email'],
                        'subject' => 'Code de vérification administrateur',
                        'code' => $adminCode
                    ],
                    'redirect' => '/admin/verify'
                ]
            ];
        }
        
        // Pour un utilisateur normal
        unset($user['password_hash']);
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_id'] = $user['id'];
        
        logInfo("User logged in successfully", ['user_id' => $user['id']]);
        return [
            'status' => 'success', 
            'message' => 'Login successful', 
            'data' => [
                'user' => $user
            ],
            'redirect' => '/user/account'
        ];
    }
    
    
    /**
     * Vérifie le code d'authentification administrateur
     * 
     * @param array $data Données de vérification (verification_code, username/email, password)
     * @return array Statut de l'opération
     */
    public function verifyAdminCode($data) {
        logInfo("Admin verification attempt", ['email' => $data['email'] ?? $data['user_email'] ?? 'not provided']);
        
        $code = $data['verification_code'] ?? null;
        $usernameOrEmail = $data['user_email'] ?? $data['email'] ?? $data['username'] ?? $data['user_name'] ?? null;
        $password = $data['user_password'] ?? $data['password'] ?? null;
        
        if (!$usernameOrEmail || !$password || !$code) {
            logError("Admin verification failed - missing data");
            return ['status' => 'error', 'message' => 'Username/email, password and verification code are required'];
        }
        
        // Recherche de l'utilisateur par nom d'utilisateur ou email
        $users = $this->usersCRUD->get(
            ['id', 'username', 'email', 'password_hash', 'is_admin'],
            [
                'OR' => [
                    'username' => $usernameOrEmail,
                    'email' => $usernameOrEmail
                ]
            ]
        );
        
        if (empty($users)) {
            logError("Admin verification failed - user not found");
            return ['status' => 'error', 'message' => 'User not found'];
        }
        
        $user = $users[0];
        
        // Vérification du mot de passe
        if (!password_verify($password, $user['password_hash'])) {
            logError("Admin verification failed - invalid credentials", ['user_id' => $user['id']]);
            return ['status' => 'error', 'message' => 'Invalid credentials'];
        }
        
        // Vérification que l'utilisateur est bien un admin
        if (!$user['is_admin']) {
            logError("Admin verification failed - not an admin user", ['user_id' => $user['id']]);
            return ['status' => 'error', 'message' => 'Unauthorized access'];
        }

        // Suppression du mot de passe haché des données renvoyées
        unset($user['password_hash']);
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['admin'] = 'admin';
        
        logInfo("Admin verified successfully", ['user_id' => $user['id']]);
        return [
            'status' => 'success', 
            'message' => 'Admin verified successfully', 
            'data' => $user,
            'redirect' => '/admin/dashboard'
        ];
    }
    
    /**
     * Réinitialise le mot de passe d'un utilisateur
     * 
     * @param array $data Données de réinitialisation (email/user_email)
     * @return array Statut de l'opération
     */
    public function resetPassword($data) {
        logInfo("Password reset request", ['email' => $data['email'] ?? $data['user_email'] ?? 'not provided']);
        
        $email = $data['user_email'] ?? $data['email'] ?? null;
        
        if (!$email) {
            return ['status' => 'error', 'message' => 'Email is required'];
        }
        
        // Vérifier si l'utilisateur existe
        $users = $this->usersCRUD->get(['id', 'email'], ['email' => $email]);
        
        if (empty($users)) {
            logError("Password reset failed - user not found");
            return ['status' => 'error', 'message' => 'User not found'];
        }
        
        // Génération d'un jeton de réinitialisation
        $resetToken = bin2hex(random_bytes(16));
        $expiryTime = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Mise à jour de l'utilisateur avec le jeton de réinitialisation
        $this->usersCRUD->update(
            ['reset_token' => $resetToken, 'reset_token_expiry' => $expiryTime],
            ['id' => $users[0]['id']]
        );
        
        logInfo("Password reset token generated", ['user_id' => $users[0]['id']]);
        // Envoi d'un email de réinitialisation via le service de notification
        return [
            'status' => 'pending',
            'service' => 'Notification',
            'action' => 'sendEmail',
            'data' => [
                'message' => 'Password reset email sent',
                'email_data' => [
                    'type' => 'password_reset',
                    'to' => $email,
                    'subject' => 'Réinitialisation de mot de passe',
                    'reset_token' => $resetToken
                ],
                'redirect' => '/user/login'
            ]
        ];
    }
    
    /**
     * Vérifie l'email d'un utilisateur
     * 
     * @param array $data Données de vérification (email/user_email)
     * @return array Statut de l'opération
     */
    public function verifyEmail($data) {
        logInfo("Email verification attempt", ['email' => $data['email'] ?? $data['user_email'] ?? 'not provided']);
        
        $email = $data['user_email'] ?? $data['email'] ?? null;
        
        if (!$email) {
            return ['status' => 'error', 'message' => 'Email is required'];
        }
        
        // Vérifier si l'utilisateur existe
        $users = $this->usersCRUD->get(['id'], ['email' => $email]);
        
        if (empty($users)) {
            return ['status' => 'error', 'message' => 'User not found'];
        }
        
        // Mise à jour de l'utilisateur pour marquer l'email comme vérifié
        $result = $this->usersCRUD->update(['is_verified' => 1], ['id' => $users[0]['id']]);
        
        if ($result) {
            logError("Email verified successfully", ['user_id' => $users[0]['id']]);
            return ['status' => 'success', 'message' => 'Email verified successfully'];
        }
        
        logInfo("Email verification failed");
        return ['status' => 'error', 'message' => 'Failed to verify email'];
    }
    
    /**
     * Déconnecte un utilisateur
     * 
     * @param array $data Données de déconnexion (non utilisées)
     * @return array Statut de l'opération
     */
    public function logout($data) {
        logInfo("User logout", ['user_id' => $_SESSION['user_id'] ?? 'unknown']);
        session_destroy();
        return ['status' => 'success', 'message' => 'Logged out successfully'];
    }
    
    /**
     * Génère un code de vérification administrateur
     * 
     * @return string Code de vérification
     */
    private function generateAdminCode() {
        return sprintf("%06d", mt_rand(100000, 999999));
    }

    /**
     * Gère la connexion via Google OAuth2
     * 
     * @param array $data Données du profil Google
     * @return array Statut de l'opération
     */
    public function handleGoogleLogin($googleData) {
        logInfo("Google login attempt", ['email' => $googleData['email'] ?? 'not provided']);
        
        if (!isset($googleData['email']) || !isset($googleData['sub'])) {
            logError("Google login failed - missing data");
            return ['status' => 'error', 'message' => 'Invalid Google data'];
        }

        // Vérifie si l'utilisateur existe déjà avec cet email ou Google ID
        $users = $this->usersCRUD->get(
            ['id', 'username', 'email', 'is_admin', 'google_id'],
            [
                'OR' => [
                    'email' => $googleData['email'],
                    'google_id' => $googleData['sub']
                ]
            ]
        );

        if (empty($users)) {
            // Créer un nouvel utilisateur
            $username = $this->generateUniqueUsername($googleData['given_name'] ?? $googleData['email']);
            $userData = [
                'username' => $username,
                'email' => $googleData['email'],
                'google_id' => $googleData['sub'],
                'oauth_provider' => 'google',
                'oauth_token' => $googleData['access_token'] ?? null,
                'oauth_expires' => date('Y-m-d H:i:s', strtotime('+1 hour'))
            ];
            
            $userId = $this->usersCRUD->insert($userData);
            if (!$userId) {
                logError("Google login failed - could not create user");
                return ['status' => 'error', 'message' => 'Failed to create user'];
            }
            
            $user = $this->usersCRUD->get(['*'], ['id' => $userId])[0];
        } else {
            $user = $users[0];
            // Mettre à jour les informations OAuth
            $this->usersCRUD->update(
                [
                    'google_id' => $googleData['sub'],
                    'oauth_provider' => 'google',
                    'oauth_token' => $googleData['access_token'] ?? null,
                    'oauth_expires' => date('Y-m-d H:i:s', strtotime('+1 hour'))
                ],
                ['id' => $user['id']]
            );
        }

        // Créer la session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_email'] = $user['email'];
        if ($user['is_admin']) {
            $_SESSION['admin'] = 'admin';
        }

        logInfo("Google login successful", ['user_id' => $user['id']]);
        return [
            'status' => 'success',
            'message' => 'Login successful',
            'data' => ['user' => $user],
            'redirect' => $user['is_admin'] ? '/admin/dashboard' : '/user/account'
        ];
    }

    /**
     * Génère un nom d'utilisateur unique basé sur un nom donné
     */
    private function generateUniqueUsername($baseName) {
        $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $baseName));
        $originalUsername = $username;
        $counter = 1;
        
        while ($this->usersCRUD->get(['id'], ['username' => $username])) {
            $username = $originalUsername . $counter;
            $counter++;
        }
        
        return $username;
    }
    
    /**
     * Initialise l'authentification Google
     * 
     * @return array Statut de l'opération avec l'URL d'authentification
     */
    public function initiateGoogleAuth() {
        // Initialiser le client Google
        $client = $this->createGoogleClient();
        
        // Générer l'URL d'authentification
        $authUrl = $client->createAuthUrl();
        
        return [
            'status' => 'success',
            'data' => ['auth_url' => $authUrl]
        ];
    }
    
    /**
     * Traite le callback de Google OAuth
     * 
     * @param array $data Données du callback (code)
     * @return array Données utilisateur Google
     */
    public function handleGoogleCallback($data) {
        if (!isset($data['code'])) {
            return ['status' => 'error', 'message' => 'Authorization code not provided'];
        }
        
        try {
            // Initialiser le client Google
            $client = $this->createGoogleClient();
            
            // Échanger le code contre un jeton d'accès
            $token = $client->fetchAccessTokenWithAuthCode($data['code']);
            
            if (isset($token['error'])) {
                logError("Google OAuth error", ['error' => $token['error']]);
                return ['status' => 'error', 'message' => 'Failed to get access token: ' . $token['error']];
            }
            
            $client->setAccessToken($token);
            
            // Obtenir les informations de l'utilisateur
            $oauth2 = new Google_Service_Oauth2($client);
            $userInfo = $oauth2->userinfo->get();
            
            // Préparer les données utilisateur
            $googleData = [
                'sub' => $userInfo->id,
                'email' => $userInfo->email,
                'given_name' => $userInfo->givenName,
                'family_name' => $userInfo->familyName,
                'picture' => $userInfo->picture,
                'access_token' => $token['access_token'] ?? null
            ];
            
            return ['status' => 'success', 'data' => $googleData];
            
        } catch (Exception $e) {
            logError("Google OAuth exception", ['message' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Google OAuth error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Crée et configure une instance de Google_Client
     * 
     * @return Google_Client Instance configurée
     */
    private function createGoogleClient() {
        $client = new Google_Client();
        $client->setClientId(GOOGLE_CLIENT_ID);
        $client->setClientSecret(GOOGLE_CLIENT_SECRET);
        $client->setRedirectUri(GOOGLE_REDIRECT_URI);
        $client->addScope("email");
        $client->addScope("profile");
        
        return $client;
    }
}

// Classes d'adaptation pour Google OAuth

// Classe d'adaptation pour Google_Client
if (!class_exists('Google_Client')) {
    class Google_Client {
        private $clientId;
        private $clientSecret;
        private $redirectUri;
        private $scopes = [];
        private $accessToken;
        
        public function setClientId($clientId) {
            $this->clientId = $clientId;
        }
        
        public function setClientSecret($clientSecret) {
            $this->clientSecret = $clientSecret;
        }
        
        public function setRedirectUri($redirectUri) {
            $this->redirectUri = $redirectUri;
        }
        
        public function addScope($scope) {
            $this->scopes[] = $scope;
        }
        
        public function createAuthUrl() {
            // Construire l'URL d'authentification Google
            $baseUrl = 'https://accounts.google.com/o/oauth2/auth';
            $params = [
                'client_id' => $this->clientId,
                'redirect_uri' => $this->redirectUri,
                'response_type' => 'code',
                'scope' => implode(' ', $this->scopes),
                'access_type' => 'online',
                'prompt' => 'select_account'
            ];
            
            return $baseUrl . '?' . http_build_query($params);
        }
        
        public function fetchAccessTokenWithAuthCode($code) {
            // Échanger le code d'autorisation contre un jeton d'accès
            $url = 'https://oauth2.googleapis.com/token';
            $data = [
                'code' => $code,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectUri,
                'grant_type' => 'authorization_code'
            ];
            
            $options = [
                'http' => [
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($data)
                ]
            ];
            
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            
            if ($result === FALSE) {
                throw new Exception('Failed to fetch access token');
            }
            
            return json_decode($result, true);
        }
        
        public function setAccessToken($token) {
            $this->accessToken = $token;
        }
        
        public function verifyIdToken($idToken) {
            // Vérifier le jeton d'ID
            $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . $idToken;
            $result = file_get_contents($url);
            
            if ($result === FALSE) {
                throw new Exception('Failed to verify ID token');
            }
            
            $payload = json_decode($result, true);
            
            // Vérifier que le jeton est valide pour notre application
            if (isset($payload['aud']) && $payload['aud'] !== $this->clientId) {
                throw new Exception('Invalid client ID in token');
            }
            
            return $payload;
        }
    }
}

// Classe d'adaptation pour Google_Service_Oauth2
if (!class_exists('Google_Service_Oauth2')) {
    class Google_Service_Oauth2 {
        private $client;
        public $userinfo;
        
        public function __construct($client) {
            $this->client = $client;
            $this->userinfo = new Google_Service_Oauth2_Userinfo($client);
        }
    }
}

// Classe d'adaptation pour Google_Service_Oauth2_Userinfo
if (!class_exists('Google_Service_Oauth2_Userinfo')) {
    class Google_Service_Oauth2_Userinfo {
        private $client;
        
        public function __construct($client) {
            $this->client = $client;
        }
        
        public function get() {
            // Récupérer les informations de l'utilisateur
            $accessToken = $this->client->accessToken['access_token'] ?? null;
            
            if (!$accessToken) {
                throw new Exception('No access token available');
            }
            
            $url = 'https://www.googleapis.com/oauth2/v3/userinfo';
            $options = [
                'http' => [
                    'header' => "Authorization: Bearer $accessToken\r\n",
                    'method' => 'GET'
                ]
            ];
            
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            
            if ($result === FALSE) {
                throw new Exception('Failed to fetch user info');
            }
            
            $data = json_decode($result);
            return new Google_Service_Oauth2_UserinfoResource($data);
        }
    }
}

// Classe d'adaptation pour les ressources d'informations utilisateur
if (!class_exists('Google_Service_Oauth2_UserinfoResource')) {
    class Google_Service_Oauth2_UserinfoResource {
        public $id;
        public $email;
        public $givenName;
        public $familyName;
        public $picture;
        
        public function __construct($data) {
            $this->id = $data->sub ?? null;
            $this->email = $data->email ?? null;
            $this->givenName = $data->given_name ?? null;
            $this->familyName = $data->family_name ?? null;
            $this->picture = $data->picture ?? null;
        }
    }
}